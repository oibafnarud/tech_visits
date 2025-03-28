const VisitUtils = {
    formatTime(time) {
        if (!time) return '';
        return time;
    },

    getStatusClass(status) {
        return {
            completed: 'bg-green-100 text-green-800',
            in_route: 'bg-yellow-100 text-yellow-800',
            pending: 'bg-blue-100 text-blue-800'
        }[status] || 'bg-gray-100 text-gray-800';
    },

    getStatusBorderClass(status) {
        return {
            completed: 'border-green-500',
            in_route: 'border-yellow-500',
            pending: 'border-blue-500'
        }[status] || 'border-gray-500';
    },

    getStatusLabel(status) {
        return {
            completed: 'Completada',
            in_route: 'En Camino',
            pending: 'Pendiente'
        }[status] || 'Pendiente';
    },

    renderActions(visit) {
        if (visit.status === 'completed') return '';
        
        return `
            <div class="flex justify-end space-x-2 mt-4">
                ${visit.status === 'pending' ? `
                    <button onclick="updateStatus(${visit.id}, 'in_route')" 
                            class="action-button btn-route">
                        <i class="fas fa-truck mr-1"></i>
                        En Ruta
                    </button>
                ` : ''}
                
                <button onclick="updateStatus(${visit.id}, 'completed')" 
                        class="action-button btn-complete">
                    <i class="fas fa-check mr-1"></i>
                    Completar
                </button>
            </div>
        `;
    }
};

window.VisitUtils = VisitUtils;