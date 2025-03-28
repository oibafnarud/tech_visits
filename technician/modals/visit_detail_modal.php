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