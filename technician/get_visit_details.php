
<?php
require_once '../includes/session_check.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID no proporcionado']);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();

    $stmt = $db->prepare("
        SELECT * FROM visits 
        WHERE id = :id AND technician_id = :technician_id
    ");

    $stmt->execute([
        ':id' => $_GET['id'],
        ':technician_id' => $_SESSION['user_id']
    ]);

    $visit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$visit) {
        http_response_code(404);
        echo json_encode(['error' => 'Visita no encontrada']);
        exit;
    }

    // Formatear la hora para mostrar
    $visit['visit_time'] = date('h:i A', strtotime($visit['visit_time']));

    echo json_encode([
        'success' => true,
        'visit' => $visit
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los detalles de la visita']);
}