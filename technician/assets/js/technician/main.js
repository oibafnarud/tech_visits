// File: /tech_visits/technician/assets/js/technician/main.js

// Estado global de la aplicación
window.App = {
    init() {
        this.bindEvents();
    },

    bindEvents() {
        // Cerrar modales al hacer clic fuera
        document.querySelectorAll('.modal-container').forEach(modal => {
            modal.addEventListener('click', e => {
                // Si el clic fue directamente en el contenedor del modal (fondo oscuro)
                if (e.target === modal) {
                    this.hideModal(modal.id);
                }
            });
        });
    
        // Cerrar con botones de cierre
        document.querySelectorAll('[data-close-modal]').forEach(button => {
            button.addEventListener('click', e => {
                const modalId = button.getAttribute('data-close-modal');
                this.hideModal(modalId);
            });
        });
    }

    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    },

    hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    },

    toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal?.classList.contains('hidden')) {
            this.showModal(modalId);
        } else {
            this.hideModal(modalId);
        }
    }
};

// Definir todas las funciones globales necesarias
window.toggleTimeInputs = function(checkbox) {
    const timeInputs = document.getElementById('timeInputs');
    const startTime = document.querySelector('input[name="start_time"]');
    const endTime = document.querySelector('input[name="end_time"]');
    
    if (timeInputs) {
        timeInputs.style.display = checkbox.checked ? 'block' : 'none';
        startTime.required = checkbox.checked;
        endTime.required = checkbox.checked;
    }
};

window.showPendingVisits = window.togglePendingVisits = function() {
    App.toggleModal('pendingVisitsModal');
};

window.hidePendingVisits = function() {
    App.hideModal('pendingVisitsModal');
};

window.toggleNotifications = function() {
    App.toggleModal('notificationsPanel');
};

window.toggleBlockTimeModal = function() {
    App.toggleModal('blockTimeModal');
};

window.hideBlockTimeModal = function() {
    App.hideModal('blockTimeModal');
};

window.toggleMoreMenu = function() {
    App.toggleModal('moreMenu');
};

window.hideMoreMenu = function() {
    App.hideModal('moreMenu');
};

window.showPasswordModal = function() {
    App.hideModal('moreMenu');
    App.showModal('passwordModal');
};

window.hidePasswordModal = function() {
    App.hideModal('passwordModal');
};

window.showTechnicianReport = function() {
    App.hideModal('moreMenu');
    App.showModal('reportModal');
};

window.hideReportModal = function() {
    App.hideModal('reportModal');
};

window.showVisitDetail = function(visitId) {
    const content = document.getElementById('visitDetailContent');
    if (content) {
        content.innerHTML = '<div class="flex justify-center p-4"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div></div>';
        App.showModal('visitDetailModal');
        
        fetch(`get_visit_details.php?id=${visitId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = generateVisitDetailHTML(data.visit);
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
    }
};

window.hideVisitDetail = function() {
    App.hideModal('visitDetailModal');
};

// Funciones auxiliares
function generateVisitDetailHTML(visit) {
    return `
        <div class="space-y-4">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xl font-bold">${formatTime(visit.visit_time)}</div>
                    <div class="text-gray-600">${visit.client_name}</div>
                </div>
                <span class="px-3 py-1 rounded-full text-sm ${getStatusClass(visit.status)}">
                    ${getStatusLabel(visit.status)}
                </span>
            </div>
            
            <div class="space-y-3">
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-map-marker-alt w-5"></i>
                    <span class="ml-2">${visit.address}</span>
                </div>
            </div>
            
            ${visit.status !== 'completed' ? generateVisitActions(visit) : ''}
        </div>
    `;
}

function getStatusClass(status) {
    const classes = {
        completed: 'bg-green-100 text-green-800',
        in_route: 'bg-yellow-100 text-yellow-800',
        pending: 'bg-blue-100 text-blue-800'
    };
    return classes[status] || classes.pending;
}

function getStatusLabel(status) {
    const labels = {
        completed: 'Completada',
        in_route: 'En Camino',
        pending: 'Pendiente'
    };
    return labels[status] || 'Pendiente';
}

function formatTime(time) {
    if (!time) return '';
    try {
        const [hours, minutes] = time.split(':');
        return `${hours}:${minutes}`;
    } catch (e) {
        return time;
    }
}

window.loadPendingVisits = async function() {
    const content = document.getElementById('pendingVisitsContent');
    if (!content) return;

    content.innerHTML = `
        <div class="flex justify-center items-center p-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
    `;

    try {
        const response = await fetch('actions/get_pending_visits.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Error al cargar visitas');
        }

        content.innerHTML = data.visits.length ? 
            renderPendingVisits(data.visits) : 
            renderEmptyState();

    } catch (error) {
        content.innerHTML = renderError(error);
    }
};

// Función para renderizar visitas pendientes
function renderPendingVisits(visits) {
    return visits.map(visit => `
        <div class="pending-visit-card">
            <div class="pending-visit-header">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-xl font-bold">
                            ${formatTime(visit.visit_time)}
                        </div>
                        <div class="text-gray-600">
                            ${visit.visit_date}
                        </div>
                        <div class="font-medium mt-1">
                            ${visit.client_name}
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-4">
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-map-marker-alt w-5"></i>
                    <span>${visit.address}</span>
                </div>
                
                <div class="flex justify-end space-x-2 mt-4">
                    <button onclick="updateStatus(${visit.id}, 'in_route')"
                            class="action-button btn-route">
                        <i class="fas fa-truck mr-1"></i>
                        En Camino
                    </button>
                    <button onclick="updateStatus(${visit.id}, 'completed')"
                            class="action-button btn-complete">
                        <i class="fas fa-check mr-1"></i>
                        Completar
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Inicializar la aplicación
document.addEventListener('DOMContentLoaded', () => App.init());