<?php
session_start();
require 'config/db.php';

// Authz: only admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.html');
    exit;
}

$user_id   = $_SESSION['user_id'];
$role      = $_SESSION['role'];
$branch_id = $_SESSION['branch_id'] ?? null;

/* -------------------------
   Pending badges (inventory)
-------------------------- */
$pendingTransfers = 0;
if ($role === 'admin') {
    if ($res = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='pending'")) {
        $pendingTransfers = (int)($res->fetch_assoc()['pending'] ?? 0);
    }
}
$pendingStockIns = 0;
if ($role === 'admin') {
    if ($res = $conn->query("SELECT COUNT(*) AS pending FROM stock_in_requests WHERE status='pending'")) {
        $pendingStockIns = (int)($res->fetch_assoc()['pending'] ?? 0);
    }
}
$pendingTotalInventory = $pendingTransfers + $pendingStockIns;

/* -------------------------
   Get branch list for filter
-------------------------- */
$branches = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name");

/* -------------------------
   Filters
-------------------------- */
$where  = [];
$params = [];
$types  = '';

/** Branch filter
 *  Supports numeric branch_id or the special value 'NULL' (System / No Branch)
 */
if (isset($_GET['branch_id']) && $_GET['branch_id'] !== '') {
    if ($_GET['branch_id'] === 'NULL') {
        $where[] = "l.branch_id IS NULL";
    } elseif (is_numeric($_GET['branch_id'])) {
        $where[]  = "l.branch_id = ?";
        $params[] = (int)$_GET['branch_id'];
        $types   .= 'i';
    }
}

/** Date filters (index-friendly) */
$from = $_GET['from_date'] ?? '';
$to   = $_GET['to_date']   ?? '';

if ($from !== '') {
    $where[]  = "l.timestamp >= ?";
    $params[] = $from . ' 00:00:00';
    $types   .= 's';
}

if ($to !== '') {
    // exclusive upper bound: next day at 00:00:00
    $toExclusive = date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00';
    $where[]  = "l.timestamp < ?";
    $params[] = $toExclusive;
    $types   .= 's';
}

/* Optional: auto-swap if user picks reversed dates */
if ($from !== '' && $to !== '') {
    $fromDT = strtotime($from . ' 00:00:00');
    $toDT   = strtotime($to . ' 23:59:59');
    if ($fromDT > $toDT) {
        // swap inputs to be nice
        [$from, $to] = [$to, $from];
        // rebuild the two params we just pushed (last two)
        $params[count($params)-2] = $from . ' 00:00:00';
        $params[count($params)-1] = date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00';
    }
}


/** Role filter */
if (!empty($_GET['role'])) {
    $where[]  = "u.role = ?";
    $params[] = $_GET['role'];
    $types   .= 's';
}

// --- Activity filter (derived) ---
$activity = $_GET['activity'] ?? ''; // e.g., 'stockin','transfer','login','create','update','delete','request'
if ($activity !== '') {
    switch ($activity) {
        case 'login':
            // action contains 'login'
            $where[] = "LOWER(l.action) LIKE '%login%'";
            break;

        case 'stockin':
            // your heuristics for Add Stocks
            $where[] = "("
                . "LOWER(l.action) REGEXP 'stock[ -]?in|restock|incoming|receive' "
                . "OR LOWER(l.details) REGEXP 'stock[ -]?in|restock|incoming|receive|add(ed)? [0-9]+'"
                . ")";
            break;

        case 'transfer':
            $where[] = "("
                . "LOWER(l.action) LIKE '%transfer%' "
                . "OR LOWER(l.details) LIKE '%transfer%'"
                . ")";
            break;

        case 'create':
            $where[] = "("
                . "LOWER(l.action) LIKE '%create%' "
                . "OR LOWER(l.action) LIKE '%add%' "
                . "OR LOWER(l.details) LIKE '%created %' "
                . "OR LOWER(l.details) LIKE 'added %'"
                . ")";
            break;

        case 'update':
            $where[] = "("
                . "LOWER(l.action) LIKE '%update%' "
                . "OR LOWER(l.action) LIKE '%edit%' "
                . "OR LOWER(l.details) LIKE '%updated %' "
                . "OR LOWER(l.details) LIKE 'edited %'"
                . ")";
            break;

        case 'delete':
            // includes archive/restore if you want, or split them as separate types
            $where[] = "("
                . "LOWER(l.action) LIKE '%delete%' "
                . "OR LOWER(l.action) LIKE '%archive%' "
                . "OR LOWER(l.action) LIKE '%restore%' "
                . "OR LOWER(l.details) REGEXP '(deleted|archiv|restore)d?'"
                . ")";
            break;

        case 'archive':
            $where[] = "("
                . "LOWER(l.action) LIKE '%archive%' "
                . "OR LOWER(l.details) LIKE '%archiv%'"
                . ")";
            break;

        case 'restore':
            $where[] = "("
                . "LOWER(l.action) LIKE '%restore%' "
                . "OR LOWER(l.details) LIKE '%restore%'"
                . ")";
            break;
    }
}

