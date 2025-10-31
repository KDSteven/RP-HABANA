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

date_default_timezone_set('Asia/Manila'); // keep reports aligned with local time

// ---- Inputs / defaults
$reportType    = $_GET['report'] ?? 'itemized'; // daily | weekly | monthly | itemized
$selectedMonth = $_GET['month']  ?? date('Y-m');

// Compute month range with full-day coverage (00:00:00 → 23:59:59)
$startDateTime = date('Y-m-01 00:00:00', strtotime($selectedMonth . '-01'));
$endDateTime   = date('Y-m-t 23:59:59',  strtotime($selectedMonth . '-01'));

// ---- Dynamic WHERE parts
$conds  = ["s.sale_date >= ? AND s.sale_date <= ?"];
$params = [$startDateTime, $endDateTime];
$types  = "ss";

// Branch filter
if ($role === 'admin' && !empty($_GET['branch_id']) && ctype_digit($_GET['branch_id'])) {
  $branch_id = (int)$_GET['branch_id'];
  $conds[] = "s.branch_id = ?";
  $params[] = $branch_id;
  $types   .= "i";
} elseif ($role === 'staff' && $branch_id) {
  $conds[] = "s.branch_id = ?";
  $params[] = (int)$branch_id;
  $types   .= "i";
}

// Keyword search (branch name, refund reason, exact sale_id if numeric)
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
  $conds[]  = "(b.branch_name LIKE ? OR r.refund_reason LIKE ?" . (ctype_digit($q) ? " OR s.sale_id = ?" : "") . ")";
  $params[] = "%$q%";
  $params[] = "%$q%";
  $types   .= "ss";
  if (ctype_digit($q)) { $params[] = (int)$q; $types .= "i"; }
}

$whereSql = $conds ? ("WHERE " . implode(' AND ', $conds)) : "";

// ---- Period label / grouping for non-itemized
switch ($reportType) {
  case 'daily':
    $periodLabel = "DATE(s.sale_date)";
    $groupBy     = "DATE(s.sale_date)";
    break;
  case 'weekly':
    $periodLabel = "CONCAT('Week ', WEEK(s.sale_date, 1), ' - ', YEAR(s.sale_date))";
    $groupBy     = "YEAR(s.sale_date), WEEK(s.sale_date, 1)";
    break;
  case 'monthly':
    $periodLabel = "CONCAT(MONTHNAME(s.sale_date), ' ', YEAR(s.sale_date))";
    $groupBy     = "YEAR(s.sale_date), MONTH(s.sale_date)";
    break;
  default: // itemized
    $periodLabel = null;
    $groupBy     = null;
}

// ---- Build SQL (itemized vs grouped)
if ($reportType === 'itemized') {
  $sql = "
    SELECT 
      s.sale_id,
      s.sale_date,
      b.branch_name,

      ROUND(s.total, 2)               AS subtotal,
      ROUND(s.vat, 2)                 AS vat,
      ROUND(s.total + s.vat, 2)       AS grand_total,

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

      -- aggregate refunds, cap to grand total
      ROUND(COALESCE(SUM(r.refund_total), 0), 2)                           AS total_refunded_raw,
      ROUND(LEAST(COALESCE(SUM(r.refund_total), 0), (s.total + s.vat)), 2) AS total_refunded,

      TRIM(BOTH '; ' FROM COALESCE(
        NULLIF(GROUP_CONCAT(DISTINCT r.refund_reason ORDER BY r.refund_date SEPARATOR '; '), ''),
        ''
      )) AS refund_reason

    FROM sales s
    LEFT JOIN branches b    ON s.branch_id = b.branch_id
    LEFT JOIN sales_refunds r ON r.sale_id = s.sale_id
    $whereSql
    GROUP BY s.sale_id
    ORDER BY s.sale_date DESC
  ";
} else {
  // grouped (daily/weekly/monthly)
  $sql = "
    SELECT
      $periodLabel AS period,
      COUNT(DISTINCT s.sale_id)              AS total_transactions,
      ROUND(SUM(s.total + s.vat), 2)         AS total_sales,
      ROUND(COALESCE(SUM(r.refund_total), 0), 2) AS total_refunded
    FROM sales s
    LEFT JOIN sales_refunds r ON r.sale_id = s.sale_id
    LEFT JOIN branches b      ON s.branch_id = b.branch_id
    $whereSql
    GROUP BY $groupBy
    ORDER BY MIN(s.sale_date) DESC
  ";
}

// ---- Execute safely
$stmt = $conn->prepare($sql);
if ($types !== '' && $params) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$salesReportResult = $stmt->get_result();
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
<h1 style="display:flex;align-items:center;gap:8px;">
  <i class="fas fa-chart-line" style="color:#f97316;"></i> Sales Report
</h1>


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

    <?php
      $q = trim($_GET['q'] ?? '');
    ?>
    <input
      type="text"
      name="q"
      value="<?= htmlspecialchars($q) ?>"
      class="form-control w-25"
      placeholder="Search sale ID, branch, refund reason…">

    <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
      <i class="fas fa-search me-1"></i> Search
    </button>
</form>

<!-- Table -->
<div class="table-responsive">
<table class="table table-bordered">
<thead>
<?php if ($reportType === 'itemized'): ?>
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
    <th>Period</th>
    <th>Total Sales (₱)</th>
    <th>Transactions</th>
    <th>Total Refund (₱)</th>
  </tr>
<?php endif; ?>
</thead>

<tbody>
<?php if ($reportType === 'itemized'): ?>
  <?php
  $salesDataArr = [];
  if ($salesReportResult && $salesReportResult->num_rows > 0) {
      while ($row = $salesReportResult->fetch_assoc()) {
          $row['item_list'] = str_replace(',', '<br>', $row['item_list'] ?? '');
          $salesDataArr[] = $row;
      }
  }

  usort($salesDataArr, fn($a, $b) => strcmp($b['sale_date'], $a['sale_date']));

  foreach ($salesDataArr as $row):
    $gt   = round((float)$row['grand_total'], 2);
    $rf   = round((float)$row['total_refunded'], 2); // capped by SQL
    $eps  = 0.01;

    if ($rf <= 0) {
        $status = 'Not Refunded'; 
        $badge = 'secondary';
    } elseif ($rf + $eps < $gt) {
        $status = 'Partial';
        $badge = 'warning';
    } elseif ($rf <= $gt + $eps) {
        $status = 'Full';
        $badge = 'success';
    } else {
        // should no longer happen because SQL caps, but kept for safety
        $status = 'Over-refunded';
        $badge = 'danger';
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
          ₱<?= number_format($rf, 2) ?>
      </td>
      <td><?= htmlspecialchars($row['refund_reason'] ?: '—') ?></td>
      <td><span class="badge bg-<?= $badge ?>"><?= $status ?></span></td>
  </tr>
  <?php endforeach; ?>

<?php else: ?>
  <?php if ($salesReportResult && $salesReportResult->num_rows > 0): ?>
    <?php while ($row = $salesReportResult->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['period']) ?></td>
        <td>₱<?= number_format((float)$row['total_sales'], 2) ?></td>
        <td><?= (int)$row['total_transactions'] ?></td>
        <td>₱<?= number_format((float)$row['total_refunded'], 2) ?></td>
      </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="4" class="text-center text-muted">No data available</td></tr>
  <?php endif; ?>
<?php endif; ?>
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
