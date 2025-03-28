<!-- Visitas Recientes -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b">
        <h3 class="text-lg font-semibold">Visitas Recientes</h3>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            <?php
            $recentVisits = $db->query("
                SELECT v.*, u.full_name as technician_name
                FROM visits v
                JOIN users u ON v.technician_id = u.id
                ORDER BY v.visit_date DESC, v.visit_time DESC
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($recentVisits as $visit):
                $statusColors = [
                    'pending' => 'blue',
                    'in_route' => 'yellow',
                    'completed' => 'green'
                ];
                $color = $statusColors[$visit['status']];
            ?>
                <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                    <div class="flex-shrink-0">
                        <span class="inline-block p-3 rounded-full bg-<?php echo $color; ?>-100">
                            <i class="fas fa-calendar text-<?php echo $color; ?>-600"></i>
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            <?php echo htmlspecialchars($visit['client_name']); ?>
                        </p>
                        <p class="text-sm text-gray-500">
                            <?php echo htmlspecialchars($visit['technician_name']); ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900">
                            <?php echo date('d/m/Y', strtotime($visit['visit_date'])); ?>
                        </p>
                        <p class="text-sm text-gray-500">
                            <?php echo date('h:i A', strtotime($visit['visit_time'])); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 text-right">
            <a href="visits.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Ver todas las visitas <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- Próximas Visitas -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b flex justify-between items-center">
        <h3 class="text-lg font-semibold">Próximas Visitas</h3>
        <div class="flex space-x-2">
            <button onclick="changeDate('prev')" class="p-1 hover:bg-gray-100 rounded">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span id="currentDate" class="text-gray-600">
                <?php echo date('d/m/Y'); ?>
            </span>
            <button onclick="changeDate('next')" class="p-1 hover:bg-gray-100 rounded">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    <div class="p-6">
        <div id="upcomingVisits" class="space-y-4">
            <?php
            $upcomingVisits = $db->query("
                SELECT v.*, u.full_name as technician_name
                FROM visits v
                JOIN users u ON v.technician_id = u.id
                WHERE v.visit_date >= CURRENT_DATE()
                AND v.status != 'completed'
                ORDER BY v.visit_date ASC, v.visit_time ASC
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($upcomingVisits as $visit):
            ?>
                <div class="relative p-4 bg-gray-50 rounded-lg">
                    <!-- Línea de tiempo -->
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-blue-200"></div>
                    
                    <div class="flex items-start ml-6">
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($visit['client_name']); ?>
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        Técnico: <?php echo htmlspecialchars($visit['technician_name']); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium">
                                        <?php echo date('h:i A', strtotime($visit['visit_time'])); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?php echo date('d/m/Y', strtotime($visit['visit_date'])); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-2 text-sm text-gray-600">
                                <p class="truncate">
                                    <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i>
                                    <?php echo htmlspecialchars($visit['address']); ?>
                                </p>
                            </div>

                            <!-- Acciones rápidas -->
                            <div class="mt-3 flex space-x-2">
                                <?php if ($visit['location_url']): ?>
                                    <a href="<?php echo htmlspecialchars($visit['location_url']); ?>" 
                                       target="_blank"
                                       class="text-xs text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-map"></i> Ver mapa
                                    </a>
                                <?php endif; ?>
                                <button onclick="showVisitDetails(<?php echo $visit['id']; ?>)"
                                        class="text-xs text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($upcomingVisits)): ?>
                <div class="text-center py-4 text-gray-500">
                    No hay visitas próximas programadas
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let currentDateObj = new Date();

function changeDate(direction) {
    if (direction === 'prev') {
        currentDateObj.setDate(currentDateObj.getDate() - 1);
    } else {
        currentDateObj.setDate(currentDateObj.getDate() + 1);
    }
    
    // Actualizar texto de fecha
    document.getElementById('currentDate').textContent = 
        currentDateObj.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    
    // Cargar nuevas visitas
    loadUpcomingVisits(currentDateObj.toISOString().split('T')[0]);
}

function loadUpcomingVisits(date) {
    fetch(`actions/dashboard_actions.php?action=get_upcoming_visits&date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('upcomingVisits').innerHTML = data.html;
            }
        })
        .catch(error => console.error('Error:', error));
}

function showVisitDetails(visitId) {
    // Implementar modal de detalles de visita
}
</script>