<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();

    // Obtener parámetros
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['technician_id']) || !isset($data['visit_date']) || !isset($data['visit_time'])) {
        throw new Exception('Datos incompletos');
    }

    // Verificar excepciones
    $stmt = $db->prepare("
        SELECT * FROM availability_exceptions 
        WHERE technician_id = :technician_id 
        AND exception_date = :visit_date
    ");
    
    $stmt->execute([
        ':technician_id' => $data['technician_id'],
        ':visit_date' => $data['visit_date']
    ]);
    
    $exception = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exception) {
        // Si hay una excepción para ese día
        if (!$exception['is_available']) {
            throw new Exception('El técnico no está disponible en esta fecha');
        }

        // Si tiene horario específico, verificar que la hora esté dentro del rango
        if ($exception['start_time'] && $exception['end_time']) {
            $visitTime = strtotime($data['visit_time']);
            $startTime = strtotime($exception['start_time']);
            $endTime = strtotime($exception['end_time']);

            if ($visitTime < $startTime || $visitTime >= $endTime) {
                throw new Exception('El técnico solo está disponible entre ' . 
                    date('h:i A', $startTime) . ' y ' . date('h:i A', $endTime));
            }
        }
    }

    // Verificar horario regular
    $dayOfWeek = date('N', strtotime($data['visit_date'])); // 1 (lunes) a 7 (domingo)
    
    $stmt = $db->prepare("
        SELECT * FROM technician_availability 
        WHERE technician_id = :technician_id 
        AND day_of_week = :day_of_week
        AND :visit_time BETWEEN start_time AND end_time
    ");

    $stmt->execute([
        ':technician_id' => $data['technician_id'],
        ':day_of_week' => $dayOfWeek,
        ':visit_time' => $data['visit_time']
    ]);

    if (!$stmt->fetch()) {
        throw new Exception('La hora seleccionada está fuera del horario regular del técnico');
    }

    // Verificar si ya tiene otra visita en ese horario
    $stmt = $db->prepare("
        SELECT * FROM visits 
        WHERE technician_id = :technician_id 
        AND visit_date = :visit_date
        AND (
            (visit_time <= :visit_time AND ADDTIME(visit_time, SEC_TO_TIME(duration * 60)) > :visit_time)
            OR
            (visit_time < ADDTIME(:visit_time, SEC_TO_TIME(:duration * 60)) 
             AND ADDTIME(visit_time, SEC_TO_TIME(duration * 60)) >= ADDTIME(:visit_time, SEC_TO_TIME(:duration * 60)))
        )
        AND status != 'completed'
        " . (isset($data['visit_id']) ? "AND id != :visit_id" : "") . "
    ");

    $params = [
        ':technician_id' => $data['technician_id'],
        ':visit_date' => $data['visit_date'],
        ':visit_time' => $data['visit_time'],
        ':duration' => $data['duration'] ?? 60
    ];

    if (isset($data['visit_id'])) {
        $params[':visit_id'] = $data['visit_id'];
    }

    $stmt->execute($params);
    
    if ($stmt->fetch()) {
        throw new Exception('El técnico ya tiene una visita programada en este horario');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Horario disponible'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}