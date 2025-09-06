<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$sale_id = (int)($_GET['sale_id'] ?? 0);
if ($sale_id <= 0) {
    echo json_encode([]);
    exit;
}

// Fetch products in this sale
$stmt = $conn->prepare("
    SELECT p.product_name, si.quantity, si.price
    FROM sales_items si
    JOIN products p ON si.product_id = p.product_id
    WHERE si.sale_id = ?
");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'product_name' => $row['product_name'],
        'quantity' => (int)$row['quantity'],
        'price' => (float)$row['price']
    ];
}

header('Content-Type: application/json');
echo json_encode($items);
?>
