<?php

session_start();

include 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['role'])) {
   header('Content-Type: application/json');
    exit;
}

$flash = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']); // one-time

$role = $_SESSION['role'];
$branch_id = $_SESSION['branch_id'] ?? 0;

// --- FILTERS ---
$where = [];
$params = [];
$types = "";

// Date range filter
if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    $where[] = "DATE(s.sale_date) BETWEEN ? AND ?";
    $params[] = $_GET['from_date'];
    $params[] = $_GET['to_date'];
    $types .= "ss";
}

// Sale ID filter
if (!empty($_GET['sale_id'])) {
    $where[] = "s.sale_id = ?";
    $params[] = (int)$_GET['sale_id'];
    $types .= "i";
}

// Branch filter
if ($role === 'staff') {
    $where[] = "s.branch_id = ?";
    $params[] = $branch_id;
    $types .= "i";
} elseif (!empty($_GET['branch_id'])) {
    $where[] = "s.branch_id = ?";
    $params[] = (int)$_GET['branch_id'];
    $types .= "i";
}


// Count rows (respect filters)
$countSql = "
  SELECT COUNT(DISTINCT s.sale_id) AS cnt
  FROM sales s
  JOIN branches b ON s.branch_id = b.branch_id
  LEFT JOIN sales_refunds r ON s.sale_id = r.sale_id
";
if ($where) $countSql .= " WHERE " . implode(" AND ", $where);

