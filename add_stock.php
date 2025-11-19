<?php
// session_start();
// include 'config/db.php';
// include 'functions.php';

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $barcode      = trim($_POST['barcode'] ?? '');
//     $product_id   = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
//     $stock_amount = (int) ($_POST['stock_amount'] ?? 0);
//     $expiry_date  = trim($_POST['expiry_date'] ?? ''); // only required if product needs expiry

//     // Determine branch based on role
//     if (($_SESSION['role'] ?? '') === 'admin') {
//         $branch_id = $_SESSION['current_branch_id'] ?? ($_POST['branch_id'] ?? null);
//     } else {
//         $branch_id = $_SESSION['branch_id'] ?? null;
//     }

//     // Safety checks
//     if (!$branch_id) {
//         die("No branch selected or found. (role: {$_SESSION['role']})");
//     }
//     if ($stock_amount <= 0) {
//         die("Invalid stock amount.");
//     }

//     // Lookup product by barcode or id and fetch name + expiry flag
//     $product_name = null;
//     $expiry_required = 0;

//     if ($barcode !== '') {
//         $stmt = $conn->prepare("
//             SELECT product_id, product_name, expiry_required
//             FROM products
//             WHERE barcode = ?
//             LIMIT 1
//         ");
//         $stmt->bind_param("s", $barcode);
//         $stmt->execute();
//         $stmt->bind_result($product_id, $product_name, $expiry_required);
//         if (!$stmt->fetch()) {
//             die("Product with this barcode not found.");
//         }
//         $stmt->close();
//     } else {
//         if (!$product_id) die("No product selected or found.");
//         $stmt = $conn->prepare("
//             SELECT product_name, expiry_required
//             FROM products
//             WHERE product_id = ?
//             LIMIT 1
//         ");
//         $stmt->bind_param("i", $product_id);
//         $stmt->execute();
//         $stmt->bind_result($product_name, $expiry_required);
//         if (!$stmt->fetch()) {
//             die("Product not found.");
//         }
//         $stmt->close();
//     }

//     // Require expiry date only when product tracks expiry
//     $expiryDateForLot = null;
//     if ((int)$expiry_required === 1) {
//         if ($expiry_date === '') die("Expiry date is required for this product.");
//         $dt = DateTime::createFromFormat('Y-m-d', $expiry_date);
//         if (!($dt && $dt->format('Y-m-d') === $expiry_date)) {
//             die("Invalid expiry date format. Use YYYY-MM-DD.");
//         }
//         // Optional: block past dates
//         // if ($expiry_date < date('Y-m-d')) die("Expiry date cannot be in the past.");
//         $expiryDateForLot = $expiry_date;
//     }

//     // Transaction: keep totals + lots + logs consistent
//     $conn->begin_transaction();
//     try {
//         // Update total inventory
//         $stmt = $conn->prepare("SELECT inventory_id, stock FROM inventory WHERE product_id=? AND branch_id=? LIMIT 1");
//         $stmt->bind_param("ii", $product_id, $branch_id);
//         $stmt->execute();
//         $stmt->bind_result($inventory_id, $current_stock);
//         $hasRow = $stmt->fetch();
//         $stmt->close();

//         if ($hasRow) {
//             $newStock = (int)$current_stock + $stock_amount;
//             $stmt = $conn->prepare("UPDATE inventory SET stock=? WHERE inventory_id=?");
//             $stmt->bind_param("ii", $newStock, $inventory_id);
//             $stmt->execute();
//         } else {
//             $stmt = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
//             $stmt->bind_param("iii", $product_id, $branch_id, $stock_amount);
//             $stmt->execute();
//             $newStock = $stock_amount;
//             $current_stock = 0;
//         }

