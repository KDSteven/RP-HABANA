<?php
include 'config/db.php';

$branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

if ($branch_id <= 0) {
    echo json_encode([]);
    exit;
}

// 🆕 Select product name AND current stock
$stmt = $conn->prepare("
    SELECT p.product_id, p.product_name, i.stock
    FROM inventory i
    JOIN products p ON i.product_id = p.product_id
    WHERE i.branch_id = ?
");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = [
        'product_id' => $row['product_id'],
        'product_name' => $row['product_name'] . ' (Stock: ' . $row['stock'] . ')'
    ];
}

echo json_encode($products);
