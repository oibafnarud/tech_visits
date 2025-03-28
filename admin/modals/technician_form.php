<div id="technicianModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl">
        <div class="bg-white rounded-lg shadow-xl m-4">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold" id="modalTitle">Nuevo Técnico</h3>
                <button onclick="document.getElementById('technicianModal').classList.add('hidden')" 
                        class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="technicianForm" onsubmit="saveTechnician(event)" class="p-6">
                <input type="hidden" name="id" id="technicianId">
                
                <!-- Información Personal -->
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Nombre Completo *</label>
                            <input type="text" name="full_name" required
                                   class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Usuario *</label>
                            <input type="text" name="username" required
                                   pattern="[a-zA-Z0-9_]+"
                                   title="Solo letras, números y guión bajo"
                                   class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Email</label>
                            <input type="email" name="email"
                                   class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Teléfono</label>
                            <input type="tel" name="phone"
                                   class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Especialidades -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Especialidades</label>
                        <div class="space-y-2">
                            <div class="flex flex-wrap gap-2" id="selectedSpecialties">
                                <!-- Las etiquetas seleccionadas se mostrarán aquí -->
                            </div>
                            <div class="flex gap-2">
                                <select id="specialtySelect" class="flex-1 p-2 border rounded focus:ring-2 focus:ring-blue-500">
                                    <option value="">Seleccionar especialidad...</option>
                                    <option value="puerta_enrollable">Puerta Enrollable</option>
                                    <option value="puerta_corredera">Puerta Corredera</option>
                                    <option value="shutter">Shutter</option>
                                    <option value="motor">Motor</option>
                                    <option value="controlador">Controlador</option>
                                </select>
                                <button type="button" onclick="addSpecialty()"
                                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <input type="hidden" name="specialties" id="specialtiesInput">
                        </div>
                    </div>

                    <!-- Horario de Trabajo -->
                    <div class="border-t pt-4">
                        <h4 class="font-medium mb-4">Horario de Trabajo</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Hora Inicio</label>
                                <input type="time" name="work_start" required
                                       class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Hora Fin</label>
                                <input type="time" name="work_end" required
                                       class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Contraseña (solo para nuevos técnicos) -->
                    <div id="passwordFields" class="border-t pt-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Contraseña *</label>
                                <input type="password" name="password" 
                                       minlength="6"
                                       class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Confirmar Contraseña *</label>
                                <input type="password" name="password_confirm"
                                       minlength="6"
                                       class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="send_credentials" class="rounded border-gray-300">
                            <span class="ml-2 text-sm text-gray-600">
                                Enviar credenciales por email
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Botones -->
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('technicianModal').classList.add('hidden')"
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>