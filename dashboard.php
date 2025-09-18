<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

include 'config/db.php';


// -------------------- USER INFO --------------------
$user_id   = $_SESSION['user_id'];
$role      = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? null;




// Notifications (Pending Approvals)
$pending = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'")->fetch_assoc()['pending'];

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


// -------------------- FILTERS --------------------
$filters = [
    'fiscal_year' => $_GET['fiscal_year'] ?? '',
    'month'       => $_GET['month'] ?? '',
    'as_of_date'  => $_GET['as_of_date'] ?? date('Y-m-d'),
    'branch_id'   => $_GET['branch_id'] ?? $branch_id,
];

$years = range(date('Y') - 5, date('Y')); // last 5 years
// -------------------- DATE RANGE HELPER --------------------
function getDateRange($type, $value = '') {
    switch ($type) {
        case 'fiscal_year':
            if ($value) {
                $start = $value . '-01-01';
                $end   = $value . '-12-31';
            } else {
                $start = date('Y') . '-01-01';
                $end   = date('Y') . '-12-31';
            }
            break;

        case 'month':
            if ($value) {
                $start = $value . '-01'; // e.g. 2025-09
            } else {
                $start = date('Y-m') . '-01';
            }
            $end = date("Y-m-t", strtotime($start));
            break;

        case 'as_of_date':
            $date  = $value ?: date('Y-m-d');
            $start = $date . ' 00:00:00';
            $end   = $date . ' 23:59:59';
            break;

        default: // current month (fallback)
            $start = date('Y-m-01');
            $end   = date('Y-m-t');
            break;
    }
    return [$start, $end];
}

// -------------------- DATE RANGE --------------------
if (!empty($filters['fiscal_year'])) {
    [$startDate, $endDate] = getDateRange('fiscal_year', $filters['fiscal_year']);
} elseif (!empty($filters['month'])) {
    [$startDate, $endDate] = getDateRange('month', $filters['month']);
} else {
    [$startDate, $endDate] = getDateRange('month'); // default current month
}

[, $asOfDateEnd] = getDateRange('as_of_date', $filters['as_of_date']);


// -------------------- HELPER FUNCTIONS --------------------
function branchCondition($branchId, $alias = '') {
    if (!$branchId) return '';
    $prefix = $alias ? $alias . '.' : '';
    return " AND {$prefix}branch_id=" . intval($branchId);
}

function getInventoryCount($conn, $asOfDate, $branchId = null, $mode = 'total') {
    $sql = "SELECT COUNT(*) AS count FROM inventory i";
    if ($mode !== 'total') $sql .= " JOIN products p ON i.product_id = p.product_id";

    $sql .= " WHERE (i.archived = 0 OR (i.archived = 1 AND i.archived_at > ?))";

    if ($mode === 'low') $sql .= " AND i.stock <= p.critical_point";
    if ($mode === 'out') $sql .= " AND i.stock = 0";

    if ($branchId) $sql .= branchCondition($branchId);

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $asOfDate);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();
    return $count;
}

function getTotalSales($conn, $startDate, $endDate, $branchId = null) {
    $sql = "SELECT IFNULL(SUM(total),0) AS total_sales FROM sales
            WHERE sale_date BETWEEN ? AND ?";
    if ($branchId) $sql .= branchCondition($branchId);

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total_sales'] ?? 0;
    $stmt->close();
    return $total;
}
function getFastItemIds($conn, $start, $end, $branchId = null) {
    $sql = "
        SELECT p.product_id
        FROM products p
        LEFT JOIN sales_items si ON si.product_id = p.product_id
        LEFT JOIN sales s ON s.sale_id = si.sale_id
        WHERE s.sale_date BETWEEN ? AND ?
        " . ($branchId ? "AND s.branch_id = ?" : "") . "
        GROUP BY p.product_id
        HAVING SUM(si.quantity) >= 3
    ";
    $stmt = $conn->prepare($sql);
    if ($branchId) {
        $stmt->bind_param("ssi", $start, $end, $branchId);
    } else {
        $stmt->bind_param("ss", $start, $end);
    }
    $stmt->execute();
    $ids = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'product_id');
    $stmt->close();
    return $ids;
}

