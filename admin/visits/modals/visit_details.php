<!-- modals/visit_details.php -->
<div id="visitDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl">
        <div class="bg-white rounded-lg shadow-xl m-4">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold">Detalles de la Visita</h3>
                <button onclick="closeModal('visitDetailsModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="visitDetailsContent" class="p-6">
                <!-- El contenido se cargará dinámicamente -->
            </div>
            
            <script>
            function updateStatus(visitId, status) {
                if (!confirm(`¿Está seguro de ${status === 'pending' ? 'revertir' : 'cambiar'} el estado de la visita?`)) return;
            
                fetch('actions/update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        visit_id: visitId,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Estado actualizado exitosamente', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showNotification(data.error || 'Error al actualizar el estado', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al actualizar el estado', 'error');
                });
            }
            
            function confirmDelete(visitId) {
                if (!confirm('¿Está seguro de eliminar esta visita?')) return;
            
                fetch('actions/delete_visit.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        visit_id: visitId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Visita eliminada exitosamente', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showNotification(data.error || 'Error al eliminar la visita', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al eliminar la visita', 'error');
                });
            }
            </script>