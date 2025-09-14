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

if (isset($_POST['archive_service'])) {
    $conn->query("UPDATE services SET archived = 1 WHERE service_id = " . (int)$_POST['service_id']);
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

// Logs
function logAction($conn, $action, $details, $user_id = null, $branch_id = null) {
    if (!$user_id) $user_id = $_SESSION['user_id'] ?? null;
    if (!$branch_id) $branch_id = $_SESSION['branch_id'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO logs (user_id, branch_id, action, details, timestamp) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiss", $user_id, $branch_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}

/* ---------------------- HANDLE ACTIONS ---------------------- */

// Restore / Delete Product
if (isset($_POST['restore_product'])) {
    $id = (int) $_POST['inventory_id'];
    $conn->query("UPDATE inventory SET archived = 0 WHERE inventory_id = $id");

    $prod = $conn->query("SELECT p.product_name, b.branch_id 
                          FROM inventory i 
                          JOIN products p ON i.product_id = p.product_id 
                          JOIN branches b ON i.branch_id = b.branch_id 
                          WHERE i.inventory_id = $id")->fetch_assoc();

    logAction($conn, "Restore Product", "Restored product: {$prod['product_name']} (ID: $id)", null, $prod['branch_id']);
}

if (isset($_POST['delete_product'])) {
    $id = (int) $_POST['inventory_id'];

    $prod = $conn->query("SELECT p.product_name, b.branch_id 
                          FROM inventory i 
                          JOIN products p ON i.product_id = p.product_id 
                          JOIN branches b ON i.branch_id = b.branch_id 
                          WHERE i.inventory_id = $id")->fetch_assoc();

    $conn->query("DELETE FROM inventory WHERE inventory_id = $id");

    logAction($conn, "Delete Product", "Deleted product: {$prod['product_name']} (ID: $id)", null, $prod['branch_id']);
}

// Restore / Delete Branch
if (isset($_POST['restore_branch'])) {
    $id = (int) $_POST['branch_id'];
    $conn->query("UPDATE branches SET archived = 0 WHERE branch_id = $id");

    $branch = $conn->query("SELECT branch_name FROM branches WHERE branch_id = $id")->fetch_assoc();
    logAction($conn, "Restore Branch", "Restored branch: {$branch['branch_name']} (ID: $id)", null, $id);
}

if (isset($_POST['delete_branch'])) {
    $id = (int) $_POST['branch_id'];
    $branch = $conn->query("SELECT branch_name FROM branches WHERE branch_id = $id")->fetch_assoc();

    $conn->query("DELETE FROM branches WHERE branch_id = $id");

    logAction($conn, "Delete Branch", "Deleted branch: {$branch['branch_name']} (ID: $id)", null, $id);
}

// Restore / Delete User
if (isset($_POST['restore_user'])) {
    $id = (int) $_POST['user_id'];
    $conn->query("UPDATE users SET archived = 0 WHERE id = $id");

    $user = $conn->query("SELECT username, branch_id FROM users WHERE id = $id")->fetch_assoc();
    logAction($conn, "Restore User", "Restored user: {$user['username']} (ID: $id)", null, $user['branch_id']);
}

if (isset($_POST['delete_user'])) {
    $id = (int) $_POST['user_id'];
    $user = $conn->query("SELECT username, branch_id FROM users WHERE id = $id")->fetch_assoc();

    $conn->query("DELETE FROM users WHERE id = $id");

    logAction($conn, "Delete User", "Deleted user: {$user['username']} (ID: $id)", null, $user['branch_id']);
}

// Restore / Delete Service
if (isset($_POST['restore_service'])) {
    $id = (int) $_POST['service_id'];
    $conn->query("UPDATE services SET archived = 0 WHERE service_id = $id");

    $service = $conn->query("SELECT service_name, branch_id FROM services WHERE service_id = $id")->fetch_assoc();
    logAction($conn, "Restore Service", "Restored service: {$service['service_name']} (ID: $id)", null, $service['branch_id']);
}

if (isset($_POST['delete_service'])) {
    $id = (int) $_POST['service_id'];
    $service = $conn->query("SELECT service_name, branch_id FROM services WHERE service_id = $id")->fetch_assoc();

    $conn->query("DELETE FROM services WHERE service_id = $id");

    logAction($conn, "Delete Service", "Deleted service: {$service['service_name']} (ID: $id)", null, $service['branch_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<?php $pageTitle = 'Archive'; ?>
<title><?= htmlspecialchars("RP Habana — $pageTitle") ?></title>
<link rel="icon" href="img/R.P.png">
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

<?php
    // put this once before the sidebar (top of file is fine)
    $self = strtolower(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
    $isArchive = substr($self, 0, 7) === 'archive'; // matches archive.php, archive_view.php, etc.
    $invOpen   = in_array($self, ['inventory.php','physical_inventory.php'], true);
    $toolsOpen = ($self === 'backup_admin.php' || $isArchive);
    ?>

<!-- Admin Links -->
<?php if ($role === 'admin'): ?>

  <!-- Inventory group (unchanged) -->
  <div class="menu-group has-sub">
    <button class="menu-toggle" type="button" aria-expanded="<?= $invOpen ? 'true' : 'false' ?>">
      <span><i class="fas fa-box"></i> Inventory</span>
      <i class="fas fa-chevron-right caret"></i>
    </button>
    <div class="submenu" <?= $invOpen ? '' : 'hidden' ?>>
      <a href="inventory.php" class="<?= $self === 'inventory.php' ? 'active' : '' ?>">
        <i class="fas fa-list"></i> Inventory List
      </a>
      <a href="physical_inventory.php" class="<?= $self === 'physical_inventory.php' ? 'active' : '' ?>">
        <i class="fas fa-warehouse"></i> Physical Inventory
      </a>
    </div>
  </div>

  <!-- Sales (normal link with active state) -->
  <a href="sales.php" class="<?= $self === 'sales.php' ? 'active' : '' ?>">
    <i class="fas fa-receipt"></i> Sales
  </a>

  <!-- Approvals -->
  <a href="approvals.php" class="<?= $self === 'approvals.php' ? 'active' : '' ?>">
    <i class="fas fa-check-circle"></i> Approvals
    <?php if ($pending > 0): ?>
      <span class="badge-pending"><?= $pending ?></span>
    <?php endif; ?>
  </a>

  <a href="accounts.php" class="<?= $self === 'accounts.php' ? 'active' : '' ?>">
    <i class="fas fa-users"></i> Accounts
  </a>

  <!-- NEW: Backup & Restore group with Archive inside -->
  <div class="menu-group has-sub">
    <button class="menu-toggle" type="button" aria-expanded="<?= $toolsOpen ? 'true' : 'false' ?>">
      <span><i class="fas fa-screwdriver-wrench me-2"></i> Data Tools</span>
      <i class="fas fa-chevron-right caret"></i>
    </button>
    <div class="submenu" <?= $toolsOpen ? '' : 'hidden' ?>>
      <a href="/config/admin/backup_admin.php" class="<?= $self === 'backup_admin.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-database"></i> Backup & Restore
      </a>
      <a href="archive.php" class="<?= $isArchive ? 'active' : '' ?>">
        <i class="fas fa-archive"></i> Archive
      </a>
    </div>
  </div>

  <a href="logs.php" class="<?= $self === 'logs.php' ? 'active' : '' ?>">
    <i class="fas fa-file-alt"></i> Logs
  </a>

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
  
<script>
(function(){
  const groups = document.querySelectorAll('.menu-group.has-sub');

  groups.forEach((g, idx) => {
    const btn = g.querySelector('.menu-toggle');
    const panel = g.querySelector('.submenu');
    if (!btn || !panel) return;

    // Optional: restore last state from localStorage
    const key = 'sidebar-sub-' + idx;
    const saved = localStorage.getItem(key);
    if (saved === 'open') {
      btn.setAttribute('aria-expanded', 'true');
      panel.hidden = false;
    }

    btn.addEventListener('click', () => {
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!expanded));
      panel.hidden = expanded;
      localStorage.setItem(key, expanded ? 'closed' : 'open');
    });
  });
})();
</script>

<script src="notifications.js"></script>
</div>
</body>
</html>
