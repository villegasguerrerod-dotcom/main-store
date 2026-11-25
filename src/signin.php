<?php
// Get database connection
include('../settings/database.php');

$email = $_POST['email'];
$passwd = $_POST['pswd'];

// Check email and get user information
$sql_check_email = "SELECT email, password, role FROM users WHERE email = $1";
$res = pg_query_params($local_conn, $sql_check_email, array($email));

if ($res && pg_num_rows($res) > 0) {

    $user = pg_fetch_assoc($res);

    $passwd_db = $user['password'];
    $passwd_valid = password_verify($passwd, $passwd_db);

} else {
    $passwd_valid = false;
}

if ($res && pg_num_rows($res) > 0) {

    if ($passwd_valid == true) {

        session_start();

        $_SESSION['user_id'] = $user['email'];

        // NEW: Save user role in session
        $_SESSION['user_role'] = $user['role'];

        header('Location: home.php');
        exit;

    } else {
        echo "<script>alert('Incorrect password. Try again !!!'); window.location.href = 'signin.html';</script>";
        exit;
    }

} else {
    echo "<script>alert('User email does not exist. Try again !!!'); window.location.href = 'signin.html';</script>";
    exit;
}
?>