//         // If you have per-expiry lots, upsert there too
//         if ((int)$expiry_required === 1 && $expiryDateForLot) {
//             $stmt = $conn->prepare("
//                 INSERT INTO inventory_lots (product_id, branch_id, expiry_date, qty)
//                 VALUES (?, ?, ?, ?)
//                 ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)
//             ");
//             $stmt->bind_param("iisi", $product_id, $branch_id, $expiryDateForLot, $stock_amount);
//             $stmt->execute();
//         }

//         // General log only (no inventory_movements table)
//         $extra = ((int)$expiry_required === 1 && $expiryDateForLot) ? " (Expiry: $expiryDateForLot)" : "";
//         logAction(
//             $conn,
//             "Add Stock",
//             "Added $stock_amount stock to $product_name (ID: $product_id)$extra",
//             null,
//             $branch_id
//         );

//         $conn->commit();

//         // Session flash
//         $_SESSION['stock_message'] =
//             "✅ $product_name stock updated successfully.$extra<br>" .
//             "Old stock: " . (int)$current_stock . " → New stock: " . (int)$newStock . " (Branch ID: $branch_id)";

//         header("Location: inventory.php?stock=success");
//         exit;
//     } catch (Throwable $e) {
//         $conn->rollback();
//         // error_log($e->getMessage());
//         die("Failed to add stock. Please try again.");
//     }
// }

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: inventory.php'); exit; }

$barcode      = trim($_POST['barcode'] ?? '');
$product_id   = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
$stock_amount = (int)($_POST['stock_amount'] ?? 0);
$expiry_date  = trim($_POST['expiry_date'] ?? ''); // optional unless required
$remarks      = trim($_POST['remarks'] ?? '');

$role = $_SESSION['role'] ?? '';
$branch_id = ($role === 'admin')
  ? ($_SESSION['current_branch_id'] ?? ($_POST['branch_id'] ?? null))
  : ($_SESSION['branch_id'] ?? null);

if (!$branch_id) die("No branch selected or found. (role: " . htmlspecialchars($role) . ")");
if ($stock_amount <= 0) die("Invalid stock amount.");

$product_name = null; $expiry_required = 0;

if ($barcode !== '') {
  $stmt = $conn->prepare("SELECT product_id, product_name, expiry_required FROM products WHERE barcode=? LIMIT 1");
  $stmt->bind_param("s", $barcode);
  $stmt->execute();
  $stmt->bind_result($product_id, $product_name, $expiry_required);
  if (!$stmt->fetch()) { $stmt->close(); die("Product with this barcode not found."); }
  $stmt->close();
} else {
  if (!$product_id) die("No product selected or found.");
  $stmt = $conn->prepare("SELECT product_name, expiry_required FROM products WHERE product_id=? LIMIT 1");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $stmt->bind_result($product_name, $expiry_required);
  if (!$stmt->fetch()) { $stmt->close(); die("Product not found."); }
  $stmt->close();
}

