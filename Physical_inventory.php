<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}
include 'config/db.php';
include 'functions.php';

$user_id   = $_SESSION['user_id'];
$role      = $_SESSION['role'];
$branch_id = $_SESSION['branch_id'] ?? null;

// Pending notifications
$pending = 0;
if ($role === 'admin') {
  $resPT  = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='pending'");
  $pendingTransfers = $resPT ? (int)($resPT->fetch_assoc()['pending'] ?? 0) : 0;
}

// Branch selection
$branches        = $conn->query("SELECT branch_id, branch_name FROM branches");
$selected_branch = ($role === 'admin') ? ($_GET['branch'] ?? $branch_id) : $branch_id;
$selected_branch = (int)$selected_branch;

// =====================
// Export CSV (Admin)
// =====================
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $role === 'admin') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=physical_inventory_log.csv');
    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, [
        'Product ID',
        'Product Name',
        'Category',
        'System Stock',
        'Physical Count',
        'Discrepancy',     // always positive
        'Status',
        'Counted By',
        'Branch',
        'Count Date'
    ]);

    // Latest counts per product for this branch + computed positive discrepancy
    $queryCSV = "
    SELECT 
        p.product_id,
        p.product_name,
        p.category,
        i.stock AS system_stock,
        COALESCE(pi.physical_count, i.stock) AS physical_count,
        ABS(COALESCE(pi.physical_count, i.stock) - i.stock) AS discrepancy,
        CASE
            WHEN pi.physical_count IS NULL THEN 'Pending'
            WHEN pi.physical_count > i.stock THEN 'Overstock'
            WHEN pi.physical_count < i.stock THEN 'Understock'
            ELSE 'Complete'
        END AS status,
        u.username AS counted_by,
        b.branch_name AS branch,
        pi.count_date
    FROM products p
    JOIN inventory i 
        ON p.product_id = i.product_id 
       AND i.branch_id = {$selected_branch}
    LEFT JOIN (
        SELECT t1.*
        FROM physical_inventory t1
        INNER JOIN (
            SELECT product_id, MAX(count_date) AS latest
            FROM physical_inventory
            WHERE branch_id = {$selected_branch}
            GROUP BY product_id
        ) t2 ON t1.product_id = t2.product_id AND t1.count_date = t2.latest
        WHERE t1.branch_id = {$selected_branch}
    ) pi ON p.product_id = pi.product_id
    LEFT JOIN users u   ON pi.counted_by = u.id
    LEFT JOIN branches b ON i.branch_id = b.branch_id
    ORDER BY p.product_name ASC
";

    if ($resultCSV = $conn->query($queryCSV)) {
        if ($resultCSV->num_rows > 0) {
            while ($row = $resultCSV->fetch_assoc()) {
                // Ensure output order matches headers
                fputcsv($output, [
                    $row['product_id'],
                    $row['product_name'],
                    $row['category'],
                    $row['system_stock'],
                    $row['physical_count'],
                    $row['discrepancy'],   // already positive
                    $row['status'],
                    $row['counted_by'],
                    $row['branch'],
                    $row['count_date']
                ]);
            }
        } else {
            fputcsv($output, ['No data found']);
        }
    } else {
        fputcsv($output, ['Query failed']);
    }

    fclose($output);
    exit;
}

// =====================
// Fetch inventory data (for page)
// =====================
$query = "
    SELECT 
        p.product_id,
        p.product_name,
        p.category,
        i.stock AS system_stock,
        COALESCE(pi.physical_count, '') AS physical_count,
        ABS(COALESCE(pi.physical_count, i.stock) - i.stock) AS discrepancy,
        CASE
            WHEN pi.physical_count IS NULL THEN 'Pending'
            WHEN pi.physical_count > i.stock THEN 'Overstock'
            WHEN pi.physical_count < i.stock THEN 'Understock'
            ELSE 'Complete'
        END AS status
    FROM products p
    JOIN inventory i 
        ON p.product_id = i.product_id 
       AND i.branch_id = {$selected_branch}
    LEFT JOIN (
        SELECT t1.*
        FROM physical_inventory t1
        INNER JOIN (
            SELECT product_id, MAX(count_date) AS latest
            FROM physical_inventory
            WHERE branch_id = {$selected_branch}
            GROUP BY product_id
        ) t2 ON t1.product_id = t2.product_id AND t1.count_date = t2.latest
        WHERE t1.branch_id = {$selected_branch}
    ) pi ON p.product_id = pi.product_id
    ORDER BY p.product_name ASC
