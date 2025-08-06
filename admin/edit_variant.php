<?php
// Include database connection
require_once '../includes/db_connect.php';

// Check if a variant_id is passed in the URL, otherwise redirect
if (!isset($_GET['variant_id']) || empty($_GET['variant_id'])) {
    header("location: view_products.php");
    exit();
}

$variant_id = intval($_GET['variant_id']);
$message = '';
$error = '';
$variant = null;

// --- HANDLE FORM SUBMISSION (UPDATE LOGIC) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_variant'])) {

    // Get form data
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $sku = !empty($_POST['sku']) ? $_POST['sku'] : null;
    $old_image_path = $_POST['old_image_path']; // Hidden input to keep track of the old image

    // --- Handle New Image Upload ---
    $image_to_update = $old_image_path; // Default to old image path
    if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0) {
        $upload_dir = '../assets/images/products/';
        
        // Delete the old image file if it exists and is not a placeholder
        if (!empty($old_image_path) && file_exists('../' . $old_image_path)) {
            unlink('../' . $old_image_path);
        }

        // Upload the new image
        $file_name = time() . '_' . basename($_FILES['new_image']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['new_image']['tmp_name'], $target_file)) {
            $image_to_update = 'assets/images/products/' . $file_name;
        } else {
            $error = "Sorry, there was an error uploading your new image.";
        }
    }

    if (empty($error)) {
        // Prepare an update statement
        // Note: We don't usually allow changing variant_value (like "Red" to "Blue") as it can get complex.
        // We only allow updating price, stock, sku, and image.
        $sql = "UPDATE product_variants SET price = ?, stock_quantity = ?, sku = ?, image = ? WHERE variant_id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("dissi", $price, $stock_quantity, $sku, $image_to_update, $variant_id);
            
            if ($stmt->execute()) {
                // On success, redirect back to the view page
                header("location: view_products.php?status=updated");
                exit();
            } else {
                // Handle potential duplicate SKU error gracefully
                if ($conn->errno == 1062) { // 1062 is the MySQL error code for duplicate entry
                    $error = "Error: A variant with this SKU already exists.";
                } else {
                    $error = "Oops! Something went wrong. Please try again later.";
                }
            }
            $stmt->close();
        }
    }
}

// --- FETCH EXISTING VARIANT DATA TO DISPLAY IN THE FORM ---
$sql_fetch = "SELECT pv.*, p.product_name 
              FROM product_variants pv 
              JOIN products p ON pv.product_id = p.product_id
              WHERE pv.variant_id = ?";

if ($stmt_fetch = $conn->prepare($sql_fetch)) {
    $stmt_fetch->bind_param("i", $variant_id);
    if ($stmt_fetch->execute()) {
        $result = $stmt_fetch->get_result();
        if ($result->num_rows == 1) {
            $variant = $result->fetch_assoc();
        } else {
            // No variant found with this ID
            header("location: view_products.php");
            exit();
        }
    }
    $stmt_fetch->close();
}
$conn->close();

// If we got here after a failed POST, repopulate variables for the form
$price_val = $_POST['price'] ?? $variant['price'];
$stock_val = $_POST['stock_quantity'] ?? $variant['stock_quantity'];
$sku_val = $_POST['sku'] ?? $variant['sku'];

?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Variant</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: auto; background: #fff; padding: 20px 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; }
        .product-title { text-align: center; color: #555; font-size: 1.2em; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"] { width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; }
        button { background-color: #007BFF; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        .current-image { display: block; max-width: 150px; margin-top: 10px; border-radius: 5px; border: 1px solid #ddd; padding: 5px; }
        .error { padding: 10px; margin-bottom: 15px; border-radius: 4px; background-color: #f8d7da; color: #721c24; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #007BFF; text-decoration: none; }
    </style>
</head>
<body>

<div class="container">
    <a href="view_products.php" class="back-link">← Back to Products</a>
    <h2>Edit Variant</h2>
    
    <?php if ($variant): ?>
        <div class="product-title">
            <strong><?php echo htmlspecialchars($variant['product_name']); ?></strong> - Variant: <strong><?php echo htmlspecialchars($variant['variant_value']); ?></strong>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="edit_variant.php?variant_id=<?php echo $variant_id; ?>" method="post" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="price">Price (LKR)</label>
                <input type="number" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars($price_val); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="stock_quantity">Stock Quantity</label>
                <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($stock_val); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="sku">SKU</label>
                <input type="text" id="sku" name="sku" value="<?php echo htmlspecialchars($sku_val ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Current Image</label>
                <?php $image_src = !empty($variant['image']) ? '../' . $variant['image'] : 'https://via.placeholder.com/150'; ?>
                <img src="<?php echo $image_src; ?>" alt="Current Image" class="current-image">
                <!-- Hidden field to store old image path -->
                <input type="hidden" name="old_image_path" value="<?php echo htmlspecialchars($variant['image']); ?>">
            </div>
            
            <div class="form-group">
                <label for="new_image">Upload New Image (Optional)</label>
                <input type="file" id="new_image" name="new_image">
                <small>If you upload a new image, the old one will be replaced.</small>
            </div>
            
            <br>
            <button type="submit" name="update_variant">Update Variant</button>
            
        </form>
    <?php else: ?>
        <p>Variant not found.</p>
    <?php endif; ?>

</div>

</body>
</html>