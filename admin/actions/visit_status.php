<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   http_response_code(405);
   echo json_encode(['error' => 'Método no permitido']);
   exit;
}

try {
   $data = json_decode(file_get_contents('php://input'), true);
   
   if (!isset($data['visit_id']) || !isset($data['status'])) {
       throw new Exception('Datos incompletos');
   }

   // Validar estado
   $validStatus = ['pending', 'in_route', 'completed', 'cancelled'];
   if (!in_array($data['status'], $validStatus)) {
       throw new Exception('Estado no válido');
   }

   $db->beginTransaction();

   // Actualizar estado
   $stmt = $db->prepare("
       UPDATE visits 
       SET status = :status,
           completion_time = :completion_time,
           updated_at = NOW()
       WHERE id = :visit_id
   ");

   $params = [
       ':status' => $data['status'],
       ':visit_id' => $data['visit_id'],
       ':completion_time' => $data['status'] === 'completed' ? date('Y-m-d H:i:s') : null
   ];

   $stmt->execute($params);

   // Registrar el cambio en el historial
   $stmt = $db->prepare("
       INSERT INTO visit_history (
           visit_id, action, action_by, action_at, details, notes
       ) VALUES (
           :visit_id, :action, :user_id, NOW(), :details, :notes
       )
   ");

   $stmt->execute([
       ':visit_id' => $data['visit_id'],
       ':action' => 'status_change',
       ':user_id' => $_SESSION['user_id'],
       ':details' => "Estado cambiado a: " . $data['status'],
       ':notes' => $data['notes'] ?? null
   ]);

   // Si se completó la visita, registrar coordenadas
   if ($data['status'] === 'completed' && isset($data['coordinates'])) {
       $stmt = $db->prepare("
           UPDATE visits 
           SET completion_location = POINT(:lat, :lng)
           WHERE id = :visit_id
       ");

       $stmt->execute([
           ':lat' => $data['coordinates']['lat'],
           ':lng' => $data['coordinates']['lng'],
           ':visit_id' => $data['visit_id']
       ]);
   }

   $db->commit();

   // Notificar cambio si está configurado
   if ($data['notify'] ?? false) {
       $stmt = $db->prepare("
           SELECT v.*, u.email as tech_email
           FROM visits v
           JOIN users u ON v.technician_id = u.id
           WHERE v.id = :visit_id
       ");
       $stmt->execute([':visit_id' => $data['visit_id']]);
       $visit = $stmt->fetch(PDO::FETCH_ASSOC);

       // Implementar el envío de notificaciones aquí
   }

   echo json_encode(['success' => true]);

} catch (Exception $e) {
   if ($db->inTransaction()) {
       $db->rollBack();
   }
   echo json_encode(['error' => $e->getMessage()]);
}