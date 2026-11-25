<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.html');
    exit();
}

function requireRole(array $allowedRoles): void {
    $currentRole = $_SESSION['role'] ?? 'user';
    if (!in_array($currentRole, $allowedRoles, true)) {
        header('Location: home.php');
        exit();
    }
}