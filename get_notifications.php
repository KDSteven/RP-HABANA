<?php
session_start();
include 'config/db.php';

$role = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? null;

// Branch filter
$where = '';
if ($role !== 'admin' && $branch_id) {
    $where = "AND i.branch_id = $branch_id";
}

// Fetch items with stock = 0 OR stock <= critical_point, excluding archived inventory
$query = "
SELECT p.product_name, i.stock, p.critical_point, p.ceiling_point, b.branch_name
FROM inventory i
INNER JOIN products p ON i.product_id = p.product_id
INNER JOIN branches b ON i.branch_id = b.branch_id
WHERE i.archived = 0
  AND (i.stock = 0 OR i.stock <= p.critical_point)
  $where
";

$result = $conn->query($query);
$items = [];

while ($row = $result->fetch_assoc()) {
    $category = ($row['stock'] == 0) ? 'out' : 'critical';

    $items[] = [
        'product_name' => $row['product_name'],
        'stock' => (int)$row['stock'],
        'critical_point' => (int)$row['critical_point'],
        'ceiling_point' => (int)$row['ceiling_point'],
        'branch' => $row['branch_name'],
        'category' => $category
    ];
}

echo json_encode([
    'count' => count($items),
    'items' => $items
]);
?>
