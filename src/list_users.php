<?php
/*
use this URL to print data in browser
http://localhost/main-store/src/list_users.php
*/

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: signin.html');
    exit();
}

// Only administrators can access the users list
if ($_SESSION['user_role'] !== 'administrator') {
    header('Location: home.php');
    exit();
}

include('../settings/database.php');

// Filter received from the select (all | active | inactive)
$filter = $_GET['filter'] ?? 'all';

$where = '';

if ($filter == 'active') {
    $where = 'where status = true';
} elseif ($filter == 'inactive') {
    $where = 'where status = false';
}

// Get data from users table
$sql = "
    select
        id,
        firstname || ' ' || lastname as fulllname,
        email,
        mobile_phone,
        status,
        case when status = true then 'Active' else 'Inactive' end as status,
        url_photo
    from
        users
    $where
";

$res = pg_query($local_conn, $sql);


// GENERAR LAS FILAS DE LA TABLA HTML

$rows = '';

while ($row = pg_fetch_assoc($res)) {

    $photo = htmlspecialchars($row['url_photo'] ?? '', ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars($row['fulllname'] ?? '', ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($row['mobile_phone'] ?? '', ENT_QUOTES, 'UTF-8');

    $id = (int)$row['id'];
    $status = $row['status'];

    $badge = $status == 'Active'
        ? '<span class="badge badge-success">Active</span>'
        : '<span class="badge badge-secondary">Inactive</span>';

    $icon = $status == 'Active' ? 'disable.png' : 'enable.png';

    $rows .= "
    <tr>
        <td><img src='$photo' width='40' alt='User photo'></td>
        <td>$name</td>
        <td>$email</td>
        <td>$phone</td>
        <td>$badge</td>
        <td>
            <a href='edit_user.php?userID=$id'>
                <img src='icons/edit.png' width='20' alt='Edit'>
            </a>

            <a href='#'>
                <img src='icons/search.png' width='20' alt='Search'>
            </a>

            <form action=\"delete_user.php\" method=\"POST\" style=\"display:inline;\"
                onsubmit=\"return confirm('¿Está seguro de eliminar este usuario?');\">

                <input type=\"hidden\" name=\"userID\" value=\"$id\">

                <button type=\"submit\" style=\"border:none; background:none; padding:0; cursor:pointer;\">
                    <img src=\"icons/delete.png\" width=\"20\" alt=\"Delete\">
                </button>

            </form>

            <a href='update_user_status.php?userID=$id&userStatus=" . urlencode($status) . "'>
                <img src='icons/$icon' width='20' alt='Change status'>
            </a>
        </td>
    </tr>";
}

if ($rows == '') {
    $rows = '<tr><td colspan="6" class="text-center">No users found.</td></tr>';
}


// CARGAR EL ARCHIVO HTML

$html = file_get_contents(__DIR__ . '/list_users.html');

if ($html === false) {
    die('Could not load list_users.html');
}


// ENVIAR LOS DATOS AL HTML

$html = str_replace('{{USER_ROWS}}', $rows, $html);

$html = str_replace(
    'value="all"',
    'value="all"' . ($filter == 'all' ? ' selected' : ''),
    $html
);

$html = str_replace(
    'value="active"',
    'value="active"' . ($filter == 'active' ? ' selected' : ''),
    $html
);

$html = str_replace(
    'value="inactive"',
    'value="inactive"' . ($filter == 'inactive' ? ' selected' : ''),
    $html
);


// MOSTRAR EL HTML

echo $html;
exit;

?>