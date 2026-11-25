
<?php
session_start();

// 1. Verify active session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: signin.html');
    exit();
}

// 2. Only administrators can delete users
if ($_SESSION['user_role'] !== 'administrator') {
    header('Location: home.php');
    exit();
}

// 3. Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Método no permitido.');
}

// 4. Database connection
include('../settings/database.php');

// 5. Receive and validate user ID
$user_id = filter_input(INPUT_GET, 'userID', FILTER_VALIDATE_INT);

if (!$user_id) {
    exit('Identificador de usuario no válido.');
}

// 6. Prepare and execute deletion
$sql_delete_user = 'DELETE FROM users WHERE id = $1';

$res = pg_query_params(
    $local_conn,
    $sql_delete_user,
    array($user_id)
);

// 7. Show result
if ($res && pg_affected_rows($res) === 1) {
    echo "<script>
        alert('User has been deleted successfully !!!');
        window.location.href = 'list_users.php';
    </script>";
    exit();
} else {
    echo "<script>
        alert('User has not been deleted successfully !!!');
        window.location.href = 'list_users.php';
    </script>";
    exit();
}
?>
