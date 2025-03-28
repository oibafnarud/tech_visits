<?php
session_start();
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $database = new Database();
        $db = $database->connect();
        
        $stmt = $db->prepare("SELECT id, password, role, full_name FROM users WHERE username = ? AND active = 1");
        $stmt->execute([$_POST['username']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($_POST['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Redirigir según el rol
            switch ($user['role']) {
                case 'super_admin':
                case 'admin':
                case 'editor':
                    header('Location: ./admin/dashboard.php');
                    break;
                case 'technician':
                    header('Location: ./technician/visits.php');
                    break;
            }
            exit;
        }
        $error = 'Usuario o contraseña incorrectos';
    } catch(PDOException $e) {
        $error = 'Error de conexión: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Visitas</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h1 class="text-2xl font-bold mb-6 text-center">Iniciar Sesión</h1>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-700 mb-2">Usuario</label>
                <input type="text" name="username" required 
                       class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-gray-700 mb-2">Contraseña</label>
                <input type="password" name="password" required 
                       class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
            </div>
            
            <button type="submit" 
                    class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">
                Ingresar
            </button>
        </form>
    </div>
</body>
</html>