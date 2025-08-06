<?php
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add product variant
    if ($action === 'add') {
        $variant_id = $_POST['variant_id'] ?? '';
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        if ($variant_id !== '') {
            if (isset($_SESSION['cart'][$variant_id])) {
                $_SESSION['cart'][$variant_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$variant_id] = [
                    'variant_id' => $variant_id,
                    'quantity' => $quantity
                ];
            }
        }

        exit; // No message
    }

    // Add package
    if ($action === 'add_package') {
        $package_id = $_POST['package_id'] ?? '';
        if ($package_id !== '') {
            $key = 'pkg_' . $package_id;

            if (isset($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key]['quantity'] += 1;
            } else {
                $_SESSION['cart'][$key] = [
                    'package_id' => $package_id,
                    'quantity' => 1
                ];
            }
        }

        exit; // No message
    }

    // Remove item
    if ($action === 'remove') {
        $item_id = $_POST['item_id'] ?? '';
        unset($_SESSION['cart'][$item_id]);
        header('Location: ../cart.php');
        exit;
    }

    // Update quantity
    if ($action === 'update_quantity') {
        $item_id = $_POST['item_id'] ?? '';
        $new_quantity = (int)($_POST['quantity'] ?? 1);
        if ($new_quantity > 0 && isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id]['quantity'] = $new_quantity;
        }
        header('Location: ../cart.php');
        exit;
    }
}
