<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['customer_details'] = [
        'name' => $_POST['customer_name'] ?? '',
        'phone1' => $_POST['phone1'] ?? '',
        'phone2' => $_POST['phone2'] ?? '',
        'address' => $_POST['address'] ?? ''
    ];

    echo json_encode(['status' => 'success']);
    exit;
}
?>
