<div id="filterModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md">
        <div class="bg-white rounded-lg shadow-xl m-4">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-lg font-semibold">Filtros Avanzados</h3>
                <button onclick="closeFilterModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form onsubmit="applyFilters(event)" class="p-6">
                <div class="space-y-4">
                    <!-- Rango de fechas -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rango de Fechas</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500">Desde</label>
                                <input type="date" name="start_date" class="mt-1 w-full rounded-md border-gray-300">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500">Hasta</label>
                                <input type="date" name="end_date" class="mt-1 w-full rounded-md border-gray-300">
                            </div>
                        </div>
                    </div>

                    <!-- Rango de horas -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Horario</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500">Desde</label>
                                <input type="time" name="start_time" class="mt-1 w-full rounded-md border-gray-300">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500">Hasta</label>
                                <input type="time" name="end_time" class="mt-1 w-full rounded-md border-gray-300">
                            </div>
                        </div>
                    </div>

                    <!-- Tipo de servicio -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Servicio</label>
                        <select name="service_type" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">Todos</option>
                            <option value="Instalación">Instalación</option>
                            <option value="Reparación">Reparación</option>
                            <option value="Mantenimiento">Mantenimiento</option>
                        </select>
                    </div>

                    <!-- Zona/Ubicación -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Zona</label>
                        <input type="text" name="location" 
                               placeholder="Buscar por zona o dirección"
                               class="mt-1 block w-full rounded-md border-gray-300">
                    </div>

                    <!-- Opciones adicionales -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700">Opciones</label>
                        <div class="flex flex-col space-y-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="has_location" class="rounded border-gray-300 text-blue-600">
                                <span class="ml-2 text-sm text-gray-600">Solo con ubicación en mapa</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="has_phone" class="rounded border-gray-300 text-blue-600">
                                <span class="ml-2 text-sm text-gray-600">Solo con teléfono de contacto</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="resetFilters()"
                            class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                        Limpiar Filtros
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function closeFilterModal() {
    document.getElementById('filterModal').classList.add('hidden');
}

function applyFilters(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const params = new URLSearchParams(window.location.search);
    
    formData.forEach((value, key) => {
        if (value) params.set(key, value);
        else params.delete(key);
    });
    
    window.location.search = params.toString();
}

function resetFilters() {
    const baseParams = new URLSearchParams();
    baseParams.set('view', new URLSearchParams(window.location.search).get('view') || 'list');
    window.location.search = baseParams.toString();
}
</script>