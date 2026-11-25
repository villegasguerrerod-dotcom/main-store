<?php
/*
use this URL to print data in browser
http://localhost/main-store/src/list_users.php*/

session_start();
if (!isset($_SESSION['user_id'])) {
    header('refresh:0;url=signin.html');
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

    //Get data from users table
    $sql="
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
    $res = pg_query ($local_conn, $sql);
    rows = '';

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

            <a href='delete_user.php?userID=$id'>
                <img src='icons/delete.png' width='20' alt='Delete'>
            </a>

            <a href='update_user_status.php?userID=$id&userStatus=" . urlencode($status) . "'>
                <img src='icons/$icon' width='20' alt='Change status'>
            </a>
        </td>
    </tr>";
}

if ($rows == '') {
    $rows = '<tr><td colspan="6" class="text-center">No users found.</td></tr>';
}

$html = file_get_contents(__DIR__ . '/list_users.html');

if ($html === false) {
    die('Could not load list_users.html');
}

$html = str_replace('{{USER_ROWS}}', $rows, $html);

$html = str_replace('value="all"', 'value="all"' . ($filter == 'all' ? ' selected' : ''), $html);
$html = str_replace('value="active"', 'value="active"' . ($filter == 'active' ? ' selected' : ''), $html);
$html = str_replace('value="inactive"', 'value="inactive"' . ($filter == 'inactive' ? ' selected' : ''), $html);

echo $html;
exit;

    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Store - List Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <form method="GET" action="list_users.php">
        <select name="filter" onchange="this.form.submit()">
            <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>List all users</option>
            <option value="active" <?= $filter == 'active' ? 'selected' : '' ?>>List active users only</option>
            <option value="inactive" <?= $filter == 'inactive' ? 'selected' : '' ?>>List inactive users only</option>
        </select>
    </form>
    <table class="table table-striped">
         <thead>
            <tr>
                <th>Photo</th> 
                <th>Fullname</th>  
                <th>Email</th>
                <th>Mobile Phone</th>
                <th>Status </th>
                <th>Options </th>
                    
            </th>
        </thead>    
        </tr>
        <?php  while ($row = pg_fetch_assoc($res)) {?> 
        <tr>
        <!-- Here print data from database -->
            <td>
                <img src=<?= $row['url_photo']?> width="40" alt="user photo profile">
            </td>
            <td><?= $row ['fulllname']?></td>
            <td><?= $row ['email']?></td>
            <td><?= $row ['mobile_phone']?></td>
            <td><?= $row ['status']?></td>
            <td>
                <a href="#">
                    <a href="edit_user.php?userID=<?= $row['id']?>">
                    <img src="icons/edit.png" width="20">
                </a> 
                <a href="#">
                    <img src="icons/search.png" width="20">
                </a>
                <a href="#">
                    <a href="delete_user.php?userID=<?= $row['id']?>">
                    <img src="icons/delete.png" width="20">
                </a> 
                <a href="update_user_status.php?userID=<?= $row['id'] ?>&userStatus=<?= $row['status'] ?>">
                    <?php
                        if($row['status'] == 'Active'){
                            echo "<img src='icons/disable.png' alt='Inactive user' width='20'>";
                        }else{
                            echo "<img src='icons/enable.png' alt='Active user' width='20'>";
                        }
                    ?>
                </a>
            </td>
        </tr>
         <?php }?>
    </table>
    </body>
    <a href=home.php>Go to home</a>
</html>