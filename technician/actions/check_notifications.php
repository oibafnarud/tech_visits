<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';
require_once '../../includes/Notification.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();
    
    $notification = new Notification($db);
    
    // Obtener conteo de no leídas
    $unreadCount = $notification->getUnreadCount($_SESSION['user_id']);
    
    // Obtener notificaciones nuevas
    $lastCheck = $_SESSION['last_notification_check'] ?? date('Y-m-d H:i:s', strtotime('-1 minute'));
    $newNotifications = $notification->getNewNotifications($_SESSION['user_id'], $lastCheck);
    
    // Actualizar último check
    $_SESSION['last_notification_check'] = date('Y-m-d H:i:s');
    
    echo json_encode([
        'success' => true,
        'unread_count' => $unreadCount,
        'new_notifications' => $newNotifications,
        'last_check' => $lastCheck
    ]);

} catch (Exception $e) {
    error_log("Error checking notifications: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}