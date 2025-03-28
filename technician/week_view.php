<?php
$weekStart = date('Y-m-d', strtotime('monday this week', strtotime($selected_date)));
$weekDays = [];

for ($i = 0; $i < 7; $i++) {
    $currentDate = date('Y-m-d', strtotime($weekStart . " +$i days"));
    $weekDays[] = [
        'date' => $currentDate,
        'dayName' => strftime('%a', strtotime($currentDate)),
        'dayNumber' => date('j', strtotime($currentDate)),
        'isToday' => $currentDate === date('Y-m-d')
    ];
}

// Agrupar visitas por día
$visitsByDay = [];
foreach ($visits as $visit) {
    if (!isset($visitsByDay[$visit['visit_date']])) {
        $visitsByDay[$visit['visit_date']] = [];
    }
    $visitsByDay[$visit['visit_date']][] = $visit;
}
?>

<!-- Encabezado de la semana -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <div class="text-center mb-4">
        <h2 class="text-xl font-bold">
            <?php 
            echo strftime('%B %Y', strtotime($weekStart));
            ?>
        </h2>
    </div>
    
    <div class="grid grid-cols-7 gap-2">
        <?php foreach ($weekDays as $day): ?>
            <div class="text-center <?php echo $day['isToday'] ? 'bg-blue-100 rounded-lg' : ''; ?> p-2">
                <div class="text-sm text-gray-600"><?php echo $day['dayName']; ?></div>
                <div class="text-lg font-bold"><?php echo $day['dayNumber']; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Visitas de la semana -->
<div class="space-y-4">
    <?php foreach ($weekDays as $day): ?>
        <?php if (isset($visitsByDay[$day['date']])): ?>
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 bg-gray-50 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold">
                            <?php echo strftime('%A %d', strtotime($day['date'])); ?>
                        </h3>
                        <span class="text-sm text-gray-500">
                            <?php echo count($visitsByDay[$day['date']]); ?> visitas
                        </span>
                    </div>
                </div>
                
                <div class="p-4 grid gap-3">
                    <?php foreach ($visitsByDay[$day['date']] as $visit): ?>
                        <div class="flex items-center p-2 rounded border
                            <?php echo $visit['status'] === 'completed' ? 'bg-green-50 border-green-200' : 
                                ($visit['status'] === 'in_route' ? 'bg-yellow-50 border-yellow-200' : 
                                 'bg-white'); ?>">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <span class="font-semibold">
                                        <?php echo date('h:i A', strtotime($visit['visit_time'])); ?>
                                    </span>
                                    <?php if ($visit['client_name'] === 'Personal'): ?>
                                        <span class="ml-2 px-2 py-1 bg-gray-100 rounded-full text-xs text-gray-600">
                                            Personal
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($visit['client_name']); ?>
                                </div>
                            </div>
                            
                            <button onclick="showVisitDetail(<?php echo $visit['id']; ?>)"
                                    class="flex items-center justify-center px-3 py-1 text-blue-600 hover:bg-blue-50 rounded">
                                <i class="fas fa-eye mr-1"></i>
                                Ver
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>