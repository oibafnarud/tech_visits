// File: /includes/error_page.php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Sistema de Visitas</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
        <div class="text-red-500 mb-4">
            <i class="fas fa-exclamation-circle text-6xl"></i>
        </div>
        <h1 class="text-2xl font-bold mb-4">Ha ocurrido un error</h1>
        <p class="text-gray-600 mb-6">
            Lo sentimos, ha ocurrido un error mientras procesábamos su solicitud.
            Por favor, inténtelo nuevamente más tarde.
        </p>
        <div class="space-y-4">
            <button onclick="history.back()" 
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </button>
            <a href="/admin/dashboard.php" 
               class="block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <i class="fas fa-home mr-2"></i>Ir al Dashboard
            </a>
        </div>
    </div>
</body>
</html>