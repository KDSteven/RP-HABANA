<?php
session_start();

$branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
if (!$branch_id) {
    die("❌ Invalid branch ID.");
}

include 'config/db.php';

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
    $product_id = intval($_POST['product_id']);
    $product_name = $_POST['product_name'];
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $markup_price = floatval($_POST['markup_price']);
    $retail_price = floatval($_POST['retail_price']);
    $ceiling_point = intval($_POST['ceiling_point']);
    $critical_point = intval($_POST['critical_point']);
    $stock = intval($_POST['stock']);
    $vat = floatval($_POST['vat']);
    $expiration_date = $_POST['expiration_date'] ?: null;

    if ($stock > $ceiling_point) {
        echo "<script>alert('Stock cannot exceed Ceiling Point!'); window.history.back();</script>";
        exit();
    }

    // Fetch old product data for logging
    $stmtOld = $conn->prepare("SELECT product_name, category, price, markup_price, retail_price, ceiling_point, critical_point, vat, expiration_date FROM products WHERE product_id = ?");
    $stmtOld->bind_param("i", $product_id);
    $stmtOld->execute();
    $oldResult = $stmtOld->get_result();
    $oldData = $oldResult->fetch_assoc();
    $stmtOld->close();

    if (!$oldData) {
        die("❌ Product not found.");
    }

    // Update products table
    $product_sql = "UPDATE products SET 
                        product_name = ?, 
                        category = ?, 
                        price = ?, 
                        markup_price = ?, 
                        retail_price = ?, 
                        ceiling_point = ?, 
                        critical_point = ?, 
                        vat = ?, 
                        expiration_date = ?
                    WHERE product_id = ?";
    $stmt1 = $conn->prepare($product_sql);
    $stmt1->bind_param(
        'ssdddiissi',
        $product_name,
        $category,
        $price,
        $markup_price,
        $retail_price,
        $ceiling_point,
        $critical_point,
        $vat,
        $expiration_date,
        $product_id
    );
    $stmt1->execute();

    // Insert or update inventory stock
    $updateStockSQL = "
        INSERT INTO inventory (product_id, branch_id, stock)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE stock = VALUES(stock)";
    $stmt2 = $conn->prepare($updateStockSQL);
    $stmt2->bind_param("iii", $product_id, $branch_id, $stock);
    $stmt2->execute();

    // Build log details by comparing old and new data
    $changes = [];
    $fields = [
        'product_name' => $product_name,
        'category' => $category,
        'price' => $price,
        'markup_price' => $markup_price,
        'retail_price' => $retail_price,
        'ceiling_point' => $ceiling_point,
        'critical_point' => $critical_point,
        'vat' => $vat,
        'expiration_date' => $expiration_date,
        'stock' => $stock,
    ];

    foreach ($fields as $key => $newVal) {
        // oldData keys do not include stock, so fetch stock from inventory
        if ($key === 'stock') {
            // Fetch old stock
            $stmtOldStock = $conn->prepare("SELECT stock FROM inventory WHERE product_id = ? AND branch_id = ?");
            $stmtOldStock->bind_param("ii", $product_id, $branch_id);
            $stmtOldStock->execute();
            $resOldStock = $stmtOldStock->get_result();
            $oldStockRow = $resOldStock->fetch_assoc();
            $oldVal = $oldStockRow ? (int)$oldStockRow['stock'] : 0;
            $stmtOldStock->close();
        } else {
            $oldVal = $oldData[$key] ?? null;
            if ($key === 'expiration_date') {
                // Normalize null/empty
                $oldVal = $oldVal ?: null;
                $newVal = $newVal ?: null;
            }
        }

        if ($oldVal != $newVal) {
            $changes[] = "$key changed from '" . htmlspecialchars($oldVal) . "' to '" . htmlspecialchars($newVal) . "'";
        }
    }

    $changeDetails = count($changes) > 0 ? implode("; ", $changes) : "No changes detected";

    // Log the action
    logAction($conn, "Edit Product", "Edited product ID $product_id: $changeDetails");

    if ($stmt1->affected_rows >= 0 && $stmt2->affected_rows >= 0) {
        echo "<script>alert('Product updated successfully!'); window.location.href = 'inventory.php?branch=$branch_id';</script>";
    } else {
        echo "Error updating product or stock: " . $conn->error;
    }

    $stmt1->close();
    $stmt2->close();
}
?>
