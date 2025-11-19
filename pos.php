<?php
session_start();
include 'config/db.php';
include 'functions.php';

if (!isset($_SESSION['role'])) {
    header("Location: index.html");
    exit;
}



$user_id   = (int)($_SESSION['user_id'] ?? 0);
$role      = $_SESSION['role'];
$branch_id = (int)($_SESSION['branch_id'] ?? 0);
$search    = trim($_GET['search'] ?? '');
$errorMessage = '';
$showReceiptModal = false;
$lastSaleId = null;

$activeShift = get_active_shift($conn, $user_id, $branch_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shift_action'])) {
    try {
        if ($_POST['shift_action'] === 'start') {
            $opening = (float)($_POST['opening_cash'] ?? 0);
            $note    = trim($_POST['opening_note'] ?? '');
            $sid = start_shift($conn, $user_id, $branch_id, $opening, $note);
            // refresh state
            $activeShift = get_active_shift($conn, $user_id, $branch_id);
            $_SESSION['toast'] = ['type'=>'success','msg'=>'Shift started.'];
            header("Location: pos.php"); exit;
        }
        if ($_POST['shift_action'] === 'end' && $activeShift) {
            $closing = (float)($_POST['closing_cash'] ?? 0);
            $note    = trim($_POST['closing_note'] ?? '');
            $r = end_shift($conn, (int)$activeShift['shift_id'], $closing, $note);
            $_SESSION['toast'] = [
              'type'=>'success',
              'msg'=>'Shift closed. Diff: '.number_format($r['difference'],2).' (Expected: ₱'.number_format($r['expected'],2).')'
            ];
            header("Location: pos.php"); exit;
        }
    } catch (Exception $e) {
        $_SESSION['toast'] = ['type'=>'danger','msg'=>$e->getMessage()];
        header("Location: pos.php"); exit;
    }
}

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
    // require an open shift
    $active = get_active_shift($conn, $user_id, $branch_id);
    if (!$active) {
        throw new Exception("You must Start Shift before processing sales.");
    }
    $shift_id = (int)$active['shift_id'];


    // ---------- 1. CALCULATE SUBTOTAL ----------
  $subtotal = 0.0;
$totalVat = 0.0;

