<?php
// shift_summary.php
session_start();
require 'config/db.php';
require 'functions.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
  header('Location: index.html'); exit;
}

$user_id   = (int)$_SESSION['user_id'];
$role      = $_SESSION['role'];
$branch_id = (int)($_SESSION['branch_id'] ?? 0);

/* badges */
$pending = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE LOWER(status) = 'pending'");
    if ($result) $pending = (int)$result->fetch_assoc()['pending'];
}
$pendingTransfers = 0;
$pendingStockIns  = 0;
if ($role === 'admin') {
    if ($r = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='pending'"))
        $pendingTransfers = (int)$r->fetch_assoc()['pending'];
    if ($r = $conn->query("SELECT COUNT(*) AS pending FROM stock_in_requests WHERE status='pending'"))
        $pendingStockIns = (int)$r->fetch_assoc()['pending'];
}
$pendingTotalInventory = $pendingTransfers + $pendingStockIns;

/* shifts (last 14 days for this branch) */
$shifts = [];
$stmt = $conn->prepare("
  SELECT s.shift_id, u.name AS cashier_name, s.start_time, s.end_time
  FROM shifts s
  LEFT JOIN users u ON u.id = s.user_id
  WHERE s.branch_id = ? AND s.start_time >= DATE_SUB(NOW(), INTERVAL 14 DAY)
  ORDER BY s.shift_id DESC
");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $shifts[] = $r;
$stmt->close();

/* default selection = active if any */
$active = get_active_shift($conn, $user_id, $branch_id);
$defaultShiftId = $active ? (int)$active['shift_id'] : (int)($shifts[0]['shift_id'] ?? 0);

/* sidebar name */
$currentName = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($fetchedName);
    if ($stmt->fetch()) $currentName = $fetchedName;
    $stmt->close();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Shift Summary</title>
  <link rel="icon" href="img/R.P.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/notifications.css">
    <link rel="stylesheet" href="css/summary.css">
  <audio id="notifSound" src="notif.mp3" preload="auto"></audio>
  <style>
    .kpi-card{border-radius:14px}
    .kpi-val{font-size:1.6rem;font-weight:700}
    .mono{font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;}
  </style>
</head>
<body class="bg-light">
<div class="sidebar" id="mainSidebar">
  <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="false">
    <i class="fas fa-bars" aria-hidden="true"></i>
  </button>

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

    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>

<?php
$self = strtolower(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
$isArchive = substr($self, 0, 7) === 'archive';
$invOpen   = in_array($self, ['inventory.php','physical_inventory.php'], true);
$toolsOpen = ($self === 'backup_admin.php' || $isArchive);
?>

<?php if ($role === 'admin'): ?>
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
    <i class="fa fa-wrench"></i> Services
  </a>

  <a href="sales.php" class="<?= $self === 'sales.php' ? 'active' : '' ?>">
    <i class="fas fa-receipt"></i> Sales
  </a>

  <a href="accounts.php" class="<?= $self === 'accounts.php' ? 'active' : '' ?>">
    <i class="fas fa-users"></i> Accounts & Branches
  </a>

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
      <a href="barcode-print.php" class="<?= $self === 'barcode-print.php' ? 'active' : '' ?>">
        <i class="fas fa-barcode"></i> Barcode Labels
      </a>
    </div>
  </div>
<?php endif; ?>

<?php if ($role === 'staff'): ?>
  <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
  <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
  <a href="shift_summary.php" class="<?= $self === 'shift_summary.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-clipboard-check"></i> Shift Summary</a>
<?php endif; ?>

  <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>

<div class="container-fluid page-content py-4" id="summary-wrapper">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="summary-header">
      <h2><i class="fa-solid fa-clipboard-check"></i> Shift Summary</h2>
    </div>
  </div>

  <!-- Shift picker -->
  <div class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-sm-6 col-lg-4">
        <label class="form-label">Select Shift</label>
        <select id="shiftSelect" class="form-select">
          <?php foreach ($shifts as $s): ?>
            <?php
              $label = '#'.$s['shift_id'].' — '.($s['cashier_name'] ?? 'User').' — '.($s['start_time']);
              if (!empty($s['end_time'])) $label .= ' → '.$s['end_time'];
              $sid = (int)$s['shift_id'];
            ?>
            <option value="<?= $sid ?>" <?= $sid === $defaultShiftId ? 'selected':'' ?>>
              <?= htmlspecialchars($label, ENT_QUOTES) ?>
            </option>
          <?php endforeach; ?>
          <?php if (!$shifts): ?>
            <option value="0">No shifts in last 14 days</option>
          <?php endif; ?>
        </select>
      </div>
      <div class="col-auto">
        <button id="refreshBtn" class="btn btn-primary"><i class="fa-solid fa-rotate"></i> Refresh</button>
      </div>
      <div class="col-auto text-muted small">Auto-refreshes every 10 seconds.</div>
    </div>
  </div>

  <!-- KPIs (only essentials) -->
<!-- ====== Cards layout (single row, two stacked columns) ====== -->
<div class="row g-3 align-items-start">
  <!-- LEFT COLUMN: Cash Drawer + Shift Details -->
  <div class="col-lg-4 d-flex flex-column gap-3">
    <!-- Cash Drawer -->
    <div class="card p-3 kpi-card">
      <div class="kpi">
        <div class="ico"><i class="fa-solid fa-cash-register"></i></div>
        <div>
          <div class="label">Cash Drawer (expected)</div>
          <div id="kpiExpected" class="value mono">₱0.00</div>
          <div id="kpiExpectedBreak" class="sub"></div>
        </div>
      </div>
    </div>

    <!-- Shift Details -->
    <div class="card p-3">
      <h5 class="mb-2">Shift Details</h5>
      <div id="shiftDetails" class="small"></div>
    </div>
  </div>

  <!-- RIGHT COLUMN: KPI summary + Pay-In/Pay-Out -->
  <div class="col-lg-8 d-flex flex-column gap-3">
    <!-- KPI Summary -->
    <div class="card p-3 kpi-card">
      <div class="row g-3">
        <div class="col-6 col-lg-3">
          <div class="kpi">
            <div class="ico"><i class="fa-solid fa-receipt"></i></div>
            <div>
              <div class="label">Sales (count)</div>
              <div id="kpiSalesCnt" class="value mono">0</div>
              <div class="sub muted">today</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="kpi">
            <div class="ico"><i class="fa-solid fa-peso-sign"></i></div>
            <div>
              <div class="label">Gross Ex-VAT</div>
              <div id="kpiGross" class="value mono">₱0.00</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="kpi">
            <div class="ico"><i class="fa-solid fa-percent"></i></div>
            <div>
              <div class="label">VAT</div>
              <div id="kpiVat" class="value mono">₱0.00</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="kpi">
            <div class="ico"><i class="fa-solid fa-wallet"></i></div>
            <div>
              <div class="label">Net Cash from Sales</div>
              <div id="kpiNetCash" class="value mono">₱0.00</div>
            </div>
          </div>
        </div>
      </div>

      <hr class="my-3">

      <div class="row g-3">
        <div class="col-6 col-lg-3">
          <div class="label">Discounts</div>
          <div id="kpiDisc" class="mono">₱0.00</div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="label">Pay-In / Pay-Out</div>
          <div class="mono">
            <span id="kpiPin"  class="amt amt-in">₱0.00</span>
            /
            <span id="kpiPout" class="amt amt-out">₱0.00</span>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="label">Refunds (cash out)</div>
          <div id="kpiRefund" class="mono amt amt-out">₱0.00</div>
          <div id="kpiRefundCnt" class="sub muted">0 txns</div>
        </div>
      </div>
    </div>

    <!-- Pay-In / Pay-Out table -->
    <div class="card p-3 smooth" data-section="cash-moves">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Pay-In / Pay-Out</h5>
        <span class="text-muted small">Most recent first</span>
      </div>
      <div class="table-responsive">
        <table class="table table-modern table-sm table-striped align-middle">
          <thead>
            <tr>
              <th style="width:190px;">Time</th>
              <th style="width:110px;">Type</th>
              <th>Reason</th>
              <th class="text-end" style="width:140px;">Amount</th>
            </tr>
          </thead>
          <tbody id="movesTbody">
            <tr><td colspan="4" class="text-center text-muted">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const API_BASE = '';

function peso(n){
  const v = Number(n||0);
  return '₱' + v.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
}
function fmtDate(s){
  if (!s) return '';
  return new Date(s.replace(' ', 'T')).toLocaleString();
}
function setText(id, text){ const el = document.getElementById(id); if (el) el.textContent = text; }
function setHTML(id, html){ const el = document.getElementById(id); if (el) el.innerHTML = html; }

let __prev = { expected:0, salesCnt:0, gross:0, vat:0, net:0, disc:0, pin:0, pout:0, refund:0 };
let __refreshTimer = null;

/* animate numbers (money-safe) */
function animateNumber(el, from, to, opts={}){
  const dur = opts.duration ?? 350;
  if (!el) return setText(el, to);
  const start = performance.now();
  const step = (t) => {
    const p = Math.min(1, (t - start) / dur);
    const v = from + (to - from) * p;
    el.textContent = opts.format ? opts.format(v) : String(v.toFixed?.(0) ?? v);
    if (p < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
}

/* show a tiny +Δ / −Δ chip beside KPI value */
function showDeltaChip(parent, diff){
  if (!parent) return;
  let chip = parent.querySelector('.delta-chip');
  if (!chip){
    chip = document.createElement('span');
    chip.className = 'delta-chip';
    parent.appendChild(chip);
  }
  const neg = diff < 0;
  chip.classList.toggle('neg', neg);
  const pretty = (n)=> (neg? '−' : '+') + (Math.abs(n)).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
  chip.textContent = pretty(diff);
  chip.classList.add('show');
  setTimeout(()=>chip.classList.remove('show'), 1400);
}

/* helper to render money with animation + delta */
function updateMoney(id, newVal, keyForPrev){
  const el = document.getElementById(id);
  const parent = el?.parentElement || null;
  const prev = Number(__prev[keyForPrev] || 0);
  if (el){
    animateNumber(el, prev, newVal, { duration: 400, format: v => '₱' + Number(v).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}) });
    const diff = newVal - prev;
    if (Math.abs(diff) >= 0.009) showDeltaChip(parent?.closest('.kpi')?.querySelector('.value') ? parent.closest('.kpi').querySelector('.value') : parent, diff);
  }
  __prev[keyForPrev] = newVal;
}

/* row reveal */
function revealRows(tbody){
  if (!tbody) return;
  [...tbody.querySelectorAll('tr')].forEach((tr,i)=>{
    setTimeout(()=>tr.classList.add('show'), 20 + i*25);
  });
}

/* pause auto refresh if tab hidden */
document.addEventListener('visibilitychange', ()=>{
  if (document.hidden && __refreshTimer){ clearInterval(__refreshTimer); __refreshTimer=null; }
  else if (!document.hidden && !__refreshTimer){ __refreshTimer = setInterval(loadSummary, 10000); }
});

async function loadSummary(){
  try{
    const shiftId = Number(document.getElementById('shiftSelect')?.value || 0);
    const url = API_BASE + 'shift_summary_data.php' + (shiftId ? ('?shift_id='+shiftId) : '');
    const r = await fetch(url, {cache:'no-store', credentials:'same-origin'});
    const data = JSON.parse(await r.text());
    if (!data.ok) throw new Error(data.error || 'API error');
    if (!data.active){ clearUI(); return; }
    renderSummary(data);
  }catch(err){
    console.error('loadSummary() failed:', err);
    alert('Failed to load Shift Summary (see console).');
  }
}

function clearUI(){
  setText('kpiExpected','₱0.00');
  setText('kpiSalesCnt','0');
  setText('kpiGross','₱0.00');
  setText('kpiVat','₱0.00');
  setText('kpiNetCash','₱0.00');
  setText('kpiDisc','₱0.00');
  setText('kpiPin','₱0.00');
  setText('kpiPout','₱0.00');
  setHTML('kpiExpectedBreak','');
  setHTML('shiftDetails','');
}

function renderSummary(data){
  const shift = data.shift || {};
  const agg   = data.agg   || {};
  const sales = agg.sales  || {};
  const moves = agg.moves  || {};     // <-- NEW
  const refunds = agg.refunds || {};               // <-- NEW


  const expected = Number(agg.expected || 0);
  const opening  = Number(shift.opening_cash || 0);
  const netCash  = Number(sales.net_cash_to_drawer || 0);
  const payInTot  = Number(moves.pay_in_total || 0);   // <-- NEW
  const payOutTot = Number(moves.pay_out_total || 0);  // <-- NEW
  const refundTot = Number(refunds.refund_total || 0);   // <-- NEW
  const refundCnt = Number(refunds.refund_count || 0);   // <-- NEW

  setText('kpiExpected', peso(expected));
  // Animated money + delta
updateMoney('kpiExpected', expected, 'expected');
setHTML('kpiExpectedBreak',
  `<span class="text-muted small">
    Opening ${peso(opening)} + Net sales ${peso(netCash)}
    + Pay-in ${peso(payInTot)} − Pay-out ${peso(payOutTot)} − Refunds ${peso(refundTot)}
  </span>`);

// counts & other KPIs
animateNumber(document.getElementById('kpiSalesCnt'), __prev.salesCnt, Number(sales.sale_count||0));
__prev.salesCnt = Number(sales.sale_count||0);

updateMoney('kpiGross',   Number(sales.gross_total_ex_vat||0), 'gross');
updateMoney('kpiVat',     Number(sales.vat_total||0),          'vat');
updateMoney('kpiNetCash', Number(netCash||0),                  'net');
updateMoney('kpiDisc',    Number(sales.discount_total||0),     'disc');
updateMoney('kpiPin',     Number(payInTot||0),                 'pin');
updateMoney('kpiPout',    Number(payOutTot||0),                'pout');
updateMoney('kpiRefund',  Number(refundTot||0),                'refund');
setText('kpiRefundCnt', `${refundCnt} txn${refundCnt===1?'':'s'}`);

  // NEW: update pay in/out tiles
  setText('kpiPin',  peso(payInTot));
  setText('kpiPout', peso(payOutTot));
  setText('kpiRefund',    peso(refundTot));               // <-- NEW
  setText('kpiRefundCnt', `${refundCnt} txn${refundCnt===1?'':'s'}`); // <-- NEW

  setHTML('shiftDetails', `
    <div><strong>Shift #:</strong> ${shift.shift_id ?? ''}</div>
    <div><strong>Cashier:</strong> ${shift.cashier_name ?? ''}</div>
    <div><strong>Branch:</strong> ${shift.branch_id ?? ''}</div>
    <div><strong>Opened:</strong> ${fmtDate(shift.start_time)}</div>
    <div><strong>Closed:</strong> ${shift.end_time ? fmtDate(shift.end_time) : '—'}</div>
    <div><strong>Status:</strong> ${shift.status ?? ''}</div>
  `);
  // --- Pay-In / Pay-Out Table Rendering ---
const list  = (data.lists && data.lists.moves) || [];
const tbody = document.getElementById('movesTbody');

if (tbody) {
  if (!list.length) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No pay-in / pay-out for this shift.</td></tr>`;
  } else {
    tbody.innerHTML = list.map(m => {
      const isIn  = m.move_type === 'pay_in';
      const chip  = isIn ? 'badge-in' : 'badge-out';
      const label = isIn ? 'Pay-In'   : 'Pay-Out';
      const amtCl = isIn ? 'amt amt-in' : 'amt amt-out';
      const reason = (m.reason || '').replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));

      return `
        <tr>
          <td class="small">${fmtDate(m.created_at)}</td>
          <td><span class="chip ${chip}"><i class="fa-solid ${isIn ? 'fa-circle-arrow-down' : 'fa-circle-arrow-up'}"></i> ${label}</span></td>
          <td>${reason}</td>
          <td class="text-end mono ${amtCl}">${peso(m.amount)}</td>
        </tr>`;
    }).join('');

  }
}

}


document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('refreshBtn')?.addEventListener('click', loadSummary);
  document.getElementById('shiftSelect')?.addEventListener('change', loadSummary);
  loadSummary();
  setInterval(loadSummary, 10000);
});
</script>
<script src="notifications.js"></script>
</body>
</html>