/* -------------------------
   Build + run query
   Note: branch_name uses COALESCE to show 'System' when NULL
-------------------------- */
$sql = "SELECT 
          l.log_id, l.user_id, l.action, l.details, l.timestamp, l.branch_id,
          COALESCE(b.branch_name, ub.branch_name, 'System') AS branch_name,
          u.username, u.role
        FROM logs l
        LEFT JOIN branches b  ON l.branch_id = b.branch_id      -- branch saved on the log
        LEFT JOIN users u     ON l.user_id   = u.id
        LEFT JOIN branches ub ON u.branch_id = ub.branch_id";   

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY l.timestamp DESC LIMIT 100";


$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

/* -------------------------
   Other badges (accounts)
-------------------------- */
$pendingResetsCount = 0;
if ($role === 'admin') {
    if ($res = $conn->query("SELECT COUNT(*) AS c FROM password_resets WHERE status='pending'")) {
        $pendingResetsCount = (int)$res->fetch_assoc()['c'];
    }
}

/* -------------------------
   Helpers for sidebar state
-------------------------- */
$self      = strtolower(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))); // e.g., logs.php
$isArchive = str_starts_with($self, 'archive'); // archive.php, archive_view.php, ...
$invOpen   = in_array($self, ['inventory.php','physical_inventory.php'], true);
$toolsOpen = ($self === 'backup_admin.php' || $isArchive);

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
      <span id="notifCount" <?= $pendingTotalInventory > 0 ? '' : 'style="display:none;"' ?>>
        <?= $pendingTotalInventory ?>
      </span>
    </span>
  </h2>

  <!-- Common -->
  <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>

  <?php if ($role === 'admin'): ?>
    <!-- Inventory group -->
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
        <!-- active compare fixed: just 'inventory.php' -->
        <a href="inventory.php#pending-requests" class="<?= $self === 'inventory.php' ? 'active' : '' ?>">
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

    <a href="sales.php" class="<?= $self === 'sales.php' ? 'active' : '' ?>">
      <i class="fas fa-receipt"></i> Sales
    </a>

    <a href="accounts.php" class="<?= $self === 'accounts.php' ? 'active' : '' ?>">
      <i class="fas fa-users"></i> Accounts
      <?php if ($pendingResetsCount > 0): ?>
        <span class="badge-pending"><?= $pendingResetsCount ?></span>
      <?php endif; ?>
    </a>

    <!-- Data Tools -->
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

  <!-- Stockman -->
  <?php $transferNotif = $transferNotif ?? 0; ?>
  <?php if ($role === 'stockman'): ?>
    <a href="inventory.php"><i class="fas fa-box"></i> Inventory
      <?php if ($transferNotif > 0): ?>
        <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;"><?= $transferNotif ?></span>
      <?php endif; ?>
    </a>
    <a href="physical_inventory.php" class="active"><i class="fas fa-warehouse"></i> Physical Inventory</a>
  <?php endif; ?>

  <!-- Staff -->
  <?php if ($role === 'staff'): ?>
    <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
    <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
  <?php endif; ?>

  <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
  <div class="header-bar">
    <h1 class="h3"><i class="fas fa-file-alt me-2"></i> Activity Logs</h1>

    <form method="get" class="filters-bar">
      <select name="branch_id" class="form-select">
        <option value="">All Branches</option>
        <?php if ($branches): while ($branch = $branches->fetch_assoc()): ?>
          <option value="<?= $branch['branch_id'] ?>" <?= (isset($_GET['branch_id']) && $_GET['branch_id']==$branch['branch_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($branch['branch_name']) ?>
          </option>
        <?php endwhile; endif; ?>
        <option value="NULL" <?= (($_GET['branch_id'] ?? '') === 'NULL') ? 'selected' : '' ?>>System / No Branch</option>
      </select>

      <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>" placeholder="From">
      <input type="date" name="to_date"   class="form-control" value="<?= htmlspecialchars($_GET['to_date']   ?? '') ?>" placeholder="To">

      <select name="role" class="form-select">
        <option value="">All Roles</option>
        <option value="admin"    <?= (($_GET['role'] ?? '') === 'admin')    ? 'selected' : '' ?>>Admin</option>
        <option value="staff"    <?= (($_GET['role'] ?? '') === 'staff')    ? 'selected' : '' ?>>Staff</option>
        <option value="stockman" <?= (($_GET['role'] ?? '') === 'stockman') ? 'selected' : '' ?>>Stockman</option>
      </select>
      
      <select name="activity" class="form-select">
        <option value="">All Activities</option>
        <option value="login"    <?= (($_GET['activity'] ?? '')==='login')    ? 'selected':'' ?>>Login</option>
        <option value="stockin"  <?= (($_GET['activity'] ?? '')==='stockin')  ? 'selected':'' ?>>Add Stocks</option>
        <option value="transfer" <?= (($_GET['activity'] ?? '')==='transfer') ? 'selected':'' ?>>Stock Transfer</option>
        <option value="create"   <?= (($_GET['activity'] ?? '')==='create')   ? 'selected':'' ?>>Create</option>
        <option value="update"   <?= (($_GET['activity'] ?? '')==='update')   ? 'selected':'' ?>>Update</option>
        <option value="archive"  <?= (($_GET['activity'] ?? '')==='archive')  ? 'selected':'' ?>>Archive</option>
        <option value="restore"  <?= (($_GET['activity'] ?? '')==='restore')  ? 'selected':'' ?>>Restore</option>
        <option value="delete"   <?= (($_GET['activity'] ?? '')==='delete')   ? 'selected':'' ?>>Delete</option>
    </select>


      <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
    </form>
  </div>

  <?php if ($result && $result->num_rows > 0): ?>
    <div class="logs-feed">
      <?php while ($log = $result->fetch_assoc()):
        $rawAction = trim($log['action'] ?? '');
        $act       = strtolower($rawAction);

        $map = [
          'create'  => ['key'=>'create',  'badge'=>'badge-create',  'text'=>'Created'],
          'add'     => ['key'=>'create',  'badge'=>'badge-create',  'text'=>'Created'],
          'update'  => ['key'=>'update',  'badge'=>'badge-update',  'text'=>'Updated'],
          'edit'    => ['key'=>'update',  'badge'=>'badge-update',  'text'=>'Updated'],
          'delete'  => ['key'=>'delete',  'badge'=>'badge-delete',  'text'=>'Deleted'],
          'archive' => ['key'=>'delete',  'badge'=>'badge-delete',  'text'=>'Archived'],
          'login'   => ['key'=>'login',   'badge'=>'badge-login',   'text'=>'Login'],
          'transfer'=> ['key'=>'transfer','badge'=>'badge-transfer','text'=>'Stock Transfer'],
          'request' => ['key'=>'request', 'badge'=>'badge-request', 'text'=>'Transfer Request'],
          'stock-in' => ['key'=>'stockin','badge'=>'badge-stockin','text'=>'Add Stocks'],
          'stockin'  => ['key'=>'stockin','badge'=>'badge-stockin','text'=>'Add Stocks'],

        ];
        $cfg = ['key'=>'other','badge'=>'badge-secondary','text'=>$rawAction ?: 'Activity'];
        foreach ($map as $needle => $data) { if (str_contains($act, $needle)) { $cfg = $data; break; } }

        // ---- details-based overrides ----
        $details_lc = strtolower(trim($log['details'] ?? ''));

        // Heuristics: treat as Add Stocks if details look like receiving/restocking or "Added 10 ... to Branch ..."
        $looks_like_stockin =
            preg_match('/\bstock[\s-]?in\b/', $details_lc) ||                // "stock-in", "stock in"
            preg_match('/\b(restock(?:ed)?|incoming|receive(?:d)?)\b/', $details_lc) ||
            preg_match('/\badd(?:ed)?\s+\d+(?:\.\d+)?\b.*\bbranch\b/', $details_lc); // "Added 10 ... to Branch"

        if ($looks_like_stockin) {
            // Show "Add Stocks" (keep green style). If you have a special style, set key/badge to 'stockin'/'badge-stockin'.
            $cfg['text']  = 'Add Stocks';
            // optional:
            $cfg['key']   = 'stockin';
            $cfg['badge'] = 'badge-stockin';
        } elseif ($cfg['key'] === 'other') {
            if (str_contains($details_lc, 'transfer')) {
                $cfg = ['key'=>'transfer','badge'=>'badge-transfer','text'=>'Stock Transfer'];
            } elseif (str_contains($details_lc, 'request')) {
                $cfg = ['key'=>'request','badge'=>'badge-request','text'=>'Transfer Request'];
            }
        }

        $roleClass = match(strtolower($log['role'] ?? '')) {
          'admin'    => 'role-admin',
          'stockman' => 'role-stockman',
          'staff'    => 'role-staff',
          default    => 'role-other',
        };

        // Build fallback details for empty "login" entries
        $tz       = new DateTimeZone('Asia/Manila');
        $dt       = new DateTime($log['timestamp'], $tz);
        $tsHuman  = $dt->format('F j, Y | g:i A');   // e.g., September 16, 2025 · 2:45 PM

        $detailsRaw = trim($log['details'] ?? '');
        $detailsItems = [];

        if ($detailsRaw !== '') {
          $detailsItems[] = htmlspecialchars($detailsRaw, ENT_QUOTES, 'UTF-8');
        }

        if (str_contains($act, 'login')) {
          // Add nice defaults when details field is empty (or even append)
          $detailsItems[] = 'Date & Time: ' . htmlspecialchars($tsHuman, ENT_QUOTES, 'UTF-8');
          // If you have these columns in your table, you can include them:
          // $detailsItems[] = 'IP: ' . htmlspecialchars($log['ip_address'] ?? '—', ENT_QUOTES, 'UTF-8');
          // $detailsItems[] = 'Device: ' . htmlspecialchars($log['user_agent'] ?? '—', ENT_QUOTES, 'UTF-8');
        }

        // Render as a small list
        $detailsHTML = '<ul class="log-meta">' .
                        implode('', array_map(fn($t) => "<li>{$t}</li>", $detailsItems)) .
                      '</ul>';

        $uname    = $log['username'] ?: 'System';
        $initials = mb_strtoupper(mb_substr($uname, 0, 1));
        $tsISO    = (new DateTime($log['timestamp'], new DateTimeZone('Asia/Manila')))->format('c');
      ?>
      <div class="log-card accent-<?= $cfg['key'] ?>">
        <div class="avatar-ring <?= $roleClass ?>" aria-hidden="true">
          <span><?= htmlspecialchars($initials) ?></span>
        </div>

        <div class="log-main">
          <div class="log-top">
            <div class="who">
              <strong><?= htmlspecialchars($uname) ?></strong>
              <span class="sep">•</span>
              <span class="branch"><?= htmlspecialchars($log['branch_name']) ?></span>
            </div>
            <span class="badge <?= $cfg['badge'] ?>"><?= htmlspecialchars($cfg['text']) ?></span>
          </div>
            <small class="timeline-time" data-timestamp="<?= $tsISO ?>"></small>

            <div class="log-details">
              <?= $detailsHTML ?>
            </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>

    <button id="loadMore" class="btn btn-outline-primary mt-2">Load More</button>
  <?php else: ?>
    <p class="text-center text-muted mt-3">No logs available</p>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="notifications.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

  // Relative time
  function timeAgo(ts) {
    const date = new Date(ts), now = new Date();
    const diff = Math.floor((now - date)/1000);
    if (diff < 60)    return diff + ' sec ago';
    if (diff < 3600)  return Math.floor(diff/60)   + ' min ago';
    if (diff < 86400) return Math.floor(diff/3600) + ' hr ago';
    const d = Math.floor(diff/86400);
    return d + ' day' + (d>1?'s':'') + ' ago';
  }
  function updateTimes() {
    document.querySelectorAll('.timeline-time').forEach(el => {
      const ts = el.dataset.timestamp;
      if (ts) el.textContent = timeAgo(ts);
    });
  }
  updateTimes(); setInterval(updateTimes, 60000);

  // Load More
  let visible = 10;
  const logs = document.querySelectorAll('.logs-feed .log-card');
  logs.forEach((log, i) => log.style.display = i < visible ? 'flex' : 'none');
  const loadBtn = document.getElementById('loadMore');
  if (loadBtn) {
    loadBtn.addEventListener('click', () => {
      visible += 10;
      logs.forEach((log, i) => { if (i < visible) log.style.display = 'flex'; });
      if (visible >= logs.length) loadBtn.style.display = 'none';
    });
  }

  // Sidebar submenus (persist state)
  (function(){
    const groups = document.querySelectorAll('.menu-group.has-sub');
    groups.forEach((g, idx) => {
      const btn = g.querySelector('.menu-toggle');
      const panel = g.querySelector('.submenu');
      if (!btn || !panel) return;
      const key = 'sidebar-sub-' + idx;
      if (localStorage.getItem(key) === 'open') {
        btn.setAttribute('aria-expanded','true');
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
});
</script>
</body>
</html>