foreach ($_SESSION['cart'] as $i => $item) {
    if ($item['type'] === 'product') {
        $stmt = $conn->prepare("SELECT price, markup_price, vat FROM products WHERE product_id=?");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $price  = finalPrice($prod['price'] ?? 0, $prod['markup_price'] ?? 0);
        $vatRate = (float)($prod['vat'] ?? 0);
    } else {
        $price  = (float)$item['price'];
        $vatRate = (float)($item['vat'] ?? 0);
    }

    $qty          = (int)$item['qty'];
    $lineSubtotal = $price * $qty;
    $lineVat      = $lineSubtotal * ($vatRate / 100);

    // write back without references
    $_SESSION['cart'][$i]['calculated_price'] = $price;
    $_SESSION['cart'][$i]['calculated_vat']   = $lineVat;

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
        (branch_id, shift_id, total, discount, discount_type, vat, payment, change_given, processed_by, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')
      ");
        $stmt->bind_param(
          "iiddsdddi",
          $branch_id, $shift_id, $subtotal, $discount_value, $discount_type, $totalVat, $payment, $change, $user_id
        );
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

    if ($discount < 100 || $discount > 500) {
    $errorMessage = "Discount must be between ₱100 and ₱500.";
}


    try {
        $result = checkoutCart($conn, $user_id, $branch_id, $payment, $discount, $discount_type);
        // Redirect to self with lastSale to show receipt (PRG pattern)
        header("Location: pos.php?lastSale={$result['sale_id']}&print=1");
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

$sql = "
    SELECT p.product_id, p.product_name, p.price, p.markup_price, 
           i.stock, p.category, p.expiration_date, p.barcode
    FROM products p
    JOIN inventory i ON p.product_id = i.product_id
    WHERE i.branch_id = ? 
      AND i.stock > 0
";

$params = [$branch_id];
$types  = "i";

// If there is search input, add filtering
if (!empty($search)) {
    $sql .= " 
        AND (
            p.product_name LIKE ? OR
            p.barcode LIKE ? OR
            p.category LIKE ? OR
            p.product_id = ?
        )
    ";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = (int)$search;
    $types .= "sssi";
}

$sql .= " ORDER BY p.category, p.product_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
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
        <a href="shift_summary.php" class="<?= $self === 'shift_summary.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-clipboard-check"></i> Shift Summary</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
  </div>
</div>
<!-- POS WRAPPER -->
<div class="pos-wrapper">

    <!-- TOP BAR: SHIFT + SEARCH -->
    <div class="pos-topbar">

        <!-- SHIFT INFO -->
        <div class="shift-info">
            <?php if ($activeShift): ?>
                <strong>Shift:</strong> #<?= (int)$activeShift['shift_id'] ?> |
                <strong>Opened:</strong> <?= htmlspecialchars($activeShift['start_time']) ?> |
                <strong>Opening Cash:</strong> ₱<?= number_format((float)$activeShift['opening_cash'],2) ?>
            <?php else: ?>
                <span class="text-danger fw-bold">NO ACTIVE SHIFT</span>
            <?php endif; ?>
        </div>

        <!-- SHIFT ACTIONS -->
        <div class="shift-actions">
            <?php if ($activeShift): ?>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#payInOutModal">Petty Cash</button>
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#endShiftModal">End Shift</button>
            <?php else: ?>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#startShiftModal">Start Shift</button>
            <?php endif; ?>
        </div>

        <!-- SEARCH BOX -->
<div class="card mb-0" style="flex:1;">
  <form method="GET" class="d-flex gap-2">
    <div class="input-group">
      <span class="input-group-text">
        <i class="fas fa-search"></i>
      </span>
      <input type="text"
             name="search"
             placeholder="Scan or search product..."
             class="form-control"
             value="<?= htmlspecialchars($search ?? '', ENT_QUOTES) ?>">
    </div>
    <button class="btn btn-secondary" type="submit">
      <i class="fas fa-search"></i>
    </button>
  </form>
</div>


        <!-- BARCODE SCAN INPUT (HIDDEN) -->
        <input type="text" id="barcodeInput" autocomplete="off" autofocus
               style="opacity:0; position:absolute; left:-9999px;">
    </div>


    <!-- MAIN GRID LAYOUT -->
    <div class="pos-body">

        <!-- LEFT: CART AREA (NEVER SCROLLS) -->
        <div class="pos-cart" id="cartSection">
    <?php include "pos_cart_partial.php"; ?>
</div>

        <!-- RIGHT: PRODUCT BUTTONS (SCROLL ONLY HERE) -->
        <div class="pos-products <?= !$activeShift ? 'pe-none opacity-50' : '' ?>">

            <?php foreach ($category_products as $cat => $products): ?>
                <div class="product-section">
                    <h4><?= htmlspecialchars($cat) ?></h4>
                    <div class="product-grid">

                        <?php foreach ($products as $p): ?>
                            <button class="product-btn quick-add-btn"
                                data-type="product"
                                data-id="<?= (int)$p['product_id'] ?>"
                                data-qty="1"
                                data-expiration="<?= htmlspecialchars($p['expiration_date'] ?? '', ENT_QUOTES) ?>"
                                data-name="<?= htmlspecialchars($p['product_name'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars($p['product_name']) ?>
                            </button>
                        <?php endforeach; ?>

                    </div>
                </div>
            <?php endforeach; ?>


            <!-- SERVICES -->
            <div class="product-section">
                <h4>Services</h4>
                <div class="product-grid">

                    <?php foreach ($services as $s): ?>
                        <button class="product-btn service quick-add-btn"
                            data-type="service"
                            data-id="<?= (int)$s['service_id'] ?>"
                            data-price="<?= htmlspecialchars($s['price'], ENT_QUOTES) ?>"
                            data-qty="1"
                            data-name="<?= htmlspecialchars($s['service_name'], ENT_QUOTES) ?>">
                            <?= $s['service_name'] ?><br>
                            ₱<?= number_format($s['price'], 2) ?>
                        </button>
                    <?php endforeach; ?>

                </div>
            </div>

        </div>
    </div>


    <!-- FIXED PAYMENT BAR (ALWAYS VISIBLE) -->
    <div class="pos-payment-bar">
        <button id="openPaymentBtn" class="pay-btn">
            <i class="fas fa-money-bill-wave"></i> Pay
        </button>
        <button id="cancelOrderBtn" class="cancel-btn">
            <i class="fas fa-times"></i> Cancel
        </button>
    </div>

</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Enter Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST" id="paymentForm">
      <div class="modal-body payment-layout" id="paymentModalBody"
          data-subtotal="<?= number_format($cartSubtotal, 2, '.', '') ?>"
          data-vat="<?= number_format($totalVat, 2, '.', '') ?>"
          data-grandtotal="<?= number_format($cartGrandTotal, 2, '.', '') ?>">

          <!-- LEFT SIDE -->
          <div class="payment-left">

              <h4 class="fw-bold mb-3" id="totalDueText">
                  Total Due: ₱<?= number_format($cartGrandTotal, 2) ?>
              </h4>

              <!-- Discount -->
              <div class="d-flex gap-2">
                <input type="number" step="0.01" min="0" max="500" name="discount" 
                       id="discountInput" class="form-control" placeholder="Discount">
                <select name="discount_type" id="discountType" class="form-select" style="max-width:110px;">
                  <option value="amount">₱</option>
                  <option value="percent">%</option>
                </select>
              </div>

              <!-- Quick Cash -->
              <div class="d-flex flex-wrap gap-2 mt-2">
                <?php foreach ([50,100,200,500,1000] as $cash): ?>
                  <button type="button" 
                          class="btn btn-outline-secondary quick-cash" 
                          data-value="<?= $cash ?>">₱<?= $cash ?></button>
                <?php endforeach; ?>
              </div>

              <!-- Payment -->
              <input type="number" step="0.01" min="0"
                     name="payment" id="paymentInput" 
                     class="form-control mt-3"
                     placeholder="Enter cash received..." required>

              <!-- Change -->
              <h5 class="mt-2 text-success fw-bold" id="displayChange">₱0.00</h5>

              <!-- Notes -->
              <textarea name="note" id="paymentNote" 
                        class="form-control mt-2" rows="2" 
                        placeholder="Add a note (optional)..."></textarea>

          </div>

          <!-- RIGHT SIDE KEYPAD -->
          <div class="payment-right">

              <button type="button" class="btn num-key" data-value="1">1</button>
              <button type="button" class="btn num-key" data-value="2">2</button>
              <button type="button" class="btn num-key" data-value="3">3</button>

              <button type="button" class="btn num-key" data-value="4">4</button>
              <button type="button" class="btn num-key" data-value="5">5</button>
              <button type="button" class="btn num-key" data-value="6">6</button>

              <button type="button" class="btn num-key" data-value="7">7</button>
              <button type="button" class="btn num-key" data-value="8">8</button>
              <button type="button" class="btn num-key" data-value="9">9</button>

              <button type="button" class="btn num-key" data-value="0">0</button>
              <button type="button" class="btn btn-danger num-key" data-value="clear">C</button>
              <button type="button" class="btn btn-warning num-key" data-value="back">⌫</button>

          </div>

      </div>

      <div class="modal-footer">
        <button type="submit" name="checkout" id="checkout"
                class="btn btn-success w-100">
          Confirm Payment
        </button>
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

<!-- Change Due Modal -->
<div class="modal fade" id="changeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white py-2">
        <h6 class="modal-title mb-0"><i class="fas fa-money-bill-wave"></i> Change Due</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <div class="display-6 fw-bold" id="changeAmountText">₱0.00</div>
        <div class="text-muted small mt-2">Please hand the change to the customer.</div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">OK</button>
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

<!-- Start Shift -->
<div class="modal fade" id="startShiftModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <form method="post" class="modal-content">
      <input type="hidden" name="shift_action" value="start">
      <div class="modal-header bg-success text-white"><h5 class="m-0">Start Shift</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">Opening Cash (₱)</label>
        <input type="number" name="opening_cash" step="0.01" min="0" class="form-control" required>
        <label class="form-label mt-2">Note (optional)</label>
        <input type="text" name="opening_note" class="form-control">
      </div>
      <div class="modal-footer">
        <button class="btn btn-success w-100" type="submit">Start</button>
      </div>
    </form>
  </div>
</div>

<!-- End Shift -->
<div class="modal fade" id="endShiftModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <form method="post" class="modal-content">
      <input type="hidden" name="shift_action" value="end">
      <div class="modal-header bg-danger text-white"><h5 class="m-0">End Shift</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">Counted Cash (₱)</label>
        <input type="number" name="closing_cash" step="0.01" min="0" class="form-control" required>
        <label class="form-label mt-2">Note (optional)</label>
        <input type="text" name="closing_note" class="form-control">
        <div class="small text-muted mt-2">
          System will compare this to expected cash and save the difference.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger w-100" type="submit">Close Shift</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="payInOutModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <form method="post" class="modal-content" action="shift_cash_move.php">
      <div class="modal-header bg-secondary text-white"><h5 class="m-0">Petty Cash</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <select class="form-select" name="move_type" required>
          <option value="pay_in">Pay In (+)</option>
          <option value="pay_out">Pay Out (−)</option>
        </select>
        <input type="number" step="0.01" min="0" name="amount" class="form-control mt-2" placeholder="Amount" required>
        <input type="text" name="reason" class="form-control mt-2" placeholder="Reason (optional)">
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary w-100" type="submit">Record</button>
      </div>
    </form>
  </div>
</div>


<!-- Toasts -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="notifications.js"></script>
<script>
  // ---- GLOBAL TOAST CONTROL ----
// GLOBAL TOAST DEDUP MEMORY
const shownGlobalToasts = new Set();

function toastKey(title, message) {
    return (title + "|" + message).toLowerCase().trim();
}

function safeToast(title, message, type = 'primary', delay = 3000) {
    const key = toastKey(title, message);

    // Prevent duplicates
    if (shownGlobalToasts.has(key)) return;
    shownGlobalToasts.add(key);

    // Call the REAL toast function
    window._showRealToast(title, message, type, delay);
}
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {

    const paymentInput = document.getElementById("paymentInput");

    function writeToPaymentInput(val) {
        if (!paymentInput) return;

        if (val === "clear") {
            paymentInput.value = "";
        } 
        else if (val === "back") {
            paymentInput.value = paymentInput.value.slice(0, -1);
        } 
        else {
            paymentInput.value += val;
        }

        updatePaymentComputed();
        paymentInput.focus();
    }

    // Attach keypad events
    document.querySelectorAll(".num-key").forEach(btn => {
        btn.addEventListener("click", () => {
            writeToPaymentInput(btn.dataset.value);
        });
    });

});
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
  const params   = new URLSearchParams(window.location.search);
  const lastSale = params.get('lastSale');
  const doPrint  = params.get('print') === '1';
  const changeDueParam = params.get('chg');
  const changeDue = changeDueParam !== null ? parseFloat(changeDueParam) : NaN;

  function showChangePopup(amount) {
    // Prefer modal; fallback to toast if amount invalid.
    if (!isNaN(amount)) {
      const el = document.getElementById('changeAmountText');
      if (el) el.textContent = `₱${amount.toFixed(2)}`;
      const cm = new bootstrap.Modal(document.getElementById('changeModal'));
      cm.show();
    } else {
      // fallback toast
      if (window.safeToast) {
        safeToast('<i class="fas fa-money-bill-wave"></i> Change Due',
                  'Please hand the change to the customer.',
                  'success', 5000);
      }
    }
  }

  if (lastSale) {
    const frame   = document.getElementById('receiptFrame');
    const modalEl = document.getElementById('receiptModal');

    if (frame) {
      const src = 'receipt.php?sale_id=' + encodeURIComponent(lastSale) + (doPrint ? '&autoprint=1' : '');
      frame.src = src;

      if (doPrint) {
        frame.addEventListener('load', () => {
          try {
            setTimeout(() => {
              // Open print dialog from inside the iframe.
              frame.contentWindow?.focus();
              frame.contentWindow?.print();

              // Show change AFTER printing completes (best-effort)
              const afterPrint = () => {
                try {
                  // Hide the receipt modal first
                  const inst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                  inst.hide();
                } catch {}
                // Then show change popup
                showChangePopup(changeDue);
                // remove listener to avoid double firing
                frame.contentWindow?.removeEventListener('afterprint', afterPrint);
              };

              // Some browsers fire 'afterprint'; add a timeout fallback
              frame.contentWindow?.addEventListener('afterprint', afterPrint);
              setTimeout(() => {
                // Fallback in case 'afterprint' doesn’t fire
                afterPrint();
              }, 1500);
            }, 250);
          } catch (e) {
            console.warn('Auto-print failed:', e);
            // If printing blocked, still show change & keep modal open
            showChangePopup(changeDue);
          }
        });
      }
    }

    if (modalEl) {
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    }

    // Clean URL so refresh won’t retrigger print or popup
    if (history.replaceState) {
      params.delete('lastSale');
      params.delete('print');
      params.delete('chg');
      const newQS = params.toString();
      history.replaceState({}, document.title, window.location.pathname + (newQS ? '?' + newQS : ''));
    }
  }
});


