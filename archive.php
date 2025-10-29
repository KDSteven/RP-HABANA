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


/* ---------------------- HANDLE ACTIONS (SAFE) ---------------------- */

function fetch_one_assoc(mysqli $conn, string $sql): ?array {
    $res = $conn->query($sql);
    if ($res && $res->num_rows) return $res->fetch_assoc();
    return null;
}
function go_back_with_toast(string $msg, string $type='success') {
    $_SESSION['toast'] = ['msg'=>$msg, 'type'=>$type];
    header("Location: archive.php");
    exit;
}

/* ---------- PRODUCTS ---------- */
if (isset($_POST['restore_product'])) {
    $id = (int)($_POST['inventory_id'] ?? 0);
    $prod = fetch_one_assoc($conn, "
        SELECT p.product_name, b.branch_id
        FROM inventory i
        JOIN products p ON i.product_id = p.product_id
        JOIN branches b ON i.branch_id  = b.branch_id
        WHERE i.inventory_id = $id
    ");
    $conn->query("UPDATE inventory SET archived = 0 WHERE inventory_id = $id");
    $name = $prod['product_name'] ?? "inventory_id $id";
    $bid  = $prod['branch_id']    ?? ($_SESSION['branch_id'] ?? null);
    logAction($conn, "Restore Product", "Restored product: {$name} (ID: $id)", null, $bid);
    go_back_with_toast("Product restored: {$name}", "success");

}

if (isset($_POST['delete_product'])) {
    $id = (int)($_POST['inventory_id'] ?? 0);
    $prod = fetch_one_assoc($conn, "
        SELECT p.product_name, b.branch_id
        FROM inventory i
        JOIN products p ON i.product_id = p.product_id
        JOIN branches b ON i.branch_id  = b.branch_id
        WHERE i.inventory_id = $id
    ");
    $conn->query("DELETE FROM inventory WHERE inventory_id = $id");
    $name = $prod['product_name'] ?? "inventory_id $id";
    $bid  = $prod['branch_id']    ?? ($_SESSION['branch_id'] ?? null);
    logAction($conn, "Delete Product", "Deleted product: {$name} (ID: $id)", null, $bid);
    go_back_with_toast("Product Deleted: {$name}","danger");

}

/* ---------- BRANCHES ---------- */
if (isset($_POST['restore_branch'])) {
    $id = (int)($_POST['branch_id'] ?? 0);
    $branch = fetch_one_assoc($conn, "SELECT branch_name FROM branches WHERE branch_id = $id");
    $conn->query("UPDATE branches SET archived = 0 WHERE branch_id = $id");
    $name = $branch['branch_name'] ?? "branch_id $id";
    logAction($conn, "Restore Branch", "Restored branch: {$name} (ID: $id)", null, $id);
    go_back_with_toast("Branch Restored: {$name}", "success");

}

if (isset($_POST['delete_branch'])) {
    $id = (int)($_POST['branch_id'] ?? 0);
    $branch = fetch_one_assoc($conn, "SELECT branch_name FROM branches WHERE branch_id = $id");
    $conn->query("DELETE FROM branches WHERE branch_id = $id");
    $name = $branch['branch_name'] ?? "branch_id $id";
    logAction($conn, "Delete Branch", "Deleted branch: {$name} (ID: $id)", null, $id);
    go_back_with_toast("Branch Deleted: {$name}","danger");

}

/* ---------- USERS ---------- */
if (isset($_POST['restore_user'])) {
    $id = (int)($_POST['user_id'] ?? 0);
    $user = fetch_one_assoc($conn, "SELECT username, branch_id FROM users WHERE id = $id");
    $conn->query("UPDATE users SET archived = 0 WHERE id = $id");
    $name = $user['username']  ?? "user_id $id";
    $bid  = $user['branch_id'] ?? ($_SESSION['branch_id'] ?? null);
    logAction($conn, "Restore User", "Restored user: {$name} (ID: $id)", null, $bid);
    go_back_with_toast("User Restored: {$name}", "success");

}

if (isset($_POST['delete_user'])) {
    $id = (int)($_POST['user_id'] ?? 0);
    $user = fetch_one_assoc($conn, "SELECT username, branch_id FROM users WHERE id = $id");
    $conn->query("DELETE FROM users WHERE id = $id");
    $name = $user['username']  ?? "user_id $id";
    $bid  = $user['branch_id'] ?? ($_SESSION['branch_id'] ?? null);
    logAction($conn, "Delete User", "Deleted user: {$name} (ID: $id)", null, $bid);
    go_back_with_toast("User Deleted: {$name}","danger");

}

/* ---------- SERVICES ---------- */
if (isset($_POST['restore_service'])) {
    $id = (int)($_POST['service_id'] ?? 0);
    $svc = fetch_one_assoc($conn, "SELECT service_name, branch_id FROM services WHERE service_id = $id");
    $conn->query("UPDATE services SET archived = 0 WHERE service_id = $id");
    $name = $svc['service_name'] ?? "service_id $id";
    $bid  = $svc['branch_id']    ?? ($_SESSION['branch_id'] ?? null);
    logAction($conn, "Restore Service", "Restored service: {$name} (ID: $id)", null, $bid);
    go_back_with_toast("Service Restored: {$name}", "success");

}

if (isset($_POST['delete_service'])) {
    $id = (int)($_POST['service_id'] ?? 0);
    $svc = fetch_one_assoc($conn, "SELECT service_name, branch_id FROM services WHERE service_id = $id");
    $conn->query("DELETE FROM services WHERE service_id = $id");
    $name = $svc['service_name'] ?? "service_id $id";
    $bid  = $svc['branch_id']    ?? ($_SESSION['branch_id'] ?? null);
    logAction($conn, "Delete Service", "Deleted service: {$name} (ID: $id)", null, $bid);
    go_back_with_toast("Service Deleted: {$name}","danger");

}



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

$pendingTransfers = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pendingTransfers = (int)($row['pending'] ?? 0);
    }
}

