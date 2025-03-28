<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();

$stmt = $db->prepare("
    SELECT v.*, u.full_name as technician_name
    FROM visits v
    JOIN users u ON v.technician_id = u.id
    WHERE v.visit_date = CURRENT_DATE
    AND v.location_url IS NOT NULL
    AND v.location_url != ''
");
$stmt->execute();
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $visits]);