
<!-- Modal de Visitas Pendientes -->
<div id="pendingVisitsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="fixed inset-4 bg-white rounded-xl overflow-hidden flex flex-col">
        <div class="p-4 border-b flex justify-between items-center sticky top-0 bg-white">
            <h3 class="text-lg font-bold">Visitas Pendientes</h3>
            <button onclick="hidePendingVisits()" class="text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4" id="pendingVisitsContent">
            <!-- Se cargará dinámicamente -->
        </div>
    </div>
</div>