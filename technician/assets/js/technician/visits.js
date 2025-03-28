
window.Visits = {
    showDetail: function(visitId) {
        const modal = document.getElementById('visitDetailModal');
        const content = document.getElementById('visitDetailContent');
        
        content.innerHTML = `
            <div class="flex justify-center items-center p-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
        `;
        
        modal.classList.remove('hidden');
        
        fetch(`get_visit_details.php?id=${visitId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = this.renderVisitDetail(data.visit);
                } else {
                    throw new Error(data.error);
                }
            })
            .catch(error => {
                content.innerHTML = `
                    <div class="text-center text-red-600 p-4">
                        Error al cargar los detalles. Intente nuevamente.
                    </div>
                `;
            });
    },

    async updateStatus(visitId, status) {
        if (!confirm(`¿Seguro que quieres marcar esta visita como ${
            status === 'completed' ? 'completada' : 'en camino'
        }?`)) return;

        try {
            const response = await fetch('./actions/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ visit_id: visitId, status })
            });

            const data = await response.json();
            if (data.success) {
                window.location.reload();
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            alert(error.message);
        }
    }
};

// Exponer funciones globalmente para compatibilidad
window.showVisitDetail = (visitId) => window.Visits.showDetail(visitId);
window.updateStatus = (visitId, status) => window.Visits.updateStatus(visitId, status);