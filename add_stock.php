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
// ❌ remove functions.php here (inventory.php already loaded it)


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inventory.php');
    exit;
}

$barcode      = trim($_POST['barcode'] ?? '');
$product_id   = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
$stock_amount = (int)($_POST['stock_amount'] ?? 0);
$expiry_date  = trim($_POST['expiry_date'] ?? ''); // optional unless product requires it

// Determine branch based on role
$role = $_SESSION['role'] ?? '';
if ($role === 'admin') {
    $branch_id = $_SESSION['current_branch_id'] ?? ($_POST['branch_id'] ?? null);
} else {
    $branch_id = $_SESSION['branch_id'] ?? null;
}

// Safety checks
if (!$branch_id) {
    die("No branch selected or found. (role: " . htmlspecialchars($role) . ")");
}
if ($stock_amount <= 0) {
    die("Invalid stock amount.");
}

// Lookup product by barcode or id and fetch name + expiry flag
$product_name     = null;
$expiry_required  = 0;

if ($barcode !== '') {
    $stmt = $conn->prepare("
        SELECT product_id, product_name, expiry_required
        FROM products
        WHERE barcode = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $stmt->bind_result($product_id, $product_name, $expiry_required);
    if (!$stmt->fetch()) {
        $stmt->close();
        die("Product with this barcode not found.");
    }
    $stmt->close();
} else {
    if (!$product_id) die("No product selected or found.");
    $stmt = $conn->prepare("
        SELECT product_name, expiry_required
        FROM products
        WHERE product_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->bind_result($product_name, $expiry_required);
    if (!$stmt->fetch()) {
        $stmt->close();
        die("Product not found.");
    }
    $stmt->close();
}

/* -----------------------------------------------------------
   Expiry handling
   - If product tracks expiry (expiry_required=1): expiry_date is REQUIRED.
   - If user supplied an expiry_date even when not required: we will record a lot for that batch.
----------------------------------------------------------- */
$expiryDateForLot = null;

// Validate/require if product requires expiry
if ((int)$expiry_required === 1) {
    if ($expiry_date === '') {
        die("Expiry date is required for this product.");
    }
}

// If any expiry was submitted, validate & normalize it
if ($expiry_date !== '') {
    $dt = DateTime::createFromFormat('Y-m-d', $expiry_date);
    if (!($dt && $dt->format('Y-m-d') === $expiry_date)) {
        die("Invalid expiry date format. Use YYYY-MM-DD.");
    }
    // Optional: block past dates
    // if ($expiry_date < date('Y-m-d')) die("Expiry date cannot be in the past.");

    $expiryDateForLot = $expiry_date; // record this batch regardless of expiry_required flag
}

// Transaction: keep totals + lots + logs consistent
$conn->begin_transaction();

try {
    // 1) Upsert totals in inventory
    $stmt = $conn->prepare("
        SELECT inventory_id, stock
        FROM inventory
        WHERE product_id = ? AND branch_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $product_id, $branch_id);
    $stmt->execute();
    $stmt->bind_result($inventory_id, $current_stock);
    $hasRow = $stmt->fetch();
    $stmt->close();

    if ($hasRow) {
        $newStock = (int)$current_stock + $stock_amount;
        $stmt = $conn->prepare("UPDATE inventory SET stock = ? WHERE inventory_id = ?");
        $stmt->bind_param("ii", $newStock, $inventory_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $product_id, $branch_id, $stock_amount);
        $stmt->execute();
        $stmt->close();
        $current_stock = 0;
        $newStock = $stock_amount;
    }

    // 2) If an expiry was provided, record the batch in inventory_lots
    //    This works for both: required products and optional batches
    if ($expiryDateForLot) {
        // Requires UNIQUE KEY (product_id, branch_id, expiry_date) in inventory_lots
        $stmt = $conn->prepare("
            INSERT INTO inventory_lots (product_id, branch_id, expiry_date, qty)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)
        ");
        // i (product_id), i (branch_id), s (date), i (qty)
        $stmt->bind_param("iisi", $product_id, $branch_id, $expiryDateForLot, $stock_amount);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            $conn->rollback();
            die("Failed to write inventory_lots: " . htmlspecialchars($err));
        }
        $stmt->close();
    }

    // 3) Log action (general logs)
    $extra = $expiryDateForLot ? " (Expiry: $expiryDateForLot)" : "";
    logAction(
        $conn,
        "Add Stock",
        "Added $stock_amount stock to $product_name (ID: $product_id)$extra",
        null,
        $branch_id
    );

    // Commit
    $conn->commit();

    // Session flash
    $_SESSION['stock_message'] =
        "✅ $product_name stock updated successfully.$extra<br>" .
        "Old stock: " . (int)$current_stock . " → New stock: " . (int)$newStock . " (Branch ID: $branch_id)";

    // Redirect so your toast picks it up
    header("Location: inventory.php?stock=added");
    exit;


} catch (Throwable $e) {
    $conn->rollback();
    // error_log($e->getMessage());
    die("Failed to add stock. Please try again.");
}

?>
