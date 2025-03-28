<!-- admin/visits/modals/new_visit.php -->
<div id="newVisitModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-4xl">
        <div class="bg-white rounded-lg shadow-xl m-4">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold">Nueva Visita</h3>
                <button onclick="hideNewVisitModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="newVisitForm" onsubmit="saveVisit(event)" class="p-6">
                <!-- Cliente y Contacto -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Cliente/Empresa</label>
                            <input type="text" name="client_name" required
                                   class="w-full p-2 border rounded focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Nombre de Contacto</label>
                            <input type="text" name="contact_name" required
                                   class="w-full p-2 border rounded focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Teléfono</label>
                            <input type="tel" name="contact_phone" required
                                   class="w-full p-2 border rounded focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Ubicación -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Dirección</label>
                            <input type="text" name="address" required
                                   class="w-full p-2 border rounded focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Referenciaaa</label>
                            <input type="text" name="reference"
                                   class="w-full p-2 border rounded focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">URL de Google Maps</label>
                            <input type="url" name="location_url"
                                   class="w-full p-2 border rounded focus:border-blue-500"
                                   placeholder="https://maps.google.com/?q=...">
                        </div>
                    </div>
                </div>

                <!-- Programación -->
                <div class="mt-6">
                    <h4 class="font-medium mb-4">Programación</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Fecha</label>
                            <input type="date" name="visit_date" required
                                   min="<?php echo date('Y-m-d'); ?>"
                                   class="w-full p-2 border rounded focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Hora</label>
                            <input type="time" name="visit_time" required
                                   class="w-full p-2 border rounded focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Duración Estimada</label>
                            <select name="duration" required class="w-full p-2 border rounded focus:border-blue-500">
                                <option value="30">30 minutos</option>
                                <option value="60" selected>1 hora</option>
                                <option value="90">1.5 horas</option>
                                <option value="120">2 horas</option>
                                <option value="180">3 horas</option>
                                <option value="240">4 horas</option>
                            </select>
                        </div>
                        <div id="availabilityFeedback" class="text-sm mt-2"></div>
                    </div>
                </div>

                <!-- Tipo de Servicio -->
                <div class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">    
                <div>    
                <label class="block text-sm font-medium mb-1">Técnico</label>
                <select name="technician_id" required 
                        class="w-full p-2 border rounded focus:border-blue-500">
                        <option value="">Seleccionar técnico...</option>
                        <?php foreach ($technicians as $tech): ?>
                        <option value="<?php echo $tech['id']; ?>">
                        <?php echo htmlspecialchars($tech['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Tipo de Servicio</label>
                    <select name="service_type" required
                            class="w-full p-2 border rounded focus:border-blue-500">
                        <option value="">Seleccionar tipo...</option>
                        <option value="Instalación">Instalación</option>
                        <option value="Reparación">Reparación</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                        <option value="Revisión">Revisión</option>
                    </select>
                </div>
                </div>
                </div>

                <!-- Notas -->
                <div class="mt-6">
                    <label class="block text-sm font-medium mb-1">Notas</label>
                    <textarea name="notes" rows="3"
                              class="w-full p-2 border rounded focus:border-blue-500"></textarea>
                </div>

                <!-- Botones -->
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="hideNewVisitModal()"
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Guardar Visita
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function saveVisit(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    try {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Verificando disponibilidad...';

        // Verificar disponibilidad primero
        const availabilityCheck = await validateAvailability();
        if (!availabilityCheck.success) {
            showNotification(availabilityCheck.error, 'error');
            return;
        }

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
        const formData = new FormData(form);

        const response = await fetch('actions/save_visit.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            showNotification(
                formData.get('id') ? 'Visita actualizada exitosamente' : 'Visita creada exitosamente', 
                'success'
            );
            closeModal('visitFormModal');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(data.error || 'Error al guardar la visita', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al procesar la solicitud', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

function showNewVisitModal() {
    document.getElementById('newVisitModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function hideNewVisitModal() {
    document.getElementById('newVisitModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    const form = document.getElementById('newVisitForm');
    if (form) form.reset();
}

function validateAvailability() {
    const form = document.getElementById('visitForm');
    const formData = new FormData(form);
    
    return fetch('actions/check_visit_availability.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            technician_id: formData.get('technician_id'),
            visit_date: formData.get('visit_date'),
            visit_time: formData.get('visit_time'),
            duration: formData.get('duration'),
            visit_id: formData.get('id') // para edición
        })
    })
    .then(response => response.json());
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function setupRealTimeValidation() {
    const form = document.getElementById('visitForm');
    const fields = ['technician_id', 'visit_date', 'visit_time'];
    
    fields.forEach(field => {
        form.elements[field]?.addEventListener('change', async () => {
            // Solo validar si todos los campos necesarios están llenos
            if (fields.every(f => form.elements[f]?.value)) {
                try {
                    const check = await validateAvailability();
                    const feedback = document.getElementById('availabilityFeedback');
                    
                    if (check.success) {
                        feedback.className = 'text-sm text-green-600 mt-2';
                        feedback.innerHTML = '<i class="fas fa-check mr-1"></i>Horario disponible';
                    } else {
                        feedback.className = 'text-sm text-red-600 mt-2';
                        feedback.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>${check.error}`;
                    }
                } catch (error) {
                    console.error('Error en validación:', error);
                }
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', setupRealTimeValidation);

</script>