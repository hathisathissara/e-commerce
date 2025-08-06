<?php
session_start();
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once '../includes/db_connect.php';
const LOW_STOCK_THRESHOLD = 10;

// --- Data Fetching for Dashboard Cards ---

// 1. Product & Variant Counts
$total_products = $conn->query("SELECT COUNT(product_id) as total FROM products")->fetch_assoc()['total'] ?? 0;
$total_variants = $conn->query("SELECT COUNT(variant_id) as total FROM product_variants")->fetch_assoc()['total'] ?? 0;
$low_stock_count = $conn->query("SELECT COUNT(variant_id) as total FROM product_variants WHERE stock_quantity < " . LOW_STOCK_THRESHOLD)->fetch_assoc()['total'] ?? 0;

// 2. Sales & Order Summary Data
// --- NEW: Calculates revenue by subtracting the delivery fee from the total bill for each confirmed order ---
$total_revenue = $conn->query("SELECT SUM(total_bill - delivery_fee) as total FROM orders WHERE order_status = 'Confirmed'")->fetch_assoc()['total'] ?? 0;
$pending_orders_count = $conn->query("SELECT COUNT(order_id) as total FROM orders WHERE order_status = 'Pending'")->fetch_assoc()['total'] ?? 0;
$confirmed_orders_count = $conn->query("SELECT COUNT(order_id) as total FROM orders WHERE order_status = 'Confirmed'")->fetch_assoc()['total'] ?? 0;


// 3. Low stock items list to display in the table
$low_stock_items_result = $conn->query("SELECT p.product_name, pv.* 
                                        FROM product_variants pv 
                                        JOIN products p ON pv.product_id = p.product_id 
                                        WHERE pv.stock_quantity < " . LOW_STOCK_THRESHOLD . " 
                                        ORDER BY pv.stock_quantity ASC");
$low_stock_items = $low_stock_items_result ? $low_stock_items_result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0984e3;
            --secondary-color: #6c5ce7;
            --danger-color: #d63031;
            --success-color: #27ae60;
            --warning-color: #fdcb6e;
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

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: var(--white);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .card h3 {
            margin-top: 0;
            color: var(--text-light);
            font-weight: 600;
            font-size: 1em;
        }

        .card .number {
            font-size: 2.2em;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1;
        }

        .card .number small {
            font-size: 0.6em;
            font-weight: 400;
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
            font-weight: 600;
        }

        .stock-level-low {
            font-weight: 700;
            color: var(--danger-color);
        }

        .view-link {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Savi’s creation </h2>
                <p>Admin Panel</p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="view_products.php">Products</a></li>
                <li><a href="manage_packages.php">Packages</a></li>
                <li><a href="manage_customers.php">Customers</a></li>
                <li><a href="manage_slider.php">Manage Slider</a></li>
                <li><a href="logout.php" class="logout">Logout</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <header class="main-header">
                <h1>Dashboard</h1>
            </header>

            <div class="summary-cards">
                <div class="card" style="border-left: 5px solid var(--success-color);">
                    <h3>Net Revenue</h3>
                    <div class="number" style="color:var(--success-color)"><small>LKR</small> <?= number_format($total_revenue, 2) ?></div>
                </div>
                <div class="card" style="border-left: 5px solid var(--warning-color);">
                    <h3>Pending Orders</h3>
                    <div class="number" style="color:var(--text-dark)"><?= $pending_orders_count ?></div>
                </div>
                <div class="card" style="border-left: 5px solid var(--primary-color);">
                    <h3>Confirmed Orders</h3>
                    <div class="number" style="color:var(--text-dark)"><?= $confirmed_orders_count ?></div>
                </div>
                <div class="card" style="border-left: 5px solid var(--danger-color);">
                    <h3>Low Stock Items</h3>
                    <div class="number" style="color:var(--danger-color)"><?= $low_stock_count ?></div>
                </div>
                <div class="card" style="border-left: 5px solid var(--info-color);">
                    <h3>Total Products</h3>
                    <div class="number" style="color:var(--info-color)"><?= $total_products ?></div>
                </div>
                <div class="card" style="border-left: 5px solid var(--secondary-color);">
                    <h3>Total Variants</h3>
                    <div class="number" style="color:var(--secondary-color)"><?= $total_variants ?></div>
                </div>
            </div>


            <div class="content-box">
                <h2>Low Stock Items (Less than <?= LOW_STOCK_THRESHOLD ?> items remaining)</h2>
                <?php if (empty($low_stock_items)): ?>
                    <p>No items are currently low on stock. Well done!</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Variant</th>
                                <th>Remaining Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td><?= htmlspecialchars($item['variant_value']) ?></td>
                                    <td><span class="stock-level-low"><?= $item['stock_quantity'] ?></span></td>
                                    <td><a href="product_details.php?product_id=<?= $item['product_id'] ?>" class="view-link">View/Edit</a></td>
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