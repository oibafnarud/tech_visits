<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}


// Verificar permisos de admin para las páginas de administración
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
    $allowed_roles = ['super_admin', 'admin', 'editor'];
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header('Location: /technician/visits.php');
        exit;
    }
}