$pendingStockIns = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM stock_in_requests WHERE status='pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pendingStockIns = (int)($row['pending'] ?? 0);
    }
}

$pendingTotalInventory = $pendingTransfers + $pendingStockIns;

// Fetch current user's full name
$currentName = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($fetchedName);
    if ($stmt->fetch()) {
        $currentName = $fetchedName;
    }
    $stmt->close();
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
<!-- Sidebar -->
<div class="sidebar" id="mainSidebar">
  <!-- Toggle button always visible on the rail -->
  <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="false">
    <i class="fas fa-bars" aria-hidden="true"></i>
  </button>

  <!-- Wrap existing sidebar content so we can hide/show it cleanly -->
  <div class="sidebar-content">
    <h2 class="user-heading">
      <span class="role"><?= htmlspecialchars(strtoupper($role), ENT_QUOTES) ?></span>
      <?php if ($currentName !== ''): ?>
        <span class="name">(<?= htmlspecialchars($currentName, ENT_QUOTES) ?>)</span>
      <?php endif; ?>
      <span class="notif-wrapper">
        <i class="fas fa-bell" id="notifBell"></i>
        <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>><?= (int)$pending ?></span>
      </span>
    </h2>

        <!-- Common -->
    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>

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
  <span><i class="fas fa-box"></i> Inventory
    <?php if ($pendingTotalInventory > 0): ?>
      <span class="badge-pending"><?= $pendingTotalInventory ?></span>
    <?php endif; ?>
  </span>
    <i class="fas fa-chevron-right caret"></i>
  </button>
  <div class="submenu" <?= $invOpen ? '' : 'hidden' ?>>
    <a href="inventory.php#pending-requests" class="<?= $self === 'inventory.php#pending-requests' ? 'active' : '' ?>">
      <i class="fas fa-list"></i> Inventory List
        <?php if ($pendingTotalInventory > 0): ?>
          <span class="badge-pending"><?= $pendingTotalInventory ?></span>
        <?php endif; ?>
    </a>
    <a href="physical_inventory.php" class="<?= $self === 'physical_inventory.php' ? 'active' : '' ?>">
      <i class="fas fa-warehouse"></i> Physical Inventory
    </a>
        <a href="barcode-print.php<?php 
        $b = (int)($_SESSION['current_branch_id'] ?? 0);
        echo $b ? ('?branch='.$b) : '';?>" class="<?= $self === 'barcode-print.php' ? 'active' : '' ?>">
        <i class="fas fa-barcode"></i> Barcode Labels
    </a>
  </div>
