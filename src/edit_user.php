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
$user = null;

if ($id) {
    $sql_edit_user = 'SELECT * FROM users WHERE id = $1';
    $res = pg_query_params($local_conn, $sql_edit_user, array($id));

    if ($res) {
        $user = pg_fetch_assoc($res);
    }
}

if (!$user) {
    die('Usuario no encontrado.');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar usuario | MAIN STORE</title>

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fc;
            color: #5a5c69;
        }

        .container {
            max-width: 850px;
            margin: 45px auto;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 22px 30px;
            background: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .card-header h2 {
            margin: 0;
            color: #4e73df;
            font-size: 21px;
            font-weight: 800;
        }

        .card-body {
            padding: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d3e2;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            color: #6e707e;
            outline: none;
        }

        input:focus {
            border-color: #bac8f5;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.12);
        }

        .photo-section {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e3e6f0;
        }

        .current-photo {
            display: block;
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid #eaecf4;
            margin: 10px 0 18px;
        }

        input[type="file"] {
            margin-top: 8px;
            font-family: inherit;
            font-size: 13px;
        }

        .buttons {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e3e6f0;
        }

        .btn {
            padding: 11px 22px;
            border: none;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-save {
            background: #4e73df;
            color: white;
        }

        .btn-cancel {
            background: #858796;
            color: white;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .card-body {
                padding: 20px;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                text-align: center;
            }
        }
    </style>
</head>

<body>

<div class="container">
    <div class="card">

        <div class="card-header">
            <h2>Editar usuario</h2>
        </div>

        <div class="card-body">

            <form action="update_user.php" method="post" enctype="multipart/form-data">

                <input type="hidden" name="id"
                    value="<?php echo htmlspecialchars($user['id']); ?>">

                <div class="form-grid">

                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="firstname"
                            value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Apellido</label>
                        <input type="text" name="lastname"
                            value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Correo electrónico</label>
                        <input type="email" name="email"
                            value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Celular</label>
                        <input type="text" name="mobile_phone"
                            value="<?php echo htmlspecialchars($user['mobile_phone'] ?? ''); ?>" required>
                    </div>

               
                </div>
                
                <!-- User role -->
                <div class="form-group" style="margin-top: 20px;">

                    <label for="role">Rol del usuario</label>

                    <select name="role" id="role" required
                        style="width: 100%; padding: 12px 14px;
                        border: 1px solid #d1d3e2; border-radius: 6px;
                        font-family: inherit; font-size: 14px;
                        color: #6e707e;">

                        <option value="user"
                            <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>
                            Usuario
                        </option>

                        <option value="administrator"
                            <?php echo $user['role'] === 'administrator' ? 'selected' : ''; ?>>
                            Administrador
                        </option>

                    </select>

                </div>

                <div class="photo-section">


                    <label>Fotografía del usuario</label>

                    <?php if (!empty($user['url_photo'])) { ?>

                        <p>Fotografía actual:</p>

                        <img class="current-photo"
                            src="<?php echo htmlspecialchars($user['url_photo']); ?>"
                            alt="Fotografía actual del usuario">

                    <?php } ?>

                    <label>Nueva fotografía (JPG o PNG)</label>
                    <input type="file" name="photo" accept=".jpg,.jpeg,.png">

                </div>

                <div class="buttons">

                    <a href="list_users.php" class="btn btn-cancel">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-save">
                        Guardar cambios
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

</body>
</html>