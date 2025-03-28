<!-- Modal para Pendientes -->
<div id="pendingVisitsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="fixed inset-4 bg-white rounded-xl overflow-hidden flex flex-col">
        <div class="sticky top-0 bg-white border-b z-10">
            <div class="flex justify-between items-center p-4">
                <div class="flex items-center space-x-2">
                    <h3 class="text-lg font-bold">Visitas Pendientes</h3>
                    <span class="pending-badge bg-yellow-500"><?php echo $pendingCount; ?></span>
                </div>
                <button onclick="hidePendingVisits()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4" id="pendingVisitsContent">
            <!-- El contenido se cargará dinámicamente -->
        </div>
    </div>
</div>