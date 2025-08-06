<?php
session_start();
include 'config/db.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$role = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? null;

// Handle Approve or Reject
if (isset($_POST['action']) && isset($_POST['request_id'])) {
    $action = $_POST['action'];
    $request_id = (int) $_POST['request_id'];

    if (in_array($action, ['approved', 'rejected'])) {
       if ($action === 'approved') {
    // Get transfer details
    $stmt = $conn->prepare("SELECT product_id, source_branch, destination_branch, quantity FROM transfer_requests WHERE request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $transfer = $stmt->get_result()->fetch_assoc();
    
    $product_id = $transfer['product_id'];
    $source_branch = $transfer['source_branch'];
    $destination_branch = $transfer['destination_branch'];
    $quantity = $transfer['quantity'];

    // 1. Reduce stock from source branch
    $stmt = $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE product_id = ? AND branch_id = ?");
    $stmt->bind_param("iii", $quantity, $product_id, $source_branch);
    $stmt->execute();

    // 2. Check if destination branch has the product
    $stmt = $conn->prepare("SELECT stock FROM inventory WHERE product_id = ? AND branch_id = ?");
    $stmt->bind_param("ii", $product_id, $destination_branch);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update stock
        $stmt = $conn->prepare("UPDATE inventory SET stock = stock + ? WHERE product_id = ? AND branch_id = ?");
        $stmt->bind_param("iii", $quantity, $product_id, $destination_branch);
        $stmt->execute();
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $product_id, $destination_branch, $quantity);
        $stmt->execute();
    }

    // 3. Update transfer request status and decision date
    $stmt = $conn->prepare("UPDATE transfer_requests SET status = ?, decision_date = NOW(), decided_by = ? WHERE request_id = ?");
    $stmt->bind_param("sii", $action, $_SESSION['user_id'], $request_id);
    $stmt->execute();

    header("Location: approvals.php?success=approved");
    exit;
}
    }
}

// Fetch Pending Transfer Requests
$requests = $conn->query("
    SELECT tr.*, p.product_name, sb.branch_name AS source_name, db.branch_name AS dest_name, u.username AS requested_by_user
    FROM transfer_requests tr
    JOIN products p ON tr.product_id = p.product_id
    JOIN branches sb ON tr.source_branch = sb.branch_id
    JOIN branches db ON tr.destination_branch = db.branch_id
    JOIN users u ON tr.requested_by = u.id
    WHERE tr.status = 'pending'
");

// Notifications (Pending Approvals)
$pending = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pending = $row['pending'] ?? 0;
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approvals - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/notifications.css">
    <link rel="stylesheet" href="css/approvals.css">
    <link rel="stylesheet" href="css/sidebar.css">
<audio id="notifSound" src="notif.mp3" preload="auto"></audio>
    <style>
      
    </style>
</head>
<body><div class="sidebar">
    <h2>
    <?= strtoupper($role) ?>
    <span class="notif-wrapper">
        <i class="fas fa-bell" id="notifBell"></i>
        <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>>0</span>
    </span>
</h2>


    <!-- Common -->
    <a href="dashboard.php" class="active"><i class="fas fa-tv"></i> Dashboard</a>

    <!-- Admin Links -->
    <?php if ($role === 'admin'): ?>
        <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
        <a href="approvals.php"><i class="fas fa-check-circle"></i> Approvals
            <?php if ($pending > 0): ?>
                <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;">
                    <?= $pending ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="accounts.php"><i class="fas fa-users"></i> Accounts</a>
        <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>
        <a href="logs.php"><i class="fas fa-file-alt"></i> Logs</a>
    <?php endif; ?>

    <!-- Stockman Links -->
    <?php if ($role === 'stockman'): ?>
        <a href="transfer.php"><i class="fas fa-exchange-alt"></i> Transfer
            <?php if ($transferNotif > 0): ?>
                <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;">
                    <?= $transferNotif ?>
                </span>
            <?php endif; ?>
        </a>
    <?php endif; ?>

    <!-- Staff Links -->
    <?php if ($role === 'staff'): ?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<div class="content">
    <h1>Pending Transfer Requests</h1>

    <?php if ($requests->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Source Branch</th>
                <th>Destination Branch</th>
                <th>Requested By</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $requests->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= htmlspecialchars($row['source_name']) ?></td>
                <td><?= htmlspecialchars($row['dest_name']) ?></td>
                <td><?= htmlspecialchars($row['requested_by_user']) ?></td>
                <td><?= date('Y-m-d H:i', strtotime($row['request_date'])) ?></td>

                <td>
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">

                        <button type="submit" name="action" value="approved" class="btn btn-approve">Approve</button>
                        <button type="submit" name="action" value="rejected" class="btn btn-reject">Reject</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No pending requests.</p>
    <?php endif; ?>
</div>
<script src="notifications.js"></script>
</body>
</html>
