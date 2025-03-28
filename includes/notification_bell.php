<?php
require_once __DIR__ . '/Notification.php';

// Verificar que la clase existe
if (!class_exists('Notification')) {
    echo "<!-- Error: Clase Notification no encontrada -->";
    return;
}

try {
    $notification = new Notification($db);
    $unreadCount = $notification->getUnreadCount($_SESSION['user_id']);
?>
    <div class="relative" x-data="{ open: false }">
        <!-- Notification Bell -->
        <button @click="open = !open" class="relative p-2 text-gray-400 hover:text-gray-500">
            <i class="fas fa-bell text-xl"></i>
            <?php if ($unreadCount > 0): ?>
                <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                    <?php echo $unreadCount; ?>
                </span>
            <?php endif; ?>
        </button>

        <!-- Notification Panel -->
        <div x-show="open" 
             @click.away="open = false"
             class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg py-2 z-50">
            <div class="max-h-96 overflow-y-auto">
                <?php
                $notifications = $notification->getNotifications($_SESSION['user_id'], 10);
                if (!empty($notifications)):
                    foreach ($notifications as $notif):
                ?>
                    <div class="px-4 py-3 hover:bg-gray-50">
                        <p class="text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($notif['title']); ?>
                        </p>
                        <p class="text-sm text-gray-500">
                            <?php echo htmlspecialchars($notif['message']); ?>
                        </p>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <div class="px-4 py-3 text-center text-gray-500">
                        No hay notificaciones
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php
} catch (Exception $e) {
    error_log("Error en notification_bell.php: " . $e->getMessage());
    echo "<!-- Error: " . htmlspecialchars($e->getMessage()) . " -->";
}
?>