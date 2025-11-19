<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/debug_notifications.log");

// Debug marker
error_log("DEBUG START: " . date('Y-m-d H:i:s'));

session_start();

// Ensure PHP uses Manila time (fixes expiry miscalculations)
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

// Load DB connection
require __DIR__ . '/config/db.php';

$role      = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? null;

$EXPIRY_SOON_DAYS = 90; // 3 months

/* ============================================
   BRANCH FILTERS FOR BOTH TABLES
============================================ */
$BRANCH_CONDITION_INV = "";   // for inventory i
$BRANCH_CONDITION_LOTS = "";  // for inventory_lots il

if ($role !== 'admin' && $branch_id) {
    $branch_id = (int)$branch_id;

    $BRANCH_CONDITION_INV  = "AND i.branch_id = $branch_id";
    $BRANCH_CONDITION_LOTS = "AND il.branch_id = $branch_id";
}

$items = [];
$today = new DateTime();

/* ============================================
   PART 1 — OUT OF STOCK & CRITICAL
============================================ */
$q1 = "
SELECT 
    p.product_name,
    i.stock,
    p.critical_point,
    b.branch_name
FROM inventory i
JOIN products p ON p.product_id = i.product_id
JOIN branches b ON b.branch_id = i.branch_id
WHERE i.archived = 0
  AND (i.stock = 0 OR i.stock <= p.critical_point)
  $BRANCH_CONDITION_INV
ORDER BY p.product_name ASC
";

$res1 = $conn->query($q1);

while ($row = $res1->fetch_assoc()) {

    $category = ($row['stock'] == 0) ? 'out' : 'critical';

    $items[] = [
        'product_name'    => $row['product_name'],
        'stock'           => (int)$row['stock'],
        'expiration_date' => null,
        'branch'          => $row['branch_name'],
        'category'        => $category
    ];
}

/* ============================================
   PART 2 — EXPIRY (inventory_lots)
============================================ */
$q2 = "
SELECT 
    p.product_name,
    b.branch_name,
    MIN(il.expiry_date) AS nearest_expiry,
    SUM(il.qty) AS total_qty
FROM inventory_lots il
JOIN products p ON p.product_id = il.product_id
JOIN branches b ON b.branch_id = il.branch_id
WHERE il.qty > 0
  AND il.expiry_date IS NOT NULL
  $BRANCH_CONDITION_LOTS
GROUP BY p.product_name, b.branch_name
ORDER BY nearest_expiry ASC
";

$res2 = $conn->query($q2);

while ($row = $res2->fetch_assoc()) {

  $expiryDt = DateTime::createFromFormat('Y-m-d', $row['nearest_expiry']);
if (!$expiryDt) {
    error_log("BAD EXPIRY FORMAT: " . $row['nearest_expiry']);
    continue;
}

$days = (int)$today->diff($expiryDt)->format('%r%a');

/* DEBUG LOG */
error_log("EXPIRY CHECK → product={$row['product_name']}, today={$today->format('Y-m-d')}, expiry={$expiryDt->format('Y-m-d')}, days_diff={$days}");


    if ($days < 0) {
    // Truly expired in the past
    $category = "expired";
} 
elseif ($days == 0) {
    // Today OR tomorrow — check actual date
    if ($expiryDt->format('Y-m-d') === $today->format('Y-m-d')) {
        $category = "expired"; // expires today
    } else {
        $category = "expiry"; // expires tomorrow = near expiry
    }
}
elseif ($days <= $EXPIRY_SOON_DAYS) {
    // Within configured soon-to-expire window
    $category = "expiry";
} 
else {
    continue; // beyond expiry watch window
}

    $items[] = [
        'product_name'    => $row['product_name'],
        'stock'           => (int)$row['total_qty'],
        'expiration_date' => $row['nearest_expiry'],
        'branch'          => $row['branch_name'],
        'category'        => $category
    ];
}

/* ============================================
   SEND JSON RESPONSE
============================================ */
echo json_encode([
    'count' => count($items),
    'items' => $items
]);

?>
