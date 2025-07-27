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
if (isset($_POST['restore_product'])) $conn->query("UPDATE products SET archived = 0 WHERE product_id = " . (int)$_POST['product_id']);
if (isset($_POST['delete_product']))  $conn->query("DELETE FROM products WHERE product_id = " . (int)$_POST['product_id']);

if (isset($_POST['restore_branch']))  $conn->query("UPDATE branches SET archived = 0 WHERE branch_id = " . (int)$_POST['branch_id']);
if (isset($_POST['delete_branch']))   $conn->query("DELETE FROM branches WHERE branch_id = " . (int)$_POST['branch_id']);

if (isset($_POST['restore_user']))    $conn->query("UPDATE users SET archived = 0 WHERE id = " . (int)$_POST['user_id']);
if (isset($_POST['delete_user']))     $conn->query("DELETE FROM users WHERE id = " . (int)$_POST['user_id']);

// Fetch data
$archived_products = $conn->query("SELECT * FROM products WHERE archived = 1");
$archived_branches = $conn->query("SELECT * FROM branches WHERE archived = 1");
$archived_users    = $conn->query("SELECT * FROM users WHERE archived = 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Archive Management</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/notifications.css">
  <link rel="stylesheet" href="css/archive.css">
<audio id="notifSound" src="notif.mp3" preload="auto"></audio>
</head>
<body>
<div class="sidebar">
  <h2>ADMIN <i class="fas fa-bell" id="notifBell" style="font-size: 24px; cursor: pointer;"></i>
<span id="notifCount" style="
    background:red; color:white; border-radius:50%; padding:2px 8px;
    font-size:12px;  position:absolute;display:none;">
0</span></h2>
  <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>
  <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
  <a href="approvals.php"><i class="fas fa-check-circle"></i> Approvals</a>
  <a href="accounts.php"><i class="fas fa-users"></i> Accounts</a>
  <a href="archive.php" class="active"><i class="fas fa-archive"></i> Archive</a>
  <a href="logs.php"><i class="fas fa-file-alt"></i> Logs</a>
  <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
  <h1>Archived Records</h1>

  <!-- Products -->
  <div class="card">
    <h2>Archived Products</h2>
    <?php if ($archived_products->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Name</th><th>Category</th><th>Price</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($p = $archived_products->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($p['product_name']) ?></td>
        <td><?= htmlspecialchars($p['category']) ?></td>
        <td><?= number_format($p['price'], 2) ?></td>
        <td>
          <form method="POST" style="display:inline-block;">
            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
            <button class="btn btn-restore" name="restore_product">Restore</button>
            <button class="btn btn-delete" name="delete_product" onclick="return confirm('Delete permanently?')">Delete</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?><p class="empty-msg">No archived products.</p><?php endif; ?>
  </div>

  <!-- Branches -->
  <div class="card">
    <h2>Archived Branches</h2>
    <?php if ($archived_branches->num_rows > 0): ?>
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
            <button class="btn btn-restore" name="restore_branch">Restore</button>
            <button class="btn btn-delete" name="delete_branch" onclick="return confirm('Delete permanently?')">Delete</button>
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
            <button class="btn btn-restore" name="restore_user">Restore</button>
            <button class="btn btn-delete" name="delete_user" onclick="return confirm('Delete permanently?')">Delete</button>
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
