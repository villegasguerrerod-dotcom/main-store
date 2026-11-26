
<?php
session_start();

// 1. Verify active session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../signin.html');
    exit();
}

// 2. Only administrators can change product status
if ($_SESSION['user_role'] !== 'administrator') {
    header('Location: list_product.php');
    exit();
}

// 3. Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list_product.php');
    exit();
}

// 4. Connect to database
include('../../settings/database.php');

// 5. Validate product ID
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    exit('Invalid product ID.');
}

// 6. Change product status
$query = "
    UPDATE products
    SET
        status = NOT status,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = $1
    RETURNING status
";

$result = pg_query_params($local_conn, $query, [$id]);

if (!$result) {
    exit('Error updating product status.');
}

// 7. Verify that the product exists
if (pg_num_rows($result) !== 1) {
    exit('Product not found.');
}

// 8. Return to product list
header('Location: list_product.php');
exit();
