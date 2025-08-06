<?php
session_start();

// Redirect if cart is empty, or page is accessed directly (not via POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    header('Location: store.php');
    exit();
}

require_once 'includes/db_connect.php';

// --- 1. Get Customer Details from Form POST ---
$customer_name = trim($_POST['customer_name']);
$phone1 = trim($_POST['phone1']);
$phone2 = trim($_POST['phone2']);
$address = trim($_POST['address']);
$admin_whatsapp_number = "94701207991"; // Your WhatsApp number
$order_ref = "ORD-" . time();

// --- 2. Find or Create Customer and get a persistent Customer ID ---
$customer_id = 0;
$stmt_find = $conn->prepare("SELECT customer_id FROM customers WHERE phone1 = ?");
$stmt_find->bind_param("s", $phone1);
$stmt_find->execute();
$result_find = $stmt_find->get_result();
if ($result_find->num_rows > 0) {
    // Customer already exists, get their ID
    $customer_id = $result_find->fetch_assoc()['customer_id'];
    // Optional: Update their details
    $stmt_update = $conn->prepare("UPDATE customers SET name = ?, address = ?, phone2 = ? WHERE customer_id = ?");
    $stmt_update->bind_param("sssi", $customer_name, $address, $phone2, $customer_id);
    $stmt_update->execute();
    $stmt_update->close();
} else {
    // Customer is new, create a new record
    $stmt_create = $conn->prepare("INSERT INTO customers (name, phone1, phone2, address) VALUES (?, ?, ?, ?)");
    $stmt_create->bind_param("ssss", $customer_name, $phone1, $phone2, $address);
    $stmt_create->execute();
    $customer_id = $conn->insert_id; // Get the ID of the new customer
    $stmt_create->close();
}
$stmt_find->close();

// --- 3. Function to get product/package info (same as in cart.php) ---
function getProductOrPackageInfo($conn, $item_id)
{
    if (strpos($item_id, 'pkg_') === 0) {
        $pkg_id = str_replace('pkg_', '', $item_id);
        $sql = "SELECT package_id, package_name AS name, total_price AS price FROM packages WHERE package_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pkg_id);
    } else {
        $sql = "SELECT pv.variant_id, CONCAT(p.product_name, ' - ', pv.variant_value) AS name, pv.price 
                FROM product_variants pv 
                JOIN products p ON pv.product_id = p.product_id 
                WHERE pv.variant_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $item_id);
    }

    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result ?? ['name' => 'Unknown Item', 'price' => 0];
}

// --- 4. Process cart items using the CORRECT session structure ---
$total_bill = 0;
$order_text_lines = []; // For building the WhatsApp message
$items_for_db = [];     // For inserting into order_items table
$packages_for_db = [];  // For inserting into order_packages table

