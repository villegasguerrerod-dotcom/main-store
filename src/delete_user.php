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

// 3. Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

// 4. Database connection
include('../settings/database.php');

// 5. Receive and validate user ID
$user_id = filter_input(INPUT_POST, 'userID', FILTER_VALIDATE_INT);

if (!$user_id || $user_id <= 0) {
    exit('Identificador de usuario no válido.');
}

// 6. Begin transaction
$begin = pg_query($local_conn, 'BEGIN');

if (!$begin) {
    exit('No se pudo iniciar la operación.');
}

// 7. Lock users table
$lock = pg_query($local_conn, 'LOCK TABLE users IN EXCLUSIVE MODE');

if (!$lock) {
    pg_query($local_conn, 'ROLLBACK');
    exit('No se pudo bloquear la tabla de usuarios.');
}

// 8. Verify administrator
$sql_admin = '
    SELECT id, role
    FROM users
    WHERE email = $1
    FOR UPDATE
';

$res_admin = pg_query_params(
    $local_conn,
    $sql_admin,
    array($_SESSION['user_id'])
);

if (!$res_admin || pg_num_rows($res_admin) !== 1) {
    pg_query($local_conn, 'ROLLBACK');
    exit('No se pudo verificar al administrador.');
}

$admin = pg_fetch_assoc($res_admin);

// 9. Prevent self-deletion
if ((int)$admin['id'] === $user_id) {
    pg_query($local_conn, 'ROLLBACK');

    echo "<script>
        alert('No puede eliminar su propia cuenta.');
        window.location.href = 'list_users.php';
    </script>";
    exit();
}

// 10. Verify target user exists
$sql_user = '
    SELECT id, role
    FROM users
    WHERE id = $1
    FOR UPDATE
';

$res_user = pg_query_params(
    $local_conn,
    $sql_user,
    array($user_id)
);

if (!$res_user || pg_num_rows($res_user) !== 1) {
    pg_query($local_conn, 'ROLLBACK');

    echo "<script>
        alert('El usuario no existe.');
        window.location.href = 'list_users.php';
    </script>";
    exit();
}

$target_user = pg_fetch_assoc($res_user);

// 11. Prevent deleting the last administrator
if ($target_user['role'] === 'administrator') {

    $sql_count = "
        SELECT COUNT(*) AS total
        FROM users
        WHERE role = 'administrator'
    ";

    $res_count = pg_query($local_conn, $sql_count);

    if (!$res_count) {
        pg_query($local_conn, 'ROLLBACK');
        exit('No se pudo verificar la cantidad de administradores.');
    }

    $count = pg_fetch_assoc($res_count);

    if ((int)$count['total'] <= 1) {
        pg_query($local_conn, 'ROLLBACK');

        echo "<script>
            alert('No puede eliminar al último administrador.');
            window.location.href = 'list_users.php';
        </script>";
        exit();
    }
}

// 12. Delete user
$sql_delete_user = 'DELETE FROM users WHERE id = $1';

$res_delete = pg_query_params(
    $local_conn,
    $sql_delete_user,
    array($user_id)
);

if (!$res_delete || pg_affected_rows($res_delete) !== 1) {
    pg_query($local_conn, 'ROLLBACK');

    echo "<script>
        alert('No se pudo eliminar el usuario.');
        window.location.href = 'list_users.php';
    </script>";
    exit();
}

// 13. Commit transaction
$commit = pg_query($local_conn, 'COMMIT');

if (!$commit) {
    pg_query($local_conn, 'ROLLBACK');
    exit('No se pudo completar la eliminación.');
}

// 14. Show result
echo "<script>
    alert('Usuario eliminado correctamente.');
    window.location.href = 'list_users.php';
</script>";
exit();

?>