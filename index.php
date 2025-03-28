<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
} else {
    // Redirigir según el rol
    if ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: technician/visits.php');
    }
    exit;
}