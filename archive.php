<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$role = $_SESSION['role'] ?? '';
if ($role !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Handle Restore & Delete
if (isset($_POST['restore_product'])) $conn->query("UPDATE inventory SET archived = 0 WHERE inventory_id = " . (int)$_POST['inventory_id']);
if (isset($_POST['delete_product']))  $conn->query("DELETE FROM inventory WHERE inventory_id = " . (int)$_POST['inventory_id']);

if (isset($_POST['restore_branch']))  $conn->query("UPDATE branches SET archived = 0 WHERE branch_id = " . (int)$_POST['branch_id']);
if (isset($_POST['delete_branch']))   $conn->query("DELETE FROM branches WHERE branch_id = " . (int)$_POST['branch_id']);

if (isset($_POST['restore_user']))    $conn->query("UPDATE users SET archived = 0 WHERE id = " . (int)$_POST['user_id']);
if (isset($_POST['delete_user']))     $conn->query("DELETE FROM users WHERE id = " . (int)$_POST['user_id']);

if (isset($_POST['restore_service'])) {$conn->query("UPDATE services SET archived = 0 WHERE service_id = " . (int)$_POST['service_id']);}

if (isset($_POST['delete_service']))  {$conn->query("DELETE FROM services WHERE service_id = " . (int)$_POST['service_id']);}


// Fetch data
$archive_services  = $conn->query(query: "SELECT * FROM services WHERE archived =1");
$archived_products = $conn->query("SELECT * FROM inventory WHERE archived = 1");
$archived_products = $conn->query("
    SELECT i.inventory_id, p.product_name, p.category, p.price, b.branch_name
    FROM inventory i
    JOIN products p ON i.product_id = p.product_id
    JOIN branches b ON i.branch_id = b.branch_id
    WHERE i.archived = 1
");


$archived_branches = $conn->query("SELECT * FROM branches WHERE archived = 1");
$archived_users    = $conn->query("SELECT * FROM users WHERE archived = 1");


// Notifications (Pending Approvals)
$pending = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'")->fetch_assoc()['pending'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Archive Management</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/notifications.css">
  <link rel="stylesheet" href="css/archive.css?>v2">
  <link rel="stylesheet" href="css/sidebar.css">
<audio id="notifSound" src="notif.mp3" preload="auto"></audio>
</head>
<body>
<div class="sidebar">
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
        <a href="sales.php"><i class="fas fa-receipt"></i> Sales</a>
        <a href="approvals.php"><i class="fas fa-check-circle"></i> Approvals
            <?php if ($pending > 0): ?>
                <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;">
                    <?= $pending ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="accounts.php"><i class="fas fa-users"></i> Accounts</a>
        <a href="archive.php" class="active"><i class="fas fa-archive"></i> Archive</a>
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

<div class="content">
  <h1>Archived Records</h1>

  <!-- Products -->
  <div class="card">
    <h2>Archived Products</h2>
    <?php if ($archived_products->num_rows > 0): ?>
       <div class="table-container">
    <table>
      <thead>
        <tr>
             <th>Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>Branch</th>
        <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($p = $archived_products->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($p['product_name']) ?></td>
        <td><?= htmlspecialchars($p['category']) ?></td>
        <td><?= number_format($p['price'], 2) ?></td>
         <td><?= htmlspecialchars($p['branch_name']) ?></td>
    
        <td>
          <form method="POST" style="display:inline-block;">
            <input type="hidden" name="inventory_id" value="<?= $p['inventory_id'] ?>">
            <button class="btn btn-restore" name="restore_product"><i class="fas fa-trash-restore"></i>Restore</button>
            <button class="btn btn-delete" name="delete_product" onclick="return confirm('Delete permanently?')"><i class="fa fa-trash" aria-hidden="true"></i>Delete</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?><p class="empty-msg">No archived products.</p><?php endif; ?>
  </div>


 <!-- Services -->
<div class="card">
  <h2>Archived Services</h2>
  <?php if ($archive_services->num_rows > 0): ?>
     <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Service Name</th>
          <th>Price</th>
          <th>Description</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($s = $archive_services->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($s['service_name']) ?></td>
            <td>₱<?= number_format($s['price'], 2) ?></td>
            <td><?= htmlspecialchars($s['description']) ?: '<em>No description</em>' ?></td>
            <td>
              <form method="POST" style="display:inline-block;">
                <input type="hidden" name="service_id" value="<?= $s['service_id'] ?>">
                <button class="btn btn-restore" name="restore_service"><i class="fas fa-trash-restore"></i>Restore</button>
                <button class="btn btn-delete" name="delete_service" onclick="return confirm('Delete permanently?')"><i class="fa fa-trash" aria-hidden="true"></i>Delete</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="empty-msg">No archived services.</p>
  <?php endif; ?>
</div>


  <!-- Branches -->
  <div class="card">
    <h2>Archived Branches</h2>
    <?php if ($archived_branches->num_rows >0): ?>
       <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Name</th><th>Location</th><th>Email</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($b = $archived_branches->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($b['branch_name']) ?></td>
        <td><?= htmlspecialchars($b['branch_location']) ?></td>
        <td><?= htmlspecialchars($b['branch_email']) ?></td>
        <td>
          <form method="POST" style="display:inline-block;">
            <input type="hidden" name="branch_id" value="<?= $b['branch_id'] ?>">
            <button class="btn btn-restore" name="restore_branch"><i class="fas fa-trash-restore"></i>Restore</button>
            <button class="btn btn-delete" name="delete_branch" onclick="return confirm('Delete permanently?')"><i class="fa fa-trash" aria-hidden="true"></i>Delete</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?><p class="empty-msg">No archived branches.</p><?php endif; ?>
  </div>

  <!-- Users -->
  <div class="card">
    <h2>Archived Users</h2>
    <?php if ($archived_users->num_rows > 0): ?>
       <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Username</th><th>Role</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($u = $archived_users->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($u['username']) ?></td>
        <td><?= htmlspecialchars($u['role']) ?></td>
        <td>
          <form method="POST" style="display:inline-block;">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <button class="btn btn-restore" name="restore_user"><i class="fas fa-trash-restore"></i>Restore</button>
            <button class="btn btn-delete" name="delete_user" onclick="return confirm('Delete permanently?')"><i class="fa fa-trash" aria-hidden="true"></i>Delete</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?><p class="empty-msg">No archived users.</p><?php endif; ?>
  </div>
<script src="notifications.js"></script>
</div>
</body>
</html>
