<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="p-6">
        <!-- Cabecera -->
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-lg font-semibold">
                    <?php echo htmlspecialchars($tech['full_name']); ?>
                </h3>
                <p class="text-sm text-gray-500">
                    @<?php echo htmlspecialchars($tech['username']); ?>
                </p>
            </div>
            <span class="px-2 py-1 text-sm rounded-full <?php 
                echo $tech['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; 
            ?>">
                <?php echo $tech['active'] ? 'Activo' : 'Inactivo'; ?>
            </span>
        </div>

        <!-- Especialidades -->
        <div class="mb-4">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Especialidades</h4>
            <div class="flex flex-wrap gap-2">
                <?php 
                $specialties = json_decode($tech['specialties'] ?? '[]', true);
                foreach ($specialties as $specialty): 
                    $label = str_replace('_', ' ', ucwords($specialty));
                ?>
                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                        <?php echo htmlspecialchars($label); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold text-blue-600">
                    <?php echo $tech['total_visits']; ?>
                </p>
                <p class="text-xs text-gray-600">Total Visitas</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">
                    <?php echo $tech['completed_visits']; ?>
                </p>
                <p class="text-xs text-gray-600">Completadas</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold <?php echo $tech['today_pending'] > 0 ? 'text-yellow-600' : 'text-gray-600'; ?>">
                    <?php echo $tech['today_pending']; ?>
                </p>
                <p class="text-xs text-gray-600">Pendientes Hoy</p>
            </div>
        </div>

        <!-- Acciones -->
        <div class="flex space-x-2">
            <button onclick="editTechnician(<?php echo $tech['id']; ?>)"
                    class="flex-1 px-3 py-2 text-sm bg-blue-50 text-blue-600 rounded hover:bg-blue-100">
                <i class="fas fa-edit mr-1"></i>Editar
            </button>
            
            <?php if ($tech['active']): ?>
                <button onclick="deactivateTechnician(<?php echo $tech['id']; ?>)"
                        class="flex-1 px-3 py-2 text-sm bg-yellow-50 text-yellow-600 rounded hover:bg-yellow-100">
                    <i class="fas fa-ban mr-1"></i>Desactivar
                </button>
            <?php else: ?>
                <button onclick="activateTechnician(<?php echo $tech['id']; ?>)"
                        class="flex-1 px-3 py-2 text-sm bg-green-50 text-green-600 rounded hover:bg-green-100">
                    <i class="fas fa-check mr-1"></i>Activar
                </button>
            <?php endif; ?>

            <button onclick="showAvailability(<?php echo $tech['id']; ?>)"
                    class="px-3 py-2 text-sm bg-gray-50 text-gray-600 rounded hover:bg-gray-100">
                <i class="fas fa-calendar"></i>
            </button>
        </div>
    </div>
</div>