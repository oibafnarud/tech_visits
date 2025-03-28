<?php

if (!isset($page_title)) {
    $page_title = 'Sistema de Visitas';
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-40">
        <div class="flex flex-col h-full">
            <!-- Logo -->
            <div class="p-4 border-b">
                <img src="/assets/images/logo.png" alt="Logo" class="h-8">
            </div>

            <!-- Menú Principal -->
            <nav class="flex-1 px-4 py-6">
                <div class="space-y-2">
                    <a href="/admin/dashboard.php" class="flex items-center px-4 py-3 rounded-lg <?php echo $current_page === 'dashboard' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-100'; ?>">
                        <i class="fas fa-home w-6"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="/admin/visits" class="flex items-center px-4 py-3 rounded-lg <?php echo $current_page === 'visits' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-100'; ?>">
                        <i class="fas fa-calendar w-6"></i>
                        <span>Visitas</span>
                    </a>

                    <a href="/admin/technicians.php" class="flex items-center px-4 py-3 rounded-lg <?php echo $current_page === 'technicians' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-100'; ?>">
                        <i class="fas fa-users w-6"></i>
                        <span>Técnicos</span>
                    </a>
                    
                    <a href="/admin/availability.php" 
                       class="flex items-center px-4 py-3 rounded-lg <?php echo $current_page === 'availability' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-100'; ?>">
                        <i class="fas fa-clock w-6"></i>
                        <span>Disponibilidad</span>
                    </a>

                    <a href="/admin/reports.php" class="flex items-center px-4 py-3 rounded-lg <?php echo $current_page === 'reports' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-100'; ?>">
                        <i class="fas fa-chart-bar w-6"></i>
                        <span>Reportes</span>
                    </a>

                    <a href="/admin/settings.php" class="flex items-center px-4 py-3 rounded-lg <?php echo $current_page === 'settings' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-100'; ?>">
                        <i class="fas fa-cog w-6"></i>
                        <span>Configuración</span>
                    </a>
                </div>
            </nav>

            <!-- Usuario -->
            <div class="border-t p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                        <p class="text-sm text-gray-500">Admin</p>
                    </div>
                <div class="flex items-center space-x-4">
                    <?php
                    if (file_exists(__DIR__ . '/notification_bell.php')) {
                        require_once __DIR__ . '/Notification.php';  // Agregar esta línea
                        include __DIR__ . '/notification_bell.php';
                    }
                    ?>
                    <a href="/logout.php" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Contenido principal -->
    <main class="lg:ml-64 min-h-screen">
        <!-- Barra superior -->
        <div class="bg-white shadow-sm">
            <div class="flex items-center justify-between p-4">
                <button class="lg:hidden" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="text-xl font-bold"><?php echo $page_title; ?></h1>
                <div class="flex items-center space-x-4">
                    <!-- Acciones específicas de la página -->
                </div>
            </div>
        </div>

        <div class="p-6">
            <?php echo $content ?? ''; ?>
        </div>
    </main>

    <script>
    function toggleSidebar() {
        const sidebar = document.querySelector('aside');
        sidebar.classList.toggle('-translate-x-full');
    }
    </script>
</body>
</html>