<?php
// add_product.php (drop-in)
// - Auto-sets expiry_required=1 when expiration_date is provided (or auto-filled by tire rule)
// - No front-end changes needed

session_start();
include 'config/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ---------- Helpers ---------- */
function logAction($conn, $action, $details, $user_id = null, $branch_id = null) {
    if (!$user_id && isset($_SESSION['user_id']))   $user_id = (int)$_SESSION['user_id'];
    if (!$branch_id && isset($_SESSION['branch_id'])) $branch_id = (int)$_SESSION['branch_id'];
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, timestamp, branch_id) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("issi", $user_id, $action, $details, $branch_id);
    $stmt->execute();
    $stmt->close();
}

// EAN-13 checksum + generator (for auto barcode if blank)
function ean13CheckDigit(string $base12): int {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $d = (int)$base12[$i];
        $sum += ($i % 2 === 0) ? $d : $d * 3;
    }
    return (10 - ($sum % 10)) % 10;
}
function makeEan13FromId(int $productId): string {
    // 200 = internal prefix; register GS1 for retail distribution
    $base12 = '200' . str_pad((string)$productId, 9, '0', STR_PAD_LEFT);
    return $base12 . ean13CheckDigit($base12);
}

/* ---------- Guard ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inventory.php');
    exit;
}

/* ---------- Collect POST ---------- */
$barcode       = trim($_POST['barcode'] ?? '');
$productName   = trim($_POST['product_name'] ?? '');
$categoryId    = (int)($_POST['category_id'] ?? 0);
$price         = (float)($_POST['price'] ?? 0);
$markupPrice   = (float)($_POST['markup_price'] ?? 0);
$retailPrice   = $price + ($price * $markupPrice / 100);
$ceilingPoint  = (int)($_POST['ceiling_point'] ?? 0);
$criticalPoint = (int)($_POST['critical_point'] ?? 0);
$vat           = (float)($_POST['vat'] ?? 0);
$stocks        = (int)($_POST['stocks'] ?? 0);
$branchId      = (int)($_POST['branch_id'] ?? 0);
$brandName     = trim($_POST['brand_name'] ?? '');
$expiration    = $_POST['expiration_date'] ?? null; // may be '' or null

/* ---------- Basic validations ---------- */
if ($productName === '' || $categoryId <= 0 || $branchId <= 0) {
    $_SESSION['stock_message'] = "❌ Missing required fields.";
    header('Location: inventory.php?ap=error');
    exit;
}
if ($price < 0 || $markupPrice < 0 || $retailPrice < 0 || $ceilingPoint < 0 || $criticalPoint < 0 || $stocks < 0 || $vat < 0) {
    $_SESSION['stock_message'] = "❌ Numeric fields cannot be negative.";
    header('Location: inventory.php?ap=error');
    exit;
}
if ($criticalPoint > $ceilingPoint) {
    $_SESSION['stock_message'] = "❌ Critical Point cannot be greater than Ceiling Point.";
    header('Location: inventory.php?ap=error');
    exit;
}
if ($stocks > $ceilingPoint) {
    $_SESSION['stock_message'] = "❌ Stocks cannot be greater than Ceiling Point.";
    header('Location: inventory.php?ap=error');
    exit;
}

/* ---------- Resolve category name ---------- */
$stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$categoryRow  = $stmt->get_result()->fetch_assoc();
$categoryName = $categoryRow['category_name'] ?? '';
$stmt->close();

/* ---------- Tire rule: auto +5 years if category contains 'tire' and no expiration provided ---------- */
if (stripos($categoryName, 'tire') !== false && empty($expiration)) {
    $dt = new DateTime();
    $dt->modify('+5 years');
    $expiration = $dt->format('Y-m-d');
}

/* ---------- Server-driven expiry_required ---------- */
$expiryRequired = !empty($expiration) ? 1 : 0;

/* ---------- Validate expiration if provided ---------- */
if (!empty($expiration)) {
    $dt = DateTime::createFromFormat('Y-m-d', $expiration);
    if (!($dt && $dt->format('Y-m-d') === $expiration)) {
        $_SESSION['stock_message'] = "❌ Invalid expiration date. Use YYYY-MM-DD.";
        header("Location: inventory.php?ap=error");
        exit;
    }
    // Optional: disallow past dates
    // if ($expiration < date('Y-m-d')) { ... reject ... }
}

