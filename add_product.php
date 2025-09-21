<?php
session_start();
include 'config/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Logging function
function logAction($conn, $action, $details, $user_id = null, $branch_id = null) {
    if (!$user_id && isset($_SESSION['user_id'])) $user_id = $_SESSION['user_id'];
    if (!$branch_id && isset($_SESSION['branch_id'])) $branch_id = $_SESSION['branch_id'];
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, timestamp, branch_id) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("issi", $user_id, $action, $details, $branch_id);
    $stmt->execute();
    $stmt->close();
}

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
    $expiration    = $_POST['expiration_date'] ?? null;

    // Get category name
    $stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $categoryRow = $stmt->get_result()->fetch_assoc();
    $categoryName = $categoryRow['category_name'] ?? '';
    $stmt->close();

    // Expiration logic for tires
    if (stripos($categoryName, 'tire') !== false && empty($expiration)) {
        $dt = new DateTime();
        $dt->modify('+5 years');
        $expiration = $dt->format('Y-m-d');
    }

    // Debug: show POST data
    // echo '<pre>'; print_r($_POST); echo '</pre>'; exit;

    // Barcode uniqueness
   // Validate barcode uniqueness per branch
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


    // Validation
    if ($criticalPoint > $ceilingPoint) die("❌ Critical > Ceiling");
    if ($stocks > $ceilingPoint) die("❌ Stocks > Ceiling");
    if ($stocks < 0 || $price < 0) die("❌ Invalid values");

    // Insert product
    $stmt = $conn->prepare("INSERT INTO products 
        (barcode, product_name, category, price, markup_price, retail_price, ceiling_point, critical_point, vat, expiration_date, brand_name) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdddiidss", $barcode, $productName, $categoryName, $price, $markupPrice, $retailPrice, $ceilingPoint, $criticalPoint, $vat, $expiration, $brandName);
    $stmt->execute();
    $productId = $conn->insert_id;
    $stmt->close();

    // Insert inventory
    $stmt = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $productId, $branchId, $stocks);
    $stmt->execute();
    $stmt->close();

    // Log action
    logAction($conn, "Add Product", "Added product '$productName' with stock $stocks to branch $branchId");

    echo "✅ Product '$productName' added successfully!";
}
