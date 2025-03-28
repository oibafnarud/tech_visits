window.Notifications = {
    init: function() {
        this.checkNotifications();
        setInterval(() => this.checkNotifications(), 30000);
    },

    toggle: function() {
        const panel = document.getElementById('notificationsPanel');
        const isHidden = panel.classList.contains('translate-x-full');
        
        if (isHidden) {
            this.load();
        }
        
        panel.classList.toggle('translate-x-full');
    },

    async load: function() {
        const loadingDiv = document.getElementById('notificationsLoading');
        const contentDiv = document.getElementById('notificationsContent');
        
        try {
            loadingDiv?.classList.remove('hidden');
            contentDiv?.classList.add('opacity-50');

            const response = await fetch('./actions/get_notifications.php');
            const data = await response.json();
            
            if (data.success) {
                this.render(data.notifications);
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Error:', error);
            this.renderError();
        } finally {
            loadingDiv?.classList.add('hidden');
            contentDiv?.classList.remove('opacity-50');
        }
    },

    async checkNotifications: function() {
        try {
            const response = await fetch('./actions/check_notifications.php');
            const data = await response.json();
            
            if (data.success && data.new_notifications?.length > 0) {
                this.playSound();
                this.updateBadge(data.unread_count);
            }
        } catch (error) {
            console.error('Error checking notifications:', error);
        }
    },

    playSound: function() {
        const audio = document.getElementById('notificationSound');
        if (audio) {
            audio.play().catch(console.error);
        }
    }
};

// Exponer función global para compatibilidad
window.toggleNotifications = () => window.Notifications.toggle();