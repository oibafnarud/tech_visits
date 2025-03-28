<?php
require_once '../../../includes/session_check.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['visit_id']) || !isset($data['status'])) {
        throw new Exception('Datos incompletos');
    }

    $database = new Database();
    $db = $database->connect();

    $db->beginTransaction();

    // Preparar los campos a actualizar según el estado
    $updateFields = ['status = :status'];
    $params = [
        ':status' => $data['status'],
        ':visit_id' => $data['visit_id']
    ];

    // Agregar campos adicionales según el estado
    switch($data['status']) {
        case 'in_route':
            $updateFields[] = 'start_time = NOW()';
            break;
            
        case 'completed':
            $updateFields[] = 'completion_time = NOW()';
            if (!empty($data['notes'])) {
                $updateFields[] = 'completion_notes = :completion_notes';
                $params[':completion_notes'] = $data['notes'];
            }
            if (!empty($data['coordinates'])) {
                $updateFields[] = 'completion_location = POINT(:lat, :lng)';
                $params[':lat'] = $data['coordinates']['lat'];
                $params[':lng'] = $data['coordinates']['lng'];
            }
            break;
            
        case 'revert':
            $updateFields = [
                'status = "pending"',
                'completion_time = NULL',
                'completion_location = NULL',
                'completion_notes = NULL'
            ];
            break;    
    }

    // Construir y ejecutar la consulta
    $sql = "UPDATE visits SET " . implode(', ', $updateFields) . " WHERE id = :visit_id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Registrar en historial
    $stmt = $db->prepare("
        INSERT INTO visit_history (
            visit_id, action, action_by, action_at, details, notes
        ) VALUES (
            :visit_id, :action, :user_id, NOW(), :details, :notes
        )
    ");

    $historyDetails = "Estado cambiado a: " . $data['status'];
    if ($data['status'] === 'completed' && !empty($data['coordinates'])) {
        $historyDetails .= " (con ubicación registrada)";
    }

    $stmt->execute([
        ':visit_id' => $data['visit_id'],
        ':action' => 'status_change',
        ':user_id' => $_SESSION['user_id'],
        ':details' => $historyDetails,
        ':notes' => $data['notes'] ?? null
    ]);

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error en update_status: " . $e->getMessage());
    echo json_encode(['error' => 'Error al actualizar el estado: ' . $e->getMessage()]);
}