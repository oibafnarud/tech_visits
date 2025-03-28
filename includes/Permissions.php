// File: /includes/Permissions.php
<?php
class Permissions {
    private $db;
    private $user;

    public function __construct($db, $user) {
        $this->db = $db;
        $this->user = $user;
    }

    public function can($action) {
        switch ($this->user['admin_level']) {
            case 'super_admin':
                return true;
            case 'admin':
                return $action !== 'delete_admin';
            case 'editor':
                return in_array($action, ['create', 'edit', 'view']);
            default:
                return false;
        }
    }

    public function logAction($actionType, $entityType, $entityId, $details = []) {
        $stmt = $this->db->prepare("
            INSERT INTO admin_logs (
                user_id, action_type, entity_type, 
                entity_id, details, ip_address
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $this->user['id'],
            $actionType,
            $entityType,
            $entityId,
            json_encode($details),
            $_SERVER['REMOTE_ADDR']
        ]);
    }
}