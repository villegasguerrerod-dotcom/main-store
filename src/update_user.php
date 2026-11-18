
<?php
session_start();
include('../settings/database.php');

/*if (!isset($_SESSION['user_id'])) {
    header('Location: signin.html');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}*/

$col = 'url_photo'; // <-- cambia si tu columna se llama photo_url

$id           = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$firstname    = trim($_POST['firstname'] ?? '');
$lastname     = trim($_POST['lastname'] ?? '');
$email        = trim($_POST['email'] ?? '');
$mobile_phone = trim($_POST['mobile_phone'] ?? '');

if (!$id || $firstname === '' || $lastname === '' || $email === '' || $mobile_phone === '') {
    exit('Datos incompletos.');
}

// ---------- A: actualizar datos ----------
$sql = 'UPDATE users 
    SET firstname = $1, lastname = $2, email = $3, mobile_phone = $4 
    WHERE id = $5';
$result = pg_query_params($local_conn, $sql, array($firstname, $lastname, $email, $mobile_phone, $id));

if (!$result) {
    echo "Error al actualizar los datos";
    exit;
   
}

// ---------- B: foto (opcional) ----------
if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        exit('Error al subir la foto (código ' . $_FILES['photo']['error'] . ').');
    }

    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        exit('La foto supera 2 MB.');
    }

    // Verificar el tipo real del archivo
    $mime = mime_content_type($_FILES['photo']['tmp_name']);
    $allowed = array('image/jpeg' => 'jpg', 'image/png' => 'png');

    if (!isset($allowed[$mime])) {
        echo "Solo se permiten fotos JPG o PNG. <a href='edit_user.php?userID=" . (int)$id . "'>Volver</a>";
        exit;
    }

    $dir = __DIR__ . '/photos/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename  = $id . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $photo_url = 'photos/' . $filename;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dir . $filename)) {
        exit('No se pudo guardar la foto. Revisa los permisos de la carpeta photos.');
    }

    $sql = "UPDATE users SET $col = $1 WHERE id = $2";
    $r = pg_query_params($local_conn, $sql, array($photo_url, $id));

    if (!$r) {
        exit('La foto se subió pero no se guardó en la base de datos: ' . pg_last_error($local_conn));
    }
}

// ---------- C: volver a la lista ----------
header('Location: list_users.php');
exit;