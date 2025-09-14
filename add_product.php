<?php
session_start();
include 'config/db.php';

// Logging function
function logAction($conn, $action, $details, $user_id = null, $branch_id = null) {
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    if (!$branch_id && isset($_SESSION['branch_id'])) {
        $branch_id = $_SESSION['branch_id'];
    }

    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, timestamp, branch_id) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("issi", $user_id, $action, $details, $branch_id);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode       = trim($_POST['barcode']);
    $productName   = trim($_POST['product_name'] ?? '');
    $category      = trim($_POST['category'] ?? '');
    $price         = floatval($_POST['price'] ?? 0);
    $markupPrice   = floatval($_POST['markup_price'] ?? 0);
    $retailPrice   = $price + ($price * ($markupPrice / 100));
    $ceilingPoint  = intval($_POST['ceiling_point'] ?? 0);
    $criticalPoint = intval($_POST['critical_point'] ?? 0);
    $vat           = floatval($_POST['vat'] ?? 0);
$expirationDate = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : NULL;
    $brandName     = trim($_POST['brand_name'] ?? '');

    // ✅ Stocks & Branch ID
    $stocks   = intval($_POST['stocks'] ?? 0);
    $branchId = $_SESSION['branch_id'] ?? ($_POST['branch_id'] ?? null);

    // Validate barcode uniqueness
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

    // Validation
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

    try {
        // Insert into products
        if ($expirationDate === null) {
            $stmt = $conn->prepare("INSERT INTO products 
                (barcode, product_name, category, price, markup_price, retail_price, ceiling_point, critical_point, vat, expiration_date, brand_name) 
                VALUES (?,?,?,?,?,?,?,?,?,NULL,?)");

            $stmt->bind_param(
                "sssdddiids", 
                $barcode, $productName, $category,
                $price, $markupPrice, $retailPrice,
                $ceilingPoint, $criticalPoint, $vat,
                $brandName
            );
        } else {
            $stmt = $conn->prepare("INSERT INTO products 
                (barcode, product_name, category, price, markup_price, retail_price, ceiling_point, critical_point, vat, expiration_date, brand_name) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?)");

            $stmt->bind_param(
                "sssdddiidss", 
                $barcode, $productName, $category,
                $price, $markupPrice, $retailPrice,
                $ceilingPoint, $criticalPoint, $vat,
                $expirationDate, $brandName
            );
        }

        $stmt->execute();
        $productId = $conn->insert_id;
        $stmt->close();

        // Insert into inventory
        $stmt2 = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
        $stmt2->bind_param("iii", $productId, $branchId, $stocks);

        if ($stmt2->execute()) {
            $stmt2->close();

            // Log action
            logAction($conn, "Add Product", "Added product '$productName' (ID: $productId) with stock $stocks to branch ID $branchId");

            $_SESSION['stock_message'] = "✅ Product '$productName' added successfully with stock: $stocks (Branch ID: $branchId)";
            header("Location: inventory.php?stock=success");
            exit();
        } else {
            $_SESSION['stock_message'] = "❌ Error adding to inventory: " . $stmt2->error;
            $stmt2->close();
            header("Location: inventory.php?stock=error");
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['stock_message'] = "❌ Database error: " . $e->getMessage();
        header("Location: inventory.php?stock=error");
        exit();
    }
}

?>