function getMovingItems($conn, $start, $end, $branchId = null, $type = 'fast', $limit = 5) {
    $excludeFastIds = '0';

    if ($type === 'slow' || $type === 'notmoving') {
        $fastIds = getFastItemIds($conn, $start, $end, $branchId);
        $excludeFastIds = !empty($fastIds) ? implode(',', array_map('intval', $fastIds)) : '0';
    }

    switch ($type) {
        case 'fast': 
            $order = "DESC"; 
            $having = "HAVING SUM(si.quantity) >= 3"; 
            break;
        case 'slow': 
            $order = "ASC"; 
            $having = "HAVING SUM(si.quantity) > 0"; 
            break;
        case 'notmoving': 
            $order = "ASC"; 
            $having = ""; 
            break;
    }

    $notInClause = ($type === 'slow' || $type === 'notmoving')
        ? " AND p.product_id NOT IN ($excludeFastIds)" : "";

    if ($type === 'notmoving') {
        $sql = "
            SELECT p.product_id, p.product_name, COALESCE(SUM(si.quantity),0) AS total_qty
            FROM products p
            LEFT JOIN sales_items si ON si.product_id = p.product_id
            LEFT JOIN sales s 
                ON s.sale_id = si.sale_id 
                AND s.sale_date BETWEEN ? AND ?
                " . ($branchId ? "AND s.branch_id = ?" : "") . "
            WHERE 1 $notInClause
            GROUP BY p.product_id
            ORDER BY total_qty ASC
            LIMIT $limit
        ";
    } else {
        $sql = "
            SELECT p.product_id, p.product_name, COALESCE(SUM(si.quantity),0) AS total_qty
            FROM products p
            LEFT JOIN sales_items si ON si.product_id = p.product_id
            LEFT JOIN sales s ON s.sale_id = si.sale_id
            WHERE s.sale_date BETWEEN ? AND ? $notInClause
            " . ($branchId ? "AND s.branch_id = ?" : "") . "
            GROUP BY p.product_id
            $having
            ORDER BY total_qty $order
            LIMIT $limit
        ";
    }

    $stmt = $conn->prepare($sql);
    if ($branchId) {
        $stmt->bind_param("ssi", $start, $end, $branchId);
    } else {
        $stmt->bind_param("ss", $start, $end);
    }
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $items;
}

// -------------------- DASHBOARD DATA --------------------
$totalProducts  = getInventoryCount($conn, $asOfDateEnd, $filters['branch_id'], 'total');
$lowStocks      = getInventoryCount($conn, $asOfDateEnd, $filters['branch_id'], 'low');
$outOfStocks    = getInventoryCount($conn, $asOfDateEnd, $filters['branch_id'], 'out');

$totalSales     = getTotalSales($conn, $startDate, $endDate, $filters['branch_id']);
$fastItems      = getMovingItems($conn, $startDate, $endDate, $filters['branch_id'], 'fast', 5);
$slowItems      = getMovingItems($conn, $startDate, $endDate, $filters['branch_id'], 'slow', 5);
$notMovingItems = getMovingItems($conn, $startDate, $endDate, $filters['branch_id'], 'notmoving', 5);

// -------------------- NOTIFICATIONS --------------------
$pending = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'")->fetch_assoc()['pending'] ?? 0;

// -------------------- SERVICE JOBS --------------------
$serviceJobQuery = "
    SELECT s.service_name, COUNT(*) as count
    FROM sales_services ss
    JOIN services s ON ss.service_id = s.service_id
    JOIN sales sa ON ss.sale_id = sa.sale_id
    WHERE sa.sale_date BETWEEN ? AND ?".branchCondition($filters['branch_id'],'sa')."
    GROUP BY s.service_name
    ORDER BY count DESC
";
$stmt = $conn->prepare($serviceJobQuery);
$stmt->bind_param("ss",$startDate,$endDate);
$stmt->execute();
$serviceJobData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
if(empty($serviceJobData)) $serviceJobData[]=['service_name'=>'No Services Sold','count'=>0];

