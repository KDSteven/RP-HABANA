<?php
session_start();
include 'config/db.php';

$role = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? null;

$where = '';
if ($role !== 'admin') {
    $where = "AND inventory.branch_id = $branch_id";
}

$query = "
SELECT products.product_name, inventory.stock, products.critical_point, branches.branch_name
FROM inventory
INNER JOIN products ON inventory.product_id = products.product_id
INNER JOIN branches ON inventory.branch_id = branches.branch_id
WHERE (inventory.stock = 0 OR inventory.stock <= products.critical_point)
$where
";


$result = $conn->query($query);

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'product_name' => $row['product_name'],
        'stock' => $row['stock'],
        'critical_point' => $row['critical_point'],
        'branch' => $row['branch_name']
    ];
}

echo json_encode([
    'count' => count($items),
    'items' => $items
]);
