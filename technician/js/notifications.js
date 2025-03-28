// File: /technician/js/notifications.js

let notificationsVisible = false;

function toggleNotifications() {
    const panel = document.getElementById('notificationsPanel');
    notificationsVisible = !notificationsVisible;
    panel.classList.toggle('hidden', !notificationsVisible);
}

async function markAsRead(notificationId) {
    try {
        const response = await fetch('actions/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notificationId })
        });

        const data = await response.json();
        if (data.success) {
            // Actualizar UI
            updateNotificationCounter();
            const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notification) {
                notification.classList.remove('border-l-4', 'border-blue-500');
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function markAllAsRead() {
    try {
        const response = await fetch('actions/mark_all_notifications_read.php', {
            method: 'POST'
        });

        const data = await response.json();
        if (data.success) {
            // Actualizar UI
            document.querySelectorAll('.border-l-4.border-blue-500').forEach(notification => {
                notification.classList.remove('border-l-4', 'border-blue-500');
            });
            updateNotificationCounter(0);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function updateNotificationCounter(count = null) {
    const counter = document.querySelector('#notificationCounter');
    if (count === 0 || count === null) {
        counter?.remove();
    } else if (counter) {
        counter.textContent = count;
    }
}

// Verificar notificaciones periódicamente
setInterval(async () => {
    if (!notificationsVisible) {
        try {
            const response = await fetch('actions/check_notifications.php');
            const data = await response.json();
            if (data.unread_count > 0) {
                updateNotificationCounter(data.unread_count);
                
                // Mostrar notificación push si hay nuevas
                if (data.new_notifications && Notification.permission === 'granted') {
                    data.new_notifications.forEach(notification => {
                        new Notification(notification.title, {
                            body: notification.message,
                            icon: '/assets/images/logo.png'
                        });
                    });
                }
            }
        } catch (error) {
            console.error('Error checking notifications:', error);
        }
    }
}, 30000); // Cada 30 segundos

// Solicitar permisos para notificaciones push
if ('Notification' in window) {
    Notification.requestPermission();
}

// Cerrar panel de notificaciones al hacer clic fuera
document.addEventListener('click', (event) => {
    const panel = document.getElementById('notificationsPanel');
    const bell = document.querySelector('.fa-bell').parentElement;
    
    if (notificationsVisible && !panel.contains(event.target) && !bell.contains(event.target)) {
        toggleNotifications();
    }
});