<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();

    $stmt = $db->prepare("
        SELECT 
            v.*,
            DATE_FORMAT(v.visit_date, '%Y-%m-%d') as visit_date,
            DATE_FORMAT(v.visit_time, '%H:%i') as visit_time
        FROM visits v 
        WHERE v.technician_id = :technician_id 
        AND v.status = 'pending'
        AND v.visit_date >= CURRENT_DATE
        ORDER BY v.visit_date ASC, v.visit_time ASC
    ");

    $stmt->execute([':technician_id' => $_SESSION['user_id']]);
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Añadir logging para debug
    error_log("Pending visits query result: " . print_r($visits, true));

    echo json_encode([
        'success' => true,
        'visits' => $visits
    ]);

} catch (Exception $e) {
    error_log("Error in get_pending_visits.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}