</div>

    <a href="services.php" class="<?= $self === 'services.php' ? 'active' : '' ?>">
      <i class="fa fa-wrench" aria-hidden="true"></i> Services
    </a>

  <!-- Sales (normal link with active state) -->
  <a href="sales.php" class="<?= $self === 'sales.php' ? 'active' : '' ?>">
    <i class="fas fa-receipt"></i> Sales
  </a>


<a href="accounts.php" class="<?= $self === 'accounts.php' ? 'active' : '' ?>">
  <i class="fas fa-users"></i> Accounts & Branches
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
        <!-- Stockman can access Barcode Labels; server forces their branch -->
        <a href="barcode-print.php" class="<?= $self === 'barcode-print.php' ? 'active' : '' ?>">
          <i class="fas fa-barcode"></i> Barcode Labels
        </a>
      </div>
    </div>
  <?php endif; ?>
    <!-- Staff Links -->
    <?php if ($role === 'staff'): ?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
  </div>
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
            <button type="button"
                    class="btn btn-restore confirm-action"
                    data-action="restore_product"
                    data-id="<?= $p['inventory_id'] ?>"
                    data-entity="product"
                    data-name="<?= htmlspecialchars($p['product_name']) ?>">
              <i class="fas fa-trash-restore"></i> Restore
            </button>

            <button type="button"
                    class="btn btn-delete confirm-action"
                    data-action="delete_product"
                    data-id="<?= $p['inventory_id'] ?>"
                    data-entity="product"
                    data-name="<?= htmlspecialchars($p['product_name']) ?>">
              <i class="fa fa-trash"></i> Delete
            </button>
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
                <button type="button"
                        class="btn btn-restore confirm-action"
                        data-action="restore_service"
                        data-id="<?= $s['service_id'] ?>"
                        data-entity="service"
                        data-name="<?= htmlspecialchars($s['service_name']) ?>">
                  <i class="fas fa-trash-restore"></i> Restore
                </button>

                <button type="button"
                        class="btn btn-delete confirm-action"
                        data-action="delete_service"
                        data-id="<?= $s['service_id'] ?>"
                        data-entity="service"
                        data-name="<?= htmlspecialchars($s['service_name']) ?>">
                  <i class="fa fa-trash"></i> Delete
                </button>
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
            <button type="button"
                    class="btn btn-restore confirm-action"
                    data-action="restore_branch"
                    data-id="<?= $b['branch_id'] ?>"
                    data-entity="branch"
                    data-name="<?= htmlspecialchars($b['branch_name']) ?>">
              <i class="fas fa-trash-restore"></i> Restore
            </button>

            <button type="button"
                    class="btn btn-delete confirm-action"
                    data-action="delete_branch"
                    data-id="<?= $b['branch_id'] ?>"
                    data-entity="branch"
                    data-name="<?= htmlspecialchars($b['branch_name']) ?>">
              <i class="fa fa-trash"></i> Delete
            </button>
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
            <button type="button"
                    class="btn btn-restore confirm-action"
                    data-action="restore_user"
                    data-id="<?= $u['id'] ?>"
                    data-entity="user"
                    data-name="<?= htmlspecialchars($u['username']) ?>">
              <i class="fas fa-trash-restore"></i> Restore
            </button>

            <button type="button"
                    class="btn btn-delete confirm-action"
                    data-action="delete_user"
                    data-id="<?= $u['id'] ?>"
                    data-entity="user"
                    data-name="<?= htmlspecialchars($u['username']) ?>">
              <i class="fa fa-trash"></i> Delete
            </button>
          </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?><p class="empty-msg">No archived users.</p><?php endif; ?>
  </div>
  
