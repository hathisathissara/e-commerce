<?php
// ALWAYS START THE SESSION AT THE VERY TOP
session_start();

// Security Check: If user is not logged in, redirect them to the login page
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once '../includes/db_connect.php';

$message = ''; // Variable to store any error messages

// Check if the form has been submitted to add a new product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_product'])) {

    // Get form data and remove any extra whitespace
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);

    // Basic validation to ensure product name is not empty
    if (!empty($product_name)) {

        // Prepare the SQL INSERT statement
        $sql = "INSERT INTO products (product_name, description) VALUES (?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            // Bind the variables to the prepared statement as parameters
            $stmt->bind_param("ss", $product_name, $description);

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // If successful, get the ID of the newly created product
                $new_product_id = $conn->insert_id;

                // Redirect the user to the product details page to add variants
                header("location: product_details.php?product_id=" . $new_product_id . "&status=product_added");
                exit();
            } else {
                $message = "Oops! Something went wrong while saving. Please try again later.";
            }

            $stmt->close();
        }
    } else {
        $message = "Product name is required. Please enter a name for the product.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Admin</title>
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0984e3;
            --secondary-color: #6c5ce7;
            --danger-color: #d63031;
            --success-color: #27ae60;
            --light-bg: #f5f6fa;
            --text-dark: #2d3436;
            --text-light: #636e72;
            --white: #ffffff;
            --border-color: #dfe6e9;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            color: var(--text-dark);
        }

        .admin-wrapper {
            display: flex;
        }

        .sidebar {
            width: 250px;
            background-color: var(--text-dark);
            color: var(--white);
            height: 100vh;
            position: sticky;
            top: 0;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            background: rgba(0, 0, 0, 0.2);
        }

        .sidebar-header h2 {
            margin: 0;
        }

        .sidebar-nav {
            list-style: none;
            padding: 20px 0;
            margin: 0;
        }

        .sidebar-nav a {
            display: block;
            color: var(--white);
            padding: 15px 20px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: var(--primary-color);
        }

        .sidebar-nav a.logout {
            background-color: var(--danger-color);
            position: absolute;
            bottom: 0;
            width: 100%;
            box-sizing: border-box;
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .content-box {
            background-color: var(--white);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        /* Form-specific styles */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .action-btn {
            background: var(--primary-color);
            color: var(--white);
            padding: 12px 25px;
            font-weight: 600;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            font-size: 1.1em;
            transition: background-color 0.2s;
        }

        .action-btn:hover {
            background-color: #0672c4;
        }

        .back-link {
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 600;
        }

        .alert-danger {
            background-color: #ffdddd;
            border-left: 5px solid var(--danger-color);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="admin-wrapper">
        <?php
        require "layout/header.php"
        ?>

        <main class="main-content">
            <header class="main-header">
                <h1>Add New Product</h1>
                <a href="view_products.php" class="back-link">← Back to All Products</a>
            </header>

            <div class="content-box">
                <p>Enter the basic details of the new product below. After saving, you will be taken to a page to add variants like colors, sizes, and their specific prices.</p>
                <hr style="margin: 20px 0;">

                <?php if (!empty($message)): ?>
                    <div class="alert-danger"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form action="add_product.php" method="post">
                    <div class="form-group">
                        <label for="product_name">Product Name</label>
                        <input type="text" id="product_name" name="product_name" placeholder="e.g., Ceramic Coffee Mug" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="A short, attractive description of the product..."></textarea>
                    </div>
                    <button type="submit" name="submit_product" class="action-btn">Save and Add Variants</button>
                </form>
            </div>
        </main>
    </div>
</body>

</html>