/* ---------- Uniqueness checks ---------- */
// Global barcode uniqueness (if provided)
if ($barcode !== '') {
    $check = $conn->prepare("SELECT product_id FROM products WHERE barcode = ?");
    $check->bind_param("s", $barcode);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $_SESSION['stock_message'] = "❌ This barcode already exists. Please use a unique barcode.";
        $check->close();
        header("Location: inventory.php?ap=error");
        exit;
    }
    $check->close();
}

// Per-branch duplication check (product with same barcode already in branch inventory)
if ($barcode !== '') {
    $check = $conn->prepare("
        SELECT p.product_id 
        FROM products p
        JOIN inventory i ON p.product_id = i.product_id
        WHERE p.barcode = ? AND i.branch_id = ?
    ");
    $check->bind_param("si", $barcode, $branchId);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $_SESSION['stock_message'] = "❌ This product already exists in this branch.";
        $check->close();
        header("Location: inventory.php?ap=error");
        exit();
    }
    $check->close();
}

/* ---------- Begin transaction for atomicity ---------- */
$conn->begin_transaction();

try {
    // Insert product
    $barcodeParam    = ($barcode !== '') ? $barcode : null;         // allow NULL
    $expirationParam = (!empty($expiration)) ? $expiration : null;  // allow NULL

    $stmt = $conn->prepare("
        INSERT INTO products 
            (barcode, product_name, category, price, markup_price, retail_price,
             ceiling_point, critical_point, vat, expiration_date, expiry_required, brand_name)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    // Types: s,s,s,d,d,d,i,i,d,s,i,s  -> "sssdddiidsis"
    $stmt->bind_param(
        "sssdddiidsis",
        $barcodeParam,     // s
        $productName,      // s
        $categoryName,     // s
        $price,            // d
        $markupPrice,      // d
        $retailPrice,      // d
        $ceilingPoint,     // i
        $criticalPoint,    // i
        $vat,              // d
        $expirationParam,  // s (nullable)
        $expiryRequired,   // i (0/1)
        $brandName         // s
    );
    $stmt->execute();
    $productId = (int)$conn->insert_id;
    $stmt->close();

    // Auto-generate barcode if blank
    if ($barcode === '') {
        $auto = makeEan13FromId($productId);
        $attempts = 0;
        do {
            $ok = true;
            $u = $conn->prepare("UPDATE products SET barcode=? WHERE product_id=?");
            $u->bind_param("si", $auto, $productId);
            try {
                $u->execute();
            } catch (mysqli_sql_exception $e) {
                // Rare: collision on UNIQUE(barcode)
                if (stripos($e->getMessage(), 'Duplicate') !== false && $attempts < 3) {
                    $attempts++;
                    $auto = substr($auto, 0, 12) . (($auto[12] ?? '0') + $attempts); // adjust last digit
                    $ok = false;
                } else {
                    throw $e;
                }
            }
            $u->close();
        } while (!$ok);
    }

    // Insert opening inventory
    $stmt2 = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
    $stmt2->bind_param("iii", $productId, $branchId, $stocks);
    $stmt2->execute();
    $stmt2->close();

    // OPTIONAL: seed initial lot if we have both opening stock and an expiration
    if ($stocks > 0 && $expiryRequired === 1 && !empty($expirationParam)) {
        // Ensure inventory_lots has UNIQUE(product_id, branch_id, expiry_date)
        $lot = $conn->prepare("
            INSERT INTO inventory_lots (product_id, branch_id, expiry_date, qty)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)
        ");
        $lot->bind_param("iisi", $productId, $branchId, $expirationParam, $stocks);
        $lot->execute();
        $lot->close();
    }

    // Log + commit
    logAction($conn, "Add Product", "Added product '$productName' (ID: $productId) with stock $stocks to branch $branchId");
    $conn->commit();

    $_SESSION['stock_message'] = "✅ Product '$productName' added successfully with stock: $stocks (Branch ID: $branchId)"
        . ($expiryRequired ? " — expiry tracking enabled" : "");

    header('Location: inventory.php?ap=added');
    exit;

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    $_SESSION['stock_message'] = "❌ Database error: " . $e->getMessage();
    header("Location: inventory.php?ap=error");
    exit;
}
