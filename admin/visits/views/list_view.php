
<div class="p-6">
    <!-- Lista de Visitas -->
    <div class="visits-container space-y-4">
        <?php if (empty($visits)): ?>
            <div class="text-center py-8">
                <div class="text-gray-400 text-5xl mb-4">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <p class="text-gray-500">No hay visitas programadas para este día</p>
            </div>
        <?php else: ?>
            <?php foreach ($visits as $visit): ?>
                <div class="visit-item bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200"
                     data-status="<?php echo $visit['status']; ?>"
                     data-tech-id="<?php echo $visit['technician_id']; ?>">
                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <!-- Hora y Estado -->
                                <div class="flex items-center space-x-3">
                                    <span class="text-lg font-semibold text-gray-800">
                                        <?php echo formatTime($visit['visit_time']); ?>
                                    </span>
                                    <span class="px-3 py-1 text-sm font-medium rounded-full <?php echo getStatusClass($visit['status']); ?>">
                                        <?php echo getStatusLabel($visit['status']); ?>
                                    </span>
                                </div>

                                <!-- Información del Cliente -->
                                <h3 class="text-xl font-bold mt-2 client-name">
                                    <?php echo htmlspecialchars($visit['client_name']); ?>
                                </h3>
                                <div class="flex items-center mt-1 space-x-2">
                                    <span class="text-sm px-2 py-1 bg-blue-50 text-blue-700 rounded-full">
                                        <i class="fas fa-tools mr-1"></i>
                                        <?php echo htmlspecialchars($visit['service_type']); ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">
                                    <i class="fas fa-user text-gray-400 mr-2"></i>
                                    Técnico: <?php echo htmlspecialchars($visit['technician_name']); ?>
                                </p>
                                <p class="text-sm text-gray-600 mt-2 client-address">
                                    <i class="fas fa-map-marker-alt text-gray-400 mr-2"></i>
                                    <?php echo htmlspecialchars($visit['address']); ?>
                                </p>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="flex items-start space-x-2">
                                <?php if ($visit['location_url']): ?>
                                    <a href="<?php echo htmlspecialchars($visit['location_url']); ?>" 
                                       target="_blank"
                                       class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg tooltip"
                                       data-tooltip="Ver en mapa">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </a>
                                <?php endif; ?>
                            
                                <?php if ($visit['status'] !== 'completed'): ?>
                                    <?php if ($visit['status'] === 'pending'): ?>
                                        <button onclick="updateStatus(<?php echo $visit['id']; ?>, 'in_route')"
                                                class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg tooltip"
                                                data-tooltip="En Camino">
                                            <i class="fas fa-truck"></i>
                                        </button>
                                    <?php endif; ?>
                            
                                    <button onclick="updateStatus(<?php echo $visit['id']; ?>, 'completed')"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg tooltip"
                                            data-tooltip="Completar">
                                        <i class="fas fa-check"></i>
                                    </button>
                            
                                    <button onclick="editVisit(<?php echo $visit['id']; ?>)"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg tooltip"
                                            data-tooltip="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                            
                                    <button onclick="deleteVisit(<?php echo $visit['id']; ?>)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg tooltip"
                                            data-tooltip="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            
                                <button onclick="showVisitDetails(<?php echo $visit['id']; ?>)"
                                        class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg tooltip"
                                        data-tooltip="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.tooltip {
    position: relative;
}

.tooltip:before {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 4px 8px;
    background-color: rgba(0,0,0,0.8);
    color: white;
    font-size: 12px;
    border-radius: 4px;
    white-space: nowrap;
    visibility: hidden;
    opacity: 0;
    transition: opacity 0.2s;
}

.tooltip:hover:before {
    visibility: visible;
    opacity: 1;
}
</style>