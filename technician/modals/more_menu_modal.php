<!-- Menú más -->
<div id="moreMenu" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-xl">
        <div class="p-4">
            <div class="mb-4">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold">Menú</h3>
                    <button onclick="toggleMoreMenu()" class="text-gray-500">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Perfil -->
                <div class="flex items-center p-4 bg-gray-50 rounded-lg mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                        <i class="fas fa-user text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <div class="font-medium"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                        <div class="text-sm text-gray-500">Técnico</div>
                    </div>
                </div>

                <!-- Opciones del menú -->
                <div class="space-y-2">
                    <button onclick="window.showTechnicianReport()" class="w-full flex items-center p-3 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-chart-bar w-8 text-blue-600"></i>
                        <span>Mi Rendimiento</span>
                    </button>

                    <button onclick="showPasswordModal()" class="w-full flex items-center p-3 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-key w-8 text-blue-600"></i>
                        <span>Cambiar Contraseña</span>
                    </button>

                    <div class="border-t my-2"></div>

                    <a href="../logout.php" class="flex items-center p-3 hover:bg-red-50 text-red-600 rounded-lg">
                        <i class="fas fa-sign-out-alt w-8"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>