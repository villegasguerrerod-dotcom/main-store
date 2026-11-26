
<?php
session_start();

// 1. Verify active session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../signin.html');
    exit();
}

// 2. Check administrator permissions
$is_admin = ($_SESSION['user_role'] === 'administrator');

// 3. Connect to database
include('../../settings/database.php');

// 4. Get products according to user role
if ($is_admin) {

    // Administrators can see all products
    $query = "
        SELECT
            id,
            name,
            description,
            code,
            price,
            stock,
            url_photo,
            status
        FROM products
        ORDER BY id DESC
    ";

    $result = pg_query($local_conn, $query);

} else {

    // Normal users can only see active products
    $query = "
        SELECT
            id,
            name,
            description,
            code,
            price,
            stock,
            url_photo,
            status
        FROM products
        WHERE status = TRUE
        ORDER BY id DESC
    ";

    $result = pg_query($local_conn, $query);
}

if (!$result) {
    exit('Error retrieving products.');
}

// 5. Generate table headers
if ($is_admin) {

    $headers = '
        <tr>
            <th>Photo</th>
            <th>Name</th>
            <th>Code</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    ';

} else {

    $headers = '
        <tr>
            <th>Photo</th>
            <th>Name</th>
            <th>Code</th>
            <th>Price</th>
            <th>Stock</th>
        </tr>
    ';
}

// 6. Generate product rows
$rows = '';

if (pg_num_rows($result) > 0) {

    while ($row = pg_fetch_assoc($result)) {

        $id = (int)$row['id'];

        $name = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
        $code = htmlspecialchars($row['code'], ENT_QUOTES, 'UTF-8');

        $price = number_format((float)$row['price'], 2);
        $stock = (int)$row['stock'];

        // Product photo
        if (!empty($row['url_photo'])) {

            $photo_filename = basename($row['url_photo']);
            $photo_url = 'photos/' . rawurlencode($photo_filename);

            $photo = '<img src="' . htmlspecialchars($photo_url, ENT_QUOTES, 'UTF-8')
                . '" alt="' . $name . '" width="70" height="70"
                style="object-fit: cover;">';

        } else {

            $photo = 'No photo';

        }

        // Base product information
        $rows .= '
            <tr>
                <td>' . $photo . '</td>
                <td>' . $name . '</td>
                <td>' . $code . '</td>
                <td>$' . $price . '</td>
                <td>' . $stock . '</td>
        ';

        // Administrator-only information and actions
        if ($is_admin) {

            $is_active = ($row['status'] === 't');

            if ($is_active) {
                $status = '<span class="badge badge-success">Active</span>';
                $status_action = 'Disable';
                $status_icon = 'fa-ban';
            } else {
                $status = '<span class="badge badge-secondary">Inactive</span>';
                $status_action = 'Enable';
                $status_icon = 'fa-check';
            }

            $rows .= '
                <td>' . $status . '</td>

                <td class="text-center">

                    <a href="edit_product.php?id=' . $id . '"
                       class="btn btn-info btn-sm"
                       title="Edit product">
                        <i class="fas fa-pencil-alt"></i>
                    </a>

                    <form action="update_product_status.php"
                          method="POST"
                          style="display:inline;">

                        <input type="hidden" name="id" value="' . $id . '">

                        <button type="submit"
                                class="btn btn-warning btn-sm"
                                title="' . $status_action . ' product">
                            <i class="fas ' . $status_icon . '"></i>
                        </button>

                    </form>

                    <form action="delete_product.php"
                          method="POST"
                          style="display:inline;"
                          onsubmit="return confirm(\'Are you sure you want to delete this product?\');">

                        <input type="hidden" name="id" value="' . $id . '">

                        <button type="submit"
                                class="btn btn-danger btn-sm"
                                title="Delete product">
                            <i class="fas fa-trash"></i>
                        </button>

                    </form>

                </td>
            </tr>
            ';

        } else {

            $rows .= '</tr>';

        }
    }

} else {

    $colspan = $is_admin ? 7 : 5;

    $rows = '
        <tr>
            <td colspan="' . $colspan . '" class="text-center">
                No products registered.
            </td>
        </tr>
    ';
}

// 7. Load HTML
$html = file_get_contents(__DIR__ . '/list_products.html');

if ($html === false) {
    exit('Could not load list_products.html');
}

// 8. Insert table headers and products
$html = str_replace('{{PRODUCT_HEADERS}}', $headers, $html);
$html = str_replace('{{PRODUCT_ROWS}}', $rows, $html);

// 9. Show Create Product button only to administrators
$create_product_button = '';

if ($is_admin) {

    $create_product_button = '
        <a href="create_product.php" class="btn btn-success btn-sm">
            <i class="fas fa-plus"></i>
            Create Product
        </a>
    ';

}

$html = str_replace(
    '{{CREATE_PRODUCT_BUTTON}}',
    $create_product_button,
    $html
);

echo $html;
exit();

?>