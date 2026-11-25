
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

// 5. Receive form data
$id           = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$firstname    = trim($_POST['firstname'] ?? '');
$lastname     = trim($_POST['lastname'] ?? '');
$email        = trim($_POST['email'] ?? '');
$mobile_phone = trim($_POST['mobile_phone'] ?? '');

if (!$id || $firstname === '' || $lastname === '' ||
    $email === '' || $mobile_phone === '') {
    exit('Datos incompletos.');
}

// 6. Update user information
$sql = 'UPDATE users
        SET firstname = $1,
            lastname = $2,
            email = $3,
            mobile_phone = $4
        WHERE id = $5';

$result = pg_query_params(
    $local_conn,
    $sql,
    array($firstname, $lastname, $email, $mobile_phone, $id)
);

if (!$result || pg_affected_rows($result) !== 1) {
    exit('Error al actualizar los datos del usuario.');
}

// 7. Process profile photo only if one was selected
if (isset($_FILES['photo']) &&
    $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        exit('Error al subir la fotografía.');
    }

    // Maximum size: 2 MB
    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        exit('La fotografía supera los 2 MB.');
    }

    // Verify actual image type
    $mime = mime_content_type($_FILES['photo']['tmp_name']);

    $allowed = array(
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
    );

    if (!isset($allowed[$mime])) {
        exit('Solo se permiten fotografías JPG o PNG.');
    }

    // Get the updated mobile number from the database
    $sql_phone = 'SELECT mobile_phone FROM users WHERE id = $1';

    $res_phone = pg_query_params(
        $local_conn,
        $sql_phone,
        array($id)
    );

    if (!$res_phone || pg_num_rows($res_phone) === 0) {
        exit('No se encontró el usuario.');
    }

    $user_data = pg_fetch_assoc($res_phone);

    // Use only numeric characters in the filename
    $phone = preg_replace('/[^0-9]/', '', $user_data['mobile_phone']);

    if ($phone === '') {
        exit('El número de celular no es válido.');
    }

    // Check that another user does not have the same phone number
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
        exit('No se pudo verificar el número de celular.');
    }

    if (pg_num_rows($res_duplicate) > 0) {
        exit('Otro usuario tiene registrado el mismo número de celular.');
    }

    // Photos directory
    $dir = __DIR__ . '/photos/';

    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            exit('No se pudo crear la carpeta photos.');
        }
    }

    if (!is_writable($dir)) {
        exit('La carpeta photos no tiene permisos de escritura.');
    }

    // Filename = mobile number + real extension
    $filename = $phone . '.' . $allowed[$mime];

    $photo_url = 'photos/' . $filename;

    // Move photo to src/photos
    if (!move_uploaded_file(
        $_FILES['photo']['tmp_name'],
        $dir . $filename
    )) {
        exit('No se pudo guardar la fotografía en la carpeta photos.');
    }

    // Update photo path in database
    $sql_photo = 'UPDATE users SET url_photo = $1 WHERE id = $2';

    $result_photo = pg_query_params(
        $local_conn,
        $sql_photo,
        array($photo_url, $id)
    );

    if (!$result_photo) {
        exit('La fotografía se guardó, pero no se pudo actualizar la base de datos.');
    }
}

// 8. Return to users list
header('Location: list_users.php');
exit;
?>
