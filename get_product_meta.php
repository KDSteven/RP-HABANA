<?php
session_start();
require 'config/db.php';

header('Content-Type: application/json');

$barcode    = trim($_GET['barcode'] ?? '');
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($barcode !== '') {
    $stmt = $conn->prepare("SELECT product_id, expiry_required FROM products WHERE barcode=? LIMIT 1");
    $stmt->bind_param("s", $barcode);
} elseif ($product_id > 0) {
    $stmt = $conn->prepare("SELECT product_id, expiry_required FROM products WHERE product_id=? LIMIT 1");
    $stmt->bind_param("i", $product_id);
} else {
    echo json_encode(['ok' => false, 'message' => 'No identifier']);
    exit;
}

$stmt->execute();
$stmt->bind_result($pid, $expiry_required);
if ($stmt->fetch()) {
    echo json_encode(['ok' => true, 'product_id' => $pid, 'expiry_required' => (int)$expiry_required === 1]);
} else {
    echo json_encode(['ok' => false, 'message' => 'Not found']);
}
$stmt->close();

