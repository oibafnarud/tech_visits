<?php
require_once '../../../includes/session_check.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['visit_id'])) {
        throw new Exception('ID de visita no proporcionado');
    }

    $database = new Database();
    $db = $database->connect();

    $db->beginTransaction();

    // Verificar si la visita existe y no está completada
    $stmt = $db->prepare("
        DELETE FROM visits 
        WHERE id = :visit_id
    ");

    $result = $stmt->execute([':visit_id' => $data['visit_id']]);

    if ($result) {
        $db->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Error al eliminar la visita');
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}