<?php
session_start();
include 'config/db.php';
include 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode = trim($_POST['barcode'] ?? '');
    $product_id = $_POST['product_id'] ?? null;
    $stock_amount = (int) ($_POST['stock_amount'] ?? 0);

    // Determine branch based on role
    if ($_SESSION['role'] === 'admin') {
        $branch_id = $_SESSION['current_branch_id'] ?? ($_POST['branch_id'] ?? null);
    } else {
        $branch_id = $_SESSION['branch_id'] ?? null;
    }

    // Safety check
    if (!$branch_id) {
        die("No branch selected or found. (role: {$_SESSION['role']})");
    }

    if ($stock_amount <= 0) {
        die("Invalid stock amount.");
    }

    // Find product either by barcode or product_id
    if (!empty($barcode)) {
        $stmt = $conn->prepare("SELECT product_id, product_name FROM products WHERE barcode = ? LIMIT 1");
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $stmt->bind_result($product_id, $product_name);
        if (!$stmt->fetch()) {
            die("Product with this barcode not found.");
        }
        $stmt->close();
    } else {
        // Lookup product name for logging
        $stmt = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->bind_result($product_name);
        $stmt->fetch();
        $stmt->close();
    }

    if (!$product_id) {
        die("No product selected or found.");
    }

    // Update or insert stock
    $stmt = $conn->prepare("SELECT inventory_id, stock FROM inventory WHERE product_id=? AND branch_id=? LIMIT 1");
    $stmt->bind_param("ii", $product_id, $branch_id);
    $stmt->execute();
    $stmt->bind_result($inventory_id, $current_stock);

    if ($stmt->fetch()) {
        $stmt->close();
        $newStock = $current_stock + $stock_amount;
        $stmt = $conn->prepare("UPDATE inventory SET stock=? WHERE inventory_id=?");
        $stmt->bind_param("ii", $newStock, $inventory_id);
        $stmt->execute();
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $product_id, $branch_id, $stock_amount);
        $stmt->execute();
        $newStock = $stock_amount;
        $current_stock = 0;
    }

    // Log action in general logs
    logAction($conn, "Add Stock", "Added $stock_amount stock to $product_name (ID: $product_id)", null, $branch_id);

    // Log in inventory_movements (very important for tracking history)
    $stmt = $conn->prepare("
        INSERT INTO inventory_movements (product_id, branch_id, quantity, movement_type, user_id, notes)
        VALUES (?, ?, ?, 'STOCK_IN', ?, ?)
    ");
    $notes = "Stock in via Add Stock module";
    $stmt->bind_param("iiiss", $product_id, $branch_id, $stock_amount, $_SESSION['user_id'], $notes);
    $stmt->execute();

    // Session confirmation message
    if (isset($newStock) && isset($current_stock)) {
        $_SESSION['stock_message'] = "✅ $product_name stock updated successfully.<br>Old stock: $current_stock → New stock: $newStock (Branch ID: $branch_id)";
    } else {
        $_SESSION['stock_message'] = "✅ $product_name stock added successfully.<br>Initial stock: $stock_amount (Branch ID: $branch_id)";
    }

    header("Location: inventory.php?stock=success");
    exit;
}
?>
