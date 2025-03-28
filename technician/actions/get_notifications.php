
<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';
require_once '../../includes/Notification.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();
    
    $notification = new Notification($db);
    $notifications = $notification->getNotifications($_SESSION['user_id'], 20, 0);
    
    // Asegurar que siempre devolvemos un array
    echo json_encode([
        'success' => true,
        'notifications' => $notifications ?: []
    ]);

} catch (Exception $e) {
    error_log("Error en get_notifications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}