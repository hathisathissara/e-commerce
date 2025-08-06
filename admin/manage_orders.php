<?php
session_start();
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once '../includes/db_connect.php';

// -----------------------------------------------------------------------------
// --- ACTION HANDLER: This block handles all POST requests (Confirm, Delete) ---
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- HANDLE DELETE ORDER ACTION ---
    if (isset($_POST['delete_order'])) {
        $order_id_to_delete = intval($_POST['order_id']);

        $sql_delete = "DELETE FROM orders WHERE order_id = ?";
        if ($stmt_delete = $conn->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $order_id_to_delete);
            $stmt_delete->execute();
            $stmt_delete->close();
        }
        header("Location: manage_orders.php?status=deleted");
        exit();
    }

    // --- HANDLE CONFIRM ORDER ACTION ---
    if (isset($_POST['confirm_order'])) {
        $order_id_to_confirm = intval($_POST['order_id']);

        $conn->begin_transaction();
        try {
            $items_to_update = [];

            // Get regular items
            $sql_items = "SELECT variant_id, quantity FROM order_items WHERE order_id = ?";
            if ($stmt_items = $conn->prepare($sql_items)) {
                $stmt_items->bind_param("i", $order_id_to_confirm);
                $stmt_items->execute();
                $result_items = $stmt_items->get_result();
                while ($row = $result_items->fetch_assoc()) {
                    $items_to_update[$row['variant_id']] = ($items_to_update[$row['variant_id']] ?? 0) + $row['quantity'];
                }
                $stmt_items->close();
            }

            // Get items from packages
            $sql_pkg_items = "SELECT pi.variant_id, (pi.quantity * op.quantity) as total_quantity 
                              FROM order_packages op 
                              JOIN package_items pi ON op.package_id = pi.package_id
                              WHERE op.order_id = ?";
            if ($stmt_pkg_items = $conn->prepare($sql_pkg_items)) {
                $stmt_pkg_items->bind_param("i", $order_id_to_confirm);
                $stmt_pkg_items->execute();
                $result_pkg_items = $stmt_pkg_items->get_result();
                while ($row = $result_pkg_items->fetch_assoc()) {
                    $items_to_update[$row['variant_id']] = ($items_to_update[$row['variant_id']] ?? 0) + $row['total_quantity'];
                }
                $stmt_pkg_items->close();
            }

            // Loop through all collected items and deduct stock
            if (!empty($items_to_update)) {
                $sql_deduct = "UPDATE product_variants SET stock_quantity = stock_quantity - ? WHERE variant_id = ? AND stock_quantity >= ?";
                $stmt_deduct = $conn->prepare($sql_deduct);
                foreach ($items_to_update as $variant_id => $quantity) {
                    // Check if there's enough stock before deducting
                    // This is an extra safety check.
                    $stmt_deduct->bind_param("iii", $quantity, $variant_id, $quantity);
                    $stmt_deduct->execute();
                }
                $stmt_deduct->close();
            }

            // Update the order status to 'Confirmed'
            $sql_update_order = "UPDATE orders SET order_status = 'Confirmed' WHERE order_id = ?";
            $stmt_update = $conn->prepare($sql_update_order);
            $stmt_update->bind_param("i", $order_id_to_confirm);
            $stmt_update->execute();
            $stmt_update->close();

            $conn->commit();
            header("Location: manage_orders.php?status=confirmed");
            exit();
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            error_log("Order Confirmation Failed: " . $exception->getMessage());
            header("Location: manage_orders.php?status=error");
            exit();
        }
    }
}


// --- DATA FETCHER with SEARCH functionality (UPDATED) ---
$search_term = trim($_GET['search'] ?? '');

// **UPDATED QUERY: Selects customer's name AND address**
$sql = "SELECT o.*, c.name as customer_name, c.address as customer_address 
        FROM orders o 
        JOIN customers c ON o.customer_id = c.customer_id 
        WHERE o.order_status = 'Pending'";

if (!empty($search_term)) {
    // Search by Ref ID, Customer Name, or Address
    $sql .= " AND (o.order_ref_id LIKE ? OR c.name LIKE ? OR c.address LIKE ?)";
    $search_param = "%" . $search_term . "%";
}
$sql .= " ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($search_term)) {
    // We bind the same parameter THREE times for the three LIKE clauses
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}
$stmt->execute();
$result = $stmt->get_result();
$pending_orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin</title>
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_orders.php" class="active">Orders</a></li>
                <li><a href="view_products.php">Products</a></li>
                <li><a href="manage_packages.php">Packages</a></li>
                <li><a href="manage_customers.php">Customers</a></li>
                <li><a href="manage_slider.php">Manage Slider</a></li>
                <li><a href="logout.php" class="logout">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h1>Manage Pending Orders</h1>
            </header>

            <div class="search-bar">
                <form action="manage_orders.php" method="GET" style="display: flex; width: 100%;">
                    <input type="text" name="search" placeholder="Search by Reference ID..." value="<?= htmlspecialchars($search_term) ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <div class="content-box">
                <table>
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Customer Name</th>
                            <th>Customer Address</th>
                            <th>Total Bill (LKR)</th>
                            <th>Order Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_orders)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding: 20px;">No pending orders found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending_orders as $order): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($order['order_ref_id']) ?></strong></td>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($order['customer_address']) ?></td>
                                    <td style="text-align: right;"><?= number_format($order['total_bill'], 2) ?></td>
                                    <td><?= date("M d, Y, g:i a", strtotime($order['order_date'])) ?></td>
                                    <td class="actions">
                                        <button type="button" class="btn-view" onclick="viewOrderDetails(<?= $order['order_id'] ?>)">View</button>
                                        <form action="manage_orders.php" method="post" onsubmit="return confirm('This action will deduct stock and cannot be undone. Are you sure?');">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <button type="submit" name="confirm_order" class="btn-confirm">Confirm</button>
                                        </form>
                                        <form action="manage_orders.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this order?');">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <button type="submit" name="delete_order" class="btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Order Details</h2>
                <span class="close-btn" onclick="closeModal()">×</span>
            </div>
            <div id="orderDetailsContent">
                <p>Loading...</p>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('orderDetailsModal');
        const modalContent = document.getElementById('orderDetailsContent');

        async function viewOrderDetails(orderId) {
            modalContent.innerHTML = '<p>Loading...</p>';
            modal.style.display = 'block';
            try {
                const response = await fetch(`get_order_details.php?order_id=${orderId}`);
                if (!response.ok) {
                    throw new Error('Network response was not ok.');
                }
                const data = await response.json();

                if (data.error) {
                    modalContent.innerHTML = `<p style="color:red;">Error: ${data.error}</p>`;
                } else if (data.items && data.items.length > 0) {
                    let listHtml = '<ul id="orderDetailsList">';
                    data.items.forEach(item => {
                        listHtml += `<li>${item}</li>`;
                    });
                    listHtml += '</ul>';
                    modalContent.innerHTML = listHtml;
                } else {
                    modalContent.innerHTML = '<p>No items found for this order.</p>';
                }
            } catch (error) {
                console.error('Fetch error:', error);
                modalContent.innerHTML = '<p style="color:red;">Failed to load order details.</p>';
            }
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>