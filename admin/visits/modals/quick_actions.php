<div id="quickActionsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm">
        <div class="bg-white rounded-lg shadow-xl m-4">
            <input type="hidden" id="quickActionVisitId">
            
            <div class="p-4 space-y-2">
                <button onclick="quickChangeStatus('in_route')" 
                        class="w-full text-left p-3 hover:bg-gray-50 rounded-lg flex items-center">
                    <i class="fas fa-truck w-8 text-yellow-600"></i>
                    <span>Marcar En Camino</span>
                </button>

                <button onclick="quickChangeStatus('completed')" 
                        class="w-full text-left p-3 hover:bg-gray-50 rounded-lg flex items-center">
                    <i class="fas fa-check w-8 text-green-600"></i>
                    <span>Marcar Completada</span>
                </button>

                <button onclick="showReschedulePrompt()" 
                        class="w-full text-left p-3 hover:bg-gray-50 rounded-lg flex items-center">
                    <i class="fas fa-calendar-alt w-8 text-blue-600"></i>
                    <span>Reprogramar</span>
                </button>

                <button onclick="showReassignPrompt()" 
                        class="w-full text-left p-3 hover:bg-gray-50 rounded-lg flex items-center">
                    <i class="fas fa-user-edit w-8 text-purple-600"></i>
                    <span>Reasignar Técnico</span>
                </button>

                <button onclick="showVisitDetails()" 
                        class="w-full text-left p-3 hover:bg-gray-50 rounded-lg flex items-center">
                    <i class="fas fa-edit w-8 text-gray-600"></i>
                    <span>Editar Detalles</span>
                </button>

                <hr class="my-2">

                <button onclick="confirmDeleteVisit()" 
                        class="w-full text-left p-3 hover:bg-red-50 rounded-lg flex items-center text-red-600">
                    <i class="fas fa-trash-alt w-8"></i>
                    <span>Eliminar Visita</span>
                </button>

                <button onclick="closeQuickActions()" 
                        class="w-full text-left p-3 hover:bg-gray-50 rounded-lg flex items-center text-gray-500">
                    <i class="fas fa-times w-8"></i>
                    <span>Cerrar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Mini modal de reprogramación -->
    <div id="reschedulePrompt" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-4">
            <h3 class="text-lg font-medium mb-4">Reprogramar Visita</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nueva Fecha</label>
                    <input type="date" id="newDate" class="mt-1 block w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nueva Hora</label>
                    <input type="time" id="newTime" class="mt-1 block w-full rounded-md border-gray-300">
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeReschedulePrompt()" 
                            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        Cancelar
                    </button>
                    <button onclick="confirmReschedule()" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mini modal de reasignación -->
    <div id="reassignPrompt" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-4">
            <h3 class="text-lg font-medium mb-4">Reasignar Técnico</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Seleccionar Técnico</label>
                    <select id="newTechnician" class="mt-1 block w-full rounded-md border-gray-300">
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>">
                                <?php echo htmlspecialchars($tech['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeReassignPrompt()" 
                            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        Cancelar
                    </button>
                    <button onclick="confirmReassign()" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showQuickActions(visitId) {
    document.getElementById('quickActionVisitId').value = visitId;
    document.getElementById('quickActionsModal').classList.remove('hidden');
}

function closeQuickActions() {
    document.getElementById('quickActionsModal').classList.add('hidden');
    document.getElementById('reschedulePrompt').classList.add('hidden');
    document.getElementById('reassignPrompt').classList.add('hidden');
}

function quickChangeStatus(status) {
    const visitId = document.getElementById('quickActionVisitId').value;
    
    fetch('actions/visit_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_status',
            visit_id: visitId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`Visita marcada como ${status}`, 'success');
            window.location.reload();
        }
    });
}

function showReschedulePrompt() {
    document.getElementById('reschedulePrompt').classList.remove('hidden');
}

function closeReschedulePrompt() {
    document.getElementById('reschedulePrompt').classList.add('hidden');
}

function confirmReschedule() {
    const visitId = document.getElementById('quickActionVisitId').value;
    const newDate = document.getElementById('newDate').value;
    const newTime = document.getElementById('newTime').value;
    
    if (!newDate || !newTime) {
        showNotification('Por favor seleccione fecha y hora', 'error');
        return;
    }

    fetch('actions/visit_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'reschedule',
            visit_id: visitId,
            new_date: newDate,
            new_time: newTime
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Visita reprogramada exitosamente', 'success');
            window.location.reload();
        }
    });
}

function showReassignPrompt() {
    document.getElementById('reassignPrompt').classList.remove('hidden');
}

function closeReassignPrompt() {
    document.getElementById('reassignPrompt').classList.add('hidden');
}

function confirmReassign() {
    const visitId = document.getElementById('quickActionVisitId').value;
    const newTechnicianId = document.getElementById('newTechnician').value;
    
    fetch('actions/visit_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'reassign',
            visit_id: visitId,
            technician_id: newTechnicianId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Técnico reasignado exitosamente', 'success');
            window.location.reload();
        }
    });
}

function confirmDeleteVisit() {
    if (confirm('¿Está seguro de eliminar esta visita?')) {
        const visitId = document.getElementById('quickActionVisitId').value;
        
        fetch('actions/visit_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                visit_id: visitId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Visita eliminada exitosamente', 'success');
                window.location.reload();
            }
        });
    }
}
</script>