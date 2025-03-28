<!-- Modal para bloquear horario -->
<div id="blockTimeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-xl modal-content">
        <div class="p-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Nueva Excepción</h3>
                <button onclick="hideBlockTimeModal()" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="exceptionForm" onsubmit="saveException(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Fecha</label>
                        <input type="date" name="exception_date" required
                               min="<?php echo date('Y-m-d'); ?>"
                               class="w-full p-2 border rounded">
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_available" value="1" 
                                   onchange="toggleTimeInputs(this)"
                                   class="rounded border-gray-300 text-blue-600">
                            <span class="ml-2">Disponible en horario específico</span>
                        </label>
                    </div>

                    <div id="timeInputs" class="hidden space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Hora Inicio</label>
                            <input type="time" name="start_time" class="w-full p-2 border rounded">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-1">Hora Fin</label>
                            <input type="time" name="end_time" class="w-full p-2 border rounded">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Motivo</label>
                        <input type="text" name="reason"
                               class="w-full p-2 border rounded"
                               placeholder="Ej: Cita médica, Vacaciones, etc.">
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="hideBlockTimeModal()"
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

<!-- Modal para ver todas las visitas del día -->
<div id="dayVisitsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-xl modal-content">
        <div class="p-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Visitas del día <span id="selectedDate"></span></h3>
                <button onclick="hideDayVisitsModal()" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="dayVisitsList" class="space-y-3 max-h-[70vh] overflow-y-auto"></div>
        </div>
    </div>
</div>

<!-- Modal Cambio de Contraseña -->
<div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-xl modal-content max-w-lg mx-auto">
        <div class="p-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Cambiar Contraseña</h3>
                <button onclick="hidePasswordModal()" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="passwordForm" onsubmit="changePassword(event)" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña Actual
                    </label>
                    <input type="password" 
                           name="current_password" 
                           required
                           minlength="6"
                           class="w-full p-3 border rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nueva Contraseña
                    </label>
                    <input type="password" 
                           name="new_password" 
                           required
                           minlength="6"
                           class="w-full p-3 border rounded-lg">
                    <p class="mt-1 text-sm text-gray-500">
                        Mínimo 6 caracteres
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Confirmar Nueva Contraseña
                    </label>
                    <input type="password" 
                           name="confirm_password" 
                           required
                           minlength="6"
                           class="w-full p-3 border rounded-lg">
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button"
                            onclick="hidePasswordModal()"
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Cambiar Contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Detalles de Visita -->
<div id="visitDetailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-xl modal-content">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-bold">Detalles de la Visita</h3>
            <button onclick="hideVisitDetailModal()" class="text-gray-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="visitDetailContent" class="p-4">
            <!-- El contenido se cargará dinámicamente -->
        </div>
    </div>
</div>

<!-- Modal para reprogramar -->
<div id="rescheduleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-xl modal-content">
        <div class="p-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Reprogramar Visita</h3>
                <button onclick="hideRescheduleModal()" class="text-gray-500">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="rescheduleForm" method="POST">
                <input type="hidden" name="action" value="reschedule">
                <input type="hidden" name="visit_id" id="rescheduleVisitId">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 mb-1">Nueva Fecha</label>
                        <input type="date" name="new_date" required
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               class="w-full p-3 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-1">Nueva Hora</label>
                        <input type="time" name="new_time" required
                               class="w-full p-3 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-1">Motivo del cambio</label>
                        <textarea name="reschedule_reason" required
                                  rows="2"
                                  class="w-full p-3 border rounded-lg"></textarea>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit"
                            class="w-full bg-blue-600 text-white p-3 rounded-lg font-semibold">
                        Confirmar Reprogramación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>