<!-- modals/reschedule_modal.php -->
<div id="rescheduleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-lg">
        <div class="bg-white rounded-lg shadow-xl m-4">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold">Reprogramar Visita</h3>
                <button onclick="closeModal('rescheduleModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="rescheduleForm" onsubmit="saveReschedule(event)" class="p-6">
                <input type="hidden" name="visit_id" id="rescheduleVisitId">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Nueva Fecha</label>
                        <input type="date" name="new_date" required
                               min="<?php echo date('Y-m-d'); ?>"
                               class="w-full p-2 border rounded focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Nueva Hora</label>
                        <input type="time" name="new_time" required
                               class="w-full p-2 border rounded focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Motivo del cambio</label>
                        <textarea name="reason" required rows="3"
                                  class="w-full p-2 border rounded focus:border-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="notify_client" class="rounded border-gray-300">
                            <span class="ml-2 text-sm text-gray-600">Notificar al cliente</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('rescheduleModal')"
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function rescheduleVisit(visitId) {
    document.getElementById('rescheduleVisitId').value = visitId;
    showModal('rescheduleModal');
}

async function saveReschedule(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    try {
        const response = await fetch('actions/reschedule_visit.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            closeModal('rescheduleModal');
            window.location.reload();
        } else {
            alert(data.error || 'Error al reprogramar la visita');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al reprogramar la visita');
    }
}
</script>

<!-- Corregir las funciones de ver y editar -->
<script>
async function showVisitDetails(visitId) {
    try {
        const response = await fetch(`actions/get_visit.php?id=${visitId}`);
        const data = await response.json();
        
        if (data.success) {
            fillVisitDetails(data.visit);
            showModal('visitDetailsModal');
        } else {
            alert(data.error || 'Error al cargar los detalles de la visita');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar los detalles de la visita');
    }
}

async function editVisit(visitId) {
    try {
        const response = await fetch(`actions/get_visit.php?id=${visitId}`);
        const data = await response.json();
        
        if (data.success) {
            fillEditForm(data.visit);
            showModal('editVisitModal');
        } else {
            alert(data.error || 'Error al cargar la visita');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar la visita');
    }
}

async function saveVisit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    try {
        const response = await fetch('actions/save_visit.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            closeModal(form.closest('.modal').id);
            window.location.reload();
        } else {
            alert(data.error || 'Error al guardar la visita');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar la visita');
    }
}

function fillVisitDetails(visit) {
    const content = document.getElementById('visitDetailsContent');
    content.innerHTML = generateVisitDetailsHTML(visit);
}

function fillEditForm(visit) {
    const form = document.getElementById('editVisitForm');
    // Llenar todos los campos del formulario
    Object.keys(visit).forEach(key => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            input.value = visit[key];
        }
    });
}
</script>