
<?php
session_start();

// 1. Verify active session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../signin.html');
    exit();
}

// 2. Only administrators can create products
if ($_SESSION['user_role'] !== 'administrator') {
    header('Location: ../home.php');
    exit();
}

// 3. Database connection
include('../../settings/database.php');

// 4. Only allow POST requests for registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 5. Receive form data
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $status = $_POST['status'] ?? '';

    // 6. Validate required fields
    if (
        $name === '' ||
        $code === '' ||
        !is_numeric($price) ||
        !is_numeric($stock) ||
        !in_array($status, ['true', 'false'], true)
    ) {
        exit('Invalid product information.');
    }

    $price_value = (float)$price;
    $stock_value = filter_var($stock, FILTER_VALIDATE_INT);

    if (
        $price_value < 0 ||
        $stock_value === false ||
        $stock_value < 0
    ) {
        exit('Invalid price or stock.');
    }

    // 7. Verify duplicate SKU
    $sql_check = 'SELECT id FROM products WHERE code = $1';

    $res_check = pg_query_params(
        $local_conn,
        $sql_check,
        array($code)
    );

    if (!$res_check) {
        exit('Could not verify product code.');
    }

    if (pg_num_rows($res_check) > 0) {
        exit('This product code already exists.');
    }

    // 8. Prepare photo
    $photo_path = null;
    $photo_filename = null;

    if (
        isset($_FILES['photo']) &&
        $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            exit('Photo upload failed.');
        }

        if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            exit('Photo exceeds the 2 MB limit.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['photo']['tmp_name']);

        $allowed_types = array(
            'image/jpeg' => 'jpg',
            'image/png' => 'png'
        );

        if (!isset($allowed_types[$mime])) {
            exit('Only JPG and PNG images are allowed.');
        }

        $photo_dir = __DIR__ . '/photos/';

        if (!is_dir($photo_dir)) {
            exit('Photo directory does not exist.');
        }

        $photo_filename = bin2hex(random_bytes(16))
            . '.' . $allowed_types[$mime];

        $photo_path = $photo_dir . $photo_filename;
    }

    // 9. Begin transaction
    $begin = pg_query($local_conn, 'BEGIN');

    if (!$begin) {
        exit('Could not start transaction.');
    }

    // 10. Insert product
    $sql_insert = '
        INSERT INTO products (
            name,
            description,
            code,
            price,
            stock,
            url_photo,
            status
        )
        VALUES ($1, $2, $3, $4, $5, $6, $7)
    ';

    $url_photo = $photo_filename !== null
        ? 'products/photos/' . $photo_filename
        : null;

    $res_insert = pg_query_params(
        $local_conn,
        $sql_insert,
        array(
            $name,
            $description,
            $code,
            $price_value,
            $stock_value,
            $url_photo,
            $status
        )
    );

    if (!$res_insert) {
        pg_query($local_conn, 'ROLLBACK');
        exit('Could not save product.');
    }

    // 11. Move photo
    if ($photo_filename !== null) {

        if (!move_uploaded_file(
            $_FILES['photo']['tmp_name'],
            $photo_path
        )) {
            pg_query($local_conn, 'ROLLBACK');
            exit('Could not save product photo.');
        }
    }

    // 12. Commit transaction
    $commit = pg_query($local_conn, 'COMMIT');

    if (!$commit) {
        pg_query($local_conn, 'ROLLBACK');

        if ($photo_path !== null && file_exists($photo_path)) {
            unlink($photo_path);
        }

        exit('Could not complete product registration.');
    }

    // 13. Show result
    echo "<script>
    alert('Product created successfully.');
    window.location.href = 'list_product.php';
    </script>";
    exit();
}
// 14. Load HTML form
$html = file_get_contents(__DIR__ . '/create_product.html');

if ($html === false) {
    exit('Could not load create_product.html');
}

echo $html;
exit();

?>
