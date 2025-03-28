<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';
require_once '../includes/Notification.php';

$database = new Database();
$db = $database->connect();

// Obtener notificaciones
$notification = new Notification($db);
$notifications = $notification->getNotifications($_SESSION['user_id'], 20);
?>

<div class="space-y-4">
    <?php foreach ($notifications as $notif): ?>
        <div class="bg-white rounded-lg shadow-sm p-4 <?php echo $notif['status'] === 'unread' ? 'border-l-4 border-blue-500' : ''; ?>">
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="font-medium"><?php echo htmlspecialchars($notif['title']); ?></h4>
                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                </div>
                <span class="text-xs text-gray-500">
                    <?php echo timeAgo($notif['created_at']); ?>
                </span>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($notifications)): ?>
        <div class="text-center py-8 text-gray-500">
            No hay notificaciones
        </div>
    <?php endif; ?>
</div>

<!-- Agregar contador de notificaciones en el header -->
<div id="notificationCounter" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
    <?php echo count(array_filter($notifications, fn($n) => $n['status'] === 'unread')); ?>
</div>