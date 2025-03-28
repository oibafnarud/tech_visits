<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();
    
    if (!isset($_POST['technician_id'])) {
        throw new Exception('Técnico no especificado');
    }

    $db->beginTransaction();

    // Eliminar horarios existentes
    $stmt = $db->prepare("DELETE FROM technician_availability WHERE technician_id = ?");
    $stmt->execute([$_POST['technician_id']]);

    // Insertar nuevos horarios
    $stmt = $db->prepare("
        INSERT INTO technician_availability 
        (technician_id, day_of_week, start_time, end_time) 
        VALUES (?, ?, ?, ?)
    ");

    // Procesar cada día
    for ($day = 1; $day <= 7; $day++) {
        if (isset($_POST['start_time'][$day]) && isset($_POST['end_time'][$day])) {
            $startTimes = $_POST['start_time'][$day];
            $endTimes = $_POST['end_time'][$day];
            
            for ($i = 0; $i < count($startTimes); $i++) {
                if (empty($startTimes[$i]) || empty($endTimes[$i])) continue;
                
                $stmt->execute([
                    $_POST['technician_id'],
                    $day,
                    $startTimes[$i],
                    $endTimes[$i]
                ]);
            }
        }
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}