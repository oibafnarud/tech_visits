<!-- Modal de Reprogramación -->
<div id="rescheduleModal" class="modal hidden">
    <div class="modal-content bg-white p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Reprogramar Visita</h3>
            <button onclick="hideRescheduleModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="rescheduleForm" onsubmit="handleReschedule(event)">
            <input type="hidden" name="visit_id" id="rescheduleVisitId">
            
            <div class="space-y-4">
                <!-- Información de la visita actual -->
                <div class="p-4 bg-gray-50 rounded-lg mb-4">
                    <div id="rescheduleVisitDetails" class="text-sm space-y-2">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>

                <!-- Nueva fecha y hora -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Nueva Fecha</label>
                        <input type="date" name="new_date" required
                               min="<?php echo date('Y-m-d'); ?>"
                               class="w-full p-2 border rounded focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Nueva Hora</label>
                        <input type="time" name="new_time" required
                               class="w-full p-2 border rounded focus:border-blue-500">
                    </div>
                </div>

                <!-- Motivo del cambio -->
                <div>
                    <label class="block text-gray-700 mb-2">Motivo de la reprogramación</label>
                    <textarea name="reschedule_reason" required
                              rows="3"
                              placeholder="Explique el motivo del cambio de fecha..."
                              class="w-full p-2 border rounded focus:border-blue-500"></textarea>
                </div>

                <!-- Notificar al técnico -->
                <div class="flex items-center">
                    <input type="checkbox" name="notify_technician" id="notifyTechnician"
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <label for="notifyTechnician" class="ml-2 block text-sm text-gray-700">
                        Notificar al técnico sobre el cambio
                    </label>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideRescheduleModal()"
                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Confirmar Reprogramación
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showRescheduleModal(visitId) {
    // Obtener los detalles de la visita
    fetch(`actions/visit_actions.php?action=get_visit_details&id=${visitId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const visit = data.visit;
                document.getElementById('rescheduleVisitId').value = visitId;
                
                // Actualizar detalles en el modal
                const details = document.getElementById('rescheduleVisitDetails');
                details.innerHTML = `
                    <div class="font-medium text-gray-800">Detalles actuales:</div>
                    <div><strong>Cliente:</strong> ${visit.client_name}</div>
                    <div><strong>Fecha actual:</strong> ${formatDate(visit.visit_date)}</div>
                    <div><strong>Hora actual:</strong> ${formatTime(visit.visit_time)}</div>
                    <div><strong>Técnico:</strong> ${visit.technician_name}</div>
                `;

                // Establecer valores por defecto
                document.querySelector('[name="new_date"]').value = visit.visit_date;
                document.querySelector('[name="new_time"]').value = visit.visit_time;

                document.getElementById('rescheduleModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al cargar los detalles de la visita', 'error');
        });
}

function hideRescheduleModal() {
    document.getElementById('rescheduleModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    document.getElementById('rescheduleForm').reset();
}

function handleReschedule(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'reschedule_visit');

    // Validar que la nueva fecha/hora sea futura
    const newDateTime = new Date(formData.get('new_date') + 'T' + formData.get('new_time'));
    if (newDateTime < new Date()) {
        showNotification('La nueva fecha y hora deben ser futuras', 'error');
        return;
    }

    // Mostrar indicador de carga
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Reprogramando...';

    fetch('actions/visit_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Visita reprogramada exitosamente', 'success');
            hideRescheduleModal();
            window.location.reload();
        } else {
            showNotification(data.error || 'Error al reprogramar la visita', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al reprogramar la visita', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}
</script>