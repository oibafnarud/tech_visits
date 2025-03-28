<div id="editVisitModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-4xl">
        <div class="bg-white rounded-lg shadow-xl m-4">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold">Editar Visita</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editVisitForm" onsubmit="handleEditSubmit(event)" class="p-6">
                <input type="hidden" name="visit_id" id="edit_visit_id">
                
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium mb-1">Cliente</label>
                            <input type="text" name="client_name" required class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Contacto</label>
                            <input type="text" name="contact_name" required class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Teléfono</label>
                            <input type="tel" name="contact_phone" required class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Dirección</label>
                            <input type="text" name="address" required class="w-full p-2 border rounded">
                        </div>
                        <div>
                        <label class="block text-sm font-medium mb-1">Referencia</label>
                        <input type="text" name="reference" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">URL Ubicación</label>
                        <input type="url" name="location_url" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Fecha</label>
                        <input type="date" name="visit_date" required class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Hora</label>
                        <input type="time" name="visit_time" required class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Técnico Asignado</label>
                        <select name="technician_id" required class="w-full p-2 border rounded">
                            <?php
                            // Obtener lista de técnicos
                            $stmt = $db->prepare("
                                SELECT id, full_name 
                                FROM users 
                                WHERE role = 'technician' 
                                AND active = 1 
                                ORDER BY full_name
                            ");
                            $stmt->execute();
                            $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($technicians as $tech): ?>
                                <option value="<?php echo $tech['id']; ?>">
                                    <?php echo htmlspecialchars($tech['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Notas</label>
                        <textarea name="notes" rows="3" class="w-full p-2 border rounded"></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>