<?php
// Activar temporalmente la visualización de errores para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../../includes/session_check.php';
require_once '../../../config/database.php';
require_once '../../../includes/Notification.php';

// Log para debug
error_log("Iniciando save_visit.php");
error_log("POST data: " . print_r($_POST, true));

header('Content-Type: application/json');

try {
    // Validar datos requeridos
    $requiredFields = ['client_name', 'visit_date', 'visit_time', 'technician_id'];
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        error_log("Campos faltantes: " . implode(', ', $missingFields));
        throw new Exception('Campos requeridos faltantes: ' . implode(', ', $missingFields));
    }

    $database = new Database();
    $db = $database->connect();
    $db->beginTransaction();

    error_log("Conexión a base de datos establecida");

    if (empty($_POST['visit_id'])) {
        // NUEVA VISITA
        $sql = "INSERT INTO visits (
            client_name, contact_name, contact_phone,
            address, reference, location_url,
            visit_date, visit_time, technician_id,
            service_type, duration, notes, 
            status, created_by
        ) VALUES (
            :client_name, :contact_name, :contact_phone,
            :address, :reference, :location_url,
            :visit_date, :visit_time, :technician_id,
            :service_type, :duration, :notes,
            'pending', :created_by
        )";

        error_log("SQL para nueva visita: " . $sql);

        $stmt = $db->prepare($sql);
        
        $params = [
            ':client_name' => $_POST['client_name'],
            ':contact_name' => $_POST['contact_name'] ?? '',
            ':contact_phone' => $_POST['contact_phone'] ?? '',
            ':address' => $_POST['address'] ?? '',
            ':reference' => $_POST['reference'] ?? '',
            ':location_url' => $_POST['location_url'] ?? '',
            ':visit_date' => $_POST['visit_date'],
            ':visit_time' => $_POST['visit_time'],
            ':technician_id' => $_POST['technician_id'],
            ':service_type' => $_POST['service_type'] ?? '',
            ':duration' => $_POST['duration'] ?? 60,
            ':notes' => $_POST['notes'] ?? '',
            ':created_by' => $_SESSION['user_id']
        ];

        error_log("Parámetros: " . print_r($params, true));

        if (!$stmt->execute($params)) {
            error_log("Error en execute: " . print_r($stmt->errorInfo(), true));
            throw new Exception('Error al guardar la visita: ' . implode(' ', $stmt->errorInfo()));
        }

        $visitId = $db->lastInsertId();
        error_log("Visita creada con ID: " . $visitId);

        // Registrar en historial
        $historyStmt = $db->prepare("
            INSERT INTO visit_history (
                visit_id, action, action_by, action_at, details
            ) VALUES (
                :visit_id, 'create', :user_id, NOW(), 'Visita creada'
            )
        ");

        if (!$historyStmt->execute([
            ':visit_id' => $visitId,
            ':user_id' => $_SESSION['user_id']
        ])) {
            error_log("Error al crear historial: " . print_r($historyStmt->errorInfo(), true));
            throw new Exception('Error al registrar el historial');
        }

        // Intentar crear notificación
        try {
            error_log("Intentando crear notificación");
            $notification = new Notification($db);
            $notification->create(
                $_POST['technician_id'],
                'Nueva visita asignada',
                "Se te ha asignado una visita para el " . date('d/m/Y', strtotime($_POST['visit_date'])) . 
                " a las " . date('H:i', strtotime($_POST['visit_time'])),
                'visit_assigned',
                'visit',
                $visitId
            );
        } catch (Exception $e) {
            error_log("Error en notificación: " . $e->getMessage());
            // No revertimos la transacción por errores en notificaciones
        }

    } else {
        // Código para actualizar visita existente...
    }

    $db->commit();
    error_log("Transacción completada exitosamente");
    
    echo json_encode([
        'success' => true,
        'message' => empty($_POST['visit_id']) ? 'Visita creada exitosamente' : 'Visita actualizada exitosamente'
    ]);

} catch (Exception $e) {
    error_log("Error en save_visit.php: " . $e->getMessage());
    
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}