<?php
include 'config/db.php';
$branch = $_GET['branch'] ?? null;

$query = "
SELECT p.product_name, SUM(si.quantity) as total_qty
FROM sales_items si
JOIN products p ON si.product_id = p.product_id
JOIN sales s ON si.sale_id = s.sale_id
";

if ($branch) {
    $query .= " WHERE s.branch_id = " . (int)$branch;
}

$query .= " GROUP BY si.product_id ORDER BY total_qty DESC LIMIT 5";

$res = $conn->query($query);
$items = [];
while($row = $res->fetch_assoc()) $items[] = $row;
echo json_encode($items);

// Pending transfer
$pending = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'")->fetch_assoc()['pending'];
echo json_encode(['pending' => $pending]);

?>
