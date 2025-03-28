<!-- Modal de Reporte -->
<div id="reportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="fixed inset-4 bg-white rounded-xl overflow-hidden flex flex-col">
        <div class="p-4 border-b flex justify-between items-center sticky top-0 bg-white">
            <h3 class="text-lg font-bold">Mi Rendimiento</h3>
            <button onclick="hideReportModal()" class="text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4">
            <div id="reportContent" class="space-y-6">
                <!-- El contenido se cargará dinámicamente -->
                <div class="animate-pulse">
                    <div class="h-8 bg-gray-200 rounded w-3/4 mb-4"></div>
                    <div class="space-y-3">
                        <div class="h-4 bg-gray-200 rounded"></div>
                        <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                        <div class="h-4 bg-gray-200 rounded w-4/6"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>