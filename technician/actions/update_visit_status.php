<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    // Obtener datos del POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['visit_id']) || !isset($data['status'])) {
        throw new Exception('Datos incompletos');
    }

    $database = new Database();
    $db = $database->connect();
    
    // Iniciar transacción
    $db->beginTransaction();

    // Verificar que la visita existe y pertenece al técnico
    $stmt = $db->prepare("
        SELECT id FROM visits 
        WHERE id = ? AND technician_id = ?
    ");
    $stmt->execute([$data['visit_id'], $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Visita no encontrada');
    }

    // Actualizar estado
    $stmt = $db->prepare("
        UPDATE visits 
        SET status = :status,
            completion_time = :completion_time,
            updated_at = NOW()
        WHERE id = :visit_id 
        AND technician_id = :technician_id
    ");

    $result = $stmt->execute([
        ':status' => $data['status'],
        ':completion_time' => $data['status'] === 'completed' ? date('Y-m-d H:i:s') : null,
        ':visit_id' => $data['visit_id'],
        ':technician_id' => $_SESSION['user_id']
    ]);

    if (!$result) {
        throw new Exception('Error al actualizar el estado');
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}