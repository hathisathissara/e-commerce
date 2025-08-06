<?php
require_once 'includes/db_connect.php';

$package_id = isset($_GET['package_id']) ? intval($_GET['package_id']) : 0;

$response = [];

if ($package_id > 0) {
    $sql = "SELECT p.product_name, pv.variant_value, pi.quantity
            FROM package_items pi
            JOIN product_variants pv ON pi.variant_id = pv.variant_id
            JOIN products p ON pv.product_id = p.product_id
            WHERE pi.package_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
