<?php
session_start();
include 'config/db.php';

header('Content-Type: application/json');

// Only logged-in users
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$branch_id = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

if ($branch_id <= 0 || empty($_POST['physical_count']) || !is_array($_POST['physical_count'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request payload']);
    exit;
}
// logging
function logAction($conn, $action, $details, $user_id = null, $branch_id = null) {
    if (!$user_id && isset($_SESSION['user_id'])) $user_id = $_SESSION['user_id'];
    if (!$branch_id && isset($_SESSION['branch_id'])) $branch_id = $_SESSION['branch_id'];

    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, timestamp, branch_id) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("issi", $user_id, $action, $details, $branch_id);
    $stmt->execute();
    $stmt->close();
}


$saved = 0;
$skipped = 0;
$notFound = 0;

foreach ($_POST['physical_count'] as $product_id => $physical_count_raw) {
    $product_id = (int)$product_id;

    // Treat empty string as NULL (Pending)
    $isBlank = ($physical_count_raw === '' || $physical_count_raw === null);
    $physical_count = $isBlank ? null : (int)$physical_count_raw;

    // Get current system stock
    $stmt = $conn->prepare("SELECT stock FROM inventory WHERE product_id=? AND branch_id=? LIMIT 1");
    $stmt->bind_param("ii", $product_id, $branch_id);
    $stmt->execute();
    $stmt->bind_result($system_stock);
    $rowOk = $stmt->fetch();
    $stmt->close();

    if (!$rowOk) { // inventory row not found
        $notFound++;
        continue;
    }

    // Compute discrepancy (always positive for storage) + status
    if ($physical_count === null) {
        $discrepancy = 0;
        $status = 'Pending';
    } else {
        $diff = $physical_count - (int)$system_stock;
        $discrepancy = abs($diff);
        if ($diff > 0)       $status = 'Overstock';
        elseif ($diff < 0)   $status = 'Understock';
        else                 $status = 'Complete';
    }

    // Check latest log to avoid duplicate entries (same physical_count)
    $check = $conn->prepare("
        SELECT physical_count 
        FROM physical_inventory 
        WHERE product_id=? AND branch_id=?
        ORDER BY count_date DESC 
        LIMIT 1
    ");
    $check->bind_param("ii", $product_id, $branch_id);
    $check->execute();
    $check->bind_result($last_count);
    $hasLast = $check->fetch();
    
    $check->close();

    if ($hasLast && ((string)$last_count === (string)$physical_count_raw)) {
        $skipped++;
        continue;
    }

    // Insert new log
$insert = $conn->prepare("
    INSERT INTO physical_inventory
        (product_id, system_stock, physical_count, discrepancy, status, counted_by, branch_id, count_date)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");

// Handle NULL properly
$pc_param = $physical_count;
if ($pc_param === null) {
    // Bind NULL explicitly using 's' type (MySQL will coerce NULL)
    $insert->bind_param(
        "iiisisi",
        $product_id,
        $system_stock,
        $pc_param,
        $discrepancy,
        $status,
        $user_id,
        $branch_id
    );
} else {
    // Normal integer bind
    $insert->bind_param(
        "iiiisii",
        $product_id,
        $system_stock,
        $pc_param,
        $discrepancy,
        $status,
        $user_id,
        $branch_id
    );
}

// Execute and check errors
   if ($insert->execute()) {
        $saved++;
        logAction($conn, "Physical Inventory Saved", "Saved product_id={$product_id}, physical_count={$physical_count}, status={$status}", $user_id, $branch_id);
    } else {
        $skipped++;
        logAction($conn, "Physical Inventory Error", "Failed to insert product_id={$product_id}: ".$insert->error, $user_id, $branch_id);
    }

    $insert->close();
}

echo json_encode([
    'success'   => true,
    'message'   => "Saved: $saved, Skipped: $skipped" . ($notFound ? ", Not found: $notFound" : ""),
    'saved'     => $saved,
    'skipped'   => $skipped,
    'not_found' => $notFound
]);
exit;

?>
