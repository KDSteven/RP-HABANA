<?php
// get_products.php
header('Content-Type: application/json');
error_reporting(0); // suppress PHP warnings

include 'config/db.php';

$view      = $_GET['view']    ?? '';
$branch_id = intval($_GET['branch'] ?? 0);

// build an array of WHERE clauses
$where = [];
if ($branch_id > 0) {
    $where[] = "i.branch_id = $branch_id";
}

if ($view === 'low_stocks') {
    $where[] = "i.stock <= p.critical_point";
} elseif ($view === 'out_of_stocks') {
    $where[] = "i.stock = 0";
} else {
    echo json_encode([]);
    exit;
}

$where_sql = implode(' AND ', $where);

$sql = "
  SELECT 
    p.product_name, 
    p.category, 
    i.stock, 
    p.critical_point 
  FROM inventory i
  JOIN products p ON i.product_id = p.product_id
  WHERE $where_sql
";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
