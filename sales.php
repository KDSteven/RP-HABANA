<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$branch_id = $_SESSION['branch_id'] ?? null;

// Pending notifications (only for admin)
$pending = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE LOWER(status) = 'pending'");
    $pending = $result ? (int)($result->fetch_assoc()['pending'] ?? 0) : 0;
}

// Report filters
$reportType = $_GET['report'] ?? 'itemized'; // daily | weekly | monthly | itemized
$selectedMonth = $_GET['month'] ?? date('Y-m');
$startDate = $selectedMonth . "-01";
$endDate = date("Y-m-t", strtotime($startDate));

// Branch filter
$branchCondition = '';
if ($role === 'admin') {
    if (!empty($_GET['branch_id']) && is_numeric($_GET['branch_id'])) {
        $branch_id = intval($_GET['branch_id']);
        $branchCondition = " AND s.branch_id = $branch_id";
    }
} else if ($role === 'staff') {
    // Staff can only see their branch
    $branchCondition = " AND s.branch_id = $branch_id";
}

// Determine period/group
switch($reportType){
    case 'weekly':
        $periodLabel = "CONCAT('Week ', WEEK(s.sale_date,1),' - ',YEAR(s.sale_date))";
        $groupBy = "YEAR(s.sale_date), WEEK(s.sale_date,1)";
        break;
    case 'monthly':
        $periodLabel = "CONCAT(MONTHNAME(s.sale_date),' ',YEAR(s.sale_date))";
        $groupBy = "YEAR(s.sale_date), MONTH(s.sale_date)";
        break;
    case 'daily':
        $periodLabel = "DATE(s.sale_date)";
        $groupBy = "DATE(s.sale_date)";
        break;
    default: // itemized
        $periodLabel = "DATE(s.sale_date)";
        $groupBy = null;
}

// Build query
if($reportType === 'itemized'){
  $query = "
SELECT 
    s.sale_id,
    s.sale_date,
    b.branch_name,
    ROUND(s.total, 2) AS subtotal,
    ROUND(s.vat, 2) AS vat,
    ROUND(s.total + s.vat, 2) AS grand_total,
    
    -- Combine products and services
    TRIM(BOTH ', ' FROM CONCAT_WS(', ',
        (SELECT GROUP_CONCAT(CONCAT(p.product_name, ' (', si.quantity, 'x₱', FORMAT(si.price, 2), ')') SEPARATOR ', ')
         FROM sales_items si
         JOIN products p ON si.product_id = p.product_id
         WHERE si.sale_id = s.sale_id),
        (SELECT GROUP_CONCAT(CONCAT(sv.service_name, ' (₱', FORMAT(ss.price, 2), ')') SEPARATOR ', ')
         FROM sales_services ss
         JOIN services sv ON ss.service_id = sv.service_id
         WHERE ss.sale_id = s.sale_id)
    )) AS item_list,

    -- Refund totals and reasons
    COALESCE(SUM(r.refund_total), 0) AS total_refunded,
    TRIM(BOTH '; ' FROM COALESCE(
        NULLIF(GROUP_CONCAT(DISTINCT r.refund_reason ORDER BY r.refund_date SEPARATOR '; '), ''), ''
    )) AS refund_reason

FROM sales s
LEFT JOIN branches b ON s.branch_id = b.branch_id
LEFT JOIN sales_refunds r ON r.sale_id = s.sale_id
WHERE s.sale_date BETWEEN '$startDate' AND '$endDate'
$branchCondition
GROUP BY s.sale_id
ORDER BY s.sale_date DESC
";

}

$salesReportResult = $conn->query($query);


