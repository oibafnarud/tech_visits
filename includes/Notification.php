<?php

class Notification {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($userId, $title, $message, $type, $referenceType = null, $referenceId = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (
                    user_id, title, message, type, 
                    reference_type, reference_id, 
                    status, created_at
                ) VALUES (
                    :user_id, :title, :message, :type,
                    :reference_type, :reference_id,
                    'unread', NOW()
                )
            ");

            return $stmt->execute([
                ':user_id' => $userId,
                ':title' => $title,
                ':message' => $message,
                ':type' => $type,
                ':reference_type' => $referenceType,
                ':reference_id' => $referenceId
            ]);
        } catch (Exception $e) {
            error_log("Error creando notificación: " . $e->getMessage());
            return false;
        }
    }

    public function getUnreadCount($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM notifications 
                WHERE user_id = ? 
                AND status = 'unread'
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error obteniendo conteo de notificaciones: " . $e->getMessage());
            return 0;
        }
    }

public function getNotifications($userId, $limit = 10, $offset = 0) {
    try {
        $stmt = $this->db->prepare("
            SELECT n.*,
                v.client_name, v.visit_date, v.visit_time
            FROM notifications n
            LEFT JOIN visits v ON v.id = n.reference_id 
                AND n.reference_type = 'visit'
            WHERE n.user_id = :user_id
            ORDER BY n.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        // Importante: bindear los parámetros correctamente para LIMIT y OFFSET
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formatear los mensajes según el tipo
        foreach ($notifications as &$notification) {
            if ($notification['reference_type'] === 'visit' && !empty($notification['client_name'])) {
                $notification['message'] = sprintf(
                    "Cliente: %s\nFecha: %s\nHora: %s",
                    $notification['client_name'],
                    date('d/m/Y', strtotime($notification['visit_date'])),
                    date('h:i A', strtotime($notification['visit_time']))
                );
            }
        }

        return $notifications;
    } catch (PDOException $e) {
        error_log("Error en getNotifications: " . $e->getMessage());
        throw new Exception("Error al obtener las notificaciones");
    }
}


public function getNewNotifications($userId, $lastCheck) {
    $stmt = $this->db->prepare("
        SELECT * 
        FROM notifications 
        WHERE user_id = :user_id 
        AND created_at > :last_check
        AND status = 'unread'
        ORDER BY created_at DESC
    ");

    $stmt->execute([
        ':user_id' => $userId,
        ':last_check' => $lastCheck
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET status = 'read', 
                    read_at = NOW() 
                WHERE id = ? 
                AND user_id = ?
            ");
            return $stmt->execute([$notificationId, $userId]);
        } catch (Exception $e) {
            error_log("Error marcando notificación como leída: " . $e->getMessage());
            return false;
        }
    }

    public function markAllAsRead($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET status = 'read', 
                    read_at = NOW() 
                WHERE user_id = ? 
                AND status = 'unread'
            ");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Error marcando todas las notificaciones como leídas: " . $e->getMessage());
            return false;
        }
    }
}