
<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();

    // Periodo actual (mes en curso)
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');

    // Estadísticas generales del mes
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_visits,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_visits,
            SUM(CASE WHEN status = 'in_route' THEN 1 ELSE 0 END) as in_route_visits,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_visits,
            AVG(CASE 
                WHEN status = 'completed' 
                THEN TIMESTAMPDIFF(MINUTE, CONCAT(visit_date, ' ', visit_time), completion_time)
                ELSE NULL 
            END) as avg_completion_time,
            COUNT(DISTINCT DATE(visit_date)) as active_days
        FROM visits 
        WHERE technician_id = :technician_id 
        AND visit_date BETWEEN :start_date AND :end_date
    ");

    $stmt->execute([
        ':technician_id' => $_SESSION['user_id'],
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ]);

    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Visitas por día de la semana
    $stmt = $db->prepare("
        SELECT 
            DAYOFWEEK(visit_date) as day_of_week,
            COUNT(*) as total
        FROM visits 
        WHERE technician_id = :technician_id 
        AND visit_date BETWEEN :start_date AND :end_date
        GROUP BY DAYOFWEEK(visit_date)
    ");

    $stmt->execute([
        ':technician_id' => $_SESSION['user_id'],
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ]);

    $visitsByDay = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tiempos promedio por tipo de servicio
    $stmt = $db->prepare("
        SELECT 
            service_type,
            COUNT(*) as total,
            AVG(CASE 
                WHEN status = 'completed' 
                THEN TIMESTAMPDIFF(MINUTE, CONCAT(visit_date, ' ', visit_time), completion_time)
                ELSE NULL 
            END) as avg_time
        FROM visits 
        WHERE technician_id = :technician_id 
        AND visit_date BETWEEN :start_date AND :end_date
        GROUP BY service_type
    ");

    $stmt->execute([
        ':technician_id' => $_SESSION['user_id'],
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ]);

    $serviceStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'stats' => $stats,
            'visitsByDay' => $visitsByDay,
            'serviceStats' => $serviceStats
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}