$pendingResetsCount = 0;
if ($role === 'admin') {
  $res = $conn->query("SELECT COUNT(*) AS c FROM password_resets WHERE status='pending'");
  $pendingResetsCount = $res ? (int)$res->fetch_assoc()['c'] : 0;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/R.P.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/notifications.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/sales.css?>v2">
    <audio id="notifSound" src="notif.mp3" preload="auto"></audio>
    <?php $pageTitle = 'Sales'; ?>
<title><?= htmlspecialchars("RP Habana — $pageTitle") ?></title>
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
  <?php if ($pendingResetsCount > 0): ?>
    <span class="badge-pending"><?= $pendingResetsCount ?></span>
  <?php endif; ?>
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
<h2>📊 Sales Report</h2>

<!-- Filters -->
<form method="get" class="mb-3 d-flex align-items-center gap-2">
    <!-- Report type -->
    <select name="report" onchange="this.form.submit()" class="form-select w-auto">
        <option value="itemized" <?= $reportType==='itemized'?'selected':'' ?>>Itemized</option>
        <option value="daily" <?= $reportType==='daily'?'selected':'' ?>>Daily</option>
        <option value="weekly" <?= $reportType==='weekly'?'selected':'' ?>>Weekly</option>
        <option value="monthly" <?= $reportType==='monthly'?'selected':'' ?>>Monthly</option>
    </select>

    <!-- Month -->
    <input type="month" name="month" value="<?= htmlspecialchars($selectedMonth) ?>" 
           onchange="this.form.submit()" class="form-control w-auto">

    <!-- Branch selector (admins only) -->
    <?php if ($role === 'admin'): ?>
        <select name="branch_id" onchange="this.form.submit()" class="form-select w-auto">
            <option value="">All Branches</option>
            <?php
            $branches = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name ASC");
            while ($b = $branches->fetch_assoc()):
                $sel = ($branch_id == $b['branch_id']) ? 'selected' : '';
            ?>
                <option value="<?= $b['branch_id'] ?>" <?= $sel ?>>
                    <?= htmlspecialchars($b['branch_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    <?php endif; ?>
</form>

<!-- Table -->
<div class="table-responsive">
<table class="table table-bordered">
<thead>
<?php if($reportType==='itemized'): ?>
<tr>
<tr>
  <th>Sale ID</th>
  <th>Date</th>
  <th>Branch</th>
  <th>Items</th>
  <th>Subtotal (₱)</th>
  <th>VAT (₱)</th>
  <th>Total (₱)</th>
  <th>Refund (₱)</th>
  <th>Reason</th>
  <th>Status</th>
</tr>

<?php else: ?>
<tr>
<th>Period</th><th>Total Sales</th><th>Transactions</th>
</tr>
<?php endif; ?>
</thead>
<tbody>
<?php
$salesDataArr = [];



// --- Sort by sale_date descending (optional but nice for clarity) ---
usort($salesDataArr, fn($a,$b) => strcmp($b['sale_date'], $a['sale_date']));

?>
<?php
// --- Fetch all sales once ---
$salesDataArr = [];
if ($salesReportResult && $salesReportResult->num_rows > 0) {
    while($row = $salesReportResult->fetch_assoc()) {
        // Replace commas with line breaks for better readability
        $row['item_list'] = str_replace(',', '<br>', $row['item_list']);
        $salesDataArr[] = $row;
    }
}

// --- Sort newest first ---
usort($salesDataArr, fn($a, $b) => strcmp($b['sale_date'], $a['sale_date']));

foreach ($salesDataArr as $row):
    $total    = (float)$row['grand_total'];
    $refunded = (float)$row['total_refunded'];

    // --- Determine refund status ---
    if ($refunded <= 0) {
        $status = 'Not Refunded';
        $badge  = 'secondary';
    } elseif ($refunded < $total - 0.01) {
        $status = 'Partial';
        $badge  = 'warning';
    } elseif ($refunded >= $total - 0.01 && $refunded <= $total + 0.01) {
        $status = 'Full';
        $badge  = 'success';
    } else {
        $status = 'Over-refunded';
        $badge  = 'danger';
    }
?>
<tr>
  <td><?= htmlspecialchars($row['sale_id']) ?></td>
  <td><?= htmlspecialchars($row['sale_date']) ?></td>
  <td><?= htmlspecialchars($row['branch_name'] ?? 'N/A') ?></td>
  <td style="white-space: normal; line-height: 1.5em;"><?= $row['item_list'] ?: '—' ?></td>
  <td>₱<?= number_format($row['subtotal'], 2) ?></td>
  <td>₱<?= number_format($row['vat'], 2) ?></td>
  <td><strong>₱<?= number_format($row['grand_total'], 2) ?></strong></td>
  <td class="<?= $refunded > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
      ₱<?= number_format($refunded, 2) ?>
  </td>
  <td><?= htmlspecialchars($row['refund_reason'] ?: '—') ?></td>
  <td><span class="badge bg-<?= $badge ?>"><?= $status ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<script src="notifications.js"></script>
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

<script src="sidebar.js"></script>

</body>
</html>
