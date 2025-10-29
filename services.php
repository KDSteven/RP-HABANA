<?php
session_start();

// Redirect to login if user not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

include 'config/db.php';
include 'functions.php';

$user_id   = $_SESSION['user_id'];
$role      = $_SESSION['role'];
$branch_id = $_SESSION['branch_id'] ?? null;

$pending = (int)($pending ?? 0);

/* -------------------- PENDING COUNTS (for sidebar badges) -------------------- */
$pendingTransfers = 0;
if ($role === 'admin') {
    if ($r = $conn->query("SELECT COUNT(*) AS c FROM transfer_requests WHERE status='pending'")) {
        $pendingTransfers = (int)($r->fetch_assoc()['c'] ?? 0);
    }
}
$pendingStockIns = 0;
if ($role === 'admin') {
    if ($r = $conn->query("SELECT COUNT(*) AS c FROM stock_in_requests WHERE status='pending'")) {
        $pendingStockIns = (int)($r->fetch_assoc()['c'] ?? 0);
    }
}
$pendingTotalInventory = $pendingTransfers + $pendingStockIns;

/* -------------------- Current branch context (tabs + filtering) -------------------- */
if (isset($_GET['branch'])) {
    $current_branch_id = (int)$_GET['branch'];
    $_SESSION['current_branch_id'] = $current_branch_id;
} else {
    $current_branch_id = $_SESSION['current_branch_id'] ?? $branch_id ?? 0;
}
$_SESSION['current_branch_id'] = $current_branch_id;

/* -------------------- Fetch branches for tabs -------------------- */
if ($role === 'staff') {
    $stmt = $conn->prepare("SELECT branch_id, branch_name, branch_location FROM branches WHERE branch_id = ?");
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $branches_result = $stmt->get_result();
    $stmt->close();
} else {
    $branches_result = $conn->query("SELECT branch_id, branch_name, branch_location FROM branches ORDER BY branch_name ASC");
}

