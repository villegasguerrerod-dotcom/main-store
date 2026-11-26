<?php
session_start();

// 1. Verify active session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: signin.html');
    exit();
}

// 2. Only administrators can update users
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
require('../settings/database.php');

// Handle errors and rollback active transaction
function abortTransaction($conn, $message) {
    pg_query($conn, 'ROLLBACK');
    exit($message);
}

// 5. Receive form data
$id           = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$firstname    = trim($_POST['firstname'] ?? '');
$lastname     = trim($_POST['lastname'] ?? '');
$email        = trim($_POST['email'] ?? '');
$mobile_phone = trim($_POST['mobile_phone'] ?? '');
$role         = $_POST['role'] ?? '';

// Validate received information
if (!$id || $firstname === '' || $lastname === '' ||
    $email === '' || $mobile_phone === '') {
    exit('Datos incompletos.');
}

// 6. Validate allowed roles
$allowed_roles = array('user', 'administrator');

if (!in_array($role, $allowed_roles, true)) {
    exit('El rol seleccionado no es válido.');
}

// 7. Start transaction
if (!pg_query($local_conn, 'BEGIN')) {
    exit('No se pudo iniciar la operación.');
}

// Lock users table while validating administrative permissions
if (!pg_query($local_conn, 'LOCK TABLE users IN EXCLUSIVE MODE')) {
    abortTransaction($local_conn, 'No se pudieron verificar los permisos.');
}

// 8. Verify administrator directly in database
$sql_actor = 'SELECT id, role FROM users WHERE email = $1 FOR UPDATE';

$res_actor = pg_query_params(
    $local_conn,
    $sql_actor,
    array($_SESSION['user_id'])
);

if (!$res_actor || pg_num_rows($res_actor) !== 1) {
    abortTransaction($local_conn, 'No se pudo verificar el administrador.');
}

$actor = pg_fetch_assoc($res_actor);

if ($actor['role'] !== 'administrator') {
    abortTransaction($local_conn, 'No tienes permisos para realizar esta operación.');
}

// 9. Get current role of user being edited
$sql_current = 'SELECT role FROM users WHERE id = $1';

$res_current = pg_query_params(
    $local_conn,
    $sql_current,
    array($id)
);

if (!$res_current || pg_num_rows($res_current) !== 1) {
    abortTransaction($local_conn, 'Usuario no encontrado.');
}

$current_user = pg_fetch_assoc($res_current);

// 10. Prevent removal of the last administrator
if ($current_user['role'] === 'administrator' && $role === 'user') {

    $sql_admins = "
        SELECT COUNT(*) AS total
        FROM users
        WHERE role = 'administrator'
    ";

    $res_admins = pg_query($local_conn, $sql_admins);

    if (!$res_admins) {
        abortTransaction($local_conn, 'No se pudo verificar la cantidad de administradores.');
    }

    $admin_count = pg_fetch_assoc($res_admins);

    if ((int)$admin_count['total'] <= 1) {
        abortTransaction($local_conn, 'No puedes quitar el rol del último administrador.');
    }
}

// 11. Validate duplicate mobile phone
$phone = preg_replace('/[^0-9]/', '', $mobile_phone);

if ($phone === '') {
    abortTransaction($local_conn, 'El número de celular no es válido.');
}

$sql_duplicate = "
    SELECT id
    FROM users
    WHERE regexp_replace(
        COALESCE(mobile_phone, ''),
        '[^0-9]',
        '',
        'g'
    ) = $1
    AND id <> $2
";

$res_duplicate = pg_query_params(
    $local_conn,
    $sql_duplicate,
    array($phone, $id)
);

if (!$res_duplicate) {
    abortTransaction($local_conn, 'No se pudo verificar el número de celular.');
}

if (pg_num_rows($res_duplicate) > 0) {
    abortTransaction($local_conn, 'Otro usuario tiene registrado el mismo número de celular.');
}

// 12. Update user information and role
$sql = 'UPDATE users
        SET firstname = $1,
            lastname = $2,
            email = $3,
            mobile_phone = $4,
            role = $5
        WHERE id = $6';

$result = pg_query_params(
    $local_conn,
    $sql,
    array($firstname, $lastname, $email, $mobile_phone, $role, $id)
);

if (!$result || pg_affected_rows($result) !== 1) {
    abortTransaction($local_conn, 'Error al actualizar los datos del usuario.');
}

// 13. Process profile photo only if one was selected
if (isset($_FILES['photo']) &&
    $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        abortTransaction($local_conn, 'Error al subir la fotografía.');
    }

    // Maximum size: 2 MB
    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        abortTransaction($local_conn, 'La fotografía supera los 2 MB.');
    }

    // Verify actual image type
    $mime = mime_content_type($_FILES['photo']['tmp_name']);

    $allowed = array(
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
    );

    if (!isset($allowed[$mime])) {
        abortTransaction($local_conn, 'Solo se permiten fotografías JPG o PNG.');
    }

    // Photos directory
    $dir = __DIR__ . '/photos/';

    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            abortTransaction($local_conn, 'No se pudo crear la carpeta photos.');
        }
    }

    if (!is_writable($dir)) {
        abortTransaction($local_conn, 'La carpeta photos no tiene permisos de escritura.');
    }

    // Filename = mobile number + real extension
    $filename = $phone . '.' . $allowed[$mime];

    $photo_url = 'photos/' . $filename;

    // Move photo to src/photos
    if (!move_uploaded_file(
        $_FILES['photo']['tmp_name'],
        $dir . $filename
    )) {
        abortTransaction($local_conn, 'No se pudo guardar la fotografía.');
    }

    // Update photo path in database
    $sql_photo = 'UPDATE users SET url_photo = $1 WHERE id = $2';

    $result_photo = pg_query_params(
        $local_conn,
        $sql_photo,
        array($photo_url, $id)
    );

    if (!$result_photo) {
        abortTransaction($local_conn, 'La fotografía se guardó, pero no se pudo actualizar la base de datos.');
    }
}

// 14. Commit transaction
if (!pg_query($local_conn, 'COMMIT')) {
    exit('No se pudieron guardar los cambios.');
}

// 15. Update session if administrator edited their own account
if ((string)$id === (string)$actor['id']) {
    $_SESSION['user_role'] = $role;
    $_SESSION['user_id'] = $email;
}

// 16. Return to users list
header('Location: list_users.php');
exit();
?>