// compute $pendingResets count (put near the query)
$pendingResetsCount = 0;
if ($role === 'admin') {
  $res = $conn->query("SELECT COUNT(*) AS c FROM password_resets WHERE status='pending'");
  $pendingResetsCount = $res ? (int)$res->fetch_assoc()['c'] : 0;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<?php $pageTitle =''; ?>
<title><?= htmlspecialchars("RP Habana — $pageTitle") ?><?= strtoupper($role) ?> Dashboard</title>
<link rel="icon" href="img/R.P.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/dashboard.css?v=<?= filemtime('css/dashboard.css') ?>">
<link rel="stylesheet" href="css/notifications.css">
<link rel="stylesheet" href="css/sidebar.css">
<audio id="notifSound" src="img/notif.mp3" preload="auto"></audio>
</head>
<body class="dashboard-page">
<div class="sidebar" >
<h2>
    <?= strtoupper($role) ?>
    <span class="notif-wrapper">
        <i class="fas fa-bell" id="notifBell"></i>
        <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>>0</span>
    </span>
</h2>


    <!-- Common -->
    <a href="dashboard.php" class="active"><i class="fas fa-tv"></i> Dashboard</a>

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
  </div>
</div>

  <!-- Sales (normal link with active state) -->
  <a href="sales.php" class="<?= $self === 'sales.php' ? 'active' : '' ?>">
    <i class="fas fa-receipt"></i> Sales
  </a>


<a href="accounts.php" class="<?= $self === 'accounts.php' ? 'active' : '' ?>">
  <i class="fas fa-users"></i> Accounts
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
     <?php
        $transferNotif = $transferNotif ?? 0; // if not set, default to 0
        ?>
    <?php if ($role === 'stockman'): ?>
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
    <?php endif; ?>
    <!-- Staff Links -->
    <?php if ($role === 'staff'): ?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content"><!-- Combined Filter Form -->
<form method="GET" class="combined-filter">
    <div>
        <label for="fiscal_year">Fiscal Year:</label>
<select name="fiscal_year" id="fiscal_year">
    <?php foreach ($years as $y): ?>
        <option value="<?= $y ?>" <?= ($y == $filters['fiscal_year'] ? 'selected' : '') ?>><?= $y ?></option>
    <?php endforeach; ?>
</select>

    </div>

    <div>
        <label for="as_of_date">As of:</label>
        <input type="date" id="as_of_date" name="as_of_date" value="<?= htmlspecialchars($_GET['as_of_date'] ?? date('Y-m-d')) ?>">
    </div>

    <div>
        <label for="month">Month:</label>
        <input type="month" id="month" name="month" value="<?= htmlspecialchars($_GET['month'] ?? date('Y-m')) ?>">
    </div>

    <div>
        <label for="branch">Branch:</label>
        <?php if ($role === 'stockman' || $role === 'staff'): 
            $branchData = $conn->query("SELECT branch_name FROM branches WHERE branch_id = $branch_id")->fetch_assoc();
            $branchName = $branchData ? htmlspecialchars($branchData['branch_name']) : 'Your Branch';
        ?>
            <input type="hidden" name="branch_id" value="<?= $branch_id ?>">
            <select id="branch" name="branch_id" disabled>
                <option value="<?= $branch_id ?>" selected><?= $branchName ?></option>
            </select>
        <?php else: 
            $branches = $conn->query("SELECT branch_id, branch_name FROM branches");
        ?>
            <select id="branch" name="branch_id">
                <option value="">All Branches</option>
                <?php while ($b = $branches->fetch_assoc()): ?>
                    <option value="<?= $b['branch_id'] ?>" <?= (isset($_GET['branch_id']) && $_GET['branch_id'] == $b['branch_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['branch_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        <?php endif; ?>
    </div>

    <button type="submit">Filter</button>
</form>
<!-- Summary Cards -->
<div class="cards">
    <div class="card green">
        <h3>Total Products</h3>
        <p><?= $totalProducts ?></p>
        <small>Inventory as of <?= htmlspecialchars($filters['as_of_date']) ?></small>
    </div>
    <div class="card orange">
        <h3>Low Stocks</h3>
        <p><?= $lowStocks ?></p>
        <small>Inventory as of <?= htmlspecialchars($filters['as_of_date']) ?></small>
    </div>
    <div class="card red">
        <h3>Out of Stocks</h3>
        <p><?= $outOfStocks ?></p>
        <small>Inventory as of <?= htmlspecialchars($filters['as_of_date']) ?></small>
    </div>
</div>

<div class="sections" style="display:flex; gap:20px; flex-wrap:wrap; align-items:flex-start;">
    <!-- Monthly Sales Overview -->
    <section style="flex:1 1 250px; min-width:150px;">
        <h2>Monthly Sales Overview</h2>
        <canvas id="salesChart" style="width:100%; height:150px;"></canvas>
    </section>

    <!-- Service Jobs -->
    <section style="flex:1 1 250px; min-width:200px;">
        <h2>Service Jobs</h2>
        <canvas id="serviceJobChart" style="width:100%; height:150px;"></canvas>
    </section>

</div>


<div class="dashboard-page bottom flex-sections">
    <!-- Fast Moving Items -->
    <section class="fast-moving">
        <h2>Fast Moving Items</h2>
        <div class="scrollable-list">
            <ul>
                <?php 
                $maxQty = max(array_column($fastItems, 'total_qty') ?: [0]);
                foreach ($fastItems as $item):
                    $percentage = ($maxQty > 0) ? ($item['total_qty'] / $maxQty) * 100 : 0;
                ?>
                <li class="item-card">
                    <div class="item-row">
                        <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                        <span class="item-qty"><?= $item['total_qty'] ?> sold</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width:<?= round($percentage) ?>%; background: #28a745;"></div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- Slow Moving Items -->
    <section class="slow-moving">
        <h2>Slow Moving Items</h2>
        <div class="scrollable-list">
            <ul>
                <?php 
                $slowMax = max(array_column($slowItems, 'total_qty') ?: [0]);
                foreach ($slowItems as $item):
                    $percentage = ($slowMax > 0) ? ($item['total_qty'] / $slowMax) * 100 : 0;
                ?>
                <li class="item-card">
                    <div class="item-row">
                        <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                        <span class="item-qty"><?= $item['total_qty'] ?> sold</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width:<?= round($percentage) ?>%; background: #ffc107;"></div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

  <!-- Not Moving Items -->
<section class="not-moving">
    <h2>Not Moving Items</h2>
    <div class="scrollable-list">
        <ul>
            <?php if (!empty($notMovingItems)): ?>
                <?php foreach ($notMovingItems as $item): ?>
                    <li class="item-card">
                        <div class="item-row">
                            <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                            <span class="item-qty"><?= htmlspecialchars($item['total_qty']) ?> sold</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress" style="width:<?= intval($item['total_qty']) ?>%; background: #dc3545;"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="item-card">No items found.</li>
            <?php endif; ?>
        </ul>
    </div>
</section>


<!-- NOTIFICATIONS -->
<script src="notifications.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Fetch Monthly Sales for Chart 
// FIX BY BRANCH
// Pass selected month and branch to the request
const selectedMonth = document.getElementById('month').value;
const selectedBranch = document.getElementById('branch').value;

fetch(`monthly_sale.php?month=${selectedMonth}&branch_id=${selectedBranch}`)
.then(r => r.json())
.then(data => {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.months,
            datasets: [{
                label: 'Sales (₱)',
                data: data.sales,
                backgroundColor: '#f7931e'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Toggle Bar/Line
    document.getElementById('salesChart').addEventListener('dblclick', () => {
        chart.config.type = (chart.config.type === 'bar') ? 'line' : 'bar';
        chart.update();
    });
});

</script>
<script>
console.log(<?= json_encode($serviceJobData) ?>);
</script>

<script>
const serviceJobData = <?= json_encode($serviceJobData) ?> || [];

if (serviceJobData.length > 0) {
    const ctx = document.getElementById('serviceJobChart').getContext('2d');
    ctx.canvas.height = serviceJobData.length * 50; // adjust height

    const labels = serviceJobData.map(item => item.service_name);
    const data = serviceJobData.map(item => item.count || 0);

    const colors = labels.map(() => `hsl(${Math.floor(Math.random()*360)}, 70%, 60%)`);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Services',
                data: data,
                backgroundColor: colors
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true },
                y: { ticks: { autoSkip: false } }
            }
        }
    });
}
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


</body>
</html>
