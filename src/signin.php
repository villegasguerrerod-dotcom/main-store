

<?php
// Get database connection
include('../settings/database.php');

// Get payload from frontend
$email = $_POST['email'];
$passwd = $_POST['pswd'];


// verificar si el email existe en la database
$sql_check_email = "SELECT email FROM users WHERE email = '$email'";

$res = pg_query($local_conn, $sql_check_email);

$total = pg_num_rows($res);


// verificar si el password del formulario es igual al de la database
$sql_get_password = "SELECT password FROM users WHERE email = '$email'";

$res = pg_query($local_conn, $sql_get_password);

if (pg_num_rows($res) > 0) {

    $row2 = pg_fetch_assoc($res);

    $passwd_db = $row2['password'];

    $passwd = password_verify($passwd, $passwd_db);

} else {

    $passwd = 0;
}


// verificar usuario y contraseña
if ($total > 0) {

    if ($passwd == true) {

        session_start();

        $_SESSION['user_id'] = $email;

        header('Location: home.php');
        exit;

    } else {

        echo "<script>
            alert('Incorrect password. Try again !!!');
            window.location.href = 'signin.html';
        </script>";
        exit;
    }

} else {

    echo "<script>
        alert('User email does not exist. Try again !!!');
        window.location.href = 'signin.html';
    </script>";
    exit;
}

?>
