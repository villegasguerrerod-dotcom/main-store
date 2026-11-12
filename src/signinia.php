
<?php
// Get database connection
include('../settings/database.php');

// Get payload from frontend
$email = $_POST['email'];
$passwd = MD5($_POST['pswd']);

// Check email
$sql_check_email = "SELECT * FROM users WHERE email = $1";

$res = pg_query_params($local_conn, $sql_check_email, array($email));

if (!$res) {
    die("Database error: " . pg_last_error($local_conn));
}

$total = pg_num_rows($res);

if ($total > 0) {

    $user = pg_fetch_assoc($res);

    if ($user['password'] == $passwd) {
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
