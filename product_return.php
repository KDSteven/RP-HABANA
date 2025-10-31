<?php
// session_start();
// include 'config/db.php';
// include 'functions.php'; // must provide get_active_shift($conn, $user_id, $branch_id)

// // ---------------- Authorization ----------------
// if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
//     header("Location: index.html");
//     exit;
// }

// $user_id = (int)$_SESSION['user_id'];
// $role    = $_SESSION['role'];

// // ---------------- Refund Logic ----------------
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $sale_id         = (int)($_POST['sale_id'] ?? 0);
//     $refund_reason   = trim($_POST['refund_reason'] ?? '');
//     $refund_products = $_POST['refund_items'] ?? [];
//     $refund_services = $_POST['refund_services'] ?? [];

//     if (!$sale_id || (empty($refund_products) && empty($refund_services))) {
//         die("Invalid refund request.");
//     }

//     // Fetch sale (must include VAT column)
//     $sale = $conn->query("
//         SELECT total, vat, branch_id, status 
//         FROM sales 
//         WHERE sale_id = $sale_id
//     ")->fetch_assoc();

//     // --- Cumulative refund total (for status) ---
//     $prevRefundedTotal = (float)($conn->query("
//         SELECT COALESCE(SUM(refund_total),0) AS t
//         FROM sales_refunds
//         WHERE sale_id = $sale_id
//     ")->fetch_assoc()['t'] ?? 0);

//     // --- Already refunded qty per product ---
//     $alreadyRefundedQty = [];
//     $res = $conn->query("
//         SELECT product_id, SUM(qty) AS qty_refunded
//         FROM sales_refund_items
//         WHERE sale_id = $sale_id
//         GROUP BY product_id
//     ");
//     if ($res) {
//         while ($r = $res->fetch_assoc()) {
//             $alreadyRefundedQty[(int)$r['product_id']] = (int)$r['qty_refunded'];
//         }
//     }

//     if (!$sale) die("Sale not found.");
//     if ($sale['status'] === 'Refunded') die("Sale already fully refunded.");

//     $branch_id = (int)$sale['branch_id'];
//     $totalSale = (float)$sale['total'];

//     $refItems = [];   // [product_id, qty, price]
//     $refundAmount = 0.0; // net refund (ex VAT)
//     $refundVAT    = 0.0; // VAT refund

//     // ---------------- Transaction Start ----------------
//     $conn->begin_transaction();

//     try {
//         // Inventory update statement
//         $updateInventory = $conn->prepare("
//             UPDATE inventory 
//             SET stock = stock + ? 
//             WHERE product_id = ? AND branch_id = ?
//         ");

//         // --- Refund Products ---
//         foreach ($refund_products as $product_id => $qtyReq) {
//         $product_id = (int)$product_id;
//         $qtyReq = (int)$qtyReq;
//         if ($qtyReq <= 0) continue;

//         $sold = $conn->query("
//             SELECT quantity, price 
//             FROM sales_items 
//             WHERE sale_id = $sale_id AND product_id = $product_id
//         ")->fetch_assoc();
//         if (!$sold) continue;

//         $soldQty = (int)$sold['quantity'];
//         $refundedSoFar = $alreadyRefundedQty[$product_id] ?? 0;
//         $remaining = max(0, $soldQty - $refundedSoFar);
//         if ($remaining <= 0) continue;

//         $qty = min($qtyReq, $remaining);

//         $subtotalNet = (float)$sold['price'] * $qty;
//         $vatPortion  = $subtotalNet * 0.12;

//         $refundAmount += $subtotalNet;
//         $refundVAT    += $vatPortion;

//         $refItems[] = [$product_id, $qty, (float)$sold['price']];

//         $updateInventory->bind_param("iii", $qty, $product_id, $branch_id);
//         $updateInventory->execute();
//     }


//         // --- Refund Services ---
//         foreach ($refund_services as $service_id => $qty) {
//             $service_id = (int)$service_id;
//             $qty = (int)$qty;
//             if ($qty <= 0) continue;

//             $service = $conn->query("
//                 SELECT price 
//                 FROM sales_services 
//                 WHERE sale_id = $sale_id AND service_id = $service_id
//             ")->fetch_assoc();
//             if (!$service) continue;

//             // --- VAT Calculation (same as products) ---
//             $subtotalNet   = $service['price'] * $qty; 
//             $vatPortion    = $subtotalNet * 0.12;
//             $subtotalTotal = $subtotalNet + $vatPortion;

//             $refundAmount += $subtotalNet;
//             $refundVAT    += $vatPortion;
//         }

//         if ($refundAmount <= 0 && $refundVAT <= 0) {
//         $conn->rollback();
//         $_SESSION['toast'] = ['type' => 'warning', 'msg' => 'No refundable quantity left for the selected items.'];
//         header("Location: history.php?toast=refund_none");
//         exit;
//         }

//         // --- Insert Refund Record ---
//         $refundTotal = $refundAmount + $refundVAT;

//         // get current active shift for the user (null if none)
//         $active = get_active_shift($conn, $user_id, (int)$sale['branch_id']);
//         $refund_shift_id = $active ? (int)$active['shift_id'] : null;

