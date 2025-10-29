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
            $stmt = $conn->prepare("
                SELECT product_id, source_branch, destination_branch, quantity 
                FROM transfer_requests 
                WHERE request_id = ?
            ");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $transfer = $stmt->get_result()->fetch_assoc();
            
            $product_id = $transfer['product_id'];
            $source_branch = $transfer['source_branch'];
            $destination_branch = $transfer['destination_branch'];
            $quantity = $transfer['quantity'];

            // 1️⃣ Reduce stock from source branch
            $stmt = $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE product_id = ? AND branch_id = ?");
            $stmt->bind_param("iii", $quantity, $product_id, $source_branch);
            $stmt->execute();

            // Record stock out
            $movement_stmt = $conn->prepare("
                INSERT INTO inventory_movements 
                    (product_id, branch_id, quantity, movement_type, reference_id, user_id, notes, created_at)
                VALUES (?, ?, ?, 'TRANSFER_OUT', ?, ?, ?, NOW())
            ");
            $notes_out = "Transfer to branch #$destination_branch (Request ID: $request_id)";
            $movement_stmt->bind_param("iiisss", $product_id, $source_branch, $quantity, $request_id, $_SESSION['user_id'], $notes_out);
            $movement_stmt->execute();
            $movement_stmt->close();

            // 2️⃣ Add stock to destination branch
            // Check if destination branch has the product
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

            // Record stock in
            $movement_stmt = $conn->prepare("
                INSERT INTO inventory_movements 
                    (product_id, branch_id, quantity, movement_type, reference_id, user_id, notes, created_at)
                VALUES (?, ?, ?, 'TRANSFER_IN', ?, ?, ?, NOW())
            ");
            $notes_in = "Received from branch #$source_branch (Request ID: $request_id)";
            $movement_stmt->bind_param("iiisss", $product_id, $destination_branch, $quantity, $request_id, $_SESSION['user_id'], $notes_in);
            $movement_stmt->execute();
            $movement_stmt->close();

            // 3️⃣ Update transfer request status
            $stmt = $conn->prepare("
                UPDATE transfer_requests 
                SET status = ?, decision_date = NOW(), decided_by = ? 
                WHERE request_id = ?
            ");
            $stmt->bind_param("sii", $action, $_SESSION['user_id'], $request_id);
            $stmt->execute();

            header("Location: approvals.php?success=approved");
            exit;

        } elseif ($action === 'rejected') {
            // Simply mark as rejected
            $stmt = $conn->prepare("
                UPDATE transfer_requests 
                SET status = 'rejected', decision_date = NOW(), decided_by = ? 
                WHERE request_id = ?
            ");
            $stmt->bind_param("ii", $_SESSION['user_id'], $request_id);
            $stmt->execute();

            header("Location: approvals.php?success=rejected");
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
    <?php $pageTitle = 'Approvals'; ?>
<title><?= htmlspecialchars("RP Habana — $pageTitle") ?></title>
    <link rel="icon" href="img/R.P.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
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
    <a href="dashboard.php" ><i class="fas fa-tv"></i> Dashboard</a>

    <!-- Admin Links -->
    <?php if ($role === 'admin'): ?>
        <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
        <a href="physical_inventory.php"><i class="fas fa-warehouse"></i> Physical Inventory</a>
        <a href="sales.php"><i class="fas fa-receipt"></i> Sales</a>
        <a href="approvals.php" class="active"><i class="fas fa-check-circle"></i> Approvals
            <?php if ($pending > 0): ?>
                <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;">
                    <?= $pending ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="accounts.php"><i class="fas fa-users"></i> Accounts</a>
        <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>
        <a href="logs.php"><i class="fas fa-file-alt"></i> Logs</a>
        <a href="/config/admin/backup_admin.php"><i class="fa-solid fa-database"></i> Backup and Restore</a>
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

<!-- ===== Transfer Approvals (box #1) ===== -->
<div class="approvals-wrap">
    <div class="approval-section">
        <div class="section-title">
            <h2><i class="fas fa-exchange-alt fa-xs text-warning"></i> Pending Transfer Requests</h2>
        </div>

    <?php if ($requests->num_rows > 0): ?>
        <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
            <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Source</th>
            <th>Destination</th>
            <th>Requested By</th>
            <th>Requested At</th>
            <th style="width:220px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $requests->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= (int)$row['quantity'] ?></td>
                <td><?= htmlspecialchars($row['source_name']) ?></td>
                <td><?= htmlspecialchars($row['dest_name']) ?></td>
                <td><?= htmlspecialchars($row['requested_by_user']) ?></td>
                <td><?= date('Y-m-d H:i', strtotime($row['request_date'])) ?></td>
                <td>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="request_id" value="<?= (int)$row['request_id'] ?>">
                    <button type="submit" name="action" value="approved" class="btn btn-sm btn-success">
                    <i class="fas fa-check"></i> Approve
                    </button>
                </form>
                <form method="POST" class="d-inline ms-1">
                    <input type="hidden" name="request_id" value="<?= (int)$row['request_id'] ?>">
                    <button type="submit" name="action" value="rejected" class="btn btn-sm btn-danger">
                    <i class="fas fa-times"></i> Reject
                    </button>
                </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        </table>
    <?php else: ?>
        <p>No pending transfer requests.</p>
    <?php endif; ?>
</div>

    <?php if (!empty($_SESSION['flash'])): ?>
        <div id="flashMessage" data-message="<?= htmlentities($_SESSION['flash'], ENT_QUOTES, 'UTF-8') ?>"></div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

</div>

<!-- try -->

<!-- Toast container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100">
  <div id="appToast" class="toast border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-primary text-white">
      <i class="fas fa-info-circle me-2"></i>
      <strong class="me-auto">System Notice</strong>
      <small>just now</small>
      <button type="button" class="btn-close btn-close-white ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="appToastBody">
      Action completed.
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="notifications.js"></script>

<script>
(function () {
  // Helper to show a toast with a specific color
  function showToast(message, type) {
    const toastEl   = document.getElementById('appToast');
    const toastBody = document.getElementById('appToastBody');
    if (!toastEl || !toastBody) return;
    
    // Reset bg classes, then add the one we need
    toastEl.classList.remove('bg-success','bg-danger','bg-info','bg-warning','bg-primary','bg-secondary','bg-dark');
    const map = { success:'bg-success', danger:'bg-danger', info:'bg-info', warning:'bg-warning' };
    toastEl.classList.add(map[type] || 'bg-info');

    toastBody.innerHTML = message;

    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
  }

  // 1) Handle transfer approve/reject via ?success=approved|rejected
  const url = new URL(window.location.href);
  const success = url.searchParams.get('success');
  if (success === 'approved') {
    showToast('Transfer request approved.', 'success');
  } else if (success === 'rejected') {
    showToast('Transfer request rejected.', 'danger');
  }
  // Clean the URL so refresh won’t re-toast
  if (success) {
    url.searchParams.delete('success');
    window.history.replaceState({}, '', url.pathname + (url.search ? '?' + url.searchParams.toString() : ''));
  }
})();
</script>

</body>
</html>
