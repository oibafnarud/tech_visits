<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';
require_once '../../includes/Notification.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();

    $stmt = $db->prepare("
        UPDATE notifications 
        SET status = 'read', read_at = NOW() 
        WHERE user_id = ? AND status = 'unread'
    ");
    
    $result = $stmt->execute([$_SESSION['user_id']]);

    echo json_encode(['success' => $result]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}