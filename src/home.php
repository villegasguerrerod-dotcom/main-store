
<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: signin.html');
    exit();
}

// Validate user role
if (!in_array($_SESSION['user_role'], array('administrator', 'user'), true)) {
    session_destroy();
    header('Location: signin.html');
    exit();
}

include 'home.html';
?>