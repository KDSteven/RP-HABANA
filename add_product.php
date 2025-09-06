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
    $productName = trim($_POST['product_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $markupPrice = floatval($_POST['markup_price'] ?? 0);
    $ceilingPoint = intval($_POST['ceiling_point'] ?? 0);
    $criticalPoint = intval($_POST['critical_point'] ?? 0);
    $brandName = trim($_POST['brand_name'] ?? '');
    $stocks = intval($_POST['stocks'] ?? 0);
    $branchId = intval($_POST['branch_id'] ?? 0);

    // Validation
    if ($criticalPoint > $ceilingPoint) {
        $_SESSION['stock_message'] = "❌ Critical Point cannot be greater than Ceiling Point.";
        header("Location: inventory.php?stock=error");
        exit;
    }
    if ($stocks > $ceilingPoint) {
        $_SESSION['stock_message'] = "❌ Stocks cannot be greater than Ceiling Point.";
        header("Location: inventory.php?stock=error");
        exit;
    }
    if ($stocks < 0 || $price < 0) {
        $_SESSION['stock_message'] = "❌ Invalid values for stock or price.";
        header("Location: inventory.php?stock=error");
        exit;
    }

    // Insert into products
    $stmt = $conn->prepare("INSERT INTO products (product_name, category, price, markup_price, ceiling_point, critical_point, brand_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddiis", $productName, $category, $price, $markupPrice, $ceilingPoint, $criticalPoint, $brandName);

    if ($stmt->execute()) {
        $productId = $conn->insert_id;
        $stmt->close();

        // Insert into inventory
        $stmt2 = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
        $stmt2->bind_param("iii", $productId, $branchId, $stocks);

        if ($stmt2->execute()) {
            $stmt2->close();

            // Logging
            logAction($conn, "Add Product", "Added product '$productName' (ID: $productId) with stock $stocks to branch ID $branchId");

            // Session-based success message
            $_SESSION['stock_message'] = "✅ Product '$productName' added successfully with stock: $stocks (Branch ID: $branchId)";
            header("Location: inventory.php?stock=success");
            exit();
        } else {
            $_SESSION['stock_message'] = "❌ Error adding to inventory: " . $stmt2->error;
            $stmt2->close();
            header("Location: inventory.php?stock=error");
            exit();
        }
        
    } else {
        $_SESSION['stock_message'] = "❌ Error adding product: " . $stmt->error;
        header("Location: inventory.php?stock=error");
        exit();
    }


}
?>
