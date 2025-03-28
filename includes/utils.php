
<?php
function getStatusClass($status) {
    switch($status) {
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'in_route':
            return 'bg-yellow-100 text-yellow-800';
        case 'pending':
            return 'bg-blue-100 text-blue-800';
        case 'not_available':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function getStatusLabel($status) {
    switch($status) {
        case 'completed':
            return 'Completada';
        case 'in_route':
            return 'En Camino';
        case 'pending':
            return 'Pendiente';
        case 'not_available':
            return 'No Disponible';
        default:
            return ucfirst($status);
    }
}

function formatDate($date) {
    if (!$date) return '';
    return date('d/m/Y', strtotime($date));
}

function formatTime($time) {
    if (!$time) return '';
    return date('h:i A', strtotime($time));
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return "Hace un momento";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "Hace $mins minuto" . ($mins != 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Hace $hours hora" . ($hours != 1 ? 's' : '');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "Hace $days día" . ($days != 1 ? 's' : '');
    } else {
        return date('d/m/Y H:i', $time);
    }
}

?>