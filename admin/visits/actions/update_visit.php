<?php
require_once '../../../includes/session_check.php';
require_once '../../../config/database.php';
require_once '../../../includes/utils.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $database = new Database();
    $db = $database->connect();

    // Validar datos requeridos
    $requiredFields = ['visit_id', 'client_name', 'contact_name', 'contact_phone', 'address', 
                      'visit_date', 'visit_time', 'technician_id', 'service_type'];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es requerido");
        }
    }

    // Preparar la consulta
    $stmt = $db->prepare("
        UPDATE visits SET 
            client_name = :client_name,
            contact_name = :contact_name,
            contact_phone = :contact_phone,
            address = :address,
            reference = :reference,
            location_url = :location_url,
            visit_date = :visit_date,
            visit_time = :visit_time,
            duration = :duration,
            service_type = :service_type,
            technician_id = :technician_id,
            notes = :notes,
            updated_at = NOW()
        WHERE id = :visit_id
    ");

    // Ejecutar la actualización
    $result = $stmt->execute([
        ':visit_id' => $_POST['visit_id'],
        ':client_name' => $_POST['client_name'],
        ':contact_name' => $_POST['contact_name'],
        ':contact_phone' => $_POST['contact_phone'],
        ':address' => $_POST['address'],
        ':reference' => $_POST['reference'] ?? '',
        ':location_url' => $_POST['location_url'] ?? '',
        ':visit_date' => $_POST['visit_date'],
        ':visit_time' => $_POST['visit_time'],
        ':duration' => $_POST['duration'] ?? 60,
        ':service_type' => $_POST['service_type'],
        ':technician_id' => $_POST['technician_id'],
        ':notes' => $_POST['notes'] ?? ''
    ]);

    if (!$result) {
        throw new Exception('Error al actualizar la visita');
    }

    // Registrar en el historial
    $stmt = $db->prepare("
        INSERT INTO visit_history (
            visit_id, action, action_by, action_at, details
        ) VALUES (
            :visit_id, 'update', :user_id, NOW(), 'Visita actualizada'
        )
    ");

    $stmt->execute([
        ':visit_id' => $_POST['visit_id'],
        ':user_id' => $_SESSION['user_id']
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Error en update_visit.php: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}