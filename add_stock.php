<?php
session_start();
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id   = (int)($_POST['product_id'] ?? 0);
    $branch_id    = (int)($_POST['branch_id'] ?? 0);
    $stock_amount = (int)($_POST['stock_amount'] ?? 0);
    $user_id      = $_SESSION['user_id'] ?? 0;

    if ($product_id > 0 && $stock_amount > 0) {
        // Get current stock
        $current_stock = 0;
        $stmt = $conn->prepare("SELECT stock FROM inventory WHERE product_id = ? AND branch_id = ?");
        $stmt->bind_param("ii", $product_id, $branch_id);
        $stmt->execute();
        $stmt->bind_result($current_stock);
        $stmt->fetch();
        $stmt->close();

        // Update stock
        $stmt = $conn->prepare("UPDATE inventory SET stock = stock + ? WHERE product_id = ? AND branch_id = ?");
        $stmt->bind_param("iii", $stock_amount, $product_id, $branch_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Stock updated successfully!";

            // ✅ Log the stock addition
            $action = 'stock_added';
            $old_value = json_encode(['stock' => $current_stock]);
            $new_value = json_encode(['stock' => $current_stock + $stock_amount]);

            $log_stmt = $conn->prepare("
                INSERT INTO inventory_logs 
                (branch_id, item_type, item_id, action, quantity_change, old_value, new_value, performed_by) 
                VALUES (?, 'product', ?, ?, ?, ?, ?, ?)
            ");
            $log_stmt->bind_param(
                "iisissi",
                $branch_id,
                $product_id,
                $action,
                $stock_amount,
                $old_value,
                $new_value,
                $user_id
            );
            $log_stmt->execute();
            $log_stmt->close();

        } else {
            $_SESSION['error'] = "Failed to update stock.";
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "Invalid data.";
    }
}

header("Location: inventory.php");
exit;