//         $insertRefund = $conn->prepare("
//             INSERT INTO sales_refunds 
//                 (sale_id, refunded_by, refund_amount, refund_vat, refund_reason, refund_date, refund_total)
//             VALUES (?, ?, ?, ?, ?, NOW(), ?)
//         ");
//         $insertRefund->bind_param(
//             "iiddsd",
//             $sale_id,
//             $user_id,
//             $refundAmount,
//             $refundVAT,
//             $refund_reason,
//             $refundTotal
//         );
//         $insertRefund->execute();
//         $refund_id = $conn->insert_id;  // <-- capture it

//         // Insert product lines
//         if (!empty($refItems)) {
//         $insItem = $conn->prepare("
//             INSERT INTO sales_refund_items (refund_id, sale_id, product_id, qty, price)
//             VALUES (?, ?, ?, ?, ?)
//         ");
//         foreach ($refItems as [$pid, $q, $price]) {
//             $insItem->bind_param("iiiid", $refund_id, $sale_id, $pid, $q, $price);
//             $insItem->execute();
//         }
//         $insItem->close();
//         }

//         // ✅ Update sale status based on cumulative refunds
//         $upd = $conn->prepare("
//             UPDATE sales s
//             LEFT JOIN (
//                 SELECT sale_id, COALESCE(SUM(refund_total), 0) AS refunded
//                 FROM sales_refunds
//                 WHERE sale_id = ?
//             ) r ON r.sale_id = s.sale_id
//             SET s.status = CASE
//                 WHEN r.refunded >= s.total - 0.0001 THEN 'Refunded'
//                 WHEN r.refunded > 0 THEN 'Partial Refund'
//                 ELSE 'Paid'
//             END
//             WHERE s.sale_id = ?
//         ");
//         $upd->bind_param("ii", $sale_id, $sale_id);
//         $upd->execute();
//         $upd->close();

//         // --- Determine new cumulative refund total ---
//         $refundTotal = $refundAmount + $refundVAT;
//         $newCumulative = $prevRefundedTotal + $refundTotal;

//         if ($newCumulative + 0.0001 >= (float)$sale['total']) {
//             $conn->query("UPDATE sales SET status = 'Refunded' WHERE sale_id = $sale_id");
//         } else {
//             $conn->query("UPDATE sales SET status = 'Partial Refund' WHERE sale_id = $sale_id");
//         }

//         // Commit
//         $conn->commit();
//            // ✅ Success toast
//             $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Refund processed successfully.'];
//             header("Location: history.php?toast=refund_ok");
//             exit;

//         } catch (Throwable $e) {
//             $conn->rollback();
//             // ❌ Error toast               
//             $_SESSION['toast'] = ['type' => 'danger', 'msg' => 'Refund failed: ' . $e->getMessage()];
//             header("Location: history.php?toast=refund_err");
//             exit;
//         }
//         }

session_start();
require __DIR__ . '/config/db.php';
require __DIR__ . '/functions.php'; // for get_active_shift()

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
  header('Location: index.html'); exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: history.php'); exit;
}

$sale_id         = (int)($_POST['sale_id'] ?? 0);
$refund_reason   = trim($_POST['refund_reason'] ?? '');
$refund_products = $_POST['refund_items'] ?? [];
$refund_services = $_POST['refund_services'] ?? [];

if (!$sale_id || (empty($refund_products) && empty($refund_services))) {
  $_SESSION['toast'] = ['type' => 'warning', 'msg' => 'Invalid refund request.'];
  header('Location: history.php?toast=refund_err'); exit;
}

