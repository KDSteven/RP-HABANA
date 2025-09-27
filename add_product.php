<?php
// add_product.php
session_start();
include 'config/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$price         = isset($_POST['price']) ? (float)$_POST['price'] : null;
$markup        = isset($_POST['markup_price']) ? (float)$_POST['markup_price'] : null;
$retail        = isset($_POST['retail_price']) ? (float)$_POST['retail_price'] : null;
$ceiling       = isset($_POST['ceiling_point']) ? (int)$_POST['ceiling_point'] : null;
$critical      = isset($_POST['critical_point']) ? (int)$_POST['critical_point'] : null;
$stocks        = isset($_POST['stocks']) ? (int)$_POST['stocks'] : null;
$vat           = isset($_POST['vat']) ? (float)$_POST['vat'] : null;

$nums = [
  'price'          => $price,
  'markup_price'   => $markup,
  'retail_price'   => $retail,
  'ceiling_point'  => $ceiling,
  'critical_point' => $critical,
  'stocks'         => $stocks,
  'vat'            => $vat,
];

foreach ($nums as $k => $v) {
  if ($v === null || !is_numeric($v) || $v < 0) {
    // redirect with a flash toast
    header("Location: inventory.php?ap=error");
    exit;
  }
}

// Logical guard
if ($critical > $ceiling) {
  header("Location: inventory.php?ap=error");
  exit;
}

// Optionally recompute retail to avoid trusting client value
$retail = $price + ($price * ($markup / 100));
if ($retail < 0) { // paranoia
  header("Location: inventory.php?ap=error");
  exit;
}

/**
 * Logging helper (kept from your version)
 */
function logAction($conn, $action, $details, $user_id = null, $branch_id = null) {
    if (!$user_id && isset($_SESSION['user_id'])) $user_id = $_SESSION['user_id'];
    if (!$branch_id && isset($_SESSION['branch_id'])) $branch_id = $_SESSION['branch_id'];
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, timestamp, branch_id) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("issi", $user_id, $action, $details, $branch_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * =========================
 * BARCODE HELPERS (place here)
 * =========================
 */

// EAN-13 checksum + builder (numeric only) – use if you prefer EAN-13 labels
function ean13CheckDigit(string $base12): int {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $d = (int)$base12[$i];
        $sum += ($i % 2 === 0) ? $d : $d * 3;
    }
    return (10 - ($sum % 10)) % 10;
}
function makeEan13FromId(int $productId): string {
    // 200 = internal prefix (not GS1-registered). For retail, register GS1.
    $base12 = '200' . str_pad((string)$productId, 9, '0', STR_PAD_LEFT);
    return $base12 . ean13CheckDigit($base12);
}

