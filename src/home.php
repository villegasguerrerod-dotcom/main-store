
<?php
session_start();

// 1. Verify active session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: signin.html');
    exit();
}

// 2. Validate user role
if (!in_array($_SESSION['user_role'], array('administrator', 'user'), true)) {
    session_destroy();
    header('Location: signin.html');
    exit();
}

// 3. Load home HTML
$html = file_get_contents(__DIR__ . '/home.html');

if ($html === false) {
    exit('Could not load home.html');
}

// 4. Show List Users only to administrators
$list_users_option = '';

if ($_SESSION['user_role'] === 'administrator') {
    $list_users_option = '
        <a href="list_users.php" class="menu-link">
            <i class="fas fa-fw fa-users"></i>
            List Users
        </a>
    ';
}

// 5. Replace HTML placeholder
// 5. Prepare Users card only for administrators
$users_card = '';

if ($_SESSION['user_role'] === 'administrator') {
    $users_card = '
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="option-card">
                <i class="fas fa-users"></i>

                <h5 class="font-weight-bold">
                    Users
                </h5>

                <p class="text-gray-600">
                    View the registered users.
                </p>

                <a href="list_users.php">Access</a>
            </div>
        </div>
    ';
}

// 6. Replace HTML placeholders
$html = str_replace(
    '{{LIST_USERS_OPTION}}',
    $list_users_option,
    $html
);

$html = str_replace(
    '{{USERS_CARD}}',
    $users_card,
    $html
);

// 6. Display home page
echo $html;
exit();
?>
