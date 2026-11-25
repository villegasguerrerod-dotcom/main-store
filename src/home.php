<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('refresh:0;url=signin.html');
    exit();
}
include 'home.html';
?>