";
$inventoryRes = $conn->query($query);
if (!$inventoryRes) {
    die('Inventory query failed: ' . $conn->error);
}


// Last saved timestamp for this branch
$lastSavedRow = $conn->query("
    SELECT MAX(count_date) AS last_saved 
    FROM physical_inventory 
    WHERE branch_id = {$selected_branch}
");
$lastSaved = $lastSavedRow ? ($lastSavedRow->fetch_assoc()['last_saved'] ?? 'Never') : 'Never';

// =====================
// Compute inventory stats (map new labels)
// =====================
$totalProducts = 0;
$stats = ['overstock' => 0, 'understock' => 0, 'complete' => 0, 'pending' => 0];

if ($inventoryRes) {
    while ($rowTemp = $inventoryRes->fetch_assoc()) {
        $totalProducts++;
        $status = $rowTemp['status'];

        if ($status === 'Overstock')      $stats['overstock']++;
        elseif ($status === 'Understock') $stats['understock']++;
        elseif ($status === 'Complete')   $stats['complete']++;
        else                               $stats['pending']++; // Pending or null
    }

    // rewind for table render
    $inventoryRes->data_seek(0);
}


// Keep your existing card labels by mapping:
$match        = $stats['complete'];                          // "Match" card = Complete
$mismatch     = $stats['overstock'] + $stats['understock'];  // "Mismatch" card = Over+Under
$pendingCount = $stats['pending'];

// ----- Badges used in the sidebar (compute ONCE) -----
$pendingResetsCount   = 0;
$pendingTransfers     = 0;
$pendingStockIns      = 0;
$pendingTotalInventory = 0;

if ($role === 'admin') {
    // Password reset requests
    if ($res = $conn->query("SELECT COUNT(*) AS c FROM password_resets WHERE LOWER(status)='pending'")) {
        $pendingResetsCount = (int)($res->fetch_assoc()['c'] ?? 0);
    }

    // Transfer + Stock-in requests
    if ($res = $conn->query("SELECT COUNT(*) AS c FROM transfer_requests WHERE LOWER(status)='pending'")) {
        $pendingTransfers = (int)($res->fetch_assoc()['c'] ?? 0);
    }
    if ($res = $conn->query("SELECT COUNT(*) AS c FROM stock_in_requests WHERE LOWER(status)='pending'")) {
        $pendingStockIns = (int)($res->fetch_assoc()['c'] ?? 0);
    }

    $pendingTotalInventory = $pendingTransfers + $pendingStockIns;
}

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
<?php $pageTitle = 'Physical Inventory'; ?>
<title><?= htmlspecialchars("RP Habana — $pageTitle") ?></title>
<link rel="icon" href="img/R.P.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/notifications.css">
<link rel="stylesheet" href="css/physical_inventory.css?>v2">
</head>
<body class="inventory-page">

<div class="sidebar">
  <h2 class="user-heading">
    <span class="role"><?= htmlspecialchars(strtoupper($role), ENT_QUOTES) ?></span>
    <?php if ($currentName !== ''): ?>
      <span class="name"> (<?= htmlspecialchars($currentName, ENT_QUOTES) ?>)</span>
    <?php endif; ?>
    <span class="notif-wrapper">
      <i class="fas fa-bell" id="notifBell"></i>
      <span id="notifCount" <?= $pendingTotalInventory > 0 ? '' : 'style="display:none;"' ?>><?= (int)$pendingTotalInventory ?></span>
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
  <span><i class="fas fa-box"></i> Inventory
    <?php if ($pendingTotalInventory > 0): ?>
      <span class="badge-pending"><?= $pendingTotalInventory ?></span>
    <?php endif; ?>
  </span>
    <i class="fas fa-chevron-right caret"></i>
  </button>
  <div class="submenu" <?= $invOpen ? '' : 'hidden' ?>>
    <a href="inventory.php" class="<?= $self === 'inventory.php' ? 'active' : '' ?>">
        <i class="fas fa-list"></i> Inventory List
        <?php if ($pendingTotalInventory > 0): ?>
          <span class="badge-pending"><?= $pendingTotalInventory ?></span>
        <?php endif; ?>
    </a>
    <a href="physical_inventory.php" class="<?= $self === 'physical_inventory.php' ? 'active' : '' ?>">
      <i class="fas fa-warehouse"></i> Physical Inventory
    </a>
      </a>
        <a href="barcode-print.php<?php 
        $b = (int)($_SESSION['current_branch_id'] ?? 0);
        echo $b ? ('?branch='.$b) : '';?>" class="<?= $self === 'barcode-print.php' ? 'active' : '' ?>">
        <i class="fas fa-barcode"></i> Barcode Labels
    </a>
  </div>
</div>
    <!-- Current page -->
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
        <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>


<div class="container py-4">
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <!-- Branch Selector Card -->
    <div class="card shadow-sm p-3 flex-grow-1" style="min-width:200px;">
        <label class="fw-bold mb-2">Select Branch:</label>
        <?php if($role==='admin'): ?>
            <form method="GET" class="d-flex gap-2">
                <select name="branch" class="form-select" onchange="this.form.submit()">
                    <?php
                    $branches = $conn->query("SELECT branch_id, branch_name FROM branches");
                    while($b=$branches->fetch_assoc()):
                    ?>
                        <option value="<?= $b['branch_id'] ?>" <?= ($selected_branch==$b['branch_id'])?'selected':'' ?>>
                            <?= htmlspecialchars($b['branch_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        <?php else: ?>
            <input type="text" class="form-control" value="<?= htmlspecialchars($branches->fetch_assoc()['branch_name'] ?? 'Your Branch') ?>" disabled>
        <?php endif; ?>
    </div>

    <!-- Last Saved Card -->
    <div class="card shadow-sm p-3 text-center flex-grow-1" style="min-width:180px; background: linear-gradient(135deg, #6c757d, #343a40); color:white;">
        <i class="fas fa-clock me-2"></i>
        <strong>Last Saved:</strong> <?= $lastSaved ?>
    </div>
</div>

<!-- Stats Cards -->
<div class="d-flex flex-wrap gap-3 mb-3">
    <div class="card shadow-sm p-3 flex-fill text-white" style="background: linear-gradient(135deg,#007bff,#00c6ff);">
        <h6>Total Products <i class="fas fa-box"></i></h6>
        <h4><?= $totalProducts ?></h4>
    </div>
    <div class="card shadow-sm p-3 flex-fill text-white" style="background: linear-gradient(135deg,#28a745,#85e085);">
        <h6>Match <i class="fas fa-check-circle"></i></h6>
        <h4><?= $match ?></h4>
    </div>
    <div class="card shadow-sm p-3 flex-fill text-white" style="background: linear-gradient(135deg,#dc3545,#ff7b7b);">
        <h6>Mismatch <i class="fas fa-exclamation-circle"></i></h6>
        <h4><?= $mismatch ?></h4>
    </div>
    <div class="card shadow-sm p-3 flex-fill text-white" style="background: linear-gradient(135deg,#6c757d,#adb5bd);">
        <h6>Pending <i class="fas fa-hourglass-half"></i></h6>
        <h4><?= $pendingCount ?></h4>
    </div>
</div>

<div class="d-flex gap-2 mb-3 flex-wrap align-items-center shadow-sm p-2 rounded" style="background-color:#f8f9fa; position: sticky; top:0; z-index: 10;">
    <input type="text" id="searchInput" class="form-control w-auto" placeholder="Search products..." onkeyup="filterTable()">
    
    <?php if ($role==='admin'): ?>
      <a
        class="btn btn-success"
        href="?export=csv&branch=<?= urlencode((string)$selected_branch) ?>"
      >
        <i class="fas fa-file-csv"></i> Export CSV
      </a>
    <?php endif; ?>
        
    
    <div class="ms-auto fw-bold">
        Total Mismatch: <span id="totalMismatch"><?= $mismatch ?></span>
    </div>
</div>
<!-- TABLE -->
<div class="card shadow-sm mb-4">
  <div class="card-body table-responsive">
    <form id="physicalInventoryForm">
      <input type="hidden" name="branch_id" value="<?= intval($selected_branch) ?>">

      <table class="table table-bordered align-middle" id="inventoryTable">
        <thead class="table-dark sticky-top" id="inventoryTHead">
          <tr>
            <th>Product ID</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>System Stock</th>
            <th>Physical Count</th>
            <th>Discrepancy</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
            <?php while ($row = $inventoryRes->fetch_assoc()): 
                $system = (int)$row['system_stock'];
                $phys   = ($row['physical_count'] === '' ? '' : (int)$row['physical_count']);
                $disc   = ($row['physical_count'] === '' ? 0 : abs($phys - $system));
                $status = $row['status'];
                $class  = ($status === 'Overstock')  ? 'status-over'
                    : (($status === 'Understock') ? 'status-under'
                    : (($status === 'Complete')   ? 'status-complete'
                    : 'status-pending'));
            ?>
            <tr>
                <td>
                <?= htmlspecialchars($row['product_id']) ?>
                <input type="hidden" name="product_id[]" value="<?= htmlspecialchars($row['product_id']) ?>">
                </td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td class="system-stock"><?= htmlspecialchars($system) ?></td>
                <td>
                <input
                    type="number"
                    min="0"
                    class="form-control form-control-sm physical-count"
                    value="<?= ($row['physical_count'] === '' ? '' : htmlspecialchars($phys)) ?>"
                    placeholder="Count"
                    oninput="updateDiscrepancy(this)"
                >
                </td>
                <td class="discrepancy"><?= htmlspecialchars($disc) ?></td>
                <td>
                <span class="badge status-badge <?= $class ?>"><?= htmlspecialchars($status) ?></span>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>

      </table>

      <div class="mt-3 d-flex gap-2">
        <button type="button" class="btn btn-primary" onclick="saveChanges()">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Toast container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100">
  <div id="appToast" class="toast border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-primary text-white">
      <i class="fas fa-info-circle me-2"></i>
      <strong class="me-auto">System Notice</strong>
      <small class="text-muted">just now</small>
      <button type="button" class="btn-close btn-close-white ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="appToastBody">
      Action completed.
    </div>
  </div>
</div>

<!-- add this BEFORE your showToast() script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<!-- Function for Toast -->
<script>
// Toast helper
function showToast(message, type = "info") {
  const toastEl = document.getElementById("appToast");
  const toastBody = document.getElementById("appToastBody");
  if (!toastEl || !toastBody) return;

  // Reset classes
  const header = toastEl.querySelector(".toast-header");
  header.classList.remove("bg-primary","bg-success","bg-danger","bg-warning");

  // Apply type color
  switch (type) {
    case "success": header.classList.add("bg-success","text-white"); break;
    case "danger":  header.classList.add("bg-danger","text-white");  break;
    case "warning": header.classList.add("bg-warning","text-dark");  break;
    default:        header.classList.add("bg-primary","text-white"); break;
  }

  // Set message
  toastBody.innerText = message;

  // Show toast
  const toast = new bootstrap.Toast(toastEl);
  toast.show();
}
</script>


<script>
// ===== Filter/Search =====
function filterTable() {
  const input = document.getElementById('searchInput')?.value.toLowerCase() || '';
  document.querySelectorAll('#physicalInventoryForm tbody tr').forEach(row => {
    const text = row.innerText.toLowerCase();
    row.style.display = text.includes(input) ? '' : 'none';
  });
}

// ===== Update Discrepancy & Badge =====
function updateDiscrepancy(input) {
  const row = input.closest('tr');
  const systemStockEl = row.querySelector('.system-stock');
  const discrepancyEl = row.querySelector('.discrepancy');
  const badge = row.querySelector('.status-badge');

  const systemStock = parseInt(systemStockEl?.innerText) || 0;
  const hasValue = input.value.trim() !== '';
  const physicalCount = hasValue ? (parseInt(input.value) || 0) : null;

  let status = 'Pending';
  let discrepancy = 0;

  if (physicalCount === null) {
    status = 'Pending';
    discrepancy = 0;
  } else if (physicalCount > systemStock) {
    status = 'Overstock';
    discrepancy = Math.abs(physicalCount - systemStock);
  } else if (physicalCount < systemStock) {
    status = 'Understock';
    discrepancy = Math.abs(systemStock - physicalCount);
  } else {
    status = 'Complete';
    discrepancy = 0;
  }

  // Update DOM
  discrepancyEl.innerText = discrepancy;

  // Reset and apply badge classes
  badge.classList.remove('status-over','status-under','status-complete','status-pending');
  badge.classList.add(
    status === 'Overstock'  ? 'status-over'  :
    status === 'Understock' ? 'status-under' :
    status === 'Complete'   ? 'status-complete' : 'status-pending'
  );
  badge.innerText = status;

  // Mark row changed + update count
  markChanged(input);
  updateMismatchCount();
}

// ===== Highlight Changed Rows =====
function markChanged(input) {
  const row = input.closest('tr');
  row.dataset.changed = 'true';
  row.classList.add('changed');
}

// ===== Update Total Mismatch (Under/Over) Count =====
function updateMismatchCount() {
  let total = 0;
  document.querySelectorAll('#inventoryTable tbody tr').forEach(row => {
    const badge = row.querySelector('.status-badge');
    if (!badge) return;
    const isIssue = badge.classList.contains('status-over') || badge.classList.contains('status-under');
    if (isIssue) total++;
  });
  const el = document.getElementById('totalMismatch');
  if (el) el.innerText = total;
}

// ===== Save Only Changed Rows =====
function saveChanges() {
  const form = document.getElementById('physicalInventoryForm');
  const changedRows = [...form.querySelectorAll('tbody tr[data-changed="true"]')];
  if (changedRows.length === 0) {
    showToast("No changes detected.", "warning");
    return;
  }

  const formData = new FormData();
  formData.append('branch_id', form.querySelector('input[name="branch_id"]').value);

  changedRows.forEach(row => {
    const productId = row.querySelector('input[name="product_id[]"]').value;
    const physicalInput = row.querySelector('input.physical-count');
    const val = (physicalInput.value ?? '').trim();
    formData.append(`physical_count[${productId}]`, val);
  });

  fetch('save_physical_inventory.php', { method:'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      showToast(data.message || "Saved successfully.", "success");
      setTimeout(() => location.reload(), 1500); // reload after toast
    })
    .catch(err => {
      console.error(err);
      showToast("Error saving inventory. Please try again.", "danger");
    });
}


// ===== Init =====
document.addEventListener('DOMContentLoaded', () => {
  updateMismatchCount();

  // (Optional) live-recalc for any pre-filled inputs after load
  document.querySelectorAll('input.physical-count').forEach(inp => {
    if (inp.value.trim() !== '') updateDiscrepancy(inp);
  });
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
