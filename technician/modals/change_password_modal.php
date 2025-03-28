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