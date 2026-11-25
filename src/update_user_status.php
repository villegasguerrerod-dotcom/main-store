
<?php
session_start();

// 1. Verify active session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: signin.html');
    exit();
}

// 2. Only administrators can update user status
if ($_SESSION['user_role'] !== 'administrator') {
    header('Location: home.php');
    exit();
}

// 3. Database connection
include('../settings/database.php');

// 4. Receive and validate data
$user_id = filter_input(INPUT_GET, 'userID', FILTER_VALIDATE_INT);
$user_status = $_GET['userStatus'] ?? '';

if (!$user_id || !in_array($user_status, array('Active', 'Inactive'), true)) {
    exit('Datos no válidos.');
}

// 5. Determine new status
$new_userStatus = ($user_status === 'Active') ? 'false' : 'true';
// 6. Update user status
$sql_update_user_status = '
    UPDATE users
    SET status = $1
    WHERE id = $2
';

$res = pg_query_params(
    $local_conn,
    $sql_update_user_status,
    array($new_userStatus, $user_id)
);

// 7. Show result
if ($res && pg_affected_rows($res) === 1) {
    echo "<script>
        alert('User status has been updated successfully !!!');
        window.location.href = 'list_users.php';
    </script>";
    exit();
} else {
    echo "<script>
        alert('User status has not been updated !!!');
        window.location.href = 'list_users.php';
    </script>";
    exit();
}
?>
