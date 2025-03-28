
<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

// Verificar que el usuario sea super_admin
if ($_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$database = new Database();
$db = $database->connect();

// Manejar petición GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($_GET['action'] === 'get' && isset($_GET['id'])) {
        $stmt = $db->prepare("
            SELECT id, username, full_name, email, role, active 
            FROM users 
            WHERE id = ? AND role IN ('admin', 'editor')
        ");
        $stmt->execute([$_GET['id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            echo json_encode([
                'success' => false,
                'error' => 'Administrador no encontrado'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'admin' => $admin
        ]);
        exit;
    }
}

// Manejar petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    
    // Si es JSON (para toggle_status o delete)
    if (empty($_POST)) {
        $data = json_decode(file_get_contents('php://input'), true);
    }

    try {
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'toggle_status':
                    $stmt = $db->prepare("UPDATE users SET active = ? WHERE id = ?");
                    $result = $stmt->execute([$data['active'], $data['admin_id']]);
                    echo json_encode(['success' => $result]);
                    exit;

                case 'delete':
                    if ($data['admin_id'] == $_SESSION['user_id']) {
                        throw new Exception('No puede eliminarse a sí mismo');
                    }

                    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role IN ('admin', 'editor')");
                    $result = $stmt->execute([$data['admin_id']]);
                    echo json_encode(['success' => $result]);
                    exit;
            }
        }

        // Crear/Actualizar administrador
        if (empty($data['admin_id'])) {
            // Nuevo administrador
            $stmt = $db->prepare("
                INSERT INTO users (
                    username, password, full_name, 
                    email, role, created_at,
                    created_by, active
                ) VALUES (
                    :username, :password, :full_name, 
                    :email, :role, NOW(),
                    :created_by, 1
                )
            ");

            $result = $stmt->execute([
                ':username' => $data['username'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':full_name' => $data['full_name'],
                ':email' => $data['email'],
                ':role' => $data['role'],
                ':created_by' => $_SESSION['user_id']
            ]);
        } else {
            // Actualizar administrador
            $sql = "UPDATE users SET 
                    username = :username,
                    full_name = :full_name,
                    email = :email,
                    role = :role";
            
            $params = [
                ':username' => $data['username'],
                ':full_name' => $data['full_name'],
                ':email' => $data['email'],
                ':role' => $data['role'],
                ':id' => $data['admin_id']
            ];

            if (!empty($data['password'])) {
                $sql .= ", password = :password";
                $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = :id AND role IN ('admin', 'editor')";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($params);
        }

        echo json_encode(['success' => $result]);

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}