<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SESSION['role'] !== 'technician') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();

    // Verificar contraseña actual
    $stmt = $db->prepare("SELECT password FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($_POST['current_password'], $user['password'])) {
        echo json_encode(['error' => 'La contraseña actual es incorrecta']);
        exit;
    }

    // Validar nueva contraseña
    if (strlen($_POST['new_password']) < 6) {
        echo json_encode(['error' => 'La nueva contraseña debe tener al menos 6 caracteres']);
        exit;
    }

    if ($_POST['new_password'] !== $_POST['confirm_password']) {
        echo json_encode(['error' => 'Las contraseñas no coinciden']);
        exit;
    }

    // Actualizar contraseña
    $stmt = $db->prepare("
        UPDATE users 
        SET password = :new_password,
            password_changed_at = NOW()
        WHERE id = :user_id
    ");

    $result = $stmt->execute([
        ':new_password' => password_hash($_POST['new_password'], PASSWORD_DEFAULT),
        ':user_id' => $_SESSION['user_id']
    ]);

    if ($result) {
        // Registrar el cambio de contraseña
        $stmt = $db->prepare("
            INSERT INTO password_changes (
                user_id,
                changed_at,
                ip_address
            ) VALUES (
                :user_id,
                NOW(),
                :ip_address
            )
        ");

        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':ip_address' => $_SERVER['REMOTE_ADDR']
        ]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error al actualizar la contraseña']);
    }

} catch (PDOException $e) {
    error_log("Error en cambio de contraseña: " . $e->getMessage());
    echo json_encode(['error' => 'Error al procesar la solicitud']);
}
?>