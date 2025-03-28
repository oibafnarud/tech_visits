const App = {
    init() {
        // Inicializamos las funciones y eventos
        this.bindEvents();
        this.startNotificationCheck();
    },

    // Navegación y vistas
    changeView(view) {
        if (!view) return;
        window.location.href = `visits.php?view=${view}&date=${this.getCurrentDate()}`;
    },

    changeDate(value) {
        const currentDate = this.getCurrentDate();
        let newDate;

        if (value === 'prev' || value === 'next') {
            const date = new Date(currentDate);
            const offset = value === 'prev' ? -1 : 1;

            // Ajustar según la vista actual
            switch (this.getCurrentView()) {
                case 'month':
                    date.setMonth(date.getMonth() + offset);
                    break;
                case 'week':
                    date.setDate(date.getDate() + (offset * 7));
                    break;
                default:
                    date.setDate(date.getDate() + offset);
            }
            newDate = date.toISOString().split('T')[0];
        } else {
            newDate = value;
        }

        window.location.href = `visits.php?view=${this.getCurrentView()}&date=${newDate}`;
    },

    goToDate(target) {
        if (target === 'today') {
            window.location.href = `visits.php?view=${this.getCurrentView()}&date=${new Date().toISOString().split('T')[0]}`;
        }
    },

    getCurrentDate() {
        const params = new URLSearchParams(window.location.search);
        return params.get('date') || new Date().toISOString().split('T')[0];
    },

    getCurrentView() {
        const params = new URLSearchParams(window.location.search);
        return params.get('view') || 'today';
    },

    // Manejo de modales
    togglePendingVisits() {
        this.toggleModal('pendingVisitsModal');
        this.loadPendingVisits();
    },

    toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const isHidden = modal.classList.contains('hidden');
        
        // Cerrar todos los modales primero
        document.querySelectorAll('.modal').forEach(m => {
            m.classList.add('hidden');
        });

        if (isHidden) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    },


    bindEvents() {
        // Event listeners para modales
        document.querySelectorAll('[data-modal]').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.hideModal(modal.id);
                }
            });
        });
    },

    // Funciones de toggle
    togglePendingVisits() {
        const modal = document.getElementById('pendingVisitsModal');
        if (modal) {
            modal.classList.remove('hidden');
            this.loadPendingVisits();
        }
    },

    toggleNotifications() {
        const panel = document.getElementById('notificationsPanel');
        if (panel) {
            panel.classList.remove('hidden');
            this.loadNotifications();
        }
    },

    toggleBlockTimeModal() {
        const modal = document.getElementById('blockTimeModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    },

    toggleMoreMenu() {
        const menu = document.getElementById('moreMenu');
        if (menu) {
            menu.classList.remove('hidden');
        }
    },

    // Funciones para visitas
    showVisitDetails(visitId) {
        const modal = document.getElementById('visitDetailModal');
        const content = document.getElementById('visitDetailContent');
        
        if (!modal || !content) return;

        content.innerHTML = '<div class="flex justify-center p-4"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div></div>';
        modal.classList.remove('hidden');

        fetch(`actions/get_visit_details.php?id=${visitId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = this.renderVisitDetail(data.visit);
                } else {
                    throw new Error(data.error || 'Error al cargar los detalles');
                }
            })
            .catch(error => {
                content.innerHTML = '<div class="text-center text-red-600 p-4">Error al cargar los detalles</div>';
                console.error('Error:', error);
            });
    },

    loadPendingVisits() {
        const content = document.getElementById('pendingVisitsContent');
        if (!content) return;

        content.innerHTML = '<div class="flex justify-center p-4"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div></div>';

        fetch('actions/get_pending_visits.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = this.renderPendingVisits(data.visits);
                } else {
                    throw new Error(data.error);
                }
            })
            .catch(error => {
                content.innerHTML = '<div class="text-center text-red-600 p-4">Error al cargar las visitas pendientes</div>';
                console.error('Error:', error);
            });
    },

    hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
        }
    },

    startNotificationCheck() {
        this.checkNotifications();
        setInterval(() => this.checkNotifications(), 30000);
    },

    checkNotifications() {
        fetch('actions/check_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.unread_count > 0) {
                    this.updateNotificationBadge(data.unread_count);
                }
            })
            .catch(error => console.error('Error checking notifications:', error));
    },

    updateNotificationBadge(count) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = count;
            badge.classList.toggle('hidden', count === 0);
        }
    },

    // Funciones de renderizado
    renderVisitDetail(visit) {
        return `
            <div class="space-y-4">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-xl font-bold">
                            ${this.formatTime(visit.visit_time)}
                        </div>
                        <div class="text-gray-600">${visit.client_name}</div>
                    </div>
                    <span class="status-badge ${visit.status}">
                        ${this.getStatusLabel(visit.status)}
                    </span>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-map-marker-alt w-5"></i>
                        <span class="ml-2">${visit.address}</span>
                    </div>
                    ${visit.contact_phone ? `
                        <a href="tel:${visit.contact_phone}" 
                           class="flex items-center text-blue-600 hover:text-blue-800">
                            <i class="fas fa-phone w-5"></i>
                            <span class="ml-2">${visit.contact_phone}</span>
                        </a>
                    ` : ''}
                </div>

                ${visit.status !== 'completed' ? this.renderVisitActions(visit) : ''}
            </div>
        `;
    },

    renderVisitActions(visit) {
        return `
            <div class="grid grid-cols-2 gap-2 mt-4">
                ${visit.location_url ? `
                    <a href="${visit.location_url}" 
                       target="_blank"
                       class="col-span-2 flex items-center justify-center bg-gray-100 text-gray-700 p-3 rounded-lg hover:bg-gray-200">
                        <i class="fas fa-map-marked-alt mr-2"></i>
                        Ver en Mapa
                    </a>
                ` : ''}

                ${visit.status === 'pending' ? `
                    <button onclick="App.updateVisitStatus(${visit.id}, 'in_route')"
                            class="flex items-center justify-center bg-yellow-500 text-white p-3 rounded-lg hover:bg-yellow-600">
                        <i class="fas fa-truck mr-2"></i>
                        En Camino
                    </button>
                ` : ''}

                <button onclick="App.updateVisitStatus(${visit.id}, 'completed')"
                        class="flex items-center justify-center bg-green-500 text-white p-3 rounded-lg hover:bg-green-600">
                    <i class="fas fa-check mr-2"></i>
                    Completar
                </button>
            </div>
        `;
    },

    // Utilidades
    formatTime(time) {
        return new Date(`2000-01-01T${time}`).toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    getStatusLabel(status) {
        const labels = {
            'pending': 'Pendiente',
            'in_route': 'En Camino',
            'completed': 'Completada',
            'delayed': 'Atrasada'
        };
        return labels[status] || status;
    }
};

// Exponer globalmente ANTES de la inicialización
window.App = App;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => window.App.init());