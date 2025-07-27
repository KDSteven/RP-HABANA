<?php
session_start();
include 'config/db.php';

$product_id = intval($_POST['product_id']);
$source_branch = intval($_POST['source_branch']);
$destination_branch = intval($_POST['destination_branch']);
$quantity = intval($_POST['quantity']);

if ($product_id <= 0 || $source_branch <= 0 || $destination_branch <= 0 || $quantity <= 0) {
    die("Invalid input.");
}

if ($source_branch === $destination_branch) {
    die("Source and destination cannot be the same.");
}

// ✅ FIXED: Use `stock` instead of `quantity`
$stmt = $conn->prepare("SELECT stock FROM inventory WHERE product_id = ? AND branch_id = ?");
$stmt->bind_param("ii", $product_id, $source_branch);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Product not found in source branch.");
}

$row = $result->fetch_assoc();
$current_stock = intval($row['stock']);

if ($quantity > $current_stock) {
    die("Not enough stock in source branch.");
}

$conn->begin_transaction();

try {
    // ✅ FIXED: Deduct from source using `stock`
    $stmt = $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE product_id = ? AND branch_id = ?");
    $stmt->bind_param("iii", $quantity, $product_id, $source_branch);
    $stmt->execute();

    // ✅ FIXED: Check if destination has product
    $stmt = $conn->prepare("SELECT stock FROM inventory WHERE product_id = ? AND branch_id = ?");
    $stmt->bind_param("ii", $product_id, $destination_branch);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // ✅ FIXED: Update destination's stock
        $stmt = $conn->prepare("UPDATE inventory SET stock = stock + ? WHERE product_id = ? AND branch_id = ?");
        $stmt->bind_param("iii", $quantity, $product_id, $destination_branch);
        $stmt->execute();
    } else {
        // ✅ FIXED: Insert new inventory row with `stock`
        $stmt = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $product_id, $destination_branch, $quantity);
        $stmt->execute();
    }

    // Logging the transfer
    $stmt = $conn->prepare("INSERT INTO transfer_logs (product_id, source_branch, destination_branch, quantity, transfer_date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiii", $product_id, $source_branch, $destination_branch, $quantity);
    $stmt->execute();

    $conn->commit();
    echo "Transfer successful. <a href='transfer.php'>Back</a>";
} catch (Exception $e) {
    $conn->rollback();
    die("Transfer failed: " . $e->getMessage());
}
?>