/**
 * Handle form submit
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect POST data
    $barcode       = trim($_POST['barcode'] ?? '');
    $productName   = trim($_POST['product_name'] ?? '');
    $categoryId    = intval($_POST['category_id'] ?? 0);
    $price         = floatval($_POST['price'] ?? 0);
    $markupPrice   = floatval($_POST['markup_price'] ?? 0);
    $retailPrice   = $price + ($price * $markupPrice / 100);
    $ceilingPoint  = intval($_POST['ceiling_point'] ?? 0);
    $criticalPoint = intval($_POST['critical_point'] ?? 0);
    $vat           = floatval($_POST['vat'] ?? 0);
    $stocks        = intval($_POST['stocks'] ?? 0);
    $branchId      = intval($_POST['branch_id'] ?? 0);
    $brandName     = trim($_POST['brand_name'] ?? '');
    $expiration    = $_POST['expiration_date'] ?? null; // may be null/''

    // Get category name (NOT the id) to save in products.category
    $stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $categoryRow  = $stmt->get_result()->fetch_assoc();
    $categoryName = $categoryRow['category_name'] ?? '';
    $stmt->close();

    // Expiration logic for tires (auto +5 years if missing)
    if (stripos($categoryName, 'tire') !== false && empty($expiration)) {
        $dt = new DateTime();
        $dt->modify('+5 years');
        $expiration = $dt->format('Y-m-d');
    }

    // Validate barcode uniqueness per branch (only if you have inventory table; keep as-is)
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
        $_SESSION['stock_message'] = "❌ This product already exists in this branch. Please use a different barcode or branch.";
        $check->close();
        header("Location: inventory.php?stock=error");
        exit();
    }
    $check->close();

    // Basic validations
    if ($criticalPoint > $ceilingPoint) {
        $_SESSION['stock_message'] = "❌ Critical Point cannot be greater than Ceiling Point.";
        header("Location: inventory.php?stock=error");
        exit();
    }
    if ($stocks > $ceilingPoint) {
        $_SESSION['stock_message'] = "❌ Stocks cannot be greater than Ceiling Point.";
        header("Location: inventory.php?stock=error");
        exit();
    }
    if ($stocks < 0 || $price < 0) {
        $_SESSION['stock_message'] = "❌ Invalid values for stock or price.";
        header("Location: inventory.php?stock=error");
        exit();
    }

    // Global barcode uniqueness (only if barcode is provided)
    if ($barcode !== '') {
        $check = $conn->prepare("SELECT product_id FROM products WHERE barcode = ?");
        $check->bind_param("s", $barcode);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $_SESSION['stock_message'] = "❌ This barcode already exists. Please use a unique barcode.";
            $check->close();
            header("Location: inventory.php?stock=error");
            exit();
        }
        $check->close();
    }

    try {
        // --- FIX STARTS HERE: use correct variables and one INSERT that allows NULLs ---
        $barcodeParam    = ($barcode !== '') ? $barcode : null;                          // allow NULL barcode
        $expirationParam = (!empty($expiration)) ? $expiration : null;                   // allow NULL expiration

        $stmt = $conn->prepare("
            INSERT INTO products 
                (barcode, product_name, category, price, markup_price, retail_price,
                 ceiling_point, critical_point, vat, expiration_date, brand_name)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");
        // 11 placeholders → types: s,s,s,d,d,d,i,i,d,s,s
        $stmt->bind_param(
            "sssdddiidss",
            $barcodeParam,     // s (nullable)
            $productName,      // s
            $categoryName,     // s  <-- correct variable
            $price,            // d
            $markupPrice,      // d
            $retailPrice,      // d
            $ceilingPoint,     // i
            $criticalPoint,    // i
            $vat,              // d
            $expirationParam,  // s (nullable) <-- correct variable
            $brandName         // s
        );
        $stmt->execute();
        $productId = (int)$conn->insert_id;
        $stmt->close();
        // --- FIX ENDS HERE ---

        // If barcode was left blank, auto-generate now using the new product_id
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
                    if (stripos($e->getMessage(), 'Duplicate') !== false && $attempts < 3) {
                        $attempts++;
                        $auto = $auto . $attempts; // nudge and retry
                        $ok = false;
                    } else {
                        throw $e;
                    }
                }
                $u->close();
            } while (!$ok);
        }

        // Insert opening inventory for the chosen branch
        $stmt2 = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
        $stmt2->bind_param("iii", $productId, $branchId, $stocks);

        if ($stmt2->execute()) {
            $stmt2->close();

            // Log action
            logAction($conn, "Add Product", "Added product '$productName' with stock $stocks to branch $branchId");

            $_SESSION['stock_message'] = "✅ Product '$productName' added successfully with stock: $stocks (Branch ID: $branchId)";
            header('Location: inventory.php?ap=added');
            exit();
        } else {
            $_SESSION['stock_message'] = "❌ Error adding to inventory: " . $stmt2->error;
            $stmt2->close();
            header('Location: inventory.php?ap=error');
            exit();
        }

    } catch (mysqli_sql_exception $e) {
        $_SESSION['stock_message'] = "❌ Database error: " . $e->getMessage();
        header("Location: inventory.php?stock=error");
        exit();
    }
}
?>
