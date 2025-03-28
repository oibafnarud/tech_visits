// File: /tech_visits/technician/views/month_view.php
<?php
// Calcular variables del calendario
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
        <button onclick="changeMonth('prev')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <i class="fas fa-chevron-left"></i>
        </button>
        <h2 class="text-xl font-bold text-gray-800">
            <?php echo ucfirst(strftime('%B %Y', strtotime($selected_date))); ?>
        </h2>
        <button onclick="changeMonth('next')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <!-- Rejilla del calendario -->
    <div class="p-4">
        <!-- Días de la semana -->
        <div class="grid grid-cols-7 mb-2">
            <?php foreach (['D', 'L', 'M', 'M', 'J', 'V', 'S'] as $day): ?>
                <div class="text-center text-sm font-medium text-gray-600">
                    <?php echo $day; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Días del mes -->
        <div class="grid grid-cols-7 gap-2">
            <?php
            // Espacios vacíos al inicio
            for ($i = 0; $i < $startingDay; $i++): ?>
                <div class="calendar-day opacity-0"></div>
            <?php endfor; ?>

            <?php
            // Días del mes
            for ($day = 1; $day <= $monthDays; $day++):
                $date = date('Y-m-d', strtotime("$currentYear-$currentMonth-$day"));
                $isToday = $date === date('Y-m-d');
                $hasVisits = isset($visitsByDay[$day]);
                $dayVisits = $visitsByDay[$day] ?? [];
                
                // Conteo por estado
                $totalVisits = count($dayVisits);
                $completedVisits = count(array_filter($dayVisits, fn($v) => $v['status'] === 'completed'));
                $pendingVisits = count(array_filter($dayVisits, fn($v) => $v['status'] === 'pending'));
                $inRouteVisits = $totalVisits - ($completedVisits + $pendingVisits);

                $classes = ['calendar-day'];
                if ($isToday) $classes[] = 'is-today';
                if ($hasVisits) $classes[] = 'has-visits';
            ?>
                <div onclick="<?php echo $hasVisits ? "showDayVisits($day, " . htmlspecialchars(json_encode($dayVisits)) . ")" : ''; ?>"
                     class="<?php echo implode(' ', $classes); ?>">
                    <div class="text-right mb-1 <?php echo $isToday ? 'font-bold text-blue-600' : ''; ?>">
                        <?php echo $day; ?>
                    </div>

                    <?php if ($totalVisits > 0): ?>
                        <div class="absolute bottom-1 left-0 right-0 flex justify-center space-x-1">
                            <?php if ($pendingVisits > 0): ?>
                                <span class="px-1.5 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full">
                                    <?php echo $pendingVisits; ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($inRouteVisits > 0): ?>
                                <span class="px-1.5 py-0.5 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                                    <?php echo $inRouteVisits; ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($completedVisits > 0): ?>
                                <span class="px-1.5 py-0.5 text-xs bg-green-100 text-green-800 rounded-full">
                                    <?php echo $completedVisits; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Modal para las visitas del día -->
<div id="dayVisitsModal" class="modal-container hidden">
    <div class="modal-content max-w-lg">
        <div class="modal-header">
            <h3 class="text-lg font-bold" id="selectedDateTitle"></h3>
            <button onclick="hideDayVisitsModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="dayVisitsContent" class="visits-container">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
    </div>
</div>

<script>
function showDayVisits(day, visits) {
    const modal = document.getElementById('dayVisitsModal');
    const title = document.getElementById('selectedDateTitle');
    const content = document.getElementById('dayVisitsContent');
    
    // Formatear fecha para el título
    const date = new Date(<?php echo json_encode($selected_date); ?>);
    date.setDate(day);
    title.textContent = date.toLocaleDateString('es-ES', { 
        weekday: 'long', 
        day: 'numeric', 
        month: 'long' 
    });

    if (!visits || visits.length === 0) {
        content.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-calendar-day text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No hay visitas programadas</p>
            </div>
        `;
    } else {
        // Mostrar las primeras 3 visitas inmediatamente
        const visitsToShow = visits.slice(0, 3);
        const remainingVisits = visits.slice(3);

        let html = visitsToShow.map(renderVisitCard).join('');
        
        // Si hay más visitas, agregar separador y las visitas restantes
        if (remainingVisits.length > 0) {
            html += `
                <div class="relative my-4">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-white px-3 text-sm text-gray-500">
                            ${remainingVisits.length} visitas más
                        </span>
                    </div>
                </div>
                ${remainingVisits.map(renderVisitCard).join('')}
            `;
        }

        content.innerHTML = html;
    }

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function renderVisitCard(visit) {
    return `
        <div class="month-visit-card border-l-4 ${getStatusBorderColor(visit.status)}">
            <div class="visit-card-header">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-xl font-bold">
                            ${formatTime(visit.visit_time)}
                        </div>
                        <div class="text-gray-600">
                            ${visit.client_name}
                        </div>
                    </div>
                    <span class="px-2 py-1 rounded-full text-sm ${getStatusClass(visit.status)}">
                        ${getStatusLabel(visit.status)}
                    </span>
                </div>
            </div>
            
            <div class="p-4">
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-map-marker-alt w-5"></i>
                    <span>${visit.address}</span>
                </div>
                
                ${visit.status !== 'completed' ? renderVisitActions(visit) : ''}
            </div>
        </div>
    `;
}

function renderVisitActions(visit) {
    return `
        <div class="flex justify-end space-x-2 mt-4">
            ${visit.status === 'pending' ? `
                <button onclick="updateStatus(${visit.id}, 'in_route')" 
                        class="action-button btn-route">
                    <i class="fas fa-truck mr-1"></i>
                    En Ruta
                </button>
            ` : ''}
            
            <button onclick="updateStatus(${visit.id}, 'completed')" 
                    class="action-button btn-complete">
                <i class="fas fa-check mr-1"></i>
                Completar
            </button>
            
            <button onclick="showVisitDetail(${visit.id})" 
                    class="action-button btn-details">
                <i class="fas fa-eye mr-1"></i>
                Detalles
            </button>
        </div>
    `;
}

function getStatusBorderColor(status) {
    return {
        completed: 'border-green-500',
        in_route: 'border-yellow-500',
        pending: 'border-blue-500'
    }[status] || 'border-gray-500';
}

function getStatusClass(status) {
    return {
        completed: 'bg-green-100 text-green-800',
        in_route: 'bg-yellow-100 text-yellow-800',
        pending: 'bg-blue-100 text-blue-800'
    }[status] || 'bg-gray-100 text-gray-800';
}

function getStatusLabel(status) {
    return {
        completed: 'Completada',
        in_route: 'En Camino',
        pending: 'Pendiente'
    }[status] || 'Desconocido';
}

function formatTime(time) {
    if (!time) return '';
    try {
        return new Date(`2000-01-01 ${time}`).toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    } catch (e) {
        return time;
    }
}

function hideDayVisitsModal() {
    const modal = document.getElementById('dayVisitsModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function changeMonth(direction) {
    const currentDate = new Date(<?php echo json_encode($selected_date); ?>);
    currentDate.setMonth(currentDate.getMonth() + (direction === 'prev' ? -1 : 1));
    window.location.href = `?view=month&date=${currentDate.toISOString().split('T')[0]}`;
}
</script>