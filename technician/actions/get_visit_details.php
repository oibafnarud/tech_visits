<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID no proporcionado');
    }

    $database = new Database();
    $db = $database->connect();

    $stmt = $db->prepare("
        SELECT v.*, 
               DATE_FORMAT(v.visit_time, '%H:%i') as formatted_time,
               DATE_FORMAT(v.visit_date, '%Y-%m-%d') as formatted_date
        FROM visits v 
        WHERE v.id = :id 
        AND v.technician_id = :technician_id
    ");

    $stmt->execute([
        ':id' => $_GET['id'],
        ':technician_id' => $_SESSION['user_id']
    ]);

    $visit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$visit) {
        throw new Exception('Visita no encontrada');
    }

    echo json_encode([
        'success' => true,
        'visit' => $visit
    ]);

} catch (Exception $e) {
    error_log("Error en get_visit_details: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}