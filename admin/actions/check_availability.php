<?php
// actions/check_availability.php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['technicianId'], $data['date'], $data['time'], $data['duration'])) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();

    // Verificar horario de trabajo del técnico
    $stmt = $db->prepare("
        SELECT work_start_time, work_end_time 
        FROM users 
        WHERE id = :technician_id AND active = 1
    ");
    $stmt->execute([':technician_id' => $data['technicianId']]);
    $technicianHours = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$technicianHours) {
        echo json_encode(['available' => false, 'message' => 'Técnico no disponible']);
        exit;
    }

    $requestedTime = strtotime($data['time']);
    $workStart = strtotime($technicianHours['work_start_time']);
    $workEnd = strtotime($technicianHours['work_end_time']);

    // Verificar si está dentro del horario laboral
    if ($requestedTime < $workStart || $requestedTime > $workEnd) {
        echo json_encode([
            'available' => false, 
            'message' => 'Fuera del horario laboral (' . 
                        date('h:i A', $workStart) . ' - ' . 
                        date('h:i A', $workEnd) . ')'
        ]);
        exit;
    }

    // Verificar otras visitas en el mismo horario
    $visitStartTime = $data['time'];
    $visitEndTime = date('H:i:s', strtotime($data['time'] . ' +' . $data['duration'] . ' minutes'));

    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM visits 
        WHERE technician_id = :technician_id 
        AND visit_date = :visit_date
        AND status != 'completed'
        AND (
            (visit_time <= :start_time AND ADDTIME(visit_time, SEC_TO_TIME(duration * 60)) > :start_time)
            OR 
            (visit_time < :end_time AND ADDTIME(visit_time, SEC_TO_TIME(duration * 60)) >= :end_time)
            OR
            (visit_time >= :start_time AND ADDTIME(visit_time, SEC_TO_TIME(duration * 60)) <= :end_time)
        )
    ");

    $stmt->execute([
        ':technician_id' => $data['technicianId'],
        ':visit_date' => $data['date'],
        ':start_time' => $visitStartTime,
        ':end_time' => $visitEndTime
    ]);

    $conflictingVisits = $stmt->fetchColumn();

    if ($conflictingVisits > 0) {
        echo json_encode([
            'available' => false,
            'message' => 'El técnico ya tiene una visita programada en este horario'
        ]);
        exit;
    }

    echo json_encode([
        'available' => true,
        'message' => 'Horario disponible'
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'Error al verificar disponibilidad']);
}
?>