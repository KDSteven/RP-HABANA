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
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approvals - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
      margin: 0; padding: 0; box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
        body {
      display: flex;
      height: 100vh;
      background: #f5f5f5;
      color: #333;
    }

    .sidebar {
      width: 220px;
      background-color: #f7931e;
      padding: 30px 15px;
      color: white;
    }

    .sidebar h2 {
      margin-bottom: 30px;
      font-size: 22px;
      text-align: center;
    }

    .sidebar a {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: white;
      padding: 12px 15px;
      margin: 6px 0;
      border-radius: 8px;
      transition: background 0.2s;
    }

    .sidebar a:hover, .sidebar a.active {
      background-color: #e67e00;
    }

    .sidebar a i {
      margin-right: 10px;
      font-size: 16px;
    }

    .content {
      flex: 1;
      padding: 40px;
      overflow-y: auto;
    }

    .card {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }

    .card h2 {
      margin-bottom: 20px;
      font-size: 22px;
      color: #333;
    }
        table {
            width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f7931e; color: #fff; }
        tr:hover { background: #f9f9f9; }

        .btn {
            padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;
        }
        .btn-approve { background: #28a745; color: #fff; }
        .btn-reject { background: #dc3545; color: #fff; }
        .btn-approve:hover { background: #218838; }
        .btn-reject:hover { background: #c82333; }
    </style>
</head>
<body>
<div class="sidebar">
    <h2><?= strtoupper($role) ?>
        <i class="fas fa-bell" id="notifBell" style="font-size: 24px; cursor: pointer;"></i>
<span id="notifCount" style="
    background:red; color:white; border-radius:50%; padding:2px 8px;
    font-size:12px;  position:absolute;display:none;">
0</span></h2>

    <!-- Common for all -->
    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>

    <?php if ($role === 'admin'): ?>
        <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
        <a href="approvals.php"><i class="fas fa-check-circle"></i> Approvals</a>
        <a href="accounts.php"><i class="fas fa-users"></i> Accounts</a>
        <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>
        <a href="logs.php"><i class="fas fa-file-alt"></i> Logs</a>
    <?php endif; ?>

    <?php if ($role === 'stockman'): ?>
        <a href="transfer.php"><i class="fas fa-exchange-alt"></i> Transfer Request</a>
    <?php endif; ?>

    <?php if ($role === 'staff'): ?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
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
