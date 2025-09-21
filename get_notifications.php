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

// Fetch items with stock = 0 OR stock <= critical_point OR near expiration, excluding archived inventory
$query = "
SELECT p.product_name, i.stock, p.critical_point, p.ceiling_point, p.expiration_date, b.branch_name
FROM inventory i
INNER JOIN products p ON i.product_id = p.product_id
INNER JOIN branches b ON i.branch_id = b.branch_id
WHERE i.archived = 0
  AND (i.stock = 0 OR i.stock <= p.critical_point OR p.expiration_date IS NOT NULL)
  $where
";

$result = $conn->query($query);
$items = [];

$today = new DateTime();

while ($row = $result->fetch_assoc()) {
    $category = ($row['stock'] == 0) ? 'out' : 'critical';

    // Check expiration
    if (!empty($row['expiration_date'])) {
        $expiry = new DateTime($row['expiration_date']);
        $daysLeft = (int)$today->diff($expiry)->format('%r%a'); // negative if expired

        if ($daysLeft <= 180 && $daysLeft > 0) { // 180 days = ~6 months
            $category = 'expiry';
        } elseif ($daysLeft <= 0) {
            $category = 'expired';
        }
    }

    $items[] = [
        'product_name' => $row['product_name'],
        'stock' => (int)$row['stock'],
        'critical_point' => (int)$row['critical_point'],
        'ceiling_point' => (int)$row['ceiling_point'],
        'expiration_date' => $row['expiration_date'],
        'branch' => $row['branch_name'],
        'category' => $category
    ];
}

echo json_encode([
    'count' => count($items),
    'items' => $items
]);
?>