/* -------------------- Fetch services for current branch -------------------- */
$services_stmt = $conn->prepare("
    SELECT service_id, service_name, price, description, branch_id
    FROM services
    WHERE branch_id = ? AND archived = 0
    ORDER BY service_name ASC
");
$services_stmt->bind_param("i", $current_branch_id);
$services_stmt->execute();
$services_result = $services_stmt->get_result();

/* -------------------- Archive service (POST) -------------------- */
if (isset($_POST['archive_service'])) {
    $service_id = (int)$_POST['service_id'];

    $stmt = $conn->prepare("SELECT service_name, branch_id FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $stmt->bind_result($service_name, $service_branch_id);
    $stmt->fetch();
    $stmt->close();

    if (!empty($service_name)) {
        $stmt = $conn->prepare("UPDATE services SET archived = 1 WHERE service_id = ?");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $stmt->close();

        logAction($conn, "Archive Service", "Archived service: $service_name (ID: $service_id)", null, (int)$service_branch_id);
        header("Location: services.php?archived=service");
        exit;
    } else {
        echo "Service not found!";
    }
}

/* -------------------- Current user's display name -------------------- */
$currentName = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($fetchedName);
    if ($stmt->fetch()) $currentName = $fetchedName;
    $stmt->close();
}

/* -------------------- Sidebar active states -------------------- */
$self     = strtolower(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
$isArchive = substr($self, 0, 7) === 'archive';
$invOpen   = in_array($self, ['inventory.php','physical_inventory.php'], true);
$toolsOpen = ($self === 'backup_admin.php' || $isArchive);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php $pageTitle = 'Services'; ?>
  <title><?= htmlspecialchars("RP Habana — $pageTitle") ?></title>
  <link rel="icon" href="img/R.P.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/notifications.css">
  <link rel="stylesheet" href="css/inventory.css?=v2"><!-- reuse styles -->
  <audio id="notifSound" src="notif.mp3" preload="auto"></audio>
</head>
<body class="inventory-page">

<!-- ======================= SIDEBAR (same structure) ======================= -->
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

<!-- ======================= CONTENT ======================= -->
<div class="content">

  <!-- Branch Navigation Tabs -->
  <div class="branches modern-tabs">
    <?php if ($role === 'stockman'): 
      $stockmanBranch = $conn->query("SELECT branch_name, branch_location, branch_id FROM branches WHERE branch_id = " . (int)$branch_id)->fetch_assoc();
    ?>
      <a href="services.php?branch=<?= (int)$branch_id ?>" class="active">
        <?= htmlspecialchars($stockmanBranch['branch_name']) ?>
        <small class="text-muted"><?= htmlspecialchars($stockmanBranch['branch_location']) ?></small>
      </a>
    <?php else: ?>
      <?php if ($branches_result && $branches_result->num_rows): ?>
        <?php while ($b = $branches_result->fetch_assoc()): ?>
          <a href="services.php?branch=<?= (int)$b['branch_id'] ?>"
             class="<?= ((int)$b['branch_id'] === (int)$current_branch_id) ? 'active' : '' ?>">
             <?= htmlspecialchars($b['branch_name']) ?>
             <small class="text-muted"><?= htmlspecialchars($b['branch_location']) ?></small>
          </a>
        <?php endwhile; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Manage Services Card -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="fa fa-wrench me-2"></i> Manage Services</h2>
        <button class="btn btn-create btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#addServiceModal">
          <i class="bi bi-plus-circle"></i> Add Service
        </button>
      </div>

      <?php if ($services_result && $services_result->num_rows > 0): ?>
        <div class="table-container">
          <!-- Header Table -->
          <table class="table table-header">
            <thead>
              <tr>
                <th>SERVICE ID</th>
                <th>SERVICE NAME</th>
                <th>PRICE (₱)</th>
                <th>DESCRIPTION</th>
                <th>ACTION</th>
              </tr>
            </thead>
          </table>

          <!-- Scrollable Body -->
          <div class="table-body scrollable-list">
            <table class="table table-body-table">
              <tbody>
              <?php while ($service = $services_result->fetch_assoc()): ?>
                <tr>
                  <td><?= (int)$service['service_id'] ?></td>
                  <td><?= htmlspecialchars($service['service_name']) ?></td>
                  <td><?= number_format((float)$service['price'], 2) ?></td>
                  <td><?= $service['description'] !== '' ? htmlspecialchars($service['description']) : '<em>No description</em>' ?></td>
                  <td class="text-center">
                    <div class="action-buttons">
                      <button class="btn-edit"
                              onclick='openEditServiceModal(<?= json_encode([
                                "service_id"   => (int)$service["service_id"],
                                "service_name" => $service["service_name"],
                                "price"        => (float)$service["price"],
                                "description"  => $service["description"],
                              ], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>)'>
                        <i class="fas fa-edit"></i>
                      </button>

                      <form id="archiveServiceForm-<?= (int)$service['service_id'] ?>" method="POST" style="display:inline-block;">
                        <input type="hidden" name="service_id" value="<?= (int)$service['service_id'] ?>">
                        <input type="hidden" name="archive_service" value="1">
                        <button type="button"
                                name="archive_service_btn"
                                class="btn-archive-unique"
                                data-archive-type="service"
                                data-archive-name="<?= htmlspecialchars($service['service_name']) ?>">
                          <i class="fas fa-archive"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php else: ?>
        <div class="text-center text-muted py-4">
          <i class="bi bi-info-circle fs-4 mb-2"></i>
          No services available for this branch.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ======================= MODALS ======================= -->

<!-- Add Service -->
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form id="addServiceForm" action="add_service.php" method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold" id="addServiceModalLabel">
            <i class="bi bi-plus-circle me-2"></i> Add New Service
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <input type="hidden" name="branch_id" value="<?= htmlspecialchars($current_branch_id) ?>">

        <div class="modal-body p-4">
          <div class="mb-3">
            <label for="serviceName" class="form-label fw-semibold">Service Name</label>
            <input type="text" name="service_name" id="serviceName" class="form-control" placeholder="Enter service name" required>
          </div>

          <div class="mb-3">
            <label for="servicePrice" class="form-label fw-semibold">Price (₱)</label>
            <!-- Add Service price -->
            <input type="number" step="0.01" min="0" inputmode="decimal"
                  name="price" id="servicePrice" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="serviceDescription" class="form-label fw-semibold">Description</label>
            <textarea name="description" id="serviceDescription" class="form-control" rows="3" placeholder="Optional"></textarea>
          </div>

          <div id="confirmSectionService" class="alert alert-warning mt-3 d-none">
            <p id="confirmMessageService">Are you sure you want to save this service?</p>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary btn-sm" id="cancelConfirmService">Cancel</button>
              <button type="submit" class="btn btn-success btn-sm">Yes, Save Service</button>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top-0">
          <button type="button" id="openConfirmService" class="btn btn-success fw-semibold">
            <i class="bi bi-save me-1"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Service -->
