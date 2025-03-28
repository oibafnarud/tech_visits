<?php
// views/calendar_view.php

// Obtener el mes y año actual o seleccionado
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Obtener visitas del mes
$stmt = $db->prepare("
    SELECT 
        v.*,
        u.full_name as technician_name,
        ADDTIME(v.visit_time, SEC_TO_TIME(v.duration * 60)) as end_time
    FROM visits v
    JOIN users u ON v.technician_id = u.id
    WHERE YEAR(v.visit_date) = :year 
    AND MONTH(v.visit_date) = :month
    " . ($technician !== 'all' ? "AND v.technician_id = :technician_id" : "") . "
    ORDER BY v.visit_date ASC, v.visit_time ASC
");

$params = [':year' => $year, ':month' => $month];
if ($technician !== 'all') {
    $params[':technician_id'] = $technician;
}

$stmt->execute($params);
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar visitas por día
$visitsByDay = [];
foreach ($visits as $visit) {
    $day = date('j', strtotime($visit['visit_date']));
    if (!isset($visitsByDay[$day])) {
        $visitsByDay[$day] = [];
    }
    $visitsByDay[$day][] = $visit;
}

// Información del calendario
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startingDay = date('w', $firstDay);
$monthName = date('F', $firstDay);
?>

<div class="bg-white rounded-lg shadow-sm p-6">
    <!-- Navegación del mes -->
    <div class="flex justify-between items-center mb-6">
        <button onclick="changeMonth(<?php echo $month-1; ?>, <?php echo $year; ?>)" 
                class="p-2 hover:bg-gray-100 rounded-full">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <h2 class="text-xl font-bold">
            <?php echo $monthName . ' ' . $year; ?>
        </h2>
        
        <button onclick="changeMonth(<?php echo $month+1; ?>, <?php echo $year; ?>)" 
                class="p-2 hover:bg-gray-100 rounded-full">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <!-- Días de la semana -->
    <div class="grid grid-cols-7 mb-2">
        <?php
        $weekDays = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        foreach ($weekDays as $day) {
            echo '<div class="text-center font-medium text-gray-600 py-2">' . $day . '</div>';
        }
        ?>
    </div>

    <!-- Calendario -->
    <div class="grid grid-cols-7 gap-2">
        <?php
        // Días vacíos antes del primer día del mes
        for ($i = 0; $i < $startingDay; $i++) {
            echo '<div class="aspect-square"></div>';
        }

        // Días del mes
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
            $isToday = $date === date('Y-m-d');
            $hasVisits = isset($visitsByDay[$day]);
            ?>
            <div class="aspect-square border rounded p-1 <?php echo $isToday ? 'bg-blue-50 border-blue-200' : ''; ?>">
                <div class="h-full flex flex-col">
                    <!-- Número del día -->
                    <div class="text-right mb-1">
                        <span class="<?php echo $isToday ? 'font-bold text-blue-600' : ''; ?>">
                            <?php echo $day; ?>
                        </span>
                    </div>

                    <?php if ($hasVisits): ?>
                        <div class="flex-1 overflow-y-auto">
                            <?php
                            $totalVisits = count($visitsByDay[$day]);
                            $completedVisits = count(array_filter($visitsByDay[$day], 
                                fn($v) => $v['status'] === 'completed'));
                            ?>
                            <div class="text-xs space-y-1">
                                <?php foreach ($visitsByDay[$day] as $visit): ?>
                                    <div class="px-1 py-0.5 rounded text-xs 
                                        <?php echo getStatusClass($visit['status']); ?>"
                                         onclick="showVisitDetails(<?php echo $visit['id']; ?>)"
                                         style="cursor: pointer;">
                                        <div class="font-medium">
                                            <?php echo date('h:i A', strtotime($visit['visit_time'])); ?> - 
                                            <?php echo htmlspecialchars($visit['client_name']); ?> 
                                            <span class="text-gray-600 text-xs">
                                                (<?php echo htmlspecialchars($visit['technician_name']); ?>)
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="mt-1 text-xs text-center">
                            <span class="px-1 rounded-full bg-green-100 text-green-800">
                                <?php echo $completedVisits; ?>/<?php echo $totalVisits; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }

        // Días vacíos después del último día
        $remainingDays = 7 - ((($startingDay + $daysInMonth) % 7) ?: 7);
        if ($remainingDays < 7) {
            for ($i = 0; $i < $remainingDays; $i++) {
                echo '<div class="aspect-square"></div>';
            }
        }
        ?>
    </div>
</div>

<?php
function getStatusClass($status) {
    switch ($status) {
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'in_route':
            return 'bg-yellow-100 text-yellow-800';
        default:
            return 'bg-blue-100 text-blue-800';
    }
}
?>

<script>
function changeMonth(month, year) {
    if (month < 1) {
        month = 12;
        year--;
    } else if (month > 12) {
        month = 1;
        year++;
    }
    
    const url = new URL(window.location);
    url.searchParams.set('month', month);
    url.searchParams.set('year', year);
    window.location = url;
}

function showVisitDetails(visitId) {
    // Mostrar modal con detalles
    fetch(`actions/get_visit_details.php?id=${visitId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Llenar y mostrar el modal
                document.getElementById('visitDetailsModal').classList.remove('hidden');
            }
        });
}
</script>