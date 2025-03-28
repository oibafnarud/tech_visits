<?php
// Calcular las variables necesarias para el calendario
$firstDay = date('Y-m-01', strtotime($selected_date));
$lastDay = date('Y-m-t', strtotime($selected_date));
$startingDay = (int)date('w', strtotime($firstDay));
$monthDays = (int)date('t', strtotime($selected_date));
$currentMonth = date('n', strtotime($selected_date));
$currentYear = date('Y', strtotime($selected_date));

// Agrupar visitas por día
$visitsByDay = [];
foreach ($visits as $visit) {
    $day = (int)date('j', strtotime($visit['visit_date']));
    if (!isset($visitsByDay[$day])) {
        $visitsByDay[$day] = [];
    }
    $visitsByDay[$day][] = $visit;
}
?>

<div class="bg-white rounded-lg shadow-sm">
    <!-- Navegación del mes -->
    <div class="flex justify-between items-center p-4 border-b">
        <button onclick="changeMonth('prev')" class="p-2 hover:bg-gray-100 rounded-lg">
            <i class="fas fa-chevron-left"></i>
        </button>
        <h2 class="text-xl font-bold">
            <?php echo strftime('%B %Y', strtotime($selected_date)); ?>
        </h2>
        <button onclick="changeMonth('next')" class="p-2 hover:bg-gray-100 rounded-lg">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <!-- Calendario -->
    <div class="p-2">
        <!-- Días de la semana -->
        <div class="grid grid-cols-7 mb-2">
            <?php
            $weekDays = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
            foreach ($weekDays as $day): 
            ?>
                <div class="text-center text-sm font-medium text-gray-600 py-2">
                    <?php echo $day; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Días del mes -->
        <div class="grid grid-cols-7 gap-1">
            <?php
            // Días vacíos al inicio
            for ($i = 0; $i < $startingDay; $i++): 
            ?>
                <div class="aspect-square"></div>
            <?php endfor; ?>

            <?php
            // Días del mes
            for ($day = 1; $day <= $monthDays; $day++):
                $date = date('Y-m-d', strtotime("$currentYear-$currentMonth-$day"));
                $isToday = $date === date('Y-m-d');
                $hasVisits = isset($visitsByDay[$day]);
                $visits = $hasVisits ? $visitsByDay[$day] : [];
                $totalVisits = count($visits);
                $completedVisits = count(array_filter($visits, fn($v) => $v['status'] === 'completed'));
            ?>
                <div onclick="showDayDetails(<?php echo $day; ?>, <?php echo htmlspecialchars(json_encode($visits)); ?>)"
                     class="aspect-square border rounded-lg p-1 relative 
                            <?php echo $isToday ? 'bg-blue-50 border-blue-200' : ''; ?>
                            <?php echo $hasVisits ? 'cursor-pointer hover:bg-gray-50' : ''; ?>">
                    <!-- Número del día -->
                    <div class="text-right mb-1 <?php echo $isToday ? 'font-bold text-blue-600' : ''; ?>">
                        <?php echo $day; ?>
                    </div>

                    <!-- Contador de visitas -->
                    <?php if ($hasVisits): ?>
                        <div class="absolute bottom-1 left-0 right-0 flex justify-center">
                            <div class="flex items-center space-x-1">
                                <span class="px-2 py-0.5 rounded-full text-xs
                                    <?php echo $completedVisits === $totalVisits ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo $totalVisits; ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Modal de Detalles del Día -->
<div id="dayDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b">
            <div class="flex justify-between items-center p-4">
                <h3 class="text-lg font-bold" id="dayDetailsTitle"></h3>
                <button onclick="hideDayDetails()" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div id="dayDetailsContent" class="p-4"></div>
    </div>
</div>

<script>
function showDayDetails(day, visits) {
    const modal = document.getElementById('dayDetailsModal');
    const title = document.getElementById('dayDetailsTitle');
    const content = document.getElementById('dayDetailsContent');
    
    // Formatear fecha
    const date = new Date(<?php echo json_encode($selected_date); ?>);
    date.setDate(day);
    const formattedDate = date.toLocaleDateString('es-ES', { 
        weekday: 'long', 
        day: 'numeric', 
        month: 'long' 
    });
    
    title.textContent = formattedDate;
    
    if (!visits || visits.length === 0) {
        content.innerHTML = '<div class="text-center text-gray-500 py-4">No hay visitas programadas</div>';
    } else {
        content.innerHTML = visits.map(visit => `
            <div class="mb-3 p-3 rounded-lg border ${getVisitStatusClass(visit.status)}">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="font-medium">${formatTime(visit.visit_time)}</div>
                        <div class="text-gray-800">${escapeHtml(visit.client_name)}</div>
                        <div class="text-sm text-gray-600 mt-1">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            ${escapeHtml(visit.address)}
                        </div>
                    </div>
                    <div class="ml-2">
                        <span class="px-2 py-1 rounded-full text-xs ${getStatusBadgeClass(visit.status)}">
                            ${getStatusLabel(visit.status)}
                        </span>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    modal.classList.remove('hidden');
}

function hideDayDetails() {
    document.getElementById('dayDetailsModal').classList.add('hidden');
}

function formatTime(time) {
    const [hours, minutes] = time.split(':');
    const date = new Date();
    date.setHours(hours);
    date.setMinutes(minutes);
    return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
}

function getVisitStatusClass(status) {
    switch (status) {
        case 'completed': return 'bg-green-50 border-green-200';
        case 'in_route': return 'bg-yellow-50 border-yellow-200';
        default: return 'bg-blue-50 border-blue-200';
    }
}

function getStatusBadgeClass(status) {
    switch (status) {
        case 'completed': return 'bg-green-100 text-green-800';
        case 'in_route': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-blue-100 text-blue-800';
    }
}

function getStatusLabel(status) {
    switch (status) {
        case 'completed': return 'Completada';
        case 'in_route': return 'En Ruta';
        default: return 'Pendiente';
    }
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>