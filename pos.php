<?php
session_start();
include 'config/db.php';
include 'functions.php';

if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}

$user_id   = (int)($_SESSION['user_id'] ?? 0);
$role      = $_SESSION['role'];
$branch_id = (int)($_SESSION['branch_id'] ?? 0);
$search    = trim($_GET['search'] ?? '');
$errorMessage = '';
$showReceiptModal = false;
$lastSaleId = null;

$pending = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'")
           ->fetch_assoc()['pending'] ?? 0;

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

/** Helper */
function finalPrice($price, $markup) {
    return (float)$price + ((float)$price * ((float)$markup / 100));
}

/** Server-side, trustworthy subtotal (used for initial render + fallback for JS) */
$cartSubtotal = 0.0;
foreach ($_SESSION['cart'] as $item) {
    if (($item['type'] ?? '') === 'product') {
        if (isset($item['price'])) {
            $price = (float)$item['price'];
        } else {
            $stmt = $conn->prepare("SELECT price, markup_price FROM products WHERE product_id=?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $price = $row ? finalPrice($row['price'], $row['markup_price']) : 0.0;
        }
    } else { // service
        $price = (float)($item['price'] ?? 0);
    }
    $qty = max(0, (int)($item['qty'] ?? 0));
    $cartSubtotal += $price * $qty;
}

function checkoutCart($conn, $user_id, $branch_id, $payment, $discount = 0, $discount_type = 'amount') {
    if (empty($_SESSION['cart'])) {
        throw new Exception("Cart is empty.");
    }

    // ---------- 1. CALCULATE SUBTOTAL ----------
    $subtotal = 0.0;
    $totalVat = 0.0;

    foreach ($_SESSION['cart'] as &$item) {
        if ($item['type'] === 'product') {
            $stmt = $conn->prepare("SELECT price, markup_price, vat FROM products WHERE product_id=?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $prod = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $price = finalPrice($prod['price'] ?? 0, $prod['markup_price'] ?? 0);
            $vatRate = (float)($prod['vat'] ?? 0);
        } else { // service
            $price = (float)$item['price'];
            $vatRate = (float)($item['vat'] ?? 0);
        }

        $qty = (int)$item['qty'];
        $lineSubtotal = $price * $qty;
        $lineVat = $lineSubtotal * ($vatRate / 100);

        // Save to item for later insert
        $item['calculated_price'] = $price;
        $item['calculated_vat'] = $lineVat;

        $subtotal += $lineSubtotal;
        $totalVat += $lineVat;
    }

    // ---------- 2. APPLY DISCOUNT ----------
    $discount_value = 0.0;
    if ($discount > 0) {
        $discount_value = ($discount_type === 'percent')
            ? $subtotal * ($discount / 100)
            : min($discount, $subtotal);
    }
    $after_discount = $subtotal - $discount_value;

    // ---------- 3. GRAND TOTAL ----------
    $grand_total = $after_discount + $totalVat;

    // ---------- 4. CHECK PAYMENT ----------
    if ($payment < $grand_total) {
        throw new Exception("Payment is less than total (₱" . number_format($grand_total, 2) . ")");
    }
    $change = $payment - $grand_total;

    // ---------- 5. BEGIN TRANSACTION ----------
    $conn->begin_transaction();
    try {
        // --- Insert sale ---
        $stmt = $conn->prepare("
            INSERT INTO sales 
            (branch_id, total, discount, discount_type, vat, payment, change_given, processed_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed')
        ");
        $stmt->bind_param("iddsddis", $branch_id, $subtotal, $discount_value, $discount_type, $totalVat, $payment, $change, $user_id);
        $stmt->execute();
        $sale_id = $conn->insert_id;
        $stmt->close();

        // --- Insert items & update inventory ---
        foreach ($_SESSION['cart'] as $item) {
            if ($item['type'] === 'product') {
                $pid = (int)$item['product_id'];
                $qty = (int)$item['qty'];
                $price = (float)$item['calculated_price'];
                $vat = (float)$item['calculated_vat'];

                // Update inventory
                $upd = $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE branch_id=? AND product_id=? AND stock >= ?");
                $upd->bind_param("iiii", $qty, $branch_id, $pid, $qty);
                $upd->execute();
                if ($upd->affected_rows === 0) {
                    $conn->rollback();
                    throw new Exception("Not enough stock for product ID {$pid}.");
                }
                $upd->close();

                // Insert sale item
                $ins = $conn->prepare("INSERT INTO sales_items (sale_id, product_id, quantity, price, vat) VALUES (?, ?, ?, ?, ?)");
                $ins->bind_param("iiidd", $sale_id, $pid, $qty, $price, $vat);
                $ins->execute();
                $ins->close();

            } else { // service
                $sid = (int)$item['service_id'];
                $price = (float)$item['calculated_price'];
                $vat = (float)$item['calculated_vat'];

                $ins = $conn->prepare("INSERT INTO sales_services (sale_id, service_id, price, vat) VALUES (?, ?, ?, ?)");
                $ins->bind_param("iidd", $sale_id, $sid, $price, $vat);
                $ins->execute();
                $ins->close();
            }
        }

        $conn->commit();
        $_SESSION['cart'] = []; // clear cart

        return ['sale_id' => $sale_id, 'change' => $change, 'grand_total' => $grand_total];

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $payment = (float)($_POST['payment'] ?? 0);
    $discount = (float)($_POST['discount'] ?? 0);
    $discount_type = $_POST['discount_type'] ?? 'amount';

    try {
        $result = checkoutCart($conn, $user_id, $branch_id, $payment, $discount, $discount_type);
        // Redirect to self with lastSale to show receipt (PRG pattern)
        header("Location: pos.php?lastSale=" . $result['sale_id']);
        
        exit;
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        // Optionally show a toast on page reload
    }
}

/** Near-expiration notifications (cart items) — cart-level banner */
$today = new DateTime();
$nearingExpirationProducts = [];
foreach ($_SESSION['cart'] as $item) {
    if (($item['type'] ?? '') !== 'product') continue;
    $stmt = $conn->prepare("SELECT product_name, expiration_date FROM products WHERE product_id=?");
    $stmt->bind_param("i", $item['product_id']);
    $stmt->execute();
    $prod = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!empty($prod['expiration_date'])) {
        $expDate = new DateTime($prod['expiration_date']);
        if ($expDate >= $today && $expDate <= (clone $today)->modify('+30 days')) {
            $nearingExpirationProducts[] = $prod['product_name'];
        }
    }
}

/** Show receipt after PRG */
if (isset($_GET['lastSale'])) {
    $lastSaleId = (int)$_GET['lastSale'];
    if ($lastSaleId > 0) $showReceiptModal = true;
}

/** Quick products by category for the right panel
 *  NOTE: we include expiration_date so buttons can carry it for immediate toast
 */
$category_products = [];
$stmt = $conn->prepare("
    SELECT p.product_id, p.product_name, p.price, p.markup_price, i.stock, p.category, p.expiration_date
    FROM products p
    JOIN inventory i ON p.product_id = i.product_id
    WHERE i.branch_id = ? AND i.stock > 0
    ORDER BY p.category, p.product_name
");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $category_products[$row['category']][] = $row;
}
$stmt->close();

/** Services list (filtered by branch) */
$services = [];
$branch_id = (int)($_SESSION['branch_id'] ?? 0);

if ($branch_id > 0) {
    $stmt = $conn->prepare("
        SELECT * 
        FROM services 
        WHERE branch_id = ? 
          AND archived = 0
    ");
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($s = $result->fetch_assoc()) {
        $services[] = $s;
    }
    $stmt->close();
}


/** Admin: pending password resets badge */
$pendingResetsCount = 0;
if ($role === 'admin') {
    $res = $conn->query("SELECT COUNT(*) AS c FROM password_resets WHERE status='pending'");
    $pendingResetsCount = $res ? (int)$res->fetch_assoc()['c'] : 0;
}

/** Current user name (sidebar) */
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
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>RP Habana — POS</title>
<link rel="icon" href="img/R.P.png">

<!-- Bootstrap & FontAwesome -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/notifications.css">
<link rel="stylesheet" href="css/pos.css?v=3">
<link rel="stylesheet" href="css/sidebar.css">
<audio id="notifSound" src="img/notif.mp3" preload="auto"></audio>

<style>
.pos-wrapper { display:flex; flex:1; flex-wrap:wrap; padding:20px; gap:20px; }
.cart-section { flex:2; min-width:350px; }
.controls-section { flex:1; min-width:300px; }
.quick-btn-form button { min-width:100px; text-align:center; white-space:normal; margin-bottom:5px; }
.table-wrapper { max-height:350px; overflow-y:auto; }
@media(max-width:1024px){ .pos-wrapper{flex-direction:column;} }
</style>
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
        <a href="pos.php" class="active"><i class="fas fa-cash-register"></i> Point of Sale</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
  </div>
</div>

<!-- POS Wrapper -->
<div class="pos-wrapper">

  <!-- Cart Section -->
  <div class="cart-section" id="cartSection">
    <?php if (!empty($nearingExpirationProducts)): ?>
      <div class="alert alert-warning mt-2">
        <i class="fas fa-exclamation-triangle"></i>
        Nearly Expired Products in Cart:
        <?= htmlspecialchars(implode(", ", $nearingExpirationProducts), ENT_QUOTES) ?>
      </div>
    <?php endif; ?>

    <?php include 'pos_cart_partial.php'; ?>
  </div>

  <!-- Controls Section -->
  <div class="controls-section">

    <!-- Search -->
    <div class="card mb-2">
      <form method="GET" class="d-flex gap-2">
        <div class="input-group">
          <span class="input-group-text">
            <i class="fas fa-search"></i>
          </span>
          <input type="text"
                 name="search"
                 placeholder="Scan or search product..."
                 class="form-control"
                 value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">
        </div>
        <button class="btn btn-secondary" type="submit">
          <i class="fas fa-search"></i> Search
        </button>
      </form>
    </div>

    <!-- Invisible scanner input -->
    <input type="text" id="barcodeInput" autocomplete="off" autofocus style="opacity:0; position:absolute;">

    <!-- Quick Add Products by Category -->
    <?php foreach($category_products as $cat => $products): ?>
      <div class="card mb-2 p-2">
        <h5><?= htmlspecialchars($cat, ENT_QUOTES) ?></h5>
        <div class="quick-btn-form d-flex flex-wrap gap-2">
          <?php foreach($products as $p): ?>
            <button class="btn btn-outline-primary quick-add-btn"
                    data-type="product"
                    data-id="<?= (int)$p['product_id'] ?>"
                    data-qty="1"
                    
                    data-expiration="<?= htmlspecialchars($p['expiration_date'] ?? '', ENT_QUOTES) ?>"
                    data-name="<?= htmlspecialchars($p['product_name'], ENT_QUOTES) ?>">
              <?= htmlspecialchars($p['product_name'], ENT_QUOTES) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Services -->
    <div class="card mb-2 p-2">
      <h5>Services</h5>
      <div class="quick-btn-form d-flex flex-wrap gap-2">
        <?php foreach($services as $s): ?>
          <button class="btn btn-outline-success quick-add-btn"
                  data-type="service"
                  data-id="<?= (int)$s['service_id'] ?>"
                  data-qty="1"
                  data-price="<?= htmlspecialchars($s['price'], ENT_QUOTES) ?>"
                  data-name="<?= htmlspecialchars($s['service_name'], ENT_QUOTES) ?>">
            <?= htmlspecialchars($s['service_name'], ENT_QUOTES) ?><br>₱<?= number_format((float)$s['price'],2) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Checkout -->
    <div class="card checkout-buttons p-2 d-flex gap-2">
      <button type="button" id="openPaymentBtn" class="btn btn-success">
        <i class="fas fa-money-bill-wave"></i> PAYMENT
      </button>
      <button type="button" class="btn btn-danger" id="cancelOrderBtn">
        <i class="fas fa-times"></i> CANCEL
      </button>
    </div>

  </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Enter Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST" id="paymentForm">
       <div class="modal-body text-center" id="paymentModalBody"
     data-subtotal="<?= number_format($cartSubtotal, 2, '.', '') ?>"
     data-vat="<?= number_format($totalVat, 2, '.', '') ?>"
     data-grandtotal="<?= number_format($cartGrandTotal, 2, '.', '') ?>">
  <h6 id="totalDueText">Total Due: ₱<?= number_format($cartGrandTotal, 2) ?></h6>


          <!-- Discount -->
          <div class="d-flex gap-2 mt-2">
            <input type="number" step="0.01" min="0" name="discount" id="discountInput" class="form-control" placeholder="Discount">
            <select name="discount_type" id="discountType" class="form-select" style="max-width:120px;">
              <option value="amount">₱</option>
              <option value="percent">%</option>
            </select>
          </div>

          <!-- Quick Cash -->
          <div class="d-flex flex-wrap gap-2 justify-content-center mt-2">
            <?php foreach ([50,100,200,500,1000] as $cash): ?>
              <button type="button" class="btn btn-outline-secondary quick-cash" data-value="<?= (int)$cash ?>">₱<?= (int)$cash ?></button>
            <?php endforeach; ?>
          </div>

          <!-- Payment -->
          <input type="number" step="0.01" min="0" name="payment" id="paymentInput" class="form-control mt-3" placeholder="Enter cash received..." required>

          <!-- Change Due -->
          <h6 class="mt-2 text-success" id="displayChange"></h6>

          <!-- Number Pad -->
          <div class="number-pad mt-3">
            <div class="d-grid gap-2" style="grid-template-columns: repeat(3, 1fr);">
              <?php foreach ([1,2,3,4,5,6,7,8,9,0] as $num): ?>
                <button type="button" class="btn btn-outline-dark num-btn" data-value="<?= $num ?>"><?= $num ?></button>
              <?php endforeach; ?>
              <button type="button" class="btn btn-danger num-btn" data-value="clear">C</button>
              <button type="button" class="btn btn-warning num-btn" data-value="back">⌫</button>
            </div>
          </div>

          <!-- Notes -->
          <div class="mt-3">
            <textarea name="note" id="paymentNote" class="form-control" rows="2" placeholder="Add a note (optional)..."></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" name="checkout" id="checkout" class="btn btn-success w-100">Confirm Payment</button>
        </div>
      </form>
    </div>
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

<!-- Cancel Order (Bootstrap modal) -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fas fa-ban"></i> Cancel Current Order</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to cancel this order? This will clear all items from the cart.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Order</button>
        <button type="button" class="btn btn-danger" id="confirmCancelBtn">Cancel Order</button>
      </div>
    </div>
  </div>
</div>

<!-- Toasts -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="notifications.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const lastSale = params.get('lastSale');   // from your redirect

  if (lastSale) {
    // 1) point the iframe to your receipt endpoint (adjust filename if different)
    const frame = document.getElementById('receiptFrame');
    if (frame) {
      frame.src = 'receipt.php?sale_id=' + encodeURIComponent(lastSale);
    }

    // 2) show the modal
    const modalEl = document.getElementById('receiptModal');
    if (modalEl) {
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    }

    // 3) clean the URL so reload doesn't re-open the modal
    if (history.replaceState) {
      params.delete('lastSale');
      const newQS = params.toString();
      history.replaceState({}, document.title, window.location.pathname + (newQS ? '?' + newQS : ''));
    }
  }
});

// Print button handler used by: onclick="printReceipt()"
function printReceipt() {
  const frame = document.getElementById('receiptFrame');
  if (frame && frame.contentWindow) frame.contentWindow.print();
}
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('paymentInput');
  document.querySelectorAll('.num-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      let val = btn.dataset.value;
      if(val === 'clear') input.value = '';
      else if(val === 'back') input.value = input.value.slice(0, -1);
      else input.value += val;
    });
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // ======= Toast helper =======
  function showToast(title, message, type='primary', delay=3000) {
    let container = document.querySelector('.toast-container');
    // Safety: auto-create container if missing
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container position-fixed top-0 end-0 p-3';
      document.body.appendChild(container);
    }
    const toastEl = document.createElement('div');
    toastEl.className = `toast text-bg-${type} border-0`;
    toastEl.setAttribute('role','alert');
    toastEl.setAttribute('aria-live','assertive');
    toastEl.setAttribute('aria-atomic','true');
    toastEl.innerHTML = `
      <div class="d-flex">
        <div class="toast-body"><strong>${title}:</strong> ${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>`;
    container.prepend(toastEl);
    const bsToast = new bootstrap.Toast(toastEl, {delay});
    bsToast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
  }

  // ======= Expiration helpers & settings =======
  const NEAR_EXPIRY_DAYS = 365; // change to your preferred window
  const shownExpiryToasts = new Set(); // de-dup for this page life

  // ---- Stable keys for de-dup (prevents first-action duplicate) ----
  function canonicalName(s) {
    return (s || 'Product').trim().replace(/\s+/g, ' ').toLowerCase();
  }
  function keyFor(name, norm) {
    return `${canonicalName(name)}|${norm.y}-${String(norm.m).padStart(2,'0')}-${String(norm.d).padStart(2,'0')}`;
  }

  function normalizeExpString(s) {
    if (!s) return null;
    const first10 = s.trim().slice(0, 10);
    const ymd = first10.replace(/[./]/g, '-');
    const parts = ymd.split('-');
    if (parts.length !== 3) return null;
    const [y, m, d] = parts.map(Number);
    if (!y || !m || !d) return null;
    return { y, m, d };
  }
  function makeDateEndOfDay({ y, m, d }) {
    return new Date(y, m - 1, d, 23, 59, 59, 999);
  }
  function daysUntil(fromDate, toDate) {
    const msPerDay = 24 * 60 * 60 * 1000;
    return Math.ceil((toDate.getTime() - fromDate.getTime()) / msPerDay);
  }

  // ---- FIXED: adds to shownExpiryToasts so follow-up scan won't duplicate ----
  function maybeToastExpiry(expStr, name) {
    if (!expStr) return;
    const norm = normalizeExpString(expStr);
    if (!norm) return;

    const key = keyFor(name, norm);
    if (shownExpiryToasts.has(key)) return;

    const today = new Date(); today.setHours(0,0,0,0);
    const expDate = makeDateEndOfDay(norm);
    const diffDays = daysUntil(today, expDate);

    if (diffDays <= 0) {
      showToast('<i class="fas fa-skull-crossbones"></i> Expired Product',
                `"${name || 'Product'}" has already expired!`, 'danger');
      shownExpiryToasts.add(key);
    } else if (diffDays <= NEAR_EXPIRY_DAYS) {
      showToast('<i class="fas fa-exclamation-triangle"></i> Near Expiration',
                `"${name || 'Product'}" is near expiration (${diffDays} days left)`, 'warning');
      shownExpiryToasts.add(key);
    }
  }

  function checkCartExpiration() {
    const today = new Date(); today.setHours(0,0,0,0);
    const rows = document.querySelectorAll('tr[data-expiration]');
    rows.forEach(row => {
      const raw = row.getAttribute('data-expiration');
      if (!raw) return;
      const norm = normalizeExpString(raw);
      if (!norm) return;

      const productName = row.querySelector('td')?.textContent?.trim() || 'Product';
      const key = keyFor(productName, norm);
      if (shownExpiryToasts.has(key)) return;

      const expDate = makeDateEndOfDay(norm);
      const diffDays = daysUntil(today, expDate);

      if (diffDays <= 0) {
        showToast('<i class="fas fa-skull-crossbones"></i> Expired Product',
                  `"${productName}" has already expired!`, 'danger');
        shownExpiryToasts.add(key);
      } else if (diffDays <= NEAR_EXPIRY_DAYS) {
        showToast('<i class="fas fa-exclamation-triangle"></i> Near Expiration',
                  `"${productName}" is near expiration (${diffDays} days left)`, 'warning');
        shownExpiryToasts.add(key);
      }
    });
  }

  // ======= Helpers to diff cart by name / parse HTML (for instant barcode toast) =======
  function getCartNameCountsFromDOM() {
    const counts = new Map();    // canonical name -> count
    const display = new Map();   // canonical name -> last display name seen
    const expByName = new Map(); // canonical name -> one expiration date (string)
    document.querySelectorAll('tr[data-expiration]').forEach(row => {
      const disp = row.querySelector('td')?.textContent?.trim() || 'Product';
      const cn = canonicalName(disp);
      counts.set(cn, (counts.get(cn) || 0) + 1);
      display.set(cn, disp);
      const exp = row.getAttribute('data-expiration') || '';
      if (exp && !expByName.has(cn)) expByName.set(cn, exp);
    });
    return { counts, display, expByName };
  }

  function getCartNameCountsFromHTML(html) {
    const tpl = document.createElement('template');
    tpl.innerHTML = (html || '').trim();
    const counts = new Map();
    const display = new Map();
    const expByName = new Map();
    tpl.content.querySelectorAll('tr[data-expiration]').forEach(row => {
      const disp = row.querySelector('td')?.textContent?.trim() || 'Product';
      const cn = canonicalName(disp);
      counts.set(cn, (counts.get(cn) || 0) + 1);
      display.set(cn, disp);
      const exp = row.getAttribute('data-expiration') || '';
      if (exp && !expByName.has(cn)) expByName.set(cn, exp);
    });
    return { counts, display, expByName };
  }

  function findRowByName(name) {
    const rows = document.querySelectorAll('tr[data-expiration]');
    for (const row of rows) {
      const n = row.querySelector('td')?.textContent?.trim() || 'Product';
      if (n === name) return row;
    }
    return null;
  }

  // ======= Payment modal totals (single source of truth) =======
  function readTotalsFromDOMOrFallback() {
    const subEl = document.querySelector('.subtotal');
    const vatEl = document.querySelector('.vat');
    const grdEl = document.querySelector('.grand');

    let subtotal = parseFloat(subEl?.dataset.value ?? 'NaN');
    let vat      = parseFloat(vatEl?.dataset.value ?? 'NaN');
    const grand  = parseFloat(grdEl?.dataset.value ?? 'NaN');

    if (isNaN(subtotal) && subEl)
      subtotal = parseFloat(subEl.textContent.replace(/[^0-9.-]+/g, ''));
    if (isNaN(vat) && vatEl)
      vat = parseFloat(vatEl.textContent.replace(/[^0-9.-]+/g, ''));

    if ((isNaN(vat) || vat == null) && !isNaN(grand) && !isNaN(subtotal))
      vat = grand - subtotal;

    const body = document.getElementById('paymentModalBody');
    if (isNaN(subtotal)) subtotal = parseFloat(body?.dataset.subtotal || '0');
    if (isNaN(vat))      vat      = parseFloat(body?.dataset.vat || '0');

    return { subtotal, vat };
  }

  function syncPaymentModalTotals() {
    const body = document.getElementById('paymentModalBody');
    const totalDueText = document.getElementById('totalDueText');
    const { subtotal, vat } = readTotalsFromDOMOrFallback();
    if (body) {
      body.dataset.subtotal = (subtotal || 0).toFixed(2);
      body.dataset.vat      = (vat || 0).toFixed(2);
    }
    if (totalDueText) totalDueText.textContent = `Total Due: ₱${((subtotal||0)+(vat||0)).toFixed(2)}`;
    updatePaymentComputed();
  }

  function resetPaymentModal() {
    const body = document.getElementById('paymentModalBody');
    const totalDueText = document.getElementById('totalDueText');
    const discountInput = document.getElementById('discountInput');
    const discountType  = document.getElementById('discountType');
    const paymentInput  = document.getElementById('paymentInput');
    const displayDiscount = document.getElementById('displayDiscount');
    const displayPayment  = document.getElementById('displayPayment');
    const displayChange   = document.getElementById('displayChange');

    if (body) { body.dataset.subtotal = '0.00'; body.dataset.vat = '0.00'; }
    if (totalDueText) totalDueText.textContent = 'Total Due: ₱0.00';
    if (discountInput) discountInput.value = '';
    if (discountType)  discountType.value = 'amount';
    if (paymentInput)  paymentInput.value = '';
    if (displayDiscount) displayDiscount.textContent = '₱0.00';
    if (displayPayment)  displayPayment.textContent  = '₱0.00';
    if (displayChange)   displayChange.textContent   = '₱0.00';
  }

  // ======= Payment inputs / computed fields =======
  function updatePaymentComputed() {
    const body          = document.getElementById('paymentModalBody');
    const discountInput = document.getElementById('discountInput');
    const discountType  = document.getElementById('discountType');
    const paymentInput  = document.getElementById('paymentInput');
    const totalDueText  = document.getElementById('totalDueText');
    const displayDiscount = document.getElementById('displayDiscount');
    const displayPayment  = document.getElementById('displayPayment');
    const displayChange   = document.getElementById('displayChange');

    const subtotal = parseFloat(body?.dataset.subtotal || '0');
    const vat      = parseFloat(body?.dataset.vat || '0');
    const grand    = subtotal + vat;

    let discountVal = parseFloat(discountInput?.value || '0');
    if ((discountType?.value || 'amount') === 'percent') {
      discountVal = subtotal * (discountVal / 100);
    }
    discountVal = Math.min(Math.max(discountVal, 0), grand);

    const due = Math.max(0, grand - discountVal);
    const pay = parseFloat(paymentInput?.value || '0');
    const change = Math.max(0, pay - due);

    if (displayDiscount) displayDiscount.textContent = `₱${discountVal.toFixed(2)}`;
    if (displayPayment)  displayPayment.textContent  = `₱${pay.toFixed(2)}`;
    if (displayChange)   displayChange.textContent   = `₱${change.toFixed(2)}`;
    if (totalDueText)    totalDueText.textContent    = `Total Due: ₱${due.toFixed(2)}`;
  }

  // ======= Update cart HTML (AJAX path) =======
  function updateCart(html) {
    const cartSection = document.getElementById('cartSection') || document.querySelector('.cart-section');
    if (cartSection) cartSection.innerHTML = html;

    attachCartButtons();
    attachQuickAddButtons();
    attachCancelOrder();
    syncPaymentModalTotals();

    // After render, a general scan (de-dup prevents duplicates)
    (window.queueMicrotask ? queueMicrotask : fn => setTimeout(fn, 0))(() => checkCartExpiration());
  }

  // ======= Buttons: quick add, qty +/-, remove =======
  function attachQuickAddButtons() {
    document.querySelectorAll('.quick-add-btn').forEach(btn => {
      btn.onclick = () => {
        const type = btn.dataset.type;

        // Instant expiry toast on each click (from button dataset)
        if (type === 'product') {
          const expStr = btn.dataset.expiration || '';
          const name   = btn.dataset.name || btn.textContent.trim() || 'Product';
          maybeToastExpiry(expStr, name);
        }

        const payload = { action: (type === 'product' ? 'add_product' : 'add_service'),
                          qty: parseInt(btn.dataset.qty || '1') };
        if (type === 'product') payload.product_id = btn.dataset.id;
        else {
          payload.service_id = btn.dataset.id;
          payload.price = btn.dataset.price;
          payload.name  = btn.dataset.name;
        }

        fetch('ajax_cart.php', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            updateCart(data.cart_html);
            showToast('<i class="fas fa-plus-circle"></i> Added', `${type} added to cart`, 'success');
          } else {
            showToast('<i class="fas fa-times-circle"></i> Error', data.message || 'Failed to add item', 'danger');
          }
        })
        .catch(() => showToast('<i class="fas fa-times-circle"></i> Error', 'Server error', 'danger'));
      };
    });
  }

  function attachCartButtons() {
    document.querySelectorAll('.btn-increase, .btn-decrease, .btn-remove').forEach(btn => {
      btn.onclick = () => {
        const id = btn.dataset.id;
        const type = btn.dataset.type;
        let payload;
        if (btn.classList.contains('btn-increase')) {
          payload = { action:'update_qty', item_type:type, item_id:id, qty: 1 };
        } else if (btn.classList.contains('btn-decrease')) {
          payload = { action:'update_qty', item_type:type, item_id:id, qty:-1 };
        } else {
          payload = { action:'remove_item', item_type:type, item_id:id };
        }
        fetch('ajax_cart.php', {
          method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
        })
        .then(r=>r.json())
        .then(data => {
          if (data.success) {
            updateCart(data.cart_html);
            if (payload.action==='remove_item') showToast('<i class="fas fa-trash-alt"></i> Removed','Item removed from cart','warning');
          } else showToast('<i class="fas fa-times-circle"></i> Error', data.message || 'Failed to update cart', 'danger');
        })
        .catch(() => showToast('<i class="fas fa-times-circle"></i> Error', 'Server error', 'danger'));
      };
    });
  }

  // ======= Cancel order (Bootstrap modal) =======
  function attachCancelOrder() {
    const btn = document.getElementById('cancelOrderBtn');
    if (!btn) return;
    btn.onclick = () => {
      const m = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
      m.show();
    };
  }

  // Confirm button inside the modal
  document.getElementById('confirmCancelBtn')?.addEventListener('click', () => {
    const modalEl = document.getElementById('cancelOrderModal');
    const inst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

    fetch('ajax_cart.php', {
      method: 'POST',
      headers: { 'Content-Type':'application/json' },
      body: JSON.stringify({ action:'cancel_order' })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        updateCart(data.cart_html);
        resetPaymentModal();
        showToast('<i class="fas fa-ban"></i> Canceled','Order has been canceled','success');
      } else {
        showToast('<i class="fas fa-times-circle"></i> Error', data.message || 'Failed to cancel order', 'danger');
      }
      inst.hide();
    })
    .catch(() => {
      showToast('<i class="fas fa-times-circle"></i> Error','Server error','danger');
      inst.hide();
    });
  });

  // ======= Open payment modal -> always sync totals first =======
  document.getElementById('openPaymentBtn')?.addEventListener('click', () => {
    syncPaymentModalTotals();
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
  });

  // ======= Barcode input focus + INSTANT expiry toasts =======
  (function barcodeFocus() {
    const barcodeInput = document.getElementById('barcodeInput');
    function isModalOpen(){ return !!document.querySelector('.modal.show'); }
    function isTypingInInput(){
      const a = document.activeElement;
      return a && (a.tagName === 'INPUT' || a.tagName === 'TEXTAREA' || a.isContentEditable);
    }
    function tryFocusScanner(){
      if (!isModalOpen() && !isTypingInInput()) barcodeInput?.focus();
    }
    window.addEventListener('click', tryFocusScanner);
    window.addEventListener('keydown', tryFocusScanner);

    // Submit barcode on Enter (with INSTANT expiry toast via HTML diff)
    barcodeInput?.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const code = (barcodeInput.value || '').trim();
        if (!code) return;

        // Snapshot counts BEFORE (per product name)
        const beforeSnap = getCartNameCountsFromDOM();

        fetch('pos_add_barcode.php', {
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:'barcode=' + encodeURIComponent(code)
        })
        .then(r=>r.json())
        .then(data => {
          if (data.success) {
            // INSTANT: diff the response HTML (no DOM write yet)
            const afterSnap = getCartNameCountsFromHTML(data.cart_html);

            // Find which product name(s) increased -> toast for those immediately
            afterSnap.counts.forEach((afterCount, cname) => {
              const beforeCount = beforeSnap.counts.get(cname) || 0;
              if (afterCount > beforeCount) {
                const disp = afterSnap.display.get(cname) || 'Product';
                const exp  = afterSnap.expByName.get(cname) || '';
                maybeToastExpiry(exp, disp); // instant expiry toast
              }
            });

            // Now update DOM and wire events
            const cartBox = document.getElementById('cartSection') || document.querySelector('.cart-section');
            if (cartBox) cartBox.innerHTML = data.cart_html;

            attachCartButtons(); attachQuickAddButtons(); attachCancelOrder();
            syncPaymentModalTotals();

            // Post-render general scan (dedup set prevents duplicates)
            (window.queueMicrotask ? queueMicrotask : fn => setTimeout(fn, 0))(() => checkCartExpiration());

            showToast('<i class="fas fa-barcode"></i> Barcode Scan','Product added to cart','success');
          } else {
            showToast('<i class="fas fa-times-circle"></i> Error', data.message || 'Failed to add barcode', 'danger');
          }
          barcodeInput.value = ''; tryFocusScanner();
        })
        .catch(() => showToast('<i class="fas fa-times-circle"></i> Error','Server error during barcode add','danger'));
      }
    });
  })();
<?php if(!empty($errorMessage)): ?>
    showToast(
        '<i class="fas fa-times-circle"></i> Payment Error',
        '<?= addslashes($errorMessage) ?>',
        'danger'
    );
    <?php endif; ?>

  // ======= Initial wire-up =======
  attachCartButtons();
  attachQuickAddButtons();
  attachCancelOrder();
  syncPaymentModalTotals();
  (window.queueMicrotask ? queueMicrotask : fn => setTimeout(fn, 0))(() => checkCartExpiration());
});
</script>

<script src="sidebar.js"></script>

</body>
</html>
