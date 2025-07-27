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
<style>
* {
  margin: 0; padding: 0; box-sizing: border-box;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
body {
  display: flex;
  background: #f5f5f5;
  color: #333;
}
.sidebar {
  width: 240px;
  background-color: #f7931e;
  padding: 30px 15px;
  color: white;
  min-height: 100vh;
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
}
h1 {
  margin-bottom: 25px;
  color: #333;
  font-size: 28px;
}
.card {
  background: #fff;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
  margin-bottom: 30px;
}
.card h2 {
  font-size: 22px;
  margin-bottom: 15px;
  color: #f7931e;
}
table {
  width: 100%;
  border-collapse: collapse;
  border-radius: 8px;
  overflow: hidden;
}
th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #ddd;
}
th {
  background-color: #f7931e;
  color: white;
  font-weight: bold;
}
tr:hover {
  background: #f9f9f9;
}
.btn {
  padding: 8px 14px;
  border: none;
  border-radius: 6px;
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  margin: 2px;
  transition: background 0.3s ease;
}
.btn-restore { background: #28a745; }
.btn-restore:hover { background: #218838; }
.btn-delete { background: #dc3545; }
.btn-delete:hover { background: #c82333; }
p.empty-msg {
  font-size: 15px;
  color: #777;
  font-style: italic;
}
</style>
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