/* expiry validation */
$expiryDateForLot = null;
if ((int)$expiry_required === 1 && $expiry_date === '') die("Expiry date is required for this product.");
if ($expiry_date !== '') {
  $dt = DateTime::createFromFormat('Y-m-d', $expiry_date);
  if (!($dt && $dt->format('Y-m-d') === $expiry_date)) die("Invalid expiry date format. Use YYYY-MM-DD.");
  $expiryDateForLot = $expiry_date;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$AUTO_APPROVE_ADMIN = true; // flip to false if you want admins to queue too

/* Non-admins always create a pending request */
if ($role !== 'admin' || $AUTO_APPROVE_ADMIN === false) {
  if ($stmt = $conn->prepare("
      INSERT INTO stock_in_requests
        (product_id, branch_id, quantity, expiry_date, remarks, status, requested_by, request_date, archived)
      VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW(), 0)
  ")) {
    $stmt->bind_param("iiissi", $product_id, $branch_id, $stock_amount, $expiryDateForLot, $remarks, $user_id);
    $stmt->execute(); $stmt->close();
  }

  if (function_exists('logAction')) {
    $extra = $expiryDateForLot ? " (Expiry: $expiryDateForLot)" : "";
    logAction($conn, "Stock-In Request", "Requested +$stock_amount for $product_name (ID:$product_id)$extra", null, $branch_id);
  }

  $_SESSION['stock_message'] = "📝 Stock-in request submitted for approval.";
  header("Location: inventory.php?stock=success"); exit;
}

/* Admin auto-approve path: apply stock now, then mark approved for audit trail */
/* Admin auto-approve path: apply stock now, then mark approved for audit trail */
$conn->begin_transaction();
try {
    // Lock product + inventory row, fetch ceiling + current stock
    $stmt = $conn->prepare("
        SELECT p.ceiling_point,
               i.inventory_id,
               COALESCE(i.stock,0) AS stock
        FROM products p
        LEFT JOIN inventory i
          ON i.product_id = p.product_id AND i.branch_id = ?
        WHERE p.product_id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("ii", $branch_id, $product_id);
    $stmt->execute();
    $stmt->bind_result($ceiling_point, $inventory_id, $current_stock);
    if (!$stmt->fetch()) { $stmt->close(); throw new Exception("Product not found."); }
    $stmt->close();

    $ceiling_point = (int)($ceiling_point ?? 0);
    $current_stock = (int)($current_stock ?? 0);
    $newStock      = $current_stock + $stock_amount;

    // Block if ceiling is set (>0) and would be exceeded
    if ($ceiling_point > 0 && $newStock > $ceiling_point) {
        throw new Exception("Final stock ($newStock) exceeds ceiling ($ceiling_point).");
    }

    // Upsert inventory using existence (inventory_id), not stock value
    if ($inventory_id) {
        $stmt = $conn->prepare("UPDATE inventory SET stock = ? WHERE inventory_id = ?");
        $stmt->bind_param("ii", $newStock, $inventory_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock, archived) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("iii", $product_id, $branch_id, $stock_amount);
        $stmt->execute();
        $stmt->close();
    }

    // Track per-expiry lots (optional table; requires UNIQUE(product_id,branch_id,expiry_date))
    if ($expiryDateForLot) {
        $stmt = $conn->prepare("
            INSERT INTO inventory_lots (product_id, branch_id, expiry_date, qty)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)
        ");
        $stmt->bind_param("iisi", $product_id, $branch_id, $expiryDateForLot, $stock_amount);
        $stmt->execute();
        $stmt->close();
    }

    // Audit trail: write an 'approved' row into stock_in_requests
    $stmt = $conn->prepare("
        INSERT INTO stock_in_requests
            (product_id, branch_id, quantity, expiry_date, remarks, status,
             requested_by, request_date, decided_by, decision_date, archived)
        VALUES (?, ?, ?, ?, ?, 'approved', ?, NOW(), ?, NOW(), 0)
    ");
    $stmt->bind_param("iiissii", $product_id, $branch_id, $stock_amount,
                      $expiryDateForLot, $remarks, $user_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Log + commit
    if (function_exists('logAction')) {
        $extra = $expiryDateForLot ? " (Expiry: $expiryDateForLot)" : "";
        logAction($conn, "Add Stock", "Added $stock_amount to $product_name (ID:$product_id)$extra", null, $branch_id);
    }

    $conn->commit();

    $_SESSION['stock_message'] =
        "✅ $product_name stock updated successfully" .
        ($expiryDateForLot ? " (Expiry: $expiryDateForLot)" : "") . "<br>" .
        "Old stock: $current_stock → New stock: $newStock (Branch ID: $branch_id)";
    header("Location: inventory.php?stock=success");
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['stock_message'] = "❌ Add stock failed: " . $e->getMessage();
    header("Location: inventory.php?stock=error");
    exit;
} catch (Throwable $e) {
  $conn->rollback(); die("Failed to add stock. Please try again.");
}

?>
