<?php
session_start();
require_once 'includes/db_connect.php'; // For product/package details if needed

$cart = $_SESSION['cart'] ?? [];
$customerDetails = $_SESSION['customer_details'] ?? null;
$total = 0;

// Function to get product/package info from DB
function getProductOrPackageInfo($conn, $item_id)
{
    if (strpos($item_id, 'pkg_') === 0) {
        $pkg_id = str_replace('pkg_', '', $item_id);
        $sql = "SELECT package_name AS name, total_price AS price FROM packages WHERE package_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pkg_id); // Use integer for package_id
    } else {
        $sql = "SELECT CONCAT(p.product_name, ' - ', pv.variant_value) AS name, pv.price 
                FROM product_variants pv 
                JOIN products p ON pv.product_id = p.product_id 
                WHERE pv.variant_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $item_id); // Use string for variant_id
    }

    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ?? ['name' => 'Unknown Item', 'price' => 0];
}

// Function to find the correct store page
function findStorePage()
{
    // Get the referring page to avoid circular navigation
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $current_page = basename($_SERVER['PHP_SELF']);

    // Priority order for store pages (excluding current page)
    $possible_pages = ['shop.php', 'products.php', 'catalog.php', 'store.php', 'index.php'];

    // Remove current page from possibilities to avoid loops
    $possible_pages = array_filter($possible_pages, function ($page) use ($current_page) {
        return $page !== $current_page;
    });

    // If we came from store.php, don't go back to it
    if (strpos($referer, 'store.php') !== false) {
        $possible_pages = array_filter($possible_pages, function ($page) {
            return $page !== 'store.php';
        });
    }

    // Check which pages actually exist
    foreach ($possible_pages as $page) {
        if (file_exists($page)) {
            return $page;
        }
    }

    return 'index.php'; // Default fallback
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Your Cart - Savi’s creation </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e84393;
            --secondary-color: #0984e3;
            --text-dark: #333333;
            --text-light: #f1f2f6;
            --background-color: #f7f1e3;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--background-color);
            margin: 0;
            padding: 20px;
            color: var(--text-dark);
        }

        .cart-container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-color);
        }

        .empty-cart {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }

        .empty-cart p {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 20px;
        }

        .navigation-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .store-link {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .store-link:hover {
            background: #d63074;
        }

        .cart-page-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            /* 2 parts for items, 1 part for summary */
            gap: 30px;
            align-items: flex-start;
        }

        @media (max-width: 992px) {
            .cart-page-layout {
                grid-template-columns: 1fr;
                /* Stack on smaller screens */
            }
        }

        .cart-items-panel,
        .summary-panel {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .summary-panel {
            position: sticky;
            /* Make the summary box follow on scroll */
            top: 100px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .actions form {
            display: inline;
        }

        .totals-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .final-total {
            font-size: 1.3em;
            font-weight: bold;
            color: var(--primary-color);
            border-top: 2px solid #ddd;
            padding-top: 10px;
        }

        .btn {
            background: var(--primary-color);
            color: #fff;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background: #d63074;
        }

        .btn-secondary {
            background: var(--secondary-color);
        }

        .btn-secondary:hover {
            background: #0770c4;
        }

        .add-details-btn {
            background: var(--secondary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            margin-right: 15px;
            transition: background-color 0.3s;
        }

        .add-details-btn:hover {
            background: #0770c4;
        }

        .checkout-btn {
            background: #27ae60;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }

        .checkout-btn:hover:not(.disabled) {
            background: #219a52;
        }

        .checkout-btn.disabled {
            background: #95a5a6;
            cursor: not-allowed;
            opacity: 0.6;
            filter: blur(1px);
        }

        .buttons-container {
            text-align: right;
            margin-top: 20px;
        }

        /* Modal Styles - Fixed Centering */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            margin: 0;
            transform: scale(0.9);
            transition: transform 0.3s ease;
            position: relative;
        }

        .modal.show .modal-content {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--primary-color);
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            transition: color 0.3s;
            position: absolute;
            top: 15px;
            right: 20px;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
            box-sizing: border-box;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .delivery-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .delivery-info h4 {
            margin: 0 0 10px 0;
            color: var(--secondary-color);
        }

        .save-details-btn {
            background: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.3s;
        }

        .save-details-btn:hover {
            background: #d63074;
        }

        .customer-details-display {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .customer-details-display h4 {
            margin: 0 0 10px 0;
            color: #27ae60;
        }

        .customer-details-display p {
            margin: 5px 0;
            color: var(--text-dark);
        }

        .edit-details-btn {
            background: var(--secondary-color);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .cart-container {
                padding: 20px;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
                max-height: 95vh;
            }

            .buttons-container {
                text-align: center;
            }

            .add-details-btn,
            .checkout-btn {
                display: block;
                width: 100%;
                margin: 10px 0;
            }
        }
    </style>
</head>

<body>
    <div class="cart-container">
        <h1>Your Shopping Cart</h1>

        <?php if (empty($cart)): ?>
            <div class="empty-cart">
                <p>Your cart is empty.</p>
                <div class="navigation-buttons">
                    <a href="<?= findStorePage() ?>" class="store-link">Continue Shopping</a>
                    <?php if (file_exists('index.php') && basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>
                        <a href="index.php" class="store-link" style="background: var(--secondary-color); margin-left: 10px;">Go to Home</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price (LKR)</th>
                        <th>Quantity</th>
                        <th>Subtotal (LKR)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $item_id => $item):
                        $info = getProductOrPackageInfo($conn, $item_id);
                        $price = $info['price'];
                        $name = $info['name'];
                        $qty = $item['quantity'];
                        $subtotal = $qty * $price;
                        $total += $subtotal;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($name) ?></td>
                            <td><?= number_format($price, 2) ?></td>
                            <td>
                                <form action="includes/cart_functions.php" method="post" style="display:inline-flex; gap:5px; align-items:center;">
                                    <input type="hidden" name="action" value="update_quantity">
                                    <input type="hidden" name="item_id" value="<?= htmlspecialchars($item_id) ?>">
                                    <input type="number" name="quantity" value="<?= $qty ?>" min="1" style="width: 60px;">
                                    <button type="submit" class="btn btn-secondary">Update</button>
                                </form>
                            </td>
                            <td><?= number_format($subtotal, 2) ?></td>
                            <td class="actions">
                                <form action="includes/cart_functions.php" method="post">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="item_id" value="<?= htmlspecialchars($item_id) ?>">
                                    <button type="submit" class="btn">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="totals-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>LKR <?= number_format($total, 2) ?></span>
                </div>
                <div class="total-row">
                    <span>Delivery Fee:</span>
                    <span id="delivery-fee">LKR 0.00</span>
                </div>
                <div class="total-row final-total">
                    <span>Total:</span>
                    <span id="final-total">LKR <?= number_format($total, 2) ?></span>
                </div>
            </div>

            <!-- Customer Details Display -->
            <div id="customer-details-display" class="customer-details-display" style="display: none;">
                <h4>Customer Details</h4>
                <p><strong>Name:</strong> <span id="display-name"></span></p>
                <p><strong>WhatsApp:</strong> <span id="display-phone1"></span></p>
                <p><strong>Alternative Phone:</strong> <span id="display-phone2"></span></p>
                <p><strong>Address:</strong> <span id="display-address"></span></p>
                <button class="edit-details-btn" onclick="openCustomerModal()">Edit Details</button>
            </div>

            <div class="buttons-container">
                <a href="<?= findStorePage() ?>" class="store-link" style="background: var(--secondary-color); margin-right: 15px; display: inline-block;">Continue Shopping</a>
                <button class="add-details-btn" onclick="openCustomerModal()">Add Customer Details</button>
                <button id="checkout-btn" class="checkout-btn disabled" onclick="proceedToCheckout()" disabled>
                    Proceed to Checkout
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Customer Details Modal -->
    <div id="customerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Customer Details</h2>
                <span class="close" onclick="closeCustomerModal()">&times;</span>
            </div>
            <form id="customerForm">
                <div class="form-group">
                    <label for="customer_name">Customer Name *</label>
                    <input type="text" id="customer_name" name="customer_name" required>
                </div>
                <div class="form-group">
                    <label for="phone1">WhatsApp Number *</label>
                    <input type="tel" id="phone1" name="phone1" placeholder="e.g., 0771234567" required>
                </div>
                <div class="form-group">
                    <label for="phone2">Alternative Phone Number</label>
                    <input type="tel" id="phone2" name="phone2" placeholder="Optional">
                </div>
                <div class="delivery-info">
                    <h4>Delivery Charges</h4>
                    <p><strong>Moratuwa:</strong> LKR 200.00</p>
                    <p><strong>Other Areas:</strong> LKR 400.00</p>
                </div>
                <div class="form-group">
                    <label for="address">Delivery Address *</label>
                    <textarea id="address" name="address" placeholder="Enter your full delivery address" required></textarea>
                </div>
                <button type="submit" class="save-details-btn">Save Details</button>
            </form>
        </div>
    </div>

    <script>
        let customerDetails = null;
        const subtotal = <?= $total ?>;

        window.addEventListener('DOMContentLoaded', function() {
            loadCustomerDetails();
        });

        // Load from PHP session (already passed via PHP)
        function loadCustomerDetails() {
            <?php if ($customerDetails): ?>
                customerDetails = <?= json_encode($customerDetails) ?>;
                displayCustomerDetails();
            <?php endif; ?>
        }

        // Display customer info
        function displayCustomerDetails() {
            if (!customerDetails) return;

            const deliveryFee = calculateDeliveryFee(customerDetails.address);
            updateTotals(deliveryFee);

            document.getElementById('display-name').textContent = customerDetails.name;
            document.getElementById('display-phone1').textContent = customerDetails.phone1;
            document.getElementById('display-phone2').textContent = customerDetails.phone2 || 'Not provided';
            document.getElementById('display-address').textContent = customerDetails.address;
            document.getElementById('customer-details-display').style.display = 'block';

            const checkoutBtn = document.getElementById('checkout-btn');
            checkoutBtn.classList.remove('disabled');
            checkoutBtn.disabled = false;
            checkoutBtn.textContent = 'Proceed to Checkout';

            document.querySelector('.add-details-btn').textContent = 'Edit Customer Details';
        }

        // Delivery Fee
        function calculateDeliveryFee(address) {
            if (address.toLowerCase().includes('moratuwa')) {
                return 200;
            }
            return 400;
        }

        function updateTotals(deliveryFee) {
            document.getElementById('delivery-fee').textContent = 'LKR ' + deliveryFee.toFixed(2);
            document.getElementById('final-total').textContent = 'LKR ' + (subtotal + deliveryFee).toFixed(2);
        }

        // Modal Handling
        function openCustomerModal() {
            const modal = document.getElementById('customerModal');
            modal.classList.add('show');

            if (customerDetails) {
                document.getElementById('customer_name').value = customerDetails.name;
                document.getElementById('phone1').value = customerDetails.phone1;
                document.getElementById('phone2').value = customerDetails.phone2;
                document.getElementById('address').value = customerDetails.address;
            }

            document.body.style.overflow = 'hidden';
        }

        function closeCustomerModal() {
            const modal = document.getElementById('customerModal');
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('customerModal');
            if (event.target === modal) {
                closeCustomerModal();
            }
        };

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeCustomerModal();
            }
        });

        // ✅ AJAX submission to store in session (via PHP)
        document.getElementById('customerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const name = document.getElementById('customer_name').value.trim();
            const phone1 = document.getElementById('phone1').value.trim();
            const phone2 = document.getElementById('phone2').value.trim();
            const address = document.getElementById('address').value.trim();

            if (!name || !phone1 || !address) {
                alert('Please fill in all required fields');
                return;
            }

            customerDetails = {
                name,
                phone1,
                phone2,
                address
            };

            // 🔄 Send to PHP session via AJAX
            fetch('includes/customer_details.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        customer_name: name,
                        phone1: phone1,
                        phone2: phone2,
                        address: address
                    }).toString()
                })

                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        displayCustomerDetails();
                        closeCustomerModal();
                    } else {
                        alert('Failed to save customer details.');
                    }
                })
                .catch(() => alert('AJAX request failed.'));
        });

        // Checkout
        function proceedToCheckout() {
            if (!customerDetails) {
                alert('Please add customer details first');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'process_order.php';

            const fields = ['customer_name', 'phone1', 'phone2', 'address'];
            const values = [customerDetails.name, customerDetails.phone1, customerDetails.phone2, customerDetails.address];

            fields.forEach((field, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = field;
                input.value = values[index];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    </script>

</body>

</html>