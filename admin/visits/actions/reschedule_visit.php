<?php
// admin/visits/actions/reschedule_visit.php
require_once '../../../includes/session_check.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();

    $db->beginTransaction();

    // Actualizar fecha y hora de la visita
    $stmt = $db->prepare("
        UPDATE visits 
        SET visit_date = :new_date,
            visit_time = :new_time,
            updated_at = NOW()
        WHERE id = :visit_id
    ");

    $stmt->execute([
        ':visit_id' => $_POST['visit_id'],
        ':new_date' => $_POST['new_date'],
        ':new_time' => $_POST['new_time']
    ]);

    // Registrar en historial
    $stmt = $db->prepare("
        INSERT INTO visit_history (
            visit_id, action, action_by, action_at, details, notes
        ) VALUES (
            :visit_id, 'reschedule', :user_id, NOW(), 
            :details, :notes
        )
    ");

    $details = "Visita reprogramada para: " . $_POST['new_date'] . " " . $_POST['new_time'];
    
    $stmt->execute([
        ':visit_id' => $_POST['visit_id'],
        ':user_id' => $_SESSION['user_id'],
        ':details' => $details,
        ':notes' => $_POST['reason'] ?? null
    ]);

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}