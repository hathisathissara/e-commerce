<?php
require_once '../includes/db_connect.php';

// Check for product_id in URL
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    header("location: view_products.php");
    exit();
}

$product_id = intval($_GET['product_id']);
$error = '';

// --- LOGIC TO ADD NEW VARIANT ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_new_variant'])) {
    $variant_name = $_POST['variant_name'];
    $variant_value = $_POST['variant_value'];
    $price = $_POST['price'];
    $stock = $_POST['stock_quantity'];
    $sku = !empty($_POST['sku']) ? $_POST['sku'] : null;

    // Image upload handling
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../assets/images/products/';
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = 'assets/images/products/' . $file_name;
        }
    }
    
    // Insert into database
    $sql_insert = "INSERT INTO product_variants (product_id, variant_name, variant_value, price, stock_quantity, sku, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql_insert)) {
        $stmt->bind_param("isssiss", $product_id, $variant_name, $variant_value, $price, $stock, $sku, $image_path);
        if (!$stmt->execute() && $conn->errno == 1062) {
             $error = "A variant with this SKU already exists.";
        }
        $stmt->close();
        // Redirect to same page to prevent form resubmission and show new data
        if(empty($error)){
            header("location: product_details.php?product_id=" . $product_id . "&status=variant_added");
            exit();
        }
    }
}


// --- FETCH PRODUCT DETAILS AND ITS VARIANTS ---
$product = null;
$variants = [];

// Fetch product name
$sql_product = "SELECT product_name FROM products WHERE product_id = ?";
if ($stmt_prod = $conn->prepare($sql_product)) {
    $stmt_prod->bind_param("i", $product_id);
    $stmt_prod->execute();
    $result_prod = $stmt_prod->get_result();
    if ($result_prod->num_rows == 1) {
        $product = $result_prod->fetch_assoc();
    } else {
        header("location: view_products.php"); // Product not found
        exit();
    }
    $stmt_prod->close();
}

// Fetch variants for this product
$sql_variants = "SELECT * FROM product_variants WHERE product_id = ? ORDER BY variant_id DESC";
if ($stmt_var = $conn->prepare($sql_variants)) {
    $stmt_var->bind_param("i", $product_id);
    $stmt_var->execute();
    $result_var = $stmt_var->get_result();
    while ($row = $result_var->fetch_assoc()) {
        $variants[] = $row;
    }
    $stmt_var->close();
}
$conn->close();

?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dynamically set title -->
    <title>Details for <?php echo htmlspecialchars($product['product_name'] ?? 'Product'); ?></title>
    <link rel="stylesheet" href="view_products_style.css"> <!-- Reusing same style -->
    <style>
        /* Styles copied from previous view_products.php and adjusted */
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1, h2 { color: #343a40; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #007BFF; text-decoration: none; }
        
        .variants-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .variants-table th, .variants-table td { text-align: left; padding: 12px; border-bottom: 1px solid #e9ecef; }
        .variants-table th { background-color: #f8f9fa; }
        .variants-table img { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; }
        .actions a { color: #fff; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 14px; margin-right: 5px; }
        .edit-btn { background-color: #ffc107; }
        .delete-btn { background-color: #dc3545; }
        
        #add-variant-form { background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); grid-gap: 15px; }
        .form-grid .form-group { display: flex; flex-direction: column; }
        label { margin-bottom: 5px; font-weight: bold; }
        input { padding: 10px; border-radius: 4px; border: 1px solid #ccc; }
        button { background-color: #28a745; color: white; padding: 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }

        .status-msg { padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid; }
        .success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .no-variants { text-align: center; padding: 30px; border: 2px dashed #ccc; border-radius: 8px; color: #6c757d; }
    </style>
</head>
<body>

<div class="container">
    <a href="view_products.php" class="back-link">← Back to All Products</a>
    <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'variant_added'): ?>
        <div class="status-msg success">New variant added successfully!</div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="status-msg error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <h2>Existing Variants</h2>
    <?php if (empty($variants)): ?>
        <div class="no-variants">This product has no variants yet. Add one below!</div>
    <?php else: ?>
        <table class="variants-table">
             <thead>
                <tr>
                    <th>Image</th>
                    <th>Variant</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($variants as $variant): ?>
                    <tr>
                        <td>
                            <img src="<?php echo !empty($variant['image']) ? '../' . $variant['image'] : 'https://via.placeholder.com/60'; ?>" alt="Variant Image">
                        </td>
                        <td><?php echo htmlspecialchars($variant['variant_value']); ?></td>
                        <td>LKR <?php echo number_format($variant['price'], 2); ?></td>
                        <td>
                            <strong style="color:<?php echo $variant['stock_quantity'] < 10 ? 'red' : 'green'; ?>">
                                <?php echo $variant['stock_quantity']; ?>
                            </strong>
                        </td>
                        <td class="actions">
                            <a href="edit_variant.php?variant_id=<?php echo $variant['variant_id']; ?>" class="edit-btn">Edit</a>
                            <a href="delete_variant.php?variant_id=<?php echo $variant['variant_id']; ?>&return_pid=<?php echo $product_id; ?>" class="delete-btn" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <hr style="margin: 40px 0;">

    <div id="add-variant-form">
        <h2>Add New Variant</h2>
        <form action="product_details.php?product_id=<?php echo $product_id; ?>" method="post" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label for="variant_name">Variant Type</label>
                    <input type="text" name="variant_name" value="Color" required>
                </div>
                 <div class="form-group">
                    <label for="variant_value">Variant Value</label>
                    <input type="text" name="variant_value" placeholder="e.g., Red" required>
                </div>
                 <div class="form-group">
                    <label for="price">Price (LKR)</label>
                    <input type="number" step="0.01" name="price" placeholder="1500.00" required>
                </div>
                 <div class="form-group">
                    <label for="stock_quantity">Stock</label>
                    <input type="number" name="stock_quantity" placeholder="20" required>
                </div>
                 <div class="form-group">
                    <label for="sku">SKU (Optional)</label>
                    <input type="text" name="sku" placeholder="TEDDY-RED">
                </div>
                 <div class="form-group">
                    <label for="image">Image</label>
                    <input type="file" name="image">
                </div>
            </div>
            <button type="submit" name="add_new_variant">Add Variant</button>
        </form>
    </div>
</div>

</body>
</html>