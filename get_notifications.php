<?php
// session_start();
// include 'config/db.php';

// $role = $_SESSION['role'] ?? '';
// $branch_id = $_SESSION['branch_id'] ?? null;

// // Branch filter
// $where = '';
// if ($role !== 'admin' && $branch_id) {
//     $where = "AND i.branch_id = $branch_id";
// }

// // Fetch items with stock = 0 OR stock <= critical_point OR near expiration, excluding archived inventory
// $query = "
// SELECT p.product_name, i.stock, p.critical_point, p.ceiling_point, p.expiration_date, b.branch_name
// FROM inventory i
// INNER JOIN products p ON i.product_id = p.product_id
// INNER JOIN branches b ON i.branch_id = b.branch_id
// WHERE i.archived = 0
//   AND (i.stock = 0 OR i.stock <= p.critical_point OR p.expiration_date IS NOT NULL)
//   $where
// ";

// $result = $conn->query($query);
// $items = [];

// $today = new DateTime();

// while ($row = $result->fetch_assoc()) {
//     $category = ($row['stock'] == 0) ? 'out' : 'critical';

//     // Check expiration
//     if (!empty($row['expiration_date'])) {
//         $expiry = new DateTime($row['expiration_date']);
//         $daysLeft = (int)$today->diff($expiry)->format('%r%a'); // negative if expired

//         if ($daysLeft <= 180 && $daysLeft > 0) { // 180 days = ~6 months
//             $category = 'expiry';
//         } elseif ($daysLeft <= 0) {
//             $category = 'expired';
//         }
//     }

//     $items[] = [
//         'product_name' => $row['product_name'],
//         'stock' => (int)$row['stock'],
//         'critical_point' => (int)$row['critical_point'],
//         'ceiling_point' => (int)$row['ceiling_point'],
//         'expiration_date' => $row['expiration_date'],
//         'branch' => $row['branch_name'],
//         'category' => $category
//     ];
// }

// echo json_encode([
//     'count' => count($items),
//     'items' => $items
// ]);


session_start();
header('Content-Type: application/json');
include 'config/db.php';

$role      = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? null;

/* ------------ Settings ------------ */
$EXPIRY_SOON_DAYS  = 90;     // lots expiring within N days -> "expiry"
$MAX_EXPIRY_ITEMS  = 0;     // 0 = no cap; set e.g. 50 to cap list size
/* ---------------------------------- */

/* Branch filters */
$whereInventory = '';
$whereLots      = '';
if ($role !== 'admin' && $branch_id) {
    $whereInventory = "AND i.branch_id = " . (int)$branch_id;
    $whereLots      = "AND il.branch_id = " . (int)$branch_id;
}

$items = [];
$today = new DateTime();

/* ===============================
   PART 1 — Out / Critical (inventory only)
   =============================== */
$qInv = "
SELECT 
  p.product_name,
  i.stock,
  p.critical_point,
  p.ceiling_point,
  b.branch_name
FROM inventory i
JOIN products p ON p.product_id = i.product_id
JOIN branches b ON b.branch_id  = i.branch_id
WHERE i.archived = 0
  AND (i.stock = 0 OR i.stock <= p.critical_point)
  $whereInventory
";
$resInv = $conn->query($qInv);
while ($row = $resInv->fetch_assoc()) {
    $category = ((int)$row['stock'] === 0) ? 'out' : 'critical';
    $items[] = [
        'product_name'    => $row['product_name'],
        'stock'           => (int)$row['stock'],
        'critical_point'  => (int)$row['critical_point'],
        'ceiling_point'   => (int)$row['ceiling_point'],
        'expiration_date' => null,                 // driven by lots below
        'branch'          => $row['branch_name'],
        'category'        => $category,
    ];
}

/* ===============================
   PART 2 — ALL expiring/expired lots from inventory_lots
   (not just the nearest per product)
   =============================== */

$qLots = "
SELECT 
  p.product_name,
  b.branch_name,
  il.expiry_date,
  SUM(il.qty) AS lot_qty
FROM inventory_lots il
JOIN products  p ON p.product_id = il.product_id
JOIN branches  b ON b.branch_id  = il.branch_id
WHERE il.qty > 0
  $whereLots
  AND (
        il.expiry_date < CURDATE()
     OR il.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $EXPIRY_SOON_DAYS DAY)
  )
GROUP BY p.product_name, b.branch_name, il.expiry_date
ORDER BY il.expiry_date ASC
" . ($MAX_EXPIRY_ITEMS > 0 ? "LIMIT " . (int)$MAX_EXPIRY_ITEMS : "");

$resLots = $conn->query($qLots);

while ($row = $resLots->fetch_assoc()) {
    $expiryStr = $row['expiry_date'];
    $expiryDt  = DateTime::createFromFormat('Y-m-d', $expiryStr);
    if (!$expiryDt) continue;

    $category = ($expiryDt < $today) ? 'expired' : 'expiry';

    $items[] = [
        'product_name'    => $row['product_name'],
        'stock'           => (int)$row['lot_qty'],   // sum for that exact expiry
        'critical_point'  => null,
        'ceiling_point'   => null,
        'expiration_date' => $expiryStr,
        'branch'          => $row['branch_name'],
        'category'        => $category,
    ];
}

/* Output */
echo json_encode([
    'count' => count($items),
    'items' => $items
]);

?>
