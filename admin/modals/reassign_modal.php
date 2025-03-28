<!-- Modal de Reasignación -->
<div id="reassignModal" class="modal hidden">
    <div class="modal-content bg-white p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Reasignar Visita</h3>
            <button onclick="hideReassignModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="reassignForm" onsubmit="handleReassign(event)">
            <input type="hidden" name="visit_id" id="reassignVisitId">
            
            <div class="space-y-4">
                <!-- Información de la visita actual -->
                <div class="p-4 bg-gray-50 rounded-lg">
                    <div id="reassignVisitDetails" class="text-sm space-y-2">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>

                <!-- Selector de técnico -->
                <div>
                    <label class="block text-gray-700 mb-2">Seleccionar nuevo técnico</label>
                    <select name="new_technician_id" required
                            class="w-full p-2 border rounded focus:border-blue-500">
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>">
                                <?php echo htmlspecialchars($tech['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Motivo del cambio -->
                <div>
                    <label class="block text-gray-700 mb-2">Motivo de la reasignación</label>
                    <textarea name="reassign_reason" required
                              rows="3"
                              placeholder="Explique el motivo del cambio de técnico..."
                              class="w-full p-2 border rounded focus:border-blue-500"></textarea>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideReassignModal()"
                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Confirmar Reasignación
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentVisitData = null;

function showReassignModal(visitId) {
    // Obtener los detalles de la visita
    fetch(`actions/visit_actions.php?action=get_visit_details&id=${visitId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentVisitData = data.visit;
                document.getElementById('reassignVisitId').value = visitId;
                
                // Actualizar detalles en el modal
                const details = document.getElementById('reassignVisitDetails');
                details.innerHTML = `
                    <div><strong>Cliente:</strong> ${currentVisitData.client_name}</div>
                    <div><strong>Fecha:</strong> ${formatDate(currentVisitData.visit_date)}</div>
                    <div><strong>Hora:</strong> ${formatTime(currentVisitData.visit_time)}</div>
                    <div><strong>Técnico actual:</strong> ${currentVisitData.technician_name}</div>
                `;

                // Excluir el técnico actual de las opciones
                const select = document.querySelector('[name="new_technician_id"]');
                Array.from(select.options).forEach(option => {
                    option.disabled = option.value === currentVisitData.technician_id;
                });

                document.getElementById('reassignModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al cargar los detalles de la visita', 'error');
        });
}

function hideReassignModal() {
    document.getElementById('reassignModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    document.getElementById('reassignForm').reset();
    currentVisitData = null;
}

function handleReassign(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'reassign_visit');

    // Mostrar indicador de carga
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Reasignando...';

    fetch('actions/visit_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Visita reasignada exitosamente', 'success');
            hideReassignModal();
            window.location.reload();
        } else {
            showNotification(data.error || 'Error al reasignar la visita', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al reasignar la visita', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Funciones auxiliares
function formatDate(dateStr) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateStr).toLocaleDateString('es-ES', options);
}

function formatTime(timeStr) {
    return new Date(`2000-01-01T${timeStr}`).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>