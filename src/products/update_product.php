
<?php
session_start();

// 1. Verify active session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../signin.html');
    exit();
}

// 2. Only administrators can update products
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

// 6. Get and validate form data
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$code = trim($_POST['code'] ?? '');
$price = trim($_POST['price'] ?? '');
$stock = trim($_POST['stock'] ?? '');

if ($name === '' || $code === '' || $price === '' || $stock === '') {
    exit('All required fields must be completed.');
}

if (!is_numeric($price) || (float)$price < 0) {
    exit('Invalid product price.');
}

if (!preg_match('/^\d+$/', $stock)) {
    exit('Invalid product stock.');
}

$price = (float)$price;
$stock = (int)$stock;

// 7. Get current product
$current_query = "
    SELECT id, url_photo
    FROM products
    WHERE id = $1
";

$current_result = pg_query_params($local_conn, $current_query, [$id]);

if (!$current_result || pg_num_rows($current_result) !== 1) {
    exit('Product not found.');
}

$current_product = pg_fetch_assoc($current_result);
$old_photo = $current_product['url_photo'];

// 8. Check duplicate product code
$duplicate_query = "
    SELECT id
    FROM products
    WHERE code = $1 AND id <> $2
";

$duplicate_result = pg_query_params(
    $local_conn,
    $duplicate_query,
    [$code, $id]
);

if (!$duplicate_result) {
    exit('Error validating product code.');
}

if (pg_num_rows($duplicate_result) > 0) {
    exit('This product code is already in use.');
}

// 9. Validate new photo if provided
$new_photo = $old_photo;
$new_photo_path = null;
$photo_uploaded = false;

if (
    isset($_FILES['photo']) &&
    $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
) {
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        exit('Error uploading the photo.');
    }

    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        exit('The photo must not exceed 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['photo']['tmp_name']);

    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
    ];

    if (!isset($allowed_types[$mime])) {
        exit('Only JPG and PNG photos are allowed.');
    }

    $photo_filename = bin2hex(random_bytes(16))
        . '.' . $allowed_types[$mime];

    $new_photo = 'products/photos/' . $photo_filename;
    $new_photo_path = __DIR__ . '/photos/' . $photo_filename;

    $photo_uploaded = true;
}

// 10. Start transaction
if (!pg_query($local_conn, 'BEGIN')) {
    exit('Could not start database transaction.');
}

try {

    // 11. Update product
    $update_query = "
        UPDATE products
        SET
            name = $1,
            description = $2,
            code = $3,
            price = $4,
            stock = $5,
            url_photo = $6,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = $7
    ";

    $update_result = pg_query_params(
        $local_conn,
        $update_query,
        [
            $name,
            $description,
            $code,
            $price,
            $stock,
            $new_photo,
            $id
        ]
    );

    if (!$update_result) {
        throw new Exception('Error updating product.');
    }

    // 12. Move new photo
    if ($photo_uploaded) {
        if (!move_uploaded_file(
            $_FILES['photo']['tmp_name'],
            $new_photo_path
        )) {
            throw new Exception('Could not save the new photo.');
        }
    }

    // 13. Commit transaction
    if (!pg_query($local_conn, 'COMMIT')) {
        throw new Exception('Could not save changes.');
    }

    // 14. Delete old photo after successful update
    if ($photo_uploaded && !empty($old_photo)) {
        $old_filename = basename($old_photo);
        $old_photo_path = __DIR__ . '/photos/' . $old_filename;

        if (is_file($old_photo_path)) {
            unlink($old_photo_path);
        }
    }

    echo "
        <script>
            alert('Product updated successfully.');
            window.location.href = 'list_product.php';
        </script>
    ";

} catch (Throwable $e) {

    pg_query($local_conn, 'ROLLBACK');

    // Remove new photo if the transaction failed
    if ($photo_uploaded && $new_photo_path && is_file($new_photo_path)) {
        unlink($new_photo_path);
    }

    exit('Error updating product: ' . htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    ));
}
