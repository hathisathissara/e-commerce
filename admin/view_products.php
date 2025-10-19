<?php
// PHP logic... (This part is unchanged)
session_start();
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once '../includes/db_connect.php';
$sql = "SELECT product_id, product_name, created_at FROM products ORDER BY created_at DESC";
$result = $conn->query($sql);
$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
$conn->close();

$currentPage = 'products'; // To highlight the active link in sidebar
// The template `header.php` would use this variable. (We will make a simplified version for now)
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Products</title>
    <!-- We'll paste the common CSS block here for simplicity -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Paste the same CSS block from dashboard.php here */
        :root {
            --primary-color: #0984e3;
            --secondary-color: #e84393;
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

        .main-header .action-btn {
            background: var(--success-color);
            color: var(--white);
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 5px;
            font-weight: 600;
        }

        .content-box {
            background-color: var(--white);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--light-bg);
        }

        .product-actions a {
            padding: 8px 12px;
            text-decoration: none;
            color: white;
            border-radius: 5px;
            font-size: 14px;
            background-color: var(--secondary-color);
        }

        .status-msg {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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
                <h1>All Products</h1>
                <a href="add_product.php" class="action-btn">Add New Product</a>
            </header>
            <?php if (isset($_GET['status']) && $_GET['status'] == 'product_added'): ?><div class="status-msg">New product added successfully!</div><?php endif; ?>
            <div class="content-box">
                <?php if (empty($products)): ?>
                    <p>No products found. Start by adding one!</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Created On</th>
                                <th style="text-align:right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($product['product_name']) ?></strong></td>
                                    <td><?= date("M d, Y", strtotime($product['created_at'])) ?></td>
                                    <td style="text-align:right"><a href="product_details.php?product_id=<?= $product['product_id'] ?>" class="product-actions">Manage Variants</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>