// Process cart items (using the structure from cart.php)
foreach ($_SESSION['cart'] as $item_id => $item) {
    $info = getProductOrPackageInfo($conn, $item_id);
    $quantity = $item['quantity'];
    $price = $info['price'];
    $subtotal = $quantity * $price;
    $total_bill += $subtotal;
    
    // Add to WhatsApp message
    $order_text_lines[] = "• " . $info['name'] . " x" . $quantity . " = LKR " . number_format($subtotal, 2);
    
    // Prepare for database insertion
    if (strpos($item_id, 'pkg_') === 0) {
        // It's a package
        $pkg_id = str_replace('pkg_', '', $item_id);
        $packages_for_db[] = [
            'package_id' => $pkg_id, 
            'quantity' => $quantity, 
            'price' => $price
        ];
        
        // Get package contents for WhatsApp message
        $stmt_inner = $conn->prepare("SELECT p.product_name, pv.variant_value, pi.quantity as item_qty 
                                     FROM package_items pi 
                                     JOIN product_variants pv ON pi.variant_id = pv.variant_id 
                                     JOIN products p ON pv.product_id = p.product_id 
                                     WHERE pi.package_id = ?");
        $stmt_inner->bind_param("i", $pkg_id);
        $stmt_inner->execute();
        $inner_result = $stmt_inner->get_result();
        
        if ($inner_result->num_rows > 0) {
            $order_text_lines[] = "  `Includes:`";
            while ($inner_item = $inner_result->fetch_assoc()) {
                $order_text_lines[] = "    - " . $inner_item['product_name'] . " (" . $inner_item['variant_value'] . ") x" . $inner_item['item_qty'];
            }
        }
        $stmt_inner->close();
    } else {
        // It's a regular product variant
        $items_for_db[] = [
            'variant_id' => $item_id, 
            'quantity' => $quantity, 
            'price' => $price
        ];
    }
}

// Final total calculation
$delivery_fee = (stripos($address, 'moratuwa') !== false) ? 200.00 : 400.00;
$final_total = $total_bill + $delivery_fee;

// --- 5. SAVE THE ORDER TO THE DATABASE using a Transaction ---
$conn->begin_transaction();
try {
    // Insert into 'orders' table using the correct CUSTOMER_ID
    $sql_order = "INSERT INTO orders (customer_id, order_ref_id, total_bill, delivery_fee) VALUES (?, ?, ?, ?)";
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->bind_param("isdd", $customer_id, $order_ref, $final_total, $delivery_fee);
    $stmt_order->execute();
    $new_order_id = $conn->insert_id;
    $stmt_order->close();

    // Insert items into 'order_items'
    if (!empty($items_for_db)) {
        $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, variant_id, quantity, price_at_time_of_order) VALUES (?, ?, ?, ?)");
        foreach ($items_for_db as $item) {
            $stmt_items->bind_param("iiid", $new_order_id, $item['variant_id'], $item['quantity'], $item['price']);
            $stmt_items->execute();
        }
        $stmt_items->close();
    }
    
    // Insert packages into 'order_packages'
    if (!empty($packages_for_db)) {
        $stmt_pkgs = $conn->prepare("INSERT INTO order_packages (order_id, package_id, quantity, price_at_time_of_order) VALUES (?, ?, ?, ?)");
        foreach ($packages_for_db as $pkg) {
            $stmt_pkgs->bind_param("iiid", $new_order_id, $pkg['package_id'], $pkg['quantity'], $pkg['price']);
            $stmt_pkgs->execute();
        }
        $stmt_pkgs->close();
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    error_log("Order Saving Failed: " . $e->getMessage()); // Log error for your reference
    die("Error: Could not save your order. Please contact support and mention reference: " . $order_ref);
}

// --- 6. Construct and Redirect to WhatsApp ---
$whatsapp_message = "🎉 *New Order from Savi’s creation !*\n\n";
$whatsapp_message .= "*Ref ID:* `" . $order_ref . "`\n\n";
$whatsapp_message .= "*Customer:* " . htmlspecialchars($customer_name) . "\n";
$whatsapp_message .= "*Contact:* " . htmlspecialchars($phone1);
if (!empty($phone2)) { 
    $whatsapp_message .= " / " . htmlspecialchars($phone2); 
}
$whatsapp_message .= "\n";
$whatsapp_message .= "*Address:* " . htmlspecialchars($address) . "\n\n";
$whatsapp_message .= "*Order Details:*\n";
$whatsapp_message .= implode("\n", $order_text_lines);
$whatsapp_message .= "\n\n";
$whatsapp_message .= "*Subtotal:* LKR " . number_format($total_bill, 2) . "\n";
$whatsapp_message .= "*Delivery Fee:* LKR " . number_format($delivery_fee, 2) . "\n";
$whatsapp_message .= "*Total: LKR " . number_format($final_total, 2) . "*";

$whatsapp_url = "https://wa.me/" . $admin_whatsapp_number . "?text=" . urlencode($whatsapp_message);

// Clear the cart AND customer details from session after successful order
unset($_SESSION['cart']);
unset($_SESSION['customer_details']);

header("Location: " . $whatsapp_url);
exit();
?>