<!-- Confirm Action Modal (Delete / Restore) -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-white" id="confirmActionHeader">
        <h5 class="modal-title">
          <i class="fa-solid me-2" id="confirmActionIcon"></i>
          <span id="confirmActionTitle">Confirm Action</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="confirmActionBody">
          You’re about to perform this action.
        </div>
        <div class="small text-muted mt-2" id="confirmActionNote">
          This action can be undone only if you choose Restore (delete is permanent).
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn" id="confirmActionBtn">
          Proceed
        </button>
      </div>
    </div>
  </div>
</div>

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

<?php if (!empty($_SESSION['toast'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  showToast("<?= $_SESSION['toast']['msg'] ?>", "<?= $_SESSION['toast']['type'] ?>");
});
</script>
<?php unset($_SESSION['toast']); endif; ?>


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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal For Restore and Delete -->
<script>
function styleModal(action) {
  const header = document.getElementById('confirmActionHeader');
  const icon   = document.getElementById('confirmActionIcon');
  const title  = document.getElementById('confirmActionTitle');
  const btn    = document.getElementById('confirmActionBtn');

  header.className = 'modal-header text-white';
  btn.className = 'btn';

  if (action.startsWith('delete')) {
    header.classList.add('bg-danger');
    icon.className = 'fa-solid fa-trash';
    title.textContent = 'Confirm Delete';
    btn.classList.add('btn-danger');
    btn.textContent = 'Yes, Delete';
  } else {
    header.classList.add('bg-success');
    icon.className = 'fa-solid fa-trash-restore';
    title.textContent = 'Confirm Restore';
    btn.classList.add('btn-success');
    btn.textContent = 'Yes, Restore';
  }
}

function idField(action) {
  if (action.includes('product')) return 'inventory_id';
  if (action.includes('service')) return 'service_id';
  if (action.includes('branch'))  return 'branch_id';
  if (action.includes('user'))    return 'user_id';
  return 'id';
}

document.addEventListener('DOMContentLoaded', () => {
  const modalEl = document.getElementById('confirmActionModal');
  const modal = new bootstrap.Modal(modalEl);
  const bodyEl = document.getElementById('confirmActionBody');
  const noteEl = document.getElementById('confirmActionNote');
  const confirmBtn = document.getElementById('confirmActionBtn');

  let pending = { action: '', id: '', label: '' };

  document.querySelectorAll('.confirm-action').forEach(btn => {
    btn.addEventListener('click', () => {
      pending.action = btn.dataset.action;
      pending.id     = btn.dataset.id;
      pending.label  = btn.dataset.name;

      styleModal(pending.action);
      bodyEl.innerHTML = `You’re about to <strong>${pending.action.split('_')[0]}</strong> <strong>${pending.label}</strong>.`;
      noteEl.style.display = pending.action.startsWith('delete') ? '' : 'none';

      modal.show();
    });
  });

  confirmBtn.addEventListener('click', () => {
    if (!pending.action || !pending.id) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    const a = document.createElement('input');
    a.name = pending.action;    // e.g., restore_product
    a.value = '1';
    form.appendChild(a);

    const hidden = document.createElement('input');
    hidden.name = idField(pending.action); // inventory_id / service_id / branch_id / user_id
    hidden.value = pending.id;
    form.appendChild(hidden);

    document.body.appendChild(form);
    form.submit();
  });
});
</script>

<script>
function showToast(message, type = "info") {
  const toastEl   = document.getElementById("appToast");
  const toastBody = document.getElementById("appToastBody");
  if (!toastEl || !toastBody) return;

  // Reset classes
  const header = toastEl.querySelector(".toast-header");
  header.className = "toast-header"; // reset
  header.classList.add("text-white");

  // Pick color
switch (type) {
  case "success": // ✅ Restore
    header.classList.add("bg-success", "text-white");
    break;
  case "danger":  // ✅ Delete
    header.classList.add("bg-danger", "text-white");
    break;
  case "warning":
    header.classList.add("bg-warning", "text-dark");
    break;
  default:
    header.classList.add("bg-primary", "text-white");
}


  toastBody.innerText = message;

  const bsToast = new bootstrap.Toast(toastEl);
  bsToast.show();
}
</script>

<script src="notifications.js"></script>
<script src="sidebar.js"></script>
</div>
</body>
</html>
