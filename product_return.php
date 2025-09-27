<?php
session_start();
include 'config/db.php';

// Only logged-in users allowed
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: index.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_id = (int)($_POST['sale_id'] ?? 0);
    $refund_reason = trim($_POST['refund_reason'] ?? '');
    $refund_products = $_POST['refund_items'] ?? [];
    $refund_services = $_POST['refund_services'] ?? [];

    if (!$sale_id || (empty($refund_products) && empty($refund_services))) {
        die("Invalid refund request.");
    }

    // Fetch sale (include VAT column!)
    $sale = $conn->query("SELECT total, vat, branch_id, status FROM sales WHERE sale_id = $sale_id")->fetch_assoc();
    if (!$sale) die("Sale not found.");
    if ($sale['status'] === 'Refunded') die("Sale already fully refunded.");

    $branch_id = $sale['branch_id'];
    $refundAmount = 0; // net refund
    $refundVAT = 0;    // VAT refund

    $totalSale = (float)$sale['total'];
    $totalVAT  = (float)$sale['vat'];   // total VAT stored at sale time
    $netSale   = $totalSale - $totalVAT; // net of VAT

    // Start transaction
    $conn->begin_transaction();

    try {
        // Prepare statements
        $insertRefund = $conn->prepare("
            INSERT INTO sales_refunds 
                (sale_id, refunded_by, refund_reason, refund_amount, refund_vat, refund_date)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $updateInventory = $conn->prepare("
            UPDATE inventory 
            SET stock = stock + ? 
            WHERE product_id = ? AND branch_id = ?
        ");

        // --- Refund products ---
        foreach ($refund_products as $product_id => $qty) {
            $product_id = (int)$product_id;
            $qty = (int)$qty;
            if ($qty <= 0) continue;

            $sold = $conn->query("SELECT quantity, price FROM sales_items WHERE sale_id = $sale_id AND product_id = $product_id")->fetch_assoc();
            if (!$sold) continue;

            $qty = min($qty, (int)$sold['quantity']); // prevent over-refund
            $subtotal = $sold['price'] * $qty;

            // Proportional VAT share based on subtotal vs. netSale
            $vatShare = ($netSale > 0) ? ($subtotal / $netSale) * $totalVAT : 0;

            $refundAmount += $subtotal;
            $refundVAT += $vatShare;

            // Update inventory
            $updateInventory->bind_param("iii", $qty, $product_id, $branch_id);
            $updateInventory->execute();
        }

        // --- Refund services ---
        foreach ($refund_services as $service_id => $qty) {
            $service_id = (int)$service_id;
            $qty = (int)$qty;
            if ($qty <= 0) continue;

            $service = $conn->query("SELECT price FROM sales_services WHERE sale_id = $sale_id AND service_id = $service_id")->fetch_assoc();
            if (!$service) continue;

            $subtotal = $service['price'] * $qty;
            $vatShare = ($netSale > 0) ? ($subtotal / $netSale) * $totalVAT : 0;

            $refundAmount += $subtotal;
            $refundVAT += $vatShare;
        }

        // Insert refund record (with VAT)
        $insertRefund->bind_param("iisdd", $sale_id, $user_id, $refund_reason, $refundAmount, $refundVAT);
        $insertRefund->execute();

        // Update sale status
        $refundedTotal = $refundAmount + $refundVAT;
        if ($refundedTotal >= $sale['total']) {
            $conn->query("UPDATE sales SET status = 'Refunded' WHERE sale_id = $sale_id");
        } else {
            $conn->query("UPDATE sales SET status = 'Partial Refund' WHERE sale_id = $sale_id");
        }

        $conn->commit();
        header("Location: history.php?msg=Refund processed successfully");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("Refund failed: " . $e->getMessage());
    }
}
?>