<div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form id="editServiceForm" action="update_service.php" method="POST">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title fw-bold" id="editServiceModalLabel">
            <i class="bi bi-pencil-square me-2"></i> Edit Service
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <input type="hidden" name="service_id" id="edit_service_id">
        <input type="hidden" name="branch_id" value="<?= htmlspecialchars($current_branch_id) ?>">

        <div class="modal-body p-4">
          <div class="mb-3">
            <label for="editServiceName" class="form-label fw-semibold">Service Name</label>
            <input type="text" name="service_name" id="editServiceName" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="editServicePrice" class="form-label fw-semibold">Price (₱)</label>
            <!-- Edit Service price -->
            <input type="number" step="0.01" min="0" inputmode="decimal"
                  name="price" id="editServicePrice" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="editServiceDescription" class="form-label fw-semibold">Description</label>
            <textarea name="description" id="editServiceDescription" class="form-control" rows="3" placeholder="Optional"></textarea>
          </div>

          <div id="confirmSectionEditService" class="alert alert-warning mt-3 d-none">
            <p id="confirmMessageEditService">Are you sure you want to save changes to this service?</p>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary btn-sm" id="cancelConfirmEditService">Cancel</button>
              <button type="submit" class="btn btn-success btn-sm">Yes, Save Changes</button>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top-0">
          <button type="button" id="openConfirmEditService" class="btn btn-success fw-semibold">
            <i class="bi bi-save me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Archive Service Modal -->
<div class="modal fade" id="archiveServiceModal" tabindex="-1" aria-labelledby="archiveServiceLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="archiveServiceLabel">
          <i class="fa-solid fa-box-archive me-2"></i> Archive Service
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        You’re about to archive <strong id="archiveServiceName">this service</strong> for this branch.
        <div class="small text-muted mt-2">
          This hides the service from selection but keeps history/logs.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmArchiveServiceBtn">
          <i class="fa-solid fa-archive me-1"></i> Yes, Archive
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
    <div class="toast-body" id="appToastBody">Action completed.</div>
  </div>
</div>

<!-- ======================= SCRIPTS ======================= -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" referrerpolicy="no-referrer"></script>
<script src="notifications.js"></script>
<script>
/* Generic toast helper (same as inventory page) */
function showToast(message, type = 'info') {
  const toastEl   = document.getElementById('appToast');
  const toastHead = toastEl.querySelector('.toast-header');
  const toastBody = document.getElementById('appToastBody');
  const map = { success:'bg-success', danger:'bg-danger', info:'bg-info', warning:'bg-warning' };
  toastHead.className = 'toast-header text-white ' + (map[type] || 'bg-info');
  toastBody.textContent = message;
  new bootstrap.Toast(toastEl).show();
}

/* Sidebar submenu toggle (persist state) */
(function(){
  const groups = document.querySelectorAll('.menu-group.has-sub');
  groups.forEach((g, idx) => {
    const btn = g.querySelector('.menu-toggle');
    const panel = g.querySelector('.submenu');
    if (!btn || !panel) return;
    const key = 'sidebar-sub-' + idx;
    const saved = localStorage.getItem(key);
    if (saved === 'open') { btn.setAttribute('aria-expanded','true'); panel.hidden = false; }
    btn.addEventListener('click', () => {
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!expanded));
      panel.hidden = expanded;
      localStorage.setItem(key, expanded ? 'closed' : 'open');
    });
  });
})();

