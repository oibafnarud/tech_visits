
<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';
require_once '../../includes/Notification.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['notification_id'])) {
        throw new Exception('ID de notificación no proporcionado');
    }

    $database = new Database();
    $db = $database->connect();
    
    $notification = new Notification($db);
    $result = $notification->markAsRead($data['notification_id'], $_SESSION['user_id']);
    
    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {
    error_log("Error en mark_notification_read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}