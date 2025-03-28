<!-- Panel de notificaciones -->
<div id="notificationsPanel" class="fixed inset-y-0 right-0 w-full md:w-96 bg-white shadow-lg transform translate-x-full transition-transform duration-300 z-50">
    <div class="h-full flex flex-col">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold">Notificaciones</h3>
            <button onclick="toggleNotifications()" class="text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="notificationsLoading" class="flex-1 flex items-center justify-center hidden">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <div id="notificationsContent" class="flex-1 overflow-y-auto divide-y">
            <!-- El contenido se cargará dinámicamente -->
        </div>
    </div>
</div>