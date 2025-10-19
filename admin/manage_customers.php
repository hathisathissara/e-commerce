<?php
session_start();
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once '../includes/db_connect.php';

// --- DATA FETCHER with SEARCH functionality ---
$search_term = trim($_GET['search'] ?? '');
$sql = "SELECT c.*, COUNT(o.order_id) as total_orders 
        FROM customers c 
        LEFT JOIN orders o ON c.customer_id = o.customer_id";

if (!empty($search_term)) {
    // Add search condition for name or phone number
    $sql .= " WHERE c.name LIKE ? OR c.phone1 LIKE ?";
    $search_param = "%" . $search_term . "%";
}

$sql .= " GROUP BY c.customer_id ORDER BY c.name ASC";

$stmt = $conn->prepare($sql);
if (!empty($search_term)) {
    // We bind the same parameter twice because we are searching in two columns
    $stmt->bind_param("ss", $search_param, $search_param);
}
$stmt->execute();
$result = $stmt->get_result();
$customers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Your admin theme CSS and Font links -->
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

        h1,
        h2 {
            margin-top: 0;
        }

        .search-bar {
            margin-bottom: 20px;
            display: flex;
        }

        .search-bar input {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 5px 0 0 5px;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
        }

        .search-bar button {
            padding: 10px 20px;
            border: none;
            background: var(--primary-color);
            color: white;
            cursor: pointer;
            border-radius: 0 5px 5px 0;
            font-weight: 600;
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

        td.actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .actions form,
        .actions button {
            margin: 0;
        }

        .actions button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
        }

        .btn-view {
            background-color: var(--secondary-color);
        }

        .btn-confirm {
            background-color: var(--success-color);
        }

        .btn-delete {
            background-color: var(--danger-color);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s;
        }

        .modal-content {
            background-color: var(--white);
            margin: 10% auto;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .modal-header h2 {
            margin: 0;
        }

        .close-btn {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }

        #orderDetailsList {
            list-style: none;
            padding: 0;
        }

        #orderDetailsList li {
            padding: 8px 0;
            border-bottom: 1px dashed var(--border-color);
        }

        #orderDetailsList li:last-child {
            border: none;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .btn-view-orders {
            background-color: var(--secondary-color);
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
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
                <h1>Customer Management</h1>
            </header>

            <div class="search-bar">
                <form action="manage_customers.php" method="GET" style="display: flex; width: 100%;">
                    <input type="text" name="search" placeholder="Search by Name or Phone Number..." value="<?= htmlspecialchars($search_term) ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <div class="content-box">
                <h2>All Registered Customers</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Contact Number</th>
                            <th>Total Orders</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;">No customers found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($customer['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($customer['phone1']) ?></td>
                                    <td style="text-align: center;"><?= $customer['total_orders'] ?></td>
                                    <td><?= date("M d, Y", strtotime($customer['created_at'])) ?></td>
                                    <td>
                                        <a href="customer_orders.php?customer_id=<?= $customer['customer_id'] ?>" class="btn-view-orders">View Orders</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>