<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Visitas Técnicas</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body class="bg-gray-100">
<nav class="bg-blue-600 text-white p-4 fixed w-full top-0 z-50">
    <div class="container mx-auto">
        <div class="flex justify-between items-center">
            <!-- Logo y Título -->
            <div class="flex items-center space-x-4">
                <h1 class="text-xl font-bold">Sistema de Visitas</h1>
            </div>

            <!-- Opciones de Vista -->
            <div class="flex items-center space-x-4">
                <div class="border-r pr-4">
                    <select id="viewMode" class="bg-blue-700 text-white px-3 py-1 rounded border border-blue-400">
                        <option value="day">Vista Diaria</option>
                        <option value="week">Vista Semanal</option>
                        <option value="month">Vista Mensual</option>
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <button onclick="goToDate('today')" class="px-3 py-1 bg-blue-700 rounded hover:bg-blue-800">
                        Hoy
                    </button>
                    <input type="date" id="dateSelector" class="bg-blue-700 text-white px-3 py-1 rounded border border-blue-400">
                </div>

                <!-- Perfil y Logout -->
                <div class="relative group">
                    <button class="flex items-center space-x-2 focus:outline-none">
                        <span class="hidden md:inline"><?php echo $_SESSION['full_name']; ?></span>
                        <i class="fas fa-user-circle text-xl"></i>
                    </button>
                    <div class="flex items-center space-x-4">
                        <?php
                        if (file_exists(__DIR__ . '/notification_bell.php')) {
                            require_once __DIR__ . '/Notification.php';  // Agregar esta línea
                            include __DIR__ . '/notification_bell.php';
                        }
                        ?>
                        <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <a href="../logout.php" class="hover:underline">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>