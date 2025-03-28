<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['exception_id'])) {
        throw new Exception('ID de excepción no proporcionado');
    }

    $database = new Database();
    $db = $database->connect();

    // Verificar si la excepción existe antes de eliminar
    $checkStmt = $db->prepare("SELECT id FROM availability_exceptions WHERE id = ?");
    $checkStmt->execute([$data['exception_id']]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Excepción no encontrada');
    }

    $stmt = $db->prepare("DELETE FROM availability_exceptions WHERE id = ?");
    $result = $stmt->execute([$data['exception_id']]);

    if (!$result) {
        throw new Exception('Error al eliminar la excepción');
    }

    // Verificar que realmente se eliminó
    $affected = $stmt->rowCount();
    if ($affected === 0) {
        throw new Exception('No se pudo eliminar la excepción');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Excepción eliminada correctamente',
        'affected_rows' => $affected
    ]);

} catch (Exception $e) {
    error_log("Error eliminando excepción: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}