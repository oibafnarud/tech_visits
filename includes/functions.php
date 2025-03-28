<?php
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotAuthenticated() {
    if (!isAuthenticated()) {
        header('Location: /login.php');
        exit;
    }
}

function redirectIfNotAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: /technician/visits.php');
        exit;
    }
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}