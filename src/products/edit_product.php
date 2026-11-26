
<?php
session_start();

// 1. Verify active session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../signin.html');
    exit();
}

// 2. Only administrators can edit products
if ($_SESSION['user_role'] !== 'administrator') {
    header('Location: list_product.php');
    exit();
}

// 3. Connect to database
include('../../settings/database.php');

// 4. Validate product ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    exit('Invalid product ID.');
}

// 5. Retrieve product
$query = "
    SELECT id, name, description, code, price, stock, url_photo
    FROM products
    WHERE id = $1
";

$result = pg_query_params($local_conn, $query, [$id]);

if (!$result || pg_num_rows($result) !== 1) {
    exit('Product not found.');
}

$product = pg_fetch_assoc($result);

// 6. Escape values for HTML
$name = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8');
$code = htmlspecialchars($product['code'], ENT_QUOTES, 'UTF-8');
$price = htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8');
$stock = (int)$product['stock'];

// 7. Current photo
$photo_html = '';

if (!empty($product['url_photo'])) {
    $photo_filename = basename($product['url_photo']);
    $photo_url = 'photos/' . rawurlencode($photo_filename);

    $photo_html = '
        <div class="mb-3">
            <label>Current photo</label><br>
            <img src="' . htmlspecialchars($photo_url, ENT_QUOTES, 'UTF-8') . '"
                 alt="Product photo"
                 width="120"
                 height="120"
                 style="object-fit:cover;">
        </div>
    ';
}

// 8. Display form
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Main Store</title>

    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="card shadow mb-4">

        <div class="card-header py-3">
            <h5 class="m-0 font-weight-bold text-primary">
                Edit Product
            </h5>
        </div>

        <div class="card-body">

            ' . $photo_html . '

            <form action="update_product.php" method="POST" enctype="multipart/form-data">

                <input type="hidden" name="id" value="' . $id . '">

                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" class="form-control"
                           value="' . $name . '" maxlength="150" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control"
                              rows="3">' . $description . '</textarea>
                </div>

                <div class="form-group">
                    <label>Product Code</label>
                    <input type="text" name="code" class="form-control"
                           value="' . $code . '" maxlength="50" required>
                </div>

                <div class="form-group">
                    <label>Price</label>
                    <input type="number" name="price" class="form-control"
                           value="' . $price . '" min="0" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" class="form-control"
                           value="' . $stock . '" min="0" step="1" required>
                </div>

                <div class="form-group">
                    <label>New photo (optional)</label>
                    <input type="file" name="photo" class="form-control-file"
                           accept="image/jpeg,image/png">
                    <small class="form-text text-muted">
                        JPG or PNG. Maximum size: 2 MB.
                    </small>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>

                <a href="list_product.php" class="btn btn-secondary">
                    Cancel
                </a>

            </form>

        </div>
    </div>
</div>

</body>
</html>
';

echo $html;
exit();

?>