$stmt = $conn->prepare($countSql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRows = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
$stmt->close();
/* =====================================================
   COUNT ROWS (respect filters)
===================================================== */
$countSql = "
  SELECT COUNT(DISTINCT s.sale_id) AS cnt
  FROM sales s
  JOIN branches b ON s.branch_id = b.branch_id
  LEFT JOIN sales_refunds r ON s.sale_id = r.sale_id
";
if ($where) $countSql .= " WHERE " . implode(" AND ", $where);

$stmt = $conn->prepare($countSql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRows = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
$stmt->close();

/* =====================================================
   FIXED PAGINATION SETUP ⭐ (No more warnings)
===================================================== */
$perPage = 10;
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

/* =====================================================
   PAGE BUTTON WINDOW
===================================================== */
$window = 3;
$start = max(1, $page - $window);
$end   = min($totalPages, $page + $window);

if ($start > $end) {
    $start = 1;
    $end = $totalPages;
}

$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }


$sql = "
SELECT
  s.sale_id,
  s.sale_date,
  s.total,
  s.vat AS stored_vat,
  b.branch_name,

  /* total refunded peso (already stored in sales_refunds.refund_total) */
  COALESCE(SUM(r.refund_total), 0) AS refund_amount,

  /* simpler, safer aggregate for remarks */
  IFNULL(
    GROUP_CONCAT(DISTINCT r.refund_reason ORDER BY r.refund_date SEPARATOR '; '),
    ''
  ) AS refund_remarks,

  /* NEW: refunded items list like “Oil 3 in 1 ×2; M040 ×1” */
  rp.refunded_products

FROM sales s
JOIN branches b ON b.branch_id = s.branch_id
LEFT JOIN sales_refunds r ON r.sale_id = s.sale_id

/* ---------- NEW derived table for refunded_products ---------- */
LEFT JOIN (
  SELECT
    t.sale_id,
    GROUP_CONCAT(
      CONCAT(t.product_name, ' ×', t.qty)
      ORDER BY t.product_name SEPARATOR '; '
    ) AS refunded_products
  FROM (
    SELECT
      sri.sale_id,
      sri.product_id,
      p.product_name,
      SUM(sri.qty) AS qty
    FROM sales_refund_items AS sri
    JOIN products p ON p.product_id = sri.product_id
    GROUP BY sri.sale_id, sri.product_id, p.product_name
  ) AS t
  GROUP BY t.sale_id
) AS rp ON rp.sale_id = s.sale_id
/* ---------- end new block ---------- */";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= "
GROUP BY s.sale_id
ORDER BY s.sale_date DESC
LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$typesWithLimit  = $types . "ii";
$paramsWithLimit = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
$stmt->execute();
$sales_result = $stmt->get_result();


// $stmt = $conn->prepare($sql);
// if ($params) $stmt->bind_param($types, ...$params);
// $stmt->execute();
// $sales_result = $stmt->get_result();


// Pending transfer requests (for admin notification)
$pending = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pending = $row['pending'] ?? 0;
    }
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
function keep_qs(array $overrides = []): string {
  $q = $_GET;
  unset($q['page']);
  foreach ($overrides as $k => $v) $q[$k] = $v;
  return http_build_query($q);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?php $pageTitle = 'Sales History'; ?>
  <title><?= htmlspecialchars("RP Habana — $pageTitle") ?></title>
  <link rel="icon" href="img/R.P.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/notifications.css">
  <link rel="stylesheet" href="css/history.css?v3">
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
        <a href="history.php" class="active"><i class="fas fa-history"></i> Sales History</a>
        <a href="shift_summary.php" class="<?= $self === 'shift_summary.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-clipboard-check"></i> Shift Summary</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>                                  
<div class="container-fluid page-content py-5">

    <!-- Header -->
    <div class="page-header mb-4">
      <h2><i class="fas fa-history"></i> Sales History</h2>
    </div>

    <!-- Filter Card -->
    <form method="GET" class="d-flex flex-wrap align-items-end gap-3 mb-4">

        <div class="d-flex flex-column">
          <label class="form-label">From</label>
          <input type="date" name="from_date" class="form-control"
                 value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>">
        </div>

        <div class="d-flex flex-column">
          <label class="form-label">To</label>
          <input type="date" name="to_date" class="form-control"
                 value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>">
        </div>

        <div class="d-flex flex-column">
          <label class="form-label">Sale ID</label>
          <div class="input-group">
            <input type="text" name="sale_id" id="saleIdInput" class="form-control"
                   placeholder="Sale ID"
                   value="<?= htmlspecialchars($_GET['sale_id'] ?? '') ?>">

            <button type="button" class="btn btn-outline-secondary" id="clearSaleId">
              <i class="fa-solid fa-times"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-modern btn-gradient-blue">
          <i class="fas fa-search"></i> Search
        </button>

        <button type="submit" class="btn btn-neutral">
          <i class="fas fa-filter"></i> Filter
        </button>
    </form>

    <!-- Sales Table -->
    <div class="card-custom p-4">

        <?php if ($sales_result->num_rows === 0): ?>
            <div class="text-center text-muted py-4">
              <i class="fas fa-info-circle fs-4 d-block mb-2"></i>
              No sales history found.
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-modern table-bordered align-middle w-100">
                    <thead>
                        <tr>
                            <th class="col-id">Sale ID</th>
                            <th class="col-branch">Branch</th>
                            <th class="col-date">Date</th>
                            <th class="col-total text-end">Total (₱)</th>
                            <th class="col-remarks">Remarks</th>
                            <th class="col-refitems">Refunded Items</th>
                            <th class="col-status">Status</th>
                            <th class="col-actions text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($sale = $sales_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$sale['sale_id'] ?></td>
                            <td><?= htmlspecialchars($sale['branch_name']) ?></td>
                            <td><?= htmlspecialchars($sale['sale_date']) ?></td>

                            <td>
                              <span class="fw-bold text-success">
                                ₱<?= number_format((float)$sale['total'] + (float)$sale['stored_vat'], 2) ?>
                              </span>
                            </td>

                            <?php $remarks = trim($sale['refund_remarks'] ?? '—'); ?>
                            <td><?= htmlspecialchars(mb_strimwidth($remarks, 0, 80, '…')) ?></td>

                            <?php $refItems = trim($sale['refunded_products'] ?? '—'); ?>
                            <td><?= htmlspecialchars(mb_strimwidth($refItems, 0, 60, '…')) ?></td>

                            <?php
                                $refunded = (float)$sale['refund_amount'];
                                $totalWvat = (float)$sale['total'] + (float)$sale['stored_vat'];

                                if ($refunded <= 0) { $status="Not Refund"; $badge="secondary"; }
                                elseif ($refunded >= $totalWvat-0.01) { $status="Full Refund"; $badge="success"; }
                                else { $status="Partial Refund"; $badge="warning text-dark"; }
                            ?>
                            <td><span class="badge bg-<?= $badge ?>"><?= $status ?></span></td>

                            <td class="col-actions">
                                <div class="actions-wrap">
                                    <button type="button"
                                            onclick="openReceiptModal(<?= (int)$sale['sale_id'] ?>)"
                                            class="btn btn-info btn-modern btn-sm">
                                        <i class="fas fa-receipt"></i> Receipt
                                    </button>

                                    <?php if ($status !== "Full Refund"): ?>
                                    <button class="btn-action btn-gradient-green btn-sm"
                                            onclick="openReturnModal(<?= (int)$sale['sale_id'] ?>)">
                                        <i class="fas fa-undo"></i> Refund
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav class="mt-3">
              <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="?<?= keep_qs(['page' => max(1, $page - 1)]) ?>">Prev</a>
                </li>

                <?php for ($p = $start; $p <= $end; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                  <a class="page-link" href="?<?= keep_qs(['page' => $p]) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>

                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                  <a class="page-link" href="?<?= keep_qs(['page' => min($totalPages, $page + 1)]) ?>">Next</a>
                </li>
              </ul>
            </nav>

        <?php endif; ?>
    </div>

</div>

<!-- Return Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="product_return.php" class="modal-content">
      <div class="modal-header bg-danger text-white rounded-top">
        <h5 class="modal-title"><i class="fas fa-undo me-2"></i> Return / Refund</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Products -->
        <h6>Products</h6>
        <table class="table table-sm table-bordered">
          <thead>
            <tr>
              <th>Product</th>
              <th>Qty to Refund</th>
              <th>Price (₱)</th>
            </tr>
          </thead>
          <tbody id="returnProductsBody"></tbody>
        </table>

        <!-- Services -->
        <h6>Services</h6>
        <table class="table table-sm table-bordered">
          <thead>
            <tr>
              <th>Service</th>
              <th>Qty to Refund</th>
              <th>Price (₱)</th>
            </tr>
          </thead>
          <tbody id="returnServicesBody"></tbody>
        </table>

        <input type="hidden" name="sale_id" id="returnSaleId">

        <div class="mb-3">
          <label class="form-label">Reason</label>
          <select name="refund_reason" class="form-select" required>
            <option value="" disabled selected>Select a reason</option>
            <option value="Damaged product">Damaged product</option>
            <option value="Expired product">Expired product</option>
          </select>
        </div>

        <!-- Refund values -->
        <div class="mb-3">
          <label class="form-label">Refund Amount (₱)</label>
          <input type="number" step="0.01" name="refund_amount" id="refundAmount" class="form-control" readonly required>
        </div>

        <div class="mb-3">
          <label class="form-label">Refund VAT (₱)</label>
          <input type="number" step="0.01" name="refund_vat" id="refundVAT" class="form-control" readonly required>
        </div>

        <div class="mb-3">
          <label class="form-label">Refund Total (₱)</label>
          <input type="number" step="0.01" name="refund_total" id="refundTotal" class="form-control" readonly required>
        </div>

      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-danger btn-modern"><i class="fas fa-check"></i> Process Refund</button>
      </div>
    </form>
  </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:15px;">
      <div class="receipt-header d-flex justify-content-between align-items-center" style="background-color:#f7931e;color:white;padding:10px;border-radius:5px;">
        <h5 class="modal-title m-0"><i class="fas fa-receipt"></i> Receipt</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <hr>
      <div class="modal-body p-3" style="background-color:#fff7e6;">
        <iframe id="receiptFrame" src="" style="width:100%;height:400px;border:none;border-radius:10px;"></iframe>
      </div>
      <div class="modal-footer" style="background-color:#fff3cd;">
        <button class="btn btn-outline-warning" onclick="printReceipt()"><i class="fas fa-print"></i> Print</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Toast container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100">
  <div id="appToast" class="toast border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
    <div id="appToastHeader" class="toast-header bg-primary text-white">
      <i id="appToastIcon" class="fas fa-info-circle me-2"></i>
      <strong id="appToastTitle" class="me-auto">System Notice</strong>
      <small id="appToastTime">just now</small>
      <button type="button" class="btn-close btn-close-white ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="appToastBody">Action completed.</div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- Corrected VAT Refund Calculation ---
function openReturnModal(saleId) {
  const productsTbody = document.getElementById('returnProductsBody');
  const servicesTbody = document.getElementById('returnServicesBody');
  const refundAmountInput = document.getElementById('refundAmount');
  const refundVATInput = document.getElementById('refundVAT');
  const refundTotalInput = document.getElementById('refundTotal');

  document.getElementById('returnSaleId').value = saleId;
  productsTbody.innerHTML = '';
  servicesTbody.innerHTML = '';
  refundAmountInput.value = '0.00';
  refundVATInput.value = '0.00';
  refundTotalInput.value = '0.00';
  
const url = 'get_sales_products.php?sale_id=' + encodeURIComponent(saleId);

fetch(url, {
  credentials: 'same-origin'
})
    .then(async res => {
      const txt = await res.text();
      if (!res.ok) throw new Error(`HTTP ${res.status}: ${txt.slice(0,200)}`);
      try { return JSON.parse(txt); } catch (e) {
        console.error('Non-JSON response:', txt.slice(0,500));
        throw e;
      }
    })
    .then(data => {
      const products = data.products || [];
      const services = data.services || [];
      const saleNetTotal = +data.total || 0;
      const saleVat = +data.vat || 0;
      const vatRate = saleNetTotal > 0 ? (saleVat / saleNetTotal) : 0;

      // PRODUCTS
      if (products.length === 0) {
        productsTbody.innerHTML = `<tr><td colspan="3" class="text-center">No products found</td></tr>`;
      } else {
        products.forEach(item => {
          const maxQty = (item.remaining_qty ?? item.sold_qty ?? 0);
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${item.product_name}</td>
            <td>
              <input type="number" name="refund_items[${item.product_id}]"
                     min="0" max="${maxQty}" value="${maxQty}"
                     class="form-control form-control-sm" ${maxQty === 0 ? 'disabled' : ''}>
            </td>
            <td>₱${(+item.price).toFixed(2)}</td>
          `;
          productsTbody.appendChild(tr);
        });
      }

      // SERVICES (if you keep them)
      if (services.length === 0) {
        servicesTbody.innerHTML = `<tr><td colspan="3" class="text-center">No services found</td></tr>`;
      } else {
        services.forEach(item => {
          const maxQty = (item.remaining_qty ?? 0);
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${item.service_name}</td>
            <td><input type="number" name="refund_services[${item.service_id}]"
                       min="0" max="${maxQty}" value="${maxQty}"
                       class="form-control form-control-sm" ${maxQty === 0 ? 'disabled' : ''}></td>
            <td>₱${(+item.price).toFixed(2)}</td>`;
          servicesTbody.appendChild(tr);
        });
      }

      function recalcRefund() {
        let refundSubtotal = 0;
        products.forEach(item => {
          const qty = parseFloat(document.querySelector(`input[name="refund_items[${item.product_id}]"]`)?.value || 0);
          refundSubtotal += qty * (+item.price);
        });
        services.forEach(item => {
          const qty = parseFloat(document.querySelector(`input[name="refund_services[${item.service_id}]"]`)?.value || 0);
          refundSubtotal += qty * (+item.price);
        });
        const refundVat = refundSubtotal * vatRate;
        const refundTotal = refundSubtotal + refundVat;
        refundAmountInput.value = refundSubtotal.toFixed(2);
        refundVATInput.value    = refundVat.toFixed(2);
        refundTotalInput.value  = refundTotal.toFixed(2);
      }

      recalcRefund();
      [...productsTbody.querySelectorAll('input'), ...servicesTbody.querySelectorAll('input')]
        .forEach(i => i.addEventListener('input', recalcRefund));
    })
    .catch(err => {
      console.error('Error fetching sale products:', err);
      productsTbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">Error loading products</td></tr>`;
      servicesTbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">Error loading services</td></tr>`;
    });

  new bootstrap.Modal(document.getElementById('returnModal')).show();
}
</script>
<script>
function openReceiptModal(saleId) {
  const frame = document.getElementById('receiptFrame');
  frame.src = `receipt.php?sale_id=${saleId}&autoprint=1`;
  new bootstrap.Modal(document.getElementById('receiptModal')).show();
}
// Print button handler used by: onclick="printReceipt()"
function printReceipt() {
  const frame = document.getElementById('receiptFrame');
  if (frame && frame.contentWindow) frame.contentWindow.print();
}
</script>
<script>
(function () {
  const el    = document.getElementById('appToast');
  const body  = document.getElementById('appToastBody');
  const head  = document.getElementById('appToastHeader');
  const icon  = document.getElementById('appToastIcon');
  const title = document.getElementById('appToastTitle');
  const time  = document.getElementById('appToastTime');

  // Map toast variants to header colors & icons
  const STYLES = {
    info:    { cls: 'bg-primary',  icon: 'fa-info-circle',   title: 'System Notice' },
    success: { cls: 'bg-success',  icon: 'fa-check-circle',  title: 'Success' },
    warning: { cls: 'bg-warning text-dark', icon: 'fa-exclamation-triangle', title: 'Heads up' },
    danger:  { cls: 'bg-danger',   icon: 'fa-times-circle',  title: 'Error' }
  };

  function showToast(message, variant = 'info') {
    const { cls, icon: ico, title: ttl } = STYLES[variant] || STYLES.info;

    // Reset header classes then apply
    head.className = 'toast-header ' + cls;
    // Reset icon classes then apply
    icon.className = 'fas ' + ico + ' me-2';
    title.textContent = ttl;
    body.textContent  = message;
    time.textContent  = 'just now';

    const t = new bootstrap.Toast(el, { delay: 3500 });
    t.show();
  }

  // --- Auto triggers ---

  // 1) From session flash (PHP echo into JS safely)
  <?php if (!empty($flash) && is_array($flash)): ?>
    showToast(<?= json_encode($flash['msg'] ?? 'Action completed.') ?>,
              <?= json_encode($flash['type'] ?? 'info') ?>);
  <?php endif; ?>

  // 2) From query string (?toast=refund_ok | refund_err | filters | search)
  const params = new URLSearchParams(location.search);
  const toastKey = params.get('toast'); // e.g., refund_ok
  if (toastKey) {
    switch (toastKey) {
      case 'refund_ok':
        showToast('Refund processed successfully.', 'success');
        break;
      case 'refund_err':
        showToast('Refund failed. Please try again.', 'danger');
        break;
      case 'filters':
        showToast('Filters applied.', 'info');
        break;
      case 'search':
        showToast(`Showing results for "${params.get('q') || ''}"`, 'info');
        break;
      default:
        showToast('Action completed.', 'info');
    }
  }

  // 3) Gentle “filters applied” nudge when any filter is present
  const anyFilter = ['from_date','to_date','sale_id','branch_id','q']
    .some(k => (params.get(k) || '').trim() !== '');
  if (anyFilter && !toastKey && (params.get('page') || '1') === '1') {
    // Only show once, not on every paginated click
    showToast('Filters applied.', 'info');
  }

  // Make it globally available if you want to call it manually
  window.appToast = showToast;
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const btn   = document.getElementById('clearSaleId');
  const input = document.getElementById('saleIdInput');
  if (!btn || !input) return;

  btn.addEventListener('click', (e) => {
    e.preventDefault();
    input.value = '';
    input.focus();
    if (input.form) input.form.submit(); // auto-submit with Sale ID cleared
  });
});
</script>



<script src="notifications.js"></script>
<script src="sidebar.js"></script>
</body>
</html>
