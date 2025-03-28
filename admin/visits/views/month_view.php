<?php
// views/month_view.php
$firstDay = date('Y-m-01', strtotime($date));
$lastDay = date('Y-m-t', strtotime($date));
$startingDay = date('w', strtotime($firstDay));
$monthDays = date('t', strtotime($date));
$weeks = ceil(($monthDays + $startingDay) / 7);

// Obtener todas las visitas del mes
$stmt = $db->prepare("
    SELECT v.*, u.full_name as technician_name 
    FROM visits v
    JOIN users u ON v.technician_id = u.id
    WHERE v.visit_date BETWEEN :start_date AND :end_date
    ORDER BY v.visit_time ASC
");

$stmt->execute([
    ':start_date' => $firstDay,
    ':end_date' => $lastDay
]);
$monthVisits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar visitas por día
$visitsByDay = [];
foreach ($monthVisits as $visit) {
    $day = date('j', strtotime($visit['visit_date']));
    if (!isset($visitsByDay[$day])) {
        $visitsByDay[$day] = [];
    }
    $visitsByDay[$day][] = $visit;
}
?>

<div class="p-6">
    <!-- Navegación del mes -->
    <div class="flex justify-between items-center mb-6">
        <button onclick="changeMonth('prev')" class="p-2 hover:bg-gray-100 rounded">
            <i class="fas fa-chevron-left"></i>
        </button>
        <h2 class="text-xl font-bold">
            <?php echo strftime('%B %Y', strtotime($date)); ?>
        </h2>
        <button onclick="changeMonth('next')" class="p-2 hover:bg-gray-100 rounded">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <!-- Calendario -->
    <div class="grid grid-cols-7 gap-2">
        <!-- Días de la semana -->
        <?php
        $weekDays = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        foreach ($weekDays as $day): ?>
            <div class="text-center font-medium text-gray-600 py-2">
                <?php echo $day; ?>
            </div>
        <?php endforeach; ?>

        <!-- Días vacíos al inicio -->
        <?php for ($i = 0; $i < $startingDay; $i++): ?>
            <div class="aspect-square bg-gray-50"></div>
        <?php endfor; ?>

        <!-- Días del mes -->
        <?php for ($day = 1; $day <= $monthDays; $day++): ?>
            <?php 
            $currentDate = date('Y-m-d', strtotime($firstDay . ' +' . ($day - 1) . ' days'));
            $isToday = $currentDate === date('Y-m-d');
            ?>
            <div class="aspect-square border rounded p-1 <?php echo $isToday ? 'bg-blue-50 border-blue-200' : ''; ?>">
                <div class="h-full flex flex-col">
                    <div class="text-right mb-1">
                        <?php echo $day; ?>
                    </div>
                    <?php if (isset($visitsByDay[$day])): ?>
                        <div class="flex-1 overflow-y-auto text-xs">
                        <?php foreach ($visitsByDay[$day] as $visit): ?>
                            <div class="mb-1 p-1 rounded cursor-pointer hover:bg-gray-50 border-l-4 
                                        <?php 
                                        switch($visit['status']) {
                                            case 'completed':
                                                echo 'border-green-500 bg-green-50';
                                                break;
                                            case 'in_route':
                                                echo 'border-yellow-500 bg-yellow-50';
                                                break;
                                            case 'pending':
                                                echo 'border-blue-500 bg-blue-50';
                                                break;
                                            default:
                                                echo 'border-gray-300';
                                        }
                                        ?>"
                                 onclick="showVisitDetails(<?php echo $visit['id']; ?>)">
                                <div class="text-xs font-medium">
                                    <?php echo date('h:i A', strtotime($visit['visit_time'])); ?>
                                </div>
                                <div class="text-xs truncate">
                                    <?php echo htmlspecialchars($visit['client_name']); ?>
                                </div>
                                <div class="text-xs text-gray-600 truncate">
                                    <i class="fas fa-user-md mr-1"></i>
                                    <?php echo htmlspecialchars($visit['technician_name']); ?>
                                </div>
                                <div class="text-xs text-gray-600">
                                    <i class="fas fa-tools mr-1"></i>
                                    <?php echo htmlspecialchars($visit['service_type']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>
</div>

<script>
function changeMonth(direction) {
    const currentDate = new Date('<?php echo $date; ?>');
    if (direction === 'prev') {
        currentDate.setMonth(currentDate.getMonth() - 1);
    } else {
        currentDate.setMonth(currentDate.getMonth() + 1);
    }
    window.location.href = `?view=month&date=${currentDate.toISOString().split('T')[0]}`;
}
</script>