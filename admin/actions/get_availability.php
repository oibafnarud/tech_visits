<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: application/json');
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

try {
    if (!isset($_GET['technician_id']) || !isset($_GET['start']) || !isset($_GET['end'])) {
        throw new Exception('Parámetros incompletos');
    }

    $database = new Database();
    $db = $database->connect();

    // Obtener horarios regulares
        $stmt = $db->prepare("
            SELECT 
                day_of_week,
                TIME_FORMAT(start_time, '%H') as start_time,
                TIME_FORMAT(end_time, '%H') as end_time
            FROM technician_availability 
            WHERE technician_id = ?
            ORDER BY day_of_week, start_time
        ");
    $stmt->execute([$_GET['technician_id']]);
    $regularSchedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener excepciones
    $stmt = $db->prepare("
        SELECT 
            id, 
            exception_date,
            is_available,
            reason,
            DATE_FORMAT(start_time, '%h:%i %p') as start_time_12h,
            DATE_FORMAT(end_time, '%h:%i %p') as end_time_12h,
            TIME_FORMAT(start_time, '%H:%i') as start_time,
            TIME_FORMAT(end_time, '%H:%i') as end_time
        FROM availability_exceptions 
        WHERE technician_id = ?
        AND exception_date BETWEEN ? AND ?
        ORDER BY exception_date
    ");
    $stmt->execute([$_GET['technician_id'], $_GET['start'], $_GET['end']]);
    $exceptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener visitas
    $stmt = $db->prepare("
        SELECT 
            visit_date,
            TIME_FORMAT(visit_time, '%H:%i') as visit_time,
            client_name, 
            address, 
            status
        FROM visits 
        WHERE technician_id = ?
        AND visit_date BETWEEN ? AND ?
        ORDER BY visit_date, visit_time
    ");
    $stmt->execute([$_GET['technician_id'], $_GET['start'], $_GET['end']]);
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener información del técnico
    $stmt = $db->prepare("SELECT id, full_name FROM users WHERE id = ?");
    $stmt->execute([$_GET['technician_id']]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $technician['id'],
            'name' => $technician['full_name'],
            'regular' => $regularSchedule,
            'exceptions' => $exceptions,
            'visits' => $visits,
            'range' => [
                'start' => $_GET['start'],
                'end' => $_GET['end']
            ]
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}