<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();

$visits = [];

// Visitas con ubicación para el mapa
$stmt = $db->prepare("
    SELECT v.*, u.full_name as technician_name
    FROM visits v
    JOIN users u ON v.technician_id = u.id
    WHERE v.visit_date = CURRENT_DATE 
    AND (v.location_lat IS NOT NULL OR v.location_lng IS NOT NULL)
");
$stmt->execute();
$visits['locations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Visitas próximas a vencerse sin confirmación
$stmt = $db->prepare("
    SELECT 
        v.*,
        u.full_name as technician_name,
        TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(v.visit_date, ' ', v.visit_time)) as minutes_until
    FROM visits v
    JOIN users u ON v.technician_id = u.id
    WHERE v.visit_date = CURRENT_DATE
    AND v.status = 'pending'
    AND v.visit_time <= DATE_ADD(NOW(), INTERVAL 30 MINUTE)
    ORDER BY v.visit_time ASC
");
$stmt->execute();
$visits['urgent'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($visits);