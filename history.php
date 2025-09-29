<?php

session_start();

include 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['role'])) {
   header('Content-Type: application/json');
    exit;
}

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

// --- MAIN QUERY (with refund status) ---
$sql = "
SELECT 
    s.sale_id,
    s.sale_date,
    s.total,
    s.vat AS stored_vat,
    b.branch_name,
    COALESCE(SUM(r.refund_total), 0) AS refund_amount
FROM sales s
JOIN branches b ON s.branch_id = b.branch_id
LEFT JOIN sales_refunds r ON s.sale_id = r.sale_id
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= "
GROUP BY s.sale_id
ORDER BY s.sale_date DESC
";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$sales_result = $stmt->get_result();


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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
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
<div class="sidebar">
  <h2 class="user-heading">
    <span class="role"><?= htmlspecialchars(strtoupper($role), ENT_QUOTES) ?></span>
    <?php if ($currentName !== ''): ?>
      <span class="name"> (<?= htmlspecialchars($currentName, ENT_QUOTES) ?>)</span>
    <?php endif; ?>
    <span class="notif-wrapper">
      <i class="fas fa-bell" id="notifBell"></i>
      <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>><?= (int)$pending ?></span>
    </span>
  </h2>

  <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>
  <?php if ($role === 'admin'): ?>
      <a href="inventory.php?branch=<?= $branch_id ?>"><i class="fas fa-box"></i> Inventory</a>
      <a href="transfer.php"><i class="fas fa-box"></i> Transfer</a>
  <?php endif; ?>

  <?php if ($role === 'staff'): ?>
      <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
      <a href="history.php" class="active"><i class="fas fa-history"></i> Sales History</a>
  <?php endif; ?>

  <?php if ($role === 'admin'): ?>
      <a href="accounts.php"><i class="fas fa-user"></i> Accounts</a>
      <a href=""><i class="fas fa-archive"></i> Archive</a>
      <a href=""><i class="fas fa-calendar-alt"></i> Logs</a>
  <?php endif; ?>

  <a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="container py-5">
  <div class="page-header">
    <h2><i class="fas fa-history"></i> Sales History</h2>
  </div>

  <!-- Filter Card -->
  <form method="GET" class="row g-3 mb-4">
    <div class="col-md-3">
      <label class="form-label">From</label>
      <input type="date" name="from_date" class="form-control" 
             value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">To</label>
      <input type="date" name="to_date" class="form-control" 
             value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Sale ID</label>
      <input type="text" name="sale_id" class="form-control" 
             placeholder="Sale ID" value="<?= htmlspecialchars($_GET['sale_id'] ?? '') ?>">
    </div>

    <?php if ($role === 'admin'): ?>
      <div class="col-md-3">
        <label class="form-label">Branch</label>
        <select name="branch_id" class="form-select">
          <option value="">All Branches</option>
          <?php
          $branches = $conn->query("SELECT branch_id, branch_name FROM branches");
          while ($b = $branches->fetch_assoc()): ?>
            <option value="<?= $b['branch_id'] ?>" 
              <?= (($_GET['branch_id'] ?? '') == $b['branch_id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($b['branch_name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="col-md-2 align-self-end">
      <button type="submit" class="btn btn-modern btn-gradient-blue w-100">
        <i class="fas fa-filter"></i> Filter
      </button>
    </div>
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
      <table class="table table-modern table-bordered align-middle">
        <thead>
          <tr>
            <th>Sale ID</th>
            <th>Branch</th>
            <th>Date</th>
            <th>Total (₱)</th>
            <th>Refunded (₱)</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
         <?php while ($sale = $sales_result->fetch_assoc()): 
    $refunded = (float)$sale['refund_amount'];
    $total = (float)$sale['total'];
    $vat = (float)$sale['stored_vat'];

    // Total including VAT
    $totalWithVat = $total + $vat;

    if ($refunded == 0) {
        $status = "Not Refunded";
        $badge = "secondary";
    } elseif ($refunded < $totalWithVat) {
        $status = "Partial Refund";
        $badge = "warning text-dark";
    } else {
        $status = "Fully Refunded";
        $badge = "success";
    }
?>
<tr>
    <td><?= (int)$sale['sale_id'] ?></td>
    <td><?= htmlspecialchars($sale['branch_name']) ?></td>
    <td><?= htmlspecialchars($sale['sale_date']) ?></td>
    <td><span class="fw-bold text-success">₱<?= number_format($totalWithVat, 2) ?></span></td>
    <td><span class="fw-bold text-danger">₱<?= number_format($refunded, 2) ?></span></td>
    <td><span class="badge bg-<?= $badge ?>"><?= $status ?></span></td>
    <td>
        <a href="receipt.php?sale_id=<?= (int)$sale['sale_id'] ?>" target="_blank" 
           class="btn btn-info btn-modern btn-sm"><i class="fas fa-receipt"></i> Receipt</a>
        <?php if ($status !== "Fully Refunded"): ?>
            <button onclick="openReturnModal(<?= (int)$sale['sale_id'] ?>)" 
              class="btn-action btn-gradient-green">
              <i class="fas fa-undo"></i> Refund
            </button>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>

        </tbody>
      </table>
    </div>
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
            <option value="Customer changed mind">Customer changed mind</option>
            <option value="Wrong item delivered">Wrong item delivered</option>
            <option value="Damaged product">Damaged product</option>
            <option value="Expired product">Expired product</option>
            <option value="Other">Other</option>
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


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
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

    fetch(`get_sales_products.php?sale_id=${saleId}`)
        .then(res => res.json())
        .then(data => {
            const products = data.products || [];
            const services = data.services || [];
            const totalVAT = parseFloat(data.vat || 0);
            let subtotal = 0;

            // --- PRODUCTS ---
            if (products.length === 0) {
                productsTbody.innerHTML = `<tr><td colspan="3" class="text-center">No products found</td></tr>`;
            } else {
                products.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${item.product_name}</td>
                        <td><input type="number" name="refund_items[${item.product_id}]"
                                   min="0" max="${item.quantity}" value="${item.quantity}"
                                   class="form-control form-control-sm"></td>
                        <td>₱${parseFloat(item.price).toFixed(2)}</td>
                    `;
                    productsTbody.appendChild(tr);
                    subtotal += item.quantity * item.price;
                });
            }

            // --- SERVICES ---
            if (services.length === 0) {
                servicesTbody.innerHTML = `<tr><td colspan="3" class="text-center">No services found</td></tr>`;
            } else {
                services.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${item.service_name}</td>
                        <td><input type="number" name="refund_services[${item.service_id}]"
                                   min="0" max="${item.quantity}" value="${item.quantity}"
                                   class="form-control form-control-sm"></td>
                        <td>₱${parseFloat(item.price).toFixed(2)}</td>
                    `;
                    servicesTbody.appendChild(tr);
                    subtotal += item.quantity * item.price;
                });
            }

            // --- Initial VAT & Total ---
            let vatShare = totalVAT; // initially add full VAT
            refundAmountInput.value = subtotal.toFixed(2);
            refundVATInput.value = vatShare.toFixed(2);
            refundTotalInput.value = (subtotal + vatShare).toFixed(2);

            // --- Recalculate on input ---
            const allInputs = [...productsTbody.querySelectorAll('input'), ...servicesTbody.querySelectorAll('input')];
            allInputs.forEach(input => {
                input.addEventListener('input', () => {
                    let newSubtotal = 0;
                    products.forEach(item => {
                        const qty = parseFloat(document.querySelector(`input[name="refund_items[${item.product_id}]"]`).value) || 0;
                        newSubtotal += qty * item.price;
                    });
                    services.forEach(item => {
                        const qty = parseFloat(document.querySelector(`input[name="refund_services[${item.service_id}]"]`).value) || 0;
                        newSubtotal += qty * item.price;
                    });

                    // proportional VAT
                    const newVAT = totalVAT * (newSubtotal / subtotal || 0);
                    refundAmountInput.value = newSubtotal.toFixed(2);
                    refundVATInput.value = newVAT.toFixed(2);
                    refundTotalInput.value = (newSubtotal + newVAT).toFixed(2);
                });
            });

        })
        .catch(err => {
            console.error("Error fetching sale products:", err);
            productsTbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">Error loading products</td></tr>`;
            servicesTbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">Error loading services</td></tr>`;
            refundAmountInput.value = '0.00';
            refundVATInput.value = '0.00';
            refundTotalInput.value = '0.00';
        });

    new bootstrap.Modal(document.getElementById('returnModal')).show();
}
</script>
<script src="notifications.js"></script>
</body>
</html>
