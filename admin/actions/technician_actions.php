
<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();

    // Manejador de peticiones GET
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_GET['action'])) {
            throw new Exception('No se especificó una acción');
        }

        switch ($_GET['action']) {
            case 'get_technician':
                if (!isset($_GET['id'])) {
                    throw new Exception('ID no proporcionado');
                }
                
                $stmt = $db->prepare("
                    SELECT 
                        id, username, full_name, 
                        COALESCE(email, '') as email,
                        COALESCE(phone, '') as phone,
                        COALESCE(specialties, '[]') as specialties,
                        COALESCE(work_start_time, '08:00:00') as work_start,
                        COALESCE(work_end_time, '17:00:00') as work_end,
                        active
                    FROM users 
                    WHERE id = :id AND role = 'technician'
                ");
                
                $stmt->execute([':id' => $_GET['id']]);
                $technician = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$technician) {
                    throw new Exception('Técnico no encontrado');
                }

                echo json_encode(['success' => true, 'technician' => $technician]);
                break;

            case 'toggle_status':
                if (!isset($_GET['id']) || !isset($_GET['status'])) {
                    throw new Exception('Parámetros incompletos');
                }
                
                $stmt = $db->prepare("
                    UPDATE users 
                    SET active = :status
                    WHERE id = :id AND role = 'technician'
                ");
                
                $result = $stmt->execute([
                    ':status' => $_GET['status'],
                    ':id' => $_GET['id']
                ]);
                
                if (!$result) {
                    throw new Exception('Error al actualizar el estado');
                }
                
                echo json_encode(['success' => true]);
                break;

            default:
                throw new Exception('Acción no válida');
        }
        exit;
    }

    // Manejador de peticiones POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db->beginTransaction();

        if (empty($_POST['id'])) {
            // CREAR NUEVO TÉCNICO
            if (empty($_POST['username']) || empty($_POST['full_name']) || empty($_POST['password'])) {
                throw new Exception("Faltan campos requeridos");
            }

            // Verificar si el usuario ya existe
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$_POST['username']]);
            if ($stmt->fetch()) {
                throw new Exception("El nombre de usuario ya existe");
            }

            $stmt = $db->prepare("
                INSERT INTO users (
                    username, full_name, email, phone, 
                    password, role, specialties,
                    work_start_time, work_end_time, 
                    active
                ) VALUES (
                    :username, :full_name, :email, :phone,
                    :password, 'technician', :specialties,
                    :work_start, :work_end,
                    1
                )
            ");

            $result = $stmt->execute([
                ':username' => $_POST['username'],
                ':full_name' => $_POST['full_name'],
                ':email' => $_POST['email'] ?? null,
                ':phone' => $_POST['phone'] ?? null,
                ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                ':specialties' => $_POST['specialties'] ?? '[]',
                ':work_start' => $_POST['work_start'] ?? '08:00:00',
                ':work_end' => $_POST['work_end'] ?? '17:00:00'
            ]);

        } else {
            // ACTUALIZAR TÉCNICO EXISTENTE
            $sql = "
                UPDATE users SET 
                    username = :username,
                    full_name = :full_name,
                    email = :email,
                    phone = :phone,
                    specialties = :specialties,
                    work_start_time = :work_start,
                    work_end_time = :work_end
            ";

            $params = [
                ':username' => $_POST['username'],
                ':full_name' => $_POST['full_name'],
                ':email' => $_POST['email'] ?? null,
                ':phone' => $_POST['phone'] ?? null,
                ':specialties' => $_POST['specialties'] ?? '[]',
                ':work_start' => $_POST['work_start'] ?? '08:00:00',
                ':work_end' => $_POST['work_end'] ?? '17:00:00',
                ':id' => $_POST['id']
            ];

            if (!empty($_POST['password'])) {
                $sql .= ", password = :password";
                $params[':password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = :id AND role = 'technician'";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($params);
        }

        if (!$result) {
            throw new Exception('Error al guardar los datos');
        }

        $db->commit();
        echo json_encode(['success' => true]);
        exit;
    }

    throw new Exception('Método no permitido');

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}