<?php
session_start();
// Security check: Ensure an admin is logged in
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

require_once '../includes/db_connect.php';

// Set the correct header for JSON response
header('Content-Type: application/json');

// Get and validate the order ID from the URL
$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid or missing Order ID.']);
    exit;
}

$response = ['items' => []];

// Start a transaction for safe reading
$conn->begin_transaction();
try {
    // --- 1. Get REGULAR items for the order ---
    $sql_items = "SELECT p.product_name, pv.variant_value, oi.quantity 
                  FROM order_items oi
                  JOIN product_variants pv ON oi.variant_id = pv.variant_id
                  JOIN products p ON pv.product_id = p.product_id
                  WHERE oi.order_id = ?";
    if ($stmt_items = $conn->prepare($sql_items)) {
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $result = $stmt_items->get_result();
        while ($row = $result->fetch_assoc()) {
            $response['items'][] = htmlspecialchars($row['product_name'] . ' (' . $row['variant_value'] . ') - Qty: ' . $row['quantity']);
        }
        $stmt_items->close();
    }

    // --- 2. Get PACKAGES and their inner items for the order ---
    $sql_pkgs = "SELECT op.quantity as package_quantity, p.package_name, p.package_id
                 FROM order_packages op
                 JOIN packages p ON op.package_id = p.package_id
                 WHERE op.order_id = ?";
    if ($stmt_pkgs = $conn->prepare($sql_pkgs)) {
        $stmt_pkgs->bind_param("i", $order_id);
        $stmt_pkgs->execute();
        $result = $stmt_pkgs->get_result();

        while ($pkg_row = $result->fetch_assoc()) {
            // Add the main package line to the response array
            $package_line = '<strong>' . htmlspecialchars($pkg_row['package_name']) . ' (Package) - Qty: ' . $pkg_row['package_quantity'] . '</strong>';

            // --- NEW LOGIC: Now fetch items inside this specific package ---
            $sql_inner_items = "SELECT p.product_name, pv.variant_value, pi.quantity 
                                FROM package_items pi
                                JOIN product_variants pv ON pi.variant_id = pv.variant_id
                                JOIN products p ON pv.product_id = p.product_id
                                WHERE pi.package_id = ?";
            if ($stmt_inner = $conn->prepare($sql_inner_items)) {
                $stmt_inner->bind_param("i", $pkg_row['package_id']);
                $stmt_inner->execute();
                $inner_result = $stmt_inner->get_result();

                if ($inner_result->num_rows > 0) {
                    $package_line .= '<ul style="margin: 5px 0 0 20px; padding: 0; list-style-type: circle;">';
                    while ($inner_row = $inner_result->fetch_assoc()) {
                        $package_line .= '<li><small>' . htmlspecialchars($inner_row['product_name'] . ' - ' . $inner_row['variant_value'] . ' [Qty: ' . $inner_row['quantity'] . ']') . '</small></li>';
                    }
                    $package_line .= '</ul>';
                }
                $stmt_inner->close();
            }
            $response['items'][] = $package_line;
        }
        $stmt_pkgs->close();
    }

    // All queries successful
    $conn->commit();
    echo json_encode($response);
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    http_response_code(500); // Internal Server Error
    error_log("Get Order Details Failed: " . $exception->getMessage());
    echo json_encode(['error' => 'Database error occurred.']);
}

$conn->close();
