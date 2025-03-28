<?php
function sendNotification($userId, $title, $message, $type, $referenceType = null, $referenceId = null) {
    global $db;
    $notification = new Notification($db);
    return $notification->create($userId, $title, $message, $type, $referenceType, $referenceId);
}