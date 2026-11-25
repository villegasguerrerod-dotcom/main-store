<?php
session_start();

// Verify that the user has an active session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: signin.html');
    exit();
}

// Only administrators can edit users
if ($_SESSION['user_role'] !== 'administrator') {
    header('Location: home.php');
    exit();
}

include('../settings/database.php');

$id = $_GET['userID'] ?? null;

if ($id) {
    $sql_edit_user = 'SELECT * FROM users WHERE id = $1';
    
    $res = pg_query_params($local_conn, $sql_edit_user, array($id));
    
    if ($res) {
        $user = pg_fetch_assoc($res);
    }
}

// Validar si el usuario existe para evitar errores en el HTML
if (!$user) {
    die('Usuario no encontrado.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar usuario</title>
</head>
<body>
    <h3 style="margin:0 0 4px">Editar usuario</h3>

    <form action="update_user.php" method="post" enctype="multipart/form-data">

        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

        <label>Nombre</label><br>
        <input type="text" name="firstname" value="<?php echo $user['firstname']; ?>" required><br><br>

        <label>Apellido</label><br>
        <input type="text" name="lastname" value="<?php echo $user['lastname']; ?>" required><br><br>

        <label>Correo</label><br>
        <input type="email" name="email" value="<?php echo $user['email']; ?>" required><br><br>

        <label>Celular</label><br>
        <input type="text" name="mobile_phone" value="<?php echo $user['mobile_phone']; ?>" required><br><br>

        <?php if (!empty($user['photo_url'])) { ?>
            <p>Foto actual:</p>
            <img src="<?php echo $user['photo_url']; ?>" width="120"><br><br>
        <?php } ?>

        <label>Foto (JPG o PNG)</label><br>
        <input type="file" name="photo" accept=".jpg,.jpeg,.png"><br><br>

        <button type="submit">Guardar</button>
        <a href="list_users.php">Cancelar</a>
    </form>
</body>
</html>