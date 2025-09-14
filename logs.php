<?php
session_start();
require 'config/db.php';

$role = $_SESSION['role'] ?? '';

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.html');
    exit;
}
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$branch_id = $_SESSION['branch_id'] ?? null;


$pending = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pending = $row['pending'] ?? 0;
    }
}
// Get branch list
$branches = $conn->query("SELECT branch_id, branch_name FROM branches");
// Filters
$where = [];
$params = [];
$types = '';

// Branch filter
if (!empty($_GET['branch_id']) && is_numeric($_GET['branch_id'])) {
    $where[] = "l.branch_id = ?";
    $params[] = (int)$_GET['branch_id'];
    $types .= 'i';
}

// Date filters
if (!empty($_GET['from_date'])) {
    $where[] = "DATE(l.timestamp) >= ?";
    $params[] = $_GET['from_date'];
    $types .= 's';
}
if (!empty($_GET['to_date'])) {
    $where[] = "DATE(l.timestamp) <= ?";
    $params[] = $_GET['to_date'];
    $types .= 's';
}

// Role filter — must be added **before SQL execution**
if (!empty($_GET['role'])) {
    $where[] = "u.role = ?";
    $params[] = $_GET['role'];
    $types .= 's';
}

// Build SQL
$sql = "SELECT l.log_id, l.user_id, l.action, l.details, l.timestamp,
               b.branch_name, u.username, u.role
        FROM logs l
        LEFT JOIN branches b ON l.branch_id = b.branch_id
        LEFT JOIN users u ON l.user_id = u.id";

if ($where) $sql .= " WHERE " . implode(" AND ", $where);

$sql .= " ORDER BY l.timestamp DESC LIMIT 100";

// Execute query
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();


?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<?php $pageTitle = 'Logs'; ?>
<title><?= htmlspecialchars("RP Habana — $pageTitle") ?></title>
<link rel="icon" href="img/R.P.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/logs.css?v3">
<link rel="stylesheet" href="css/notifications.css">
</head>
<body>
<div class="sidebar">
   <h2>
    <?= strtoupper($role) ?>
    <span class="notif-wrapper">
        <i class="fas fa-bell" id="notifBell"></i>
        <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>>
    <?= $pending ?>
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
     <?php
      $transferNotif = $transferNotif ?? 0; // if not set, default to 0
      ?>
    <?php if ($role === 'stockman'): ?>
        <a href="inventory.php" ><i class="fas fa-box"></i> Inventory
            <?php if ($transferNotif > 0): ?>
                <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;">
                    <?= $transferNotif ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="physical_inventory.php" class="active"><i class="fas fa-warehouse"></i> Physical Inventory</a>
    <?php endif; ?>

    <!-- Staff Links -->
    <?php if ($role === 'staff'): ?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <!-- Header + Filters -->
    <div class="header-bar">
        <h1 class="h3"><i class="fas fa-file-alt me-2"></i> System Logs</h1>

      <form method="get" class="filters-bar">
    <select name="branch_id" class="form-select">
        <option value="">All Branches</option>
        <?php while ($branch = $branches->fetch_assoc()): ?>
            <option value="<?= $branch['branch_id'] ?>" <?= (isset($_GET['branch_id']) && $_GET['branch_id']==$branch['branch_id'])?'selected':'' ?>>
                <?= htmlspecialchars($branch['branch_name']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <input type="date" name="from_date" class="form-control" value="<?= $_GET['from_date'] ?? '' ?>" placeholder="From">
    <input type="date" name="to_date" class="form-control" value="<?= $_GET['to_date'] ?? '' ?>" placeholder="To">

    <select name="role" class="form-select">
        <option value="">All Roles</option>
        <option value="admin" <?= (isset($_GET['role']) && $_GET['role']=='admin')?'selected':'' ?>>Admin</option>
        <option value="staff" <?= (isset($_GET['role']) && $_GET['role']=='staff')?'selected':'' ?>>Staff</option>
        <option value="stockman" <?= (isset($_GET['role']) && $_GET['role']=='stockman')?'selected':'' ?>>Stockman</option>
    </select>

    <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
</form>

    </div>

  <?php if ($result && $result->num_rows > 0): ?>
<div class="logs-feed">
    <?php while ($log = $result->fetch_assoc()): 
        $actionText = trim($log['action']);
        $action = strtolower($actionText);

        // Map actions to badge classes
        if(str_contains($action,'add')) $actionClass='badge-create';
        elseif(str_contains($action,'edit')) $actionClass='badge-update';
        elseif(str_contains($action,'archive') || str_contains($action,'delete')) $actionClass='badge-delete';
        elseif(str_contains($action,'login')) $actionClass='badge-login';
        else $actionClass='badge-secondary';
    ?>
    <div class="log-card">
        <div class="avatar">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($log['username'] ?? 'System') ?>&background=2196f3&color=fff&rounded=true&size=32" alt="User Avatar">
        </div>
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="d-flex align-items-center gap-1">
                    <strong><?= htmlspecialchars($log['username'] ?? 'System') ?></strong>
                    <small class="text-muted">• <?= htmlspecialchars($log['branch_name'] ?? 'N/A') ?></small>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <span class="status-dot <?= $actionClass ?>"></span>
                    <span class="badge <?= $actionClass ?>"><?= htmlspecialchars($actionText) ?></span>
                </div>
            </div>

            <small class="timeline-time" data-timestamp="<?= (new DateTime($log['timestamp'], new DateTimeZone('Asia/Manila')))->format('c') ?>"></small>

            <button type="button" class="btn btn-link p-0 toggle-details">View Details</button>
            <div class="log-details mt-1" style="display:none;">
                <p><?= htmlspecialchars($log['details']) ?></p>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<button id="loadMore" class="btn btn-outline-primary mt-2">Load More</button>
<?php else: ?>
<p class="text-center text-muted mt-3">No logs available</p>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="notifications.js"></script>

<script>document.addEventListener('DOMContentLoaded', function(){

    // Toggle details
    document.querySelectorAll('.toggle-details').forEach(btn => {
        btn.addEventListener('click', function(){
            const details = this.nextElementSibling;
            if(details.style.display === 'none' || !details.style.display){
                details.style.display = 'block';
                this.textContent = 'Hide Details';
            } else {
                details.style.display = 'none';
                this.textContent = 'View Details';
            }
        });
    });

    // Relative Time
    function timeAgo(timestamp){
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now - date)/1000);
        if(diff < 60) return diff + ' sec ago';
        if(diff < 3600) return Math.floor(diff/60) + ' min ago';
        if(diff < 86400) return Math.floor(diff/3600) + ' hr ago';
        return Math.floor(diff/86400) + ' day' + (Math.floor(diff/86400)>1?'s':'') + ' ago';
    }

    function updateTimes(){
        document.querySelectorAll('.timeline-time').forEach(el => {
            const ts = el.dataset.timestamp;
            if(ts){
                el.textContent = timeAgo(ts);
            }
        });
    }

    updateTimes();
    setInterval(updateTimes, 60000);

    // Load More
    let visible = 10;
    const logs = document.querySelectorAll('.logs-feed .log-card');
    logs.forEach((log,i) => log.style.display = i<visible ? 'flex' : 'none');

    const loadBtn = document.getElementById('loadMore');
    if(loadBtn){
        loadBtn.addEventListener('click', () => {
            visible += 10;
            logs.forEach((log,i) => { if(i<visible) log.style.display = 'flex'; });
            if(visible >= logs.length) loadBtn.style.display='none';
        });
    }
});

</script>

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
</body>
</html>
