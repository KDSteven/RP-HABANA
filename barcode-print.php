<?php
session_start();
require 'config/db.php';

/* ── Auth & role ───────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id'])) {
  header('Location: index.html');
  exit;
}

$role         = $_SESSION['role'] ?? '';
$userBranchId = $_SESSION['branch_id'] ?? null;

$pending = (int)($pending ?? 0);

/* Allow only admin or stockman on this page (adjust if staff should see it) */
if (!in_array($role, ['admin','stockman'], true)) {
  http_response_code(403);
  die('Access denied.');
}

/* ── Branch filter ─────────────────────────────────────────────────── */
$branch = isset($_GET['branch']) ? (int)$_GET['branch'] : 0;

/* Stockman can ONLY see their assigned branch; ignore any ?branch= */
if ($role === 'stockman') {
  if (!$userBranchId) {
    http_response_code(403);
    die('Access denied: no branch assigned to your account.');
  }
  $branch = (int)$userBranchId;
}

/* ── Branch label for stockman badge ─────────────────────────────── */
$stockmanBranchLabel = '';
if ($role === 'stockman' && $userBranchId) {
  if ($stmt = $conn->prepare("
      SELECT CONCAT(branch_name,
                    CASE WHEN branch_location IS NULL OR branch_location = ''
                         THEN '' ELSE CONCAT(' – ', branch_location) END) AS label
      FROM branches
      WHERE branch_id = ?
      LIMIT 1
  ")) {
    $stmt->bind_param('i', $userBranchId);
    $stmt->execute();
    $stmt->bind_result($stockmanBranchLabel);
    $stmt->fetch();
    $stmt->close();
  }
  // Fallback if not found
  if (!$stockmanBranchLabel) $stockmanBranchLabel = 'Branch #'.(int)$userBranchId;
}


/* ── Admin: fetch branches for selector pills ──────────────────────── */
$branches = [];
if ($role === 'admin') {
  if ($resB = $conn->query("SELECT branch_id, branch_name, branch_location
                             FROM branches
                             ORDER BY branch_name ASC")) {
    while ($b = $resB->fetch_assoc()) $branches[] = $b;
  }
}

/* ── NOTIFICATION COUNTS (same idea as dashboard) ──────────────────── */
$pendingTransfers      = 0;
$pendingStockIns       = 0;
$pendingTotalInventory = 0;
$pendingResetsCount    = 0;

/* Only admin sees these global counts */
if ($role === 'admin') {
  if ($res = $conn->query("SELECT COUNT(*) AS c FROM transfer_requests WHERE LOWER(status)='pending'")) {
    $pendingTransfers = (int)($res->fetch_assoc()['c'] ?? 0);
  }
  if ($res = $conn->query("SELECT COUNT(*) AS c FROM stock_in_requests WHERE LOWER(status)='pending'")) {
    $pendingStockIns = (int)($res->fetch_assoc()['c'] ?? 0);
  }
  $pendingTotalInventory = $pendingTransfers + $pendingStockIns;


}

/* Overall bell (sum whatever you want to alert on) */
$pendingBell = $pendingTotalInventory + $pendingResetsCount;

/* ── Products (optionally filtered by branch) ──────────────────────── */
$params = [];
$types  = '';

$sql = "SELECT DISTINCT
          p.product_name AS name,
          p.barcode      AS barcode,
          p.category
        FROM products p
        JOIN inventory i
          ON i.product_id = p.product_id
        WHERE p.archived = 0
          AND i.archived = 0
          AND p.barcode IS NOT NULL
          AND p.barcode <> ''";

/* If you only want items that actually have stock, uncomment this line */
/* $sql .= " AND i.stock > 0"; */

if ($branch > 0) {
  $sql .= " AND i.branch_id = ?";
  $params[] = $branch;
  $types   .= 'i';
}

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();

/* ── Group by category ─────────────────────────────────────────────── */
$groups = [];
foreach ($rows as $r) {
  $cat = $r['category'] ?: 'Uncategorized';
  $groups[$cat][] = ['name' => $r['name'], 'barcode' => $r['barcode']];
}

/* ── Sidebar state ─────────────────────────────────────────────────── */
$self      = strtolower(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
$invOpen   = in_array($self, ['inventory.php','physical_inventory.php','barcode-print.php'], true);
$isArchive = (substr($self, 0, 7) === 'archive');                 // archive.php, archive_view.php, etc.
$toolsOpen = ($self === 'backup_admin.php' || $isArchive);        // expand the submenu when on those pages

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

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
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>RP Habana — Barcode Labels</title>
<link rel="icon" href="img/R.P.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/notifications.css">
<style>
  :root{
    --bg: #f3f6fb;
    --card: #ffffff;
    --pill-bg: #edf2f7;
    --pill-fg: #0f172a;
    --pill-muted:#6b7280;
    --pill-active-bg:#ffd9a6;
    --pill-active-fg:#7a4b00;
    --section-bg:#ffeecd;
    --section-accent:#ff9800;
    --grid-gap: 16px;

    /* physical size for printing */
    --label-w: 45mm;
    --label-h: 30mm;
  }

  body{ background:var(--bg); color:#0f172a; }
  .content-wrap{ padding:24px; }

  /* header row like your ref */
  .page-head{
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:14px;
  }
  .page-title{ display:flex; align-items:center; gap:10px; }
  .page-title h1{ font-size:24px; font-weight:800; margin:0; }
  .page-actions .btn{ border-radius:10px; }

  /* branch pills (admin only) */
  .branch-pills{ display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
  .branch-pill{
    display:inline-flex; align-items:center; gap:8px;
    padding:8px 14px; border-radius:999px; background:var(--pill-bg);
    color:var(--pill-fg); border:1px solid #e5e7eb; text-decoration:none;
    font-weight:700; transition:.15s ease;
  }
  .branch-pill small{ color:var(--pill-muted); font-weight:600; }
  .branch-pill:hover{ filter:brightness(.98); text-decoration:none; }
  .branch-pill.active{ background:var(--pill-active-bg); color:var(--pill-active-fg); border-color:#f6ad55; }

  /* main card like your “Manage Products” */
  .main-card{
    background:var(--card); border:1px solid #e9eef5; border-radius:18px;
    box-shadow:0 10px 20px -12px rgba(15,23,42,0.12);
    padding:18px 18px 6px;
  }
  .main-card .card-head{
    display:flex; align-items:center; gap:10px; margin-bottom:8px;
  }
  .card-head h2{ font-size:22px; font-weight:800; margin:0; }

  /* section header bar (orange) */
  .section-bar{
    display:flex; align-items:center; gap:8px; font-weight:800; color:#7a4b00;
    background:var(--section-bg); border-left:6px solid var(--section-accent);
    border-radius:12px; padding:10px 12px; margin:12px 4px 14px;
  }

  /* grid: fluid on screen, exact 4 when printing */
  .labels-grid{
    display:grid; gap:var(--grid-gap);
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  }

  /* label card */
  .label-card{
    background:#fff; border:1px solid #e7eaf0; border-radius:14px;
    box-shadow: 0 1px 0 rgba(0,0,0,.03);
    padding:16px; text-align:center;
    display:flex; flex-direction:column; justify-content:center; align-items:center;
    height:160px;
  }
  .label-card svg{ height:56px; width:100%; max-width:230px; }
  .label-name{ margin-top:6px; font-weight:800; line-height:1.15; }
  .label-code{ font-size:12px; color:#6b7280; margin-top:2px; }
  /* Optional: if using a flex layout container */
.layout { display: flex; }
.sidebar { flex: 0 0 var(--sidebar-w); }
.content-wrap { flex: 1 1 auto; margin-left: 0 !important; }

  /* ===== PRINT LAYOUT ONLY ===== */
  @media print {
    /* Hide UI */
    .sidebar, .page-head, .branch-pills { display: none !important; }
    .content-wrap { margin: 0; padding: 0; }
    .main-card { border: 0; box-shadow: none; padding: 0; }

    /* 5 columns per page, fixed size */
    .labels-grid{
      grid-template-columns: repeat(5, var(--label-w)); /* exactly 5 columns */
      gap: 6mm;
      justify-content: start;
    }
    .label-card{
      width: var(--label-w);
      height: var(--label-h);
      padding: 5mm;
      border-radius: 6px;
      box-shadow: none;
    }
    .label-card svg{ height: 50px; max-width: none; }
    .label-name{ font-size: 10pt; }
    .label-code{ font-size: 8pt; }

    /* Category pagination — NO blank first page */
    .print-categories .category-print { 
      break-inside: avoid;
      page-break-inside: avoid;
    }
    .print-categories .category-print + .category-print {
      break-before: page;         /* modern */
      page-break-before: always;  /* legacy */
    }
  }
</style>
</head>
<body>

<!-- SIDEBAR -->
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

<!-- CONTENT -->
<div class="content-wrap">

  <!-- page header (like your Manage Products top row) -->
  <div class="page-head">
    <div class="page-title">
      <i class="fa-solid fa-barcode fa-lg text-dark"></i>
      <h1>Barcode Labels</h1>
        <?php if ($role === 'stockman' && $userBranchId): ?>
          <span class="ms-2 badge bg-warning text-dark">
            <?= h($stockmanBranchLabel) ?>
          </span>
        <?php endif; ?>
    </div>
    <div class="page-actions">
      <?php if ($role === 'admin' && $branch > 0): ?>
        <a class="btn btn-outline-secondary me-2" href="barcode-print.php">
          <i class="fa-regular fa-circle-xmark me-1"></i> Clear
        </a>
      <?php endif; ?>
      <button class="btn btn-primary" onclick="window.print()">
        <i class="fa-solid fa-print me-1"></i> Print
      </button>
    </div>
  </div>

  <!-- admin-only branch pills like your top chips -->
  <?php if ($role === 'admin'): ?>
    <div class="branch-pills">
      <a class="branch-pill <?= $branch === 0 ? 'active' : '' ?>" href="barcode-print.php">All Branches</a>
      <?php foreach ($branches as $b): ?>
        <a class="branch-pill <?= ($branch === (int)$b['branch_id']) ? 'active' : '' ?>"
           href="barcode-print.php?branch=<?= (int)$b['branch_id'] ?>">
          <?= h($b['branch_name']) ?>
          <?php if (!empty($b['branch_location'])): ?>
            <small><?= h($b['branch_location']) ?></small>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="main-card">
    <div class="card-head">
      <i class="fa-solid fa-layer-group text-warning"></i>
      <h2>Categories</h2>
      <span class="ms-auto text-muted fw-semibold">Total Labels: <?= count($rows) ?></span>
    </div>

    <?php if (empty($groups)): ?>
      <div class="alert alert-warning m-2">
        No products with barcodes found<?= $branch ? ' for this branch' : '' ?>.
      </div>
    <?php else: ?>
      <div class="print-categories">
        <?php foreach ($groups as $category => $items): ?>
          <section class="category-print">
            <div class="section-bar"><i class="fa-solid fa-folder-open"></i> <?= h($category) ?></div>

            <div class="labels-grid mb-4">
              <?php foreach ($items as $idx => $p): ?>
                <div class="label-card">
                  <svg id="bc<?= md5($category.$idx.$p['barcode']) ?>"></svg>
                  <div class="label-name"><?= h($p['name']) ?></div>
                  <div class="label-code"><?= h($p['barcode']) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Add just above your JsBarcode/custom scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.querySelectorAll('svg[id^="bc"]').forEach(svg => {
  const code = svg.closest('.label-card')?.querySelector('.label-code')?.textContent?.trim() || '';
  const fmt  = /^\d{13}$/.test(code) ? 'ean13' : 'code128';
  JsBarcode(svg, code, { format: fmt, width: 2, height: 50, displayValue: false });
});
</script>

<script>
(function(){
  const groups = document.querySelectorAll('.menu-group.has-sub');
  groups.forEach((g, idx) => {
    const btn   = g.querySelector('.menu-toggle');
    const panel = g.querySelector('.submenu');
    if (!btn || !panel) return;

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
<script src="sidebar.js"></script>
</body>
</html>
