
<?php
session_start();

// 1. Verify active session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../signin.html');
    exit();
}

// 2. Only administrators can delete products
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

// 6. Get product photo before deletion
$query = "
    SELECT url_photo
    FROM products
    WHERE id = $1
";

$result = pg_query_params($local_conn, $query, [$id]);

if (!$result) {
    exit('Error retrieving product.');
}

if (pg_num_rows($result) !== 1) {
    exit('Product not found.');
}

$product = pg_fetch_assoc($result);
$photo = $product['url_photo'];

// 7. Delete product
$delete_query = "
    DELETE FROM products
    WHERE id = $1
";

$delete_result = pg_query_params(
    $local_conn,
    $delete_query,
    [$id]
);

if (!$delete_result) {
    exit('Error deleting product.');
}

// 8. Delete associated photo after successful deletion
if (!empty($photo)) {

    $photo_filename = basename($photo);
    $photo_path = __DIR__ . '/photos/' . $photo_filename;

    if (is_file($photo_path)) {
        unlink($photo_path);
    }
}

// 9. Return to product list
header('Location: list_product.php');
exit();
