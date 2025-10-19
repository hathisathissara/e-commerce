<?php
session_start();
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) { header("location: login.php"); exit; }
require_once '../includes/db_connect.php';

$customer_id = intval($_GET['customer_id'] ?? 0);
if ($customer_id <= 0) { header("location: manage_customers.php"); exit; }

// Fetch customer details
$customer_stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer = $customer_stmt->get_result()->fetch_assoc();
$customer_stmt->close();

if (!$customer) { header("location: manage_customers.php"); exit; }

// Fetch all orders for this customer
$orders_stmt = $conn->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY order_date DESC");
$orders_stmt->bind_param("i", $customer_id);
$orders_stmt->execute();
$orders = $orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$orders_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History for <?= htmlspecialchars($customer['name']) ?></title>
    <!-- Your standard admin theme fonts and CSS links go here -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0984e3; --secondary-color: #6c5ce7; --danger-color: #d63031;
            --success-color: #27ae60; --warning-color: #f0932b; --light-bg: #f5f6fa; 
            --text-dark: #2d3436; --text-light: #636e72; --white: #ffffff; --border-color: #dfe6e9;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); margin: 0; color: var(--text-dark); }
        .admin-wrapper { display: flex; }
        .sidebar { width: 250px; background-color: var(--text-dark); color: var(--white); height: 100vh; position: sticky; top: 0; }
        .sidebar-header { padding: 20px; text-align: center; background: rgba(0,0,0,0.2); }
        .sidebar-header h2 { margin: 0; }
        .sidebar-nav { list-style: none; padding: 20px 0; margin: 0; }
        .sidebar-nav a { display: block; color: var(--white); padding: 15px 20px; text-decoration: none; transition: background 0.2s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: var(--primary-color); }
        .sidebar-nav a.logout { background-color: var(--danger-color); position: absolute; bottom: 0; width: 100%; box-sizing: border-box; }
        
        .main-content { flex: 1; padding: 30px; }
        .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .main-header h1 { margin: 0; }
        .main-header a { text-decoration: none; font-weight: 600; color: var(--primary-color); }

        .customer-card { background-color: #fff; padding: 20px 25px; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .customer-card h2 { margin: 0 0 10px; }
        .content-box { background-color: var(--white); padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 15px; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; }
        .btn-view { padding: 8px 12px; border: none; border-radius: 5px; cursor: pointer; color: white; font-weight: 600; background-color: var(--secondary-color); }
        .status-confirmed { color: var(--success-color); font-weight: bold; }
        .status-pending { color: var(--warning-color); font-weight: bold; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); animation: fadeIn 0.3s; }
        .modal-content { background-color: var(--white); margin: 10% auto; padding: 25px; border-radius: 8px; width: 90%; max-width: 600px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px;}
        .close-btn { font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa; }
        #orderDetailsList { list-style: none; padding: 0; }
        #orderDetailsList li { padding: 8px 0; border-bottom: 1px dashed var(--border-color); }
        #orderDetailsList li:last-child { border: none; }
        @keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php
        require "layout/header.php"
        ?>

        <main class="main-content">
            <header class="main-header">
                <h1>Order History</h1>
                <a href="manage_customers.php">← Back to All Customers</a>
            </header>

            <div class="customer-card">
                <h2><?= htmlspecialchars($customer['name']) ?></h2>
                <p><strong>Phone:</strong> <?= htmlspecialchars($customer['phone1'] . ' / ' . $customer['phone2']) ?></p>
                <p><strong>Address:</strong><br><?= nl2br(htmlspecialchars($customer['address'])) ?></p>
            </div>

            <div class="content-box">
                <h2>Orders (<?= count($orders) ?>)</h2>
                <table>
                    <thead><tr><th>Ref ID</th><th>Total Bill (LKR)</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="5" style="text-align:center;">This customer has not placed any orders yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($order['order_ref_id']) ?></strong></td>
                                    <td style="text-align: right;"><?= number_format($order['total_bill'], 2) ?></td>
                                    <td><span class="status-<?= strtolower($order['order_status']) ?>"><?= htmlspecialchars($order['order_status']) ?></span></td>
                                    <td><?= date("M d, Y", strtotime($order['order_date'])) ?></td>
                                    <td>
                                        <button type="button" class="btn-view" onclick="viewOrderDetails(<?= $order['order_id'] ?>)">View Items</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Re-used Order Details Modal -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Order Details</h2>
                <span class="close-btn" onclick="closeModal()">×</span>
            </div>
            <div id="orderDetailsContent"><p>Loading...</p></div>
        </div>
    </div>

    <!-- **CRITICAL PART**: JavaScript for the Modal functionality -->
    <script>
        const modal = document.getElementById('orderDetailsModal');
        const modalContent = document.getElementById('orderDetailsContent');

        async function viewOrderDetails(orderId) {
            modalContent.innerHTML = '<p>Loading...</p>';
            modal.style.display = 'block';
            try {
                // Fetch the order details from the separate PHP file
                const response = await fetch(`get_order_details.php?order_id=${orderId}`);
                if (!response.ok) {
                    throw new Error(`Network response was not ok, status: ${response.status}`);
                }
                const data = await response.json();
                
                // Display the results in the modal
                if (data.error) {
                     modalContent.innerHTML = `<p style="color:red;">Error: ${data.error}</p>`;
                } else if (data.items && data.items.length > 0) {
                    let listHtml = '<ul id="orderDetailsList">';
                    // The PHP now sends pre-formatted HTML, so we just add it
                    data.items.forEach(item_html => { 
                        listHtml += `<li>${item_html}</li>`; 
                    });
                    listHtml += '</ul>';
                    modalContent.innerHTML = listHtml;
                } else {
                     modalContent.innerHTML = '<p>No items found for this order.</p>';
                }
            } catch (error) {
                console.error('Fetch error:', error);
                modalContent.innerHTML = '<p style="color:red;">Failed to load order details. Check console for errors.</p>';
            }
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Close modal if user clicks outside of the modal content
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>