// Print button handler (manual reprint)
function printReceipt() {
  const frame = document.getElementById('receiptFrame');
  if (frame && frame.contentWindow) frame.contentWindow.print();
}
</script>

<script>
// Number Pad: 0-9, C, back
document.querySelectorAll('.num-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = document.getElementById('paymentInput');
    let val = btn.dataset.value;

    if (val === 'clear') input.value = '';
    else if (val === 'back') input.value = input.value.slice(0, -1);
    else input.value += val;

    updatePaymentComputed();
    input.focus();
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // ======= Toast helper =======
  window._showRealToast = function(title, message, type='primary', delay=3000) {
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
      safeToast('<i class="fas fa-skull-crossbones"></i> Expired Product',
                `"${name || 'Product'}" has already expired!`, 'danger');
      shownExpiryToasts.add(key);
    } else if (diffDays <= NEAR_EXPIRY_DAYS) {
      safeToast('<i class="fas fa-exclamation-triangle"></i> Near Expiration',
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
        safeToast('<i class="fas fa-skull-crossbones"></i> Expired Product',
                  `"${productName}" has already expired!`, 'danger');
        shownExpiryToasts.add(key);
      } else if (diffDays <= NEAR_EXPIRY_DAYS) {
        safeToast('<i class="fas fa-exclamation-triangle"></i> Near Expiration',
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

  // --- QUICK CASH buttons (₱50, ₱100, …) ---
document.querySelectorAll('.quick-cash').forEach(btn => {
  btn.addEventListener('click', () => {
    const inc = parseFloat(btn.dataset.value || '0');
    const input = document.getElementById('paymentInput');

    // add to current amount (common POS behavior)
    const current = parseFloat(input.value || '0');
    input.value = ( (isNaN(current) ? 0 : current) + inc ).toFixed(2);

    updatePaymentComputed();
    input.focus();
  });
});

  // ======= Update cart HTML (AJAX path) =======
  function updateCart(html) {
    const cartSection = document.getElementById('cartSection');
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
  document.querySelectorAll('.quick-add-btn, .product-btn').forEach(btn => {

    btn.onclick = () => {

      const type = btn.dataset.type;
      const payload = {
        action: (type === 'product' ? 'add_product' : 'add_service'),
        qty: parseInt(btn.dataset.qty || '1')
      };

      if (type === 'product') {
        payload.product_id = btn.dataset.id;
        payload.expiration = btn.dataset.expiration || "";
        payload.name = btn.dataset.name || "";
      } else {
        payload.service_id = btn.dataset.id;
        payload.price = btn.dataset.price;
        payload.name = btn.dataset.name;
      }

      fetch('ajax_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          updateCart(data.cart_html);
          safeToast('<i class="fas fa-check-circle"></i> Added', `${payload.name} added to cart`, 'success');
        } else {
          safeToast('Error', data.message || 'Failed to add item', 'danger');
        }
      });
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
            if (payload.action==='remove_item') safeToast('<i class="fas fa-trash-alt"></i> Removed','Item removed from cart','warning');
          } else safeToast('<i class="fas fa-times-circle"></i> Error', data.message || 'Failed to update cart', 'danger');
        })
        .catch(() => safeToast('<i class="fas fa-times-circle"></i> Error', 'Server error', 'danger'));
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
        resetToastMemory();
        safeToast('<i class="fas fa-ban"></i> Canceled','Order has been canceled','success');
      } else {
        safeToast('<i class="fas fa-times-circle"></i> Error', data.message || 'Failed to cancel order', 'danger');
      }
      inst.hide();
    })
    .catch(() => {
      safeToast('<i class="fas fa-times-circle"></i> Error','Server error','danger');
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

            safeToast('<i class="fas fa-barcode"></i> Barcode Scan','Product added to cart','success');
          } else {
            safeToast('<i class="fas fa-times-circle"></i> Error', data.message || 'Failed to add barcode', 'danger');
          }
          barcodeInput.value = ''; tryFocusScanner();
        })
        .catch(() => safeToast('<i class="fas fa-times-circle"></i> Error','Server error during barcode add','danger'));
      }
    });
  })();
<?php if(!empty($errorMessage)): ?>
    safeToast(
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
const paymentInput = document.getElementById('paymentInput');

paymentInput.addEventListener('keydown', function(e) {
  // Block minus key (both main keyboard and numpad)
  if (e.key === '-' || e.key === 'Subtract') {
    e.preventDefault();
  }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  <?php if (!$activeShift): ?>
    const m = new bootstrap.Modal(document.getElementById('startShiftModal'));
    m.show();
  <?php endif; ?>
});

document.getElementById("discountInput").addEventListener("input", function () {
    let val = parseFloat(this.value);

    // If empty, do nothing so user can continue typing
    if (this.value === "") return;

    // If value is outside allowed range, clear it
    if (val < 0 || val > 500) {
        this.value = "";
    }

    updatePaymentComputed();
});
</script>


<script src="sidebar.js"></script>

</body>
</html>
