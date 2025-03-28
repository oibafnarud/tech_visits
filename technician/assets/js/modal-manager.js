const ModalManager = {
    init() {
        this.bindGlobalEvents();
        this.setupModals();
    },

    setupModals() {
        document.querySelectorAll('.modal-container').forEach(modal => {
            const closeBtn = modal.querySelector('[data-close-modal]');
            const content = modal.querySelector('.modal-content');
            
            if (content) {
                content.onclick = e => e.stopPropagation();
            }
            
            if (closeBtn) {
                closeBtn.onclick = () => this.hideModal(modal.id);
            }
            
            modal.onclick = () => this.hideModal(modal.id);
        });
    },

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

    bindGlobalEvents() {
        window.showModal = this.showModal.bind(this);
        window.hideModal = this.hideModal.bind(this);
    }
};

window.ModalManager = ModalManager;