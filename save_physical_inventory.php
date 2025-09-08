<?php
session_start();
include 'config/db.php';

// Only logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$branch_id = $_POST['branch_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['physical_count'])) {
    foreach ($_POST['physical_count'] as $product_id => $physical_count) {
        $physical_count = (int)$physical_count;

        // Get current system stock
        $stmt = $conn->prepare("SELECT stock FROM inventory WHERE product_id=? AND branch_id=? LIMIT 1");
        $stmt->bind_param("ii", $product_id, $branch_id);
        $stmt->execute();
        $stmt->bind_result($system_stock);
        $stmt->fetch();
        $stmt->close();

        if ($system_stock === null) continue;

        // Calculate discrepancy and status
        $discrepancy = $physical_count - $system_stock;
        $status = ($discrepancy === 0) ? 'Match' : 'Mismatch';

        // Check latest log to avoid duplicate entries
        $check = $conn->prepare("
            SELECT physical_count 
            FROM physical_inventory 
            WHERE product_id=? AND branch_id=? 
            ORDER BY count_date DESC LIMIT 1
        ");
        $check->bind_param("ii", $product_id, $branch_id);
        $check->execute();
        $check->bind_result($last_count);
        $check->fetch();
        $check->close();

        // Only insert if changed OR first entry
        if ($last_count !== null && $last_count == $physical_count) {
            continue; // no change
        }

        // Insert log
        $insert = $conn->prepare("
            INSERT INTO physical_inventory
            (product_id, system_stock, physical_count, discrepancy, status, counted_by, branch_id, count_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $insert->bind_param(
            "iiiisii",
            $product_id,
            $system_stock,
            $physical_count,
            $discrepancy,
            $status,
            $user_id,
            $branch_id
        );
        $insert->execute();
        $insert->close();
    }

    header("Location: physical_inventory.php?success=1");
    exit;
}
// Print debug logs into browser console
if (!empty($debug_logs)) {
    echo "<script>";
    foreach ($debug_logs as $log) {
        echo "console.log(" . json_encode($log) . ");";
    }
    echo "</script>";
}
header("Location: physical_inventory.php?error=1");
exit;
?>
