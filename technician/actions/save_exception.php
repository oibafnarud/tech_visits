<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

// Verificar que sea un técnico
if ($_SESSION['role'] !== 'technician') {
    http_response_code(403);
    die(json_encode(['error' => 'Acceso no autorizado']));
}

try {
    $database = new Database();
    $db = $database->connect();

    // Validar datos requeridos
    if (empty($_POST['exception_date'])) {
        throw new Exception('La fecha es requerida');
    }

    // Verificar si ya existe una excepción para esa fecha
    $stmt = $db->prepare("
        DELETE FROM availability_exceptions 
        WHERE technician_id = :technician_id 
        AND exception_date = :exception_date
    ");
    $stmt->execute([
        ':technician_id' => $_SESSION['user_id'],
        ':exception_date' => $_POST['exception_date']
    ]);

    // Insertar nueva excepción
    $stmt = $db->prepare("
        INSERT INTO availability_exceptions (
            technician_id, exception_date, 
            start_time, end_time,
            is_available, reason,
            created_at
        ) VALUES (
            :technician_id, :exception_date,
            :start_time, :end_time,
            :is_available, :reason,
            NOW()
        )
    ");

    $result = $stmt->execute([
        ':technician_id' => $_SESSION['user_id'],
        ':exception_date' => $_POST['exception_date'],
        ':start_time' => isset($_POST['is_available']) ? $_POST['start_time'] : null,
        ':end_time' => isset($_POST['is_available']) ? $_POST['end_time'] : null,
        ':is_available' => isset($_POST['is_available']) ? 1 : 0,
        ':reason' => $_POST['reason'] ?? null
    ]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Excepción guardada correctamente'
        ]);
    } else {
        throw new Exception('Error al guardar la excepción');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}