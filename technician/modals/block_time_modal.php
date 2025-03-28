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