// --- fetch sale (NET total + VAT) ---
$sale = $conn->query("
  SELECT sale_id, branch_id, total, vat, status
  FROM sales
  WHERE sale_id = {$sale_id}
  LIMIT 1
")->fetch_assoc();

if (!$sale) {
  $_SESSION['toast'] = ['type' => 'danger', 'msg' => 'Sale not found.'];
  header('Location: history.php?toast=refund_err'); exit;
}

if ($sale['status'] === 'Refunded') {
  $_SESSION['toast'] = ['type' => 'info', 'msg' => 'Sale already fully refunded.'];
  header('Location: history.php'); exit;
}

$branch_id     = (int)$sale['branch_id'];
$sale_net      = (float)$sale['total'];
$sale_vat      = (float)$sale['vat'];
$grand_total   = round($sale_net + $sale_vat, 2);

// ---- already-refunded qty per product (to limit remaining) ----
$alreadyRefundedQty = [];
$res = $conn->query("
  SELECT product_id, SUM(qty) AS qty_refunded
  FROM sales_refund_items
  WHERE sale_id = {$sale_id}
  GROUP BY product_id
");
if ($res) {
  while ($r = $res->fetch_assoc()) {
    $alreadyRefundedQty[(int)$r['product_id']] = (int)$r['qty_refunded'];
  }
}

// ---- start transaction ----
$conn->begin_transaction();

try {
  $refundAmount = 0.0; // net (ex VAT)
  $refundVAT    = 0.0; // VAT component
  $refItems     = [];  // [product_id, qty, price]

  // inventory updater
  $updateInventory = $conn->prepare("
    UPDATE inventory
    SET stock = stock + ?
    WHERE product_id = ? AND branch_id = ?
  ");

  // --- products ---
  foreach ($refund_products as $product_id => $qtyReq) {
    $product_id = (int)$product_id;
    $qtyReq     = (int)$qtyReq;
    if ($qtyReq <= 0) continue;

    $sold = $conn->query("
      SELECT quantity, price
      FROM sales_items
      WHERE sale_id = {$sale_id} AND product_id = {$product_id}
      LIMIT 1
    ")->fetch_assoc();
    if (!$sold) continue;

    $soldQty         = (int)$sold['quantity'];
    $refundedSoFar   = $alreadyRefundedQty[$product_id] ?? 0;
    $remaining       = max(0, $soldQty - $refundedSoFar);
    if ($remaining <= 0) continue;

    $qty = min($qtyReq, $remaining);

    $lineNet   = (float)$sold['price'] * $qty;
    // derive VAT rate from the sale itself to stay consistent with UI calc
    $vatRate   = ($sale_net > 0) ? ($sale_vat / $sale_net) : 0.0;
    $lineVAT   = $lineNet * $vatRate;

    $refundAmount += $lineNet;
    $refundVAT    += $lineVAT;

    $refItems[] = [$product_id, $qty, (float)$sold['price']];

    $updateInventory->bind_param("iii", $qty, $product_id, $branch_id);
    $updateInventory->execute();
  }

  // --- services (if any) ---
  foreach ($refund_services as $service_id => $qtyReq) {
    $service_id = (int)$service_id;
    $qtyReq     = (int)$qtyReq;
    if ($qtyReq <= 0) continue;

    $sv = $conn->query("
      SELECT price
      FROM sales_services
      WHERE sale_id = {$sale_id} AND service_id = {$service_id}
      LIMIT 1
    ")->fetch_assoc();
    if (!$sv) continue;

    $lineNet = (float)$sv['price'] * $qtyReq;
    $vatRate = ($sale_net > 0) ? ($sale_vat / $sale_net) : 0.0;
    $lineVAT = $lineNet * $vatRate;

    $refundAmount += $lineNet;
    $refundVAT    += $lineVAT;
  }

  if ($refundAmount <= 0 && $refundVAT <= 0) {
    $conn->rollback();
    $_SESSION['toast'] = ['type' => 'warning', 'msg' => 'No refundable quantity left for the selected items.'];
    header('Location: history.php?toast=refund_none'); exit;
  }

  // --- round to 2dp to match DECIMAL(10,2) storage & avoid drift ---
  $refundAmount = round($refundAmount, 2);
  $refundVAT    = round($refundVAT, 2);
  $refundTotal  = round($refundAmount + $refundVAT, 2);

  // --- insert refund header ---
  $active = get_active_shift($conn, $user_id, $branch_id); // optional; may be null
  $ins = $conn->prepare("
    INSERT INTO sales_refunds
      (sale_id, refunded_by, refund_amount, refund_vat, refund_reason, refund_date, refund_total)
    VALUES (?, ?, ?, ?, ?, NOW(), ?)
  ");
  $ins->bind_param("iiddsd", $sale_id, $user_id, $refundAmount, $refundVAT, $refund_reason, $refundTotal);
  $ins->execute();
  $refund_id = $conn->insert_id;

  // --- insert refund items ---
  if (!empty($refItems)) {
    $insItem = $conn->prepare("
      INSERT INTO sales_refund_items (refund_id, sale_id, product_id, qty, price)
      VALUES (?, ?, ?, ?, ?)
    ");
    foreach ($refItems as [$pid, $q, $price]) {
      $insItem->bind_param("iiiid", $refund_id, $sale_id, $pid, $q, $price);
      $insItem->execute();
    }
    $insItem->close();
  }

  // --- single, authoritative status update (compare vs NET+VAT) ---
  $upd = $conn->prepare("
    UPDATE sales s
    LEFT JOIN (
      SELECT sale_id, COALESCE(SUM(refund_total),0) AS refunded
      FROM sales_refunds
      WHERE sale_id = ?
    ) r ON r.sale_id = s.sale_id
    SET s.status = CASE
      WHEN r.refunded >= ROUND(s.total + s.vat, 2) - 0.001 THEN 'Refunded'
      WHEN r.refunded > 0 THEN 'Partial Refund'
      ELSE 'Paid'
    END
    WHERE s.sale_id = ?
  ");
  $upd->bind_param("ii", $sale_id, $sale_id);
  $upd->execute();
  $upd->close();

  $conn->commit();
  $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Refund processed successfully.'];
  header('Location: history.php?toast=refund_ok'); exit;

} catch (Throwable $e) {
  $conn->rollback();
  $_SESSION['toast'] = ['type' => 'danger', 'msg' => 'Refund failed: '.$e->getMessage()];
  header('Location: history.php?toast=refund_err'); exit;
}

?>
