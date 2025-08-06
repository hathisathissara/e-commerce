<?php
session_start();
$count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
header('Content-Type: application/json');
echo json_encode(['count' => $count]);
