<?php
// get_products_by_branch.php
session_start();
header('Content-Type: application/json');
include 'config/db.php';

// (Optional) require login
if (!isset($_SESSION['user_id'])) {
  echo json_encode([]); 
  exit;
}

$branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;
if ($branch_id <= 0) {
  echo json_encode([]);
  exit;
}

/*
  Adjust table/column names if yours differ.
  We LEFT JOIN inventory so products without a row in inventory for this branch still appear with stock=0.
*/
$sql = "
  SELECT
    p.product_id,
    p.product_name,
    COALESCE(i.stock, 0) AS stock
  FROM products p
  LEFT JOIN inventory i
    ON i.product_id = p.product_id
   AND i.branch_id = ?
  ORDER BY p.product_name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($row = $res->fetch_assoc()) {
  $out[] = [
    'product_id'   => (int)$row['product_id'],
    'product_name' => $row['product_name'],
    'stock'        => (int)$row['stock'],
  ];
}
echo json_encode($out);