/* Confirm sections (add/edit service) */
document.addEventListener("DOMContentLoaded", function () {
  function setupConfirm(openBtnId, sectionId, cancelBtnId) {
    const openBtn = document.getElementById(openBtnId);
    const section = document.getElementById(sectionId);
    const cancel  = document.getElementById(cancelBtnId);
    if (!openBtn || !section || !cancel) return;
    openBtn.addEventListener('click', () => section.classList.remove('d-none'));
    cancel.addEventListener('click', () => section.classList.add('d-none'));
  }
  setupConfirm('openConfirmService', 'confirmSectionService', 'cancelConfirmService');
  setupConfirm('openConfirmEditService', 'confirmSectionEditService', 'cancelConfirmEditService');
});

/* Edit Service modal filler */
function openEditServiceModal(service) {
  document.getElementById('edit_service_id').value = service.service_id;
  document.getElementById('editServiceName').value = service.service_name;
  document.getElementById('editServicePrice').value = service.price;
  document.getElementById('editServiceDescription').value = service.description || '';
  new bootstrap.Modal(document.getElementById('editServiceModal')).show();
}

/* Archive flow (service) */
(() => {
  let pendingForm = null;
  document.querySelectorAll('.btn-archive-unique').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      pendingForm = e.currentTarget.closest('form');
      const name = e.currentTarget.dataset.archiveName || 'this service';
      document.getElementById('archiveServiceName').textContent = name;
      new bootstrap.Modal(document.getElementById('archiveServiceModal')).show();
    });
  });
  const confirmBtn = document.getElementById('confirmArchiveServiceBtn');
  if (confirmBtn) {
    confirmBtn.addEventListener('click', () => {
      if (pendingForm) pendingForm.submit();
    });
  }
})();

/* Flash messages via query params (reusing your map keys) */
document.addEventListener('DOMContentLoaded', function () {
  const url = new URL(window.location.href);
  const qp  = url.searchParams;

  const flashMap = {
    archived: { service: ['Service archived for this branch.', 'success'] },
    as: { added: ['Service added successfully.', 'success'], error: ['There was an error adding the service.', 'danger'] },
    us: { updated: ['Service updated successfully.', 'success'], error: ['There was an error updating the service.', 'danger'] }
  };

  for (const key in flashMap) {
    const val = qp.get(key);
    if (!val) continue;
    const entry = flashMap[key][val];
    if (entry) showToast(entry[0], entry[1]);
    qp.delete(key);
  }
  const cleanUrl = url.pathname + (qp.toString() ? '?' + qp.toString() : '');
  history.replaceState({}, '', cleanUrl);
});
</script>

<script>
/* Service price guards: block -, +, e/E; clamp negatives/pastes; validate on submit */
(function () {
  const ids = ["servicePrice", "editServicePrice"];

  function blockKeys(e) {
    if (["-", "+", "e", "E"].includes(e.key)) e.preventDefault();
  }

  function sanitize(el) {
    // strip forbidden chars (handles paste) and clamp to 0+
    const cleaned = (el.value || "").replace(/[+\-eE]/g, "");
    if (cleaned !== el.value) el.value = cleaned;
    const n = Number(el.value);
    if (!Number.isFinite(n) || n < 0) el.value = "0";
  }

  ids.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener("keydown", blockKeys);
    el.addEventListener("input", () => sanitize(el));
    el.addEventListener("paste", () => setTimeout(() => sanitize(el)));
    el.addEventListener("blur", () => sanitize(el));
  });

  // Submit-time safety net
  const addForm  = document.getElementById("addServiceForm");
  const editForm = document.getElementById("editServiceForm");
  [[addForm, "servicePrice"], [editForm, "editServicePrice"]].forEach(([form, fieldId]) => {
    if (!form) return;
    form.addEventListener("submit", (e) => {
      const el = document.getElementById(fieldId);
      const n  = Number(el?.value ?? "");
      if (!Number.isFinite(n) || n < 0) {
        e.preventDefault();
        alert("Price must be 0 or higher.");
        el?.focus();
      }
    });
  });
})();
</script>
<script src="sidebar.js"></script>
</body>
</html>
