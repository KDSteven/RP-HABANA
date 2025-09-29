<?php
session_start();
include 'config/db.php';

// ---------------- Authorization ----------------
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: index.html");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'];

// ---------------- Refund Logic ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_id         = (int)($_POST['sale_id'] ?? 0);
    $refund_reason   = trim($_POST['refund_reason'] ?? '');
    $refund_products = $_POST['refund_items'] ?? [];
    $refund_services = $_POST['refund_services'] ?? [];

    if (!$sale_id || (empty($refund_products) && empty($refund_services))) {
        die("Invalid refund request.");
    }

    // Fetch sale (must include VAT column)
    $sale = $conn->query("
        SELECT total, vat, branch_id, status 
        FROM sales 
        WHERE sale_id = $sale_id
    ")->fetch_assoc();

    if (!$sale) die("Sale not found.");
    if ($sale['status'] === 'Refunded') die("Sale already fully refunded.");

    $branch_id = (int)$sale['branch_id'];
    $totalSale = (float)$sale['total'];

    $refundAmount = 0.0; // net refund (ex VAT)
    $refundVAT    = 0.0; // VAT refund

    // ---------------- Transaction Start ----------------
    $conn->begin_transaction();

    try {
        // Inventory update statement
        $updateInventory = $conn->prepare("
            UPDATE inventory 
            SET stock = stock + ? 
            WHERE product_id = ? AND branch_id = ?
        ");

        // --- Refund Products ---
        foreach ($refund_products as $product_id => $qty) {
            $product_id = (int)$product_id;
            $qty = (int)$qty;
            if ($qty <= 0) continue;

            $sold = $conn->query("
                SELECT quantity, price 
                FROM sales_items 
                WHERE sale_id = $sale_id AND product_id = $product_id
            ")->fetch_assoc();
            if (!$sold) continue;

            $qty = min($qty, (int)$sold['quantity']); // prevent over-refund

            // --- VAT Calculation (treat price as net, then add 12%) ---
            $subtotalNet   = $sold['price'] * $qty; 
            $vatPortion    = $subtotalNet * 0.12;
            $subtotalTotal = $subtotalNet + $vatPortion;

            $refundAmount += $subtotalNet;
            $refundVAT    += $vatPortion;

            // Return stock
            $updateInventory->bind_param("iii", $qty, $product_id, $branch_id);
            $updateInventory->execute();
        }

        // --- Refund Services ---
        foreach ($refund_services as $service_id => $qty) {
            $service_id = (int)$service_id;
            $qty = (int)$qty;
            if ($qty <= 0) continue;

            $service = $conn->query("
                SELECT price 
                FROM sales_services 
                WHERE sale_id = $sale_id AND service_id = $service_id
            ")->fetch_assoc();
            if (!$service) continue;

            // --- VAT Calculation (same as products) ---
            $subtotalNet   = $service['price'] * $qty; 
            $vatPortion    = $subtotalNet * 0.12;
            $subtotalTotal = $subtotalNet + $vatPortion;

            $refundAmount += $subtotalNet;
            $refundVAT    += $vatPortion;
        }

        // --- Insert Refund Record ---
        $refundTotal = $refundAmount + $refundVAT;

        $insertRefund = $conn->prepare("
            INSERT INTO sales_refunds 
                (sale_id, refunded_by, refund_amount, refund_vat, refund_reason, refund_date, refund_total)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        $insertRefund->bind_param(
            "iiddsd",
            $sale_id,
            $user_id,
            $refundAmount,
            $refundVAT,
            $refund_reason,
            $refundTotal
        );
        $insertRefund->execute();

        // --- Update Sale Status ---
        if ($refundTotal >= $totalSale) {
            $conn->query("UPDATE sales SET status = 'Refunded' WHERE sale_id = $sale_id");
        } else {
            $conn->query("UPDATE sales SET status = 'Partial Refund' WHERE sale_id = $sale_id");
        }

        // Commit
        $conn->commit();
        header("Location: history.php?msg=Refund processed successfully");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("Refund failed: " . $e->getMessage());
    }
}
?>
