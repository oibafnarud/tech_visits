<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();
    
    if (!isset($_POST['technician_id']) || !isset($_POST['exception_date'])) {
        throw new Exception('Datos incompletos');
    }

    // Establecer zona horaria
    date_default_timezone_set('America/Santo_Domingo');

    // Formatear la fecha sin ajustes de zona horaria
    $exceptionDate = $_POST['exception_date'];
    
    // Validar fecha
    if ($exceptionDate < date('Y-m-d')) {
        throw new Exception('La fecha no puede ser anterior a hoy');
    }

    // Primero eliminar cualquier excepción existente para esa fecha
    $stmt = $db->prepare("
        DELETE FROM availability_exceptions 
        WHERE technician_id = :technician_id 
        AND exception_date = :exception_date
    ");
    $stmt->execute([
        ':technician_id' => $_POST['technician_id'],
        ':exception_date' => $exceptionDate
    ]);

    // Preparar los datos de la excepción
    $isAvailable = isset($_POST['is_available']) && $_POST['is_available'] == '1';
    $startTime = null;
    $endTime = null;

    // Si es disponible y tiene horario específico
    if ($isAvailable && !empty($_POST['start_time']) && !empty($_POST['end_time'])) {
        $startTime = $_POST['start_time'];
        $endTime = $_POST['end_time'];
    }

    $stmt = $db->prepare("
        INSERT INTO availability_exceptions (
            technician_id, 
            exception_date, 
            start_time, 
            end_time,
            is_available, 
            reason
        ) VALUES (
            :technician_id, 
            :exception_date, 
            :start_time, 
            :end_time,
            :is_available, 
            :reason
        )
    ");

    $params = [
        ':technician_id' => $_POST['technician_id'],
        ':exception_date' => $exceptionDate,
        ':start_time' => $startTime,
        ':end_time' => $endTime,
        ':is_available' => $isAvailable ? 1 : 0,
        ':reason' => $_POST['reason'] ?? null
    ];

    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'data' => [
            'date' => $exceptionDate,
            'is_available' => $isAvailable,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}