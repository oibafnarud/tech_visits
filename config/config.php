<?php
// Configuración de zona horaria
date_default_timezone_set('America/Caracas');

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'saedigro_techvisits');
define('DB_USER', '');  // Cambiar por tu usuario
define('DB_PASS', '');      // Cambiar por tu contraseña

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Visitas Técnicas');
define('APP_URL', 'https://tecnicos.saedigroup.com');
define('DEBUG_MODE', true); // Cambiar a false en producción

// Configuración de errores
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Funciones helpers
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function isValidDate($date) {
    return (bool)strtotime($date);
}

// Variables globales para estilos
define('STYLES', [
    'primary' => [
        'bg' => 'bg-blue-600',
        'hover' => 'hover:bg-blue-700',
        'text' => 'text-blue-600',
        'border' => 'border-blue-600',
    ],
    'success' => [
        'bg' => 'bg-green-500',
        'hover' => 'hover:bg-green-600',
        'text' => 'text-green-600',
        'border' => 'border-green-500',
    ],
    'warning' => [
        'bg' => 'bg-yellow-500',
        'hover' => 'hover:bg-yellow-600',
        'text' => 'text-yellow-600',
        'border' => 'border-yellow-500',
    ],
    'danger' => [
        'bg' => 'bg-red-500',
        'hover' => 'hover:bg-red-600',
        'text' => 'text-red-600',
        'border' => 'border-red-500',
    ]
]);

// Función para sanitizar entradas
function sanitize($input) {
    if (is_array($input)) {
        foreach($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
    } else {
        $input = trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
    return $input;
}

// Función para validar acceso
function checkAccess($requiredRole) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}