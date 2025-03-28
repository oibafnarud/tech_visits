
<div id="visitFormModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-4xl">
        <div class="bg-white rounded-lg shadow-xl m-4">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold" id="modalTitle">Nueva Visita</h3>
                <button type="button" onclick="closeModal('visitFormModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="visitForm" onsubmit="saveVisit(event)" class="p-6">
                <!-- Cliente y Contacto -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-1">Cliente/Empresa *</label>
                        <input type="text" name="client_name" required class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Contacto *</label>
                        <input type="text" name="contact_name" required class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Teléfono *</label>
                        <input type="tel" name="contact_phone" required class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Tipo de Servicio *</label>
                        <select name="service_type" required class="w-full p-2 border rounded">
                            <option value="">Seleccionar tipo...</option>
                            <option value="Instalación">Instalación</option>
                            <option value="Reparación">Reparación</option>
                            <option value="Mantenimiento">Mantenimiento</option>
                            <option value="Revisión">Revisión</option>
                        </select>
                    </div>
                </div>

                <!-- Ubicación -->
                <div class="grid grid-cols-1 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-1">Dirección *</label>
                        <input type="text" name="address" required class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Referencia</label>
                        <input type="text" name="reference" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">URL de Google Maps</label>
                        <input type="url" name="location_url" class="w-full p-2 border rounded"
                               placeholder="https://maps.google.com/?q=...">
                    </div>
                </div>

                <!-- Programación -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-1">Fecha *</label>
                        <input type="date" name="visit_date" required 
                               min="<?php echo date('Y-m-d'); ?>"
                               class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Hora *</label>
                        <input type="time" name="visit_time" required class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Duración *</label>
                        <select name="duration" required class="w-full p-2 border rounded">
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

                <!-- Técnico -->
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-1">Técnico Asignado *</label>
                    <select name="technician_id" required class="w-full p-2 border rounded">
                        <option value="">Seleccionar técnico...</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>">
                                <?php echo htmlspecialchars($tech['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Notas -->
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-1">Notas</label>
                    <textarea name="notes" rows="3" class="w-full p-2 border rounded"></textarea>
                </div>

                <!-- Botones -->
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('visitFormModal')"
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