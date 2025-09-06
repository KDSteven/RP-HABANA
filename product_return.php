<?php
session_start();
include 'config/db.php';

// Only logged-in users allowed
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$branch_id = $_SESSION['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_id = (int)($_POST['sale_id'] ?? 0);
    $refund_reason = trim($_POST['refund_reason'] ?? '');
    $refund_amount = (float)($_POST['refund_amount'] ?? 0);

    // 1️⃣ Check if sale exists
    $sale_check = $conn->prepare("SELECT status FROM sales WHERE sale_id = ?");
    $sale_check->bind_param("i", $sale_id);
    $sale_check->execute();
    $sale_result = $sale_check->get_result()->fetch_assoc();

    if (!$sale_result) {
        die("Sale not found.");
    }

    // 2️⃣ Prevent double full refund
    if ($sale_result['status'] === 'Refunded') {
        die("This sale has already been fully refunded.");
    }

    // 3️⃣ Insert into sales_refunds
    $stmt = $conn->prepare("
        INSERT INTO sales_refunds (sale_id, refunded_by, refund_reason, refund_amount, refund_date)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iisd", $sale_id, $user_id, $refund_reason, $refund_amount);
    $stmt->execute();

    // 4️⃣ Update inventory for each item in sale
    $items_stmt = $conn->prepare("SELECT product_id, quantity FROM sales_items WHERE sale_id = ?");
    $items_stmt->bind_param("i", $sale_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();

    $update_inventory = $conn->prepare("
        UPDATE inventory SET stock = stock + ? WHERE product_id = ? AND branch_id = ?
    ");

    while ($item = $items_result->fetch_assoc()) {
        $update_inventory->bind_param("iii", $item['quantity'], $item['product_id'], $branch_id);
        $update_inventory->execute();
    }

    // 5️⃣ Update sale status if full refund
    // Optional: you can check if refund_amount >= total sale amount for partial vs full
    $conn->query("UPDATE sales SET status = 'Refunded' WHERE sale_id = $sale_id");

    // 6️⃣ Redirect with success
    header("Location: history.php?msg=Refund processed successfully");
    exit;
}
?>
