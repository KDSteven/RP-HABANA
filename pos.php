<?php
session_start();
include 'config/db.php';
include 'functions.php';

if (!isset($_SESSION['role'])) {
    header("Location: index.html");
    exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id   = (int)($_SESSION['user_id'] ?? 0);
$role      = $_SESSION['role'];
$branch_id = (int)($_SESSION['branch_id'] ?? 0);
$search    = trim($_GET['search'] ?? '');
$errorMessage = '';
$showReceiptModal = false;
$lastSaleId = null;
$subtotal = 0.0;
$displaySubtotal = 0.0;
$vat = 0.0;
$grandTotal = 0.0;
$discount_value = 0.0;
$after_discount = 0.0;

$pending = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'")->fetch_assoc()['pending'] ?? 0;

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];


// --------------------- NEAR-EXPIRATION NOTIFICATIONS ---------------------
$today = new DateTime();
$nearingExpirationProducts = [];

foreach ($_SESSION['cart'] as $item) {
    if ($item['type'] !== 'product') continue;

    $stmt = $conn->prepare("SELECT product_name, expiration_date FROM products WHERE product_id=?");
    $stmt->bind_param("i", $item['product_id']);
    $stmt->execute();
    $prod = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!empty($prod['expiration_date'])) {
        $expDate = new DateTime($prod['expiration_date']);
        // If expires within 30 days (can adjust)
        if ($expDate >= $today && $expDate <= (clone $today)->modify('+30 days')) {
            $nearingExpirationProducts[] = $prod['product_name'];
        }
    }
}


// Helper
function finalPrice($price, $markup) {
    return $price + ($price * ($markup / 100));
}

// ---------- Compute subtotal up front so $total is always defined ----------
$total = 0.0;
foreach ($_SESSION['cart'] as $item) {
    if ($item['type'] === 'product') {
        // Prefer to trust session price if present, otherwise fetch
        if (isset($item['price'])) {
            $price = (float)$item['price'];
        } else {
            $stmt = $GLOBALS['conn']->prepare("SELECT price, markup_price FROM products WHERE product_id=?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
            $price = finalPrice($row['price'] ?? 0, $row['markup_price'] ?? 0);
        }
    } else { // service
        $price = (float)($item['price'] ?? 0);
    }
    $total += $price * (int)$item['qty'];
}
// $total is subtotal (before VAT & discount)

// Process lastSale GET to show receipt after PRG
if (isset($_GET['lastSale'])) {
    $lastSaleId = (int)$_GET['lastSale'];
    if ($lastSaleId > 0) $showReceiptModal = true;
}

/* ---------- addToCart ---------- */
function addToCart($id, $qty=1, $type='product', $extra=[]){
    global $conn, $branch_id;
    $qty = max(1, (int)$qty);

    foreach ($_SESSION['cart'] as &$item) {
        if ($item['type'] === $type && $item[$type . '_id'] == $id) {
            if ($type === 'product') {
                $stmt = $conn->prepare("SELECT IFNULL(stock,0) AS stock FROM inventory WHERE product_id=? AND branch_id=?");
                $stmt->bind_param("ii", $id, $branch_id);
                $stmt->execute();
                $r = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $available = (int)($r['stock'] ?? 0);

                if ($item['qty'] + $qty > $available) {
                    $item['qty'] = $available;
                    return false;
                }
            }
            $item['qty'] += $qty;
            return true;
        }
    }
    unset($item);

    if ($type === 'product') {
        $stmt = $conn->prepare("
            SELECT p.price, p.markup_price, IFNULL(i.stock,0) AS stock 
            FROM products p 
            JOIN inventory i ON p.product_id=i.product_id 
            WHERE p.product_id=? AND i.branch_id=? LIMIT 1
        ");
        $stmt->bind_param("ii", $id, $branch_id);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$prod) return false;

        $stock = (int)$prod['stock'];
        if ($stock <= 0) return false;
        if ($qty > $stock) $qty = $stock;

        $computedPrice = finalPrice($prod['price'], $prod['markup_price']);
        $itemData = [
            'type'=>'product',
            'product_id'=>$id,
            'qty'=>$qty,
            'price'=>$computedPrice,
            'stock'=>$stock
        ];
        $_SESSION['cart'][] = array_merge($itemData, $extra);
        return true;
    } else {
        $itemData = [
            'type'=>'service',
            'service_id'=>$id,
            'qty'=>$qty,
            'price'=> (float)($extra['price'] ?? 0),
            'name'=> $extra['name'] ?? 'Service'
        ];
        $_SESSION['cart'][] = array_merge($itemData, $extra);
        return true;
    }
}

// --- Handle POST ---
if ($_SERVER['REQUEST_METHOD']==='POST') {

    // Barcode scan
    if(!empty($_POST['scan_barcode'])){
        $barcode = preg_replace('/\s+/','',trim($_POST['scan_barcode']));
        $stmt = $conn->prepare("
            SELECT p.product_id,p.product_name,p.price,p.markup_price,IFNULL(i.stock,0) AS stock 
            FROM products p 
            JOIN inventory i ON p.product_id=i.product_id 
            WHERE p.barcode=? AND i.branch_id=? LIMIT 1
        ");
        $stmt->bind_param("si",$barcode,$branch_id);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if(!$prod) $errorMessage="No product found for barcode: {$barcode}.";
        elseif((int)$prod['stock']<=0) $errorMessage="{$prod['product_name']} is out of stock.";
        else addToCart($prod['product_id'],1);

        header("Location: pos.php"); exit;
    }

    // Increase
    if (isset($_POST['increase'])) {
        // optional CSRF check can be added here
        $pid = (int)($_POST['product_id'] ?? 0);
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['type'] === 'product' && $item['product_id'] === $pid) {
                $stmt = $conn->prepare("SELECT stock FROM inventory WHERE branch_id=? AND product_id=?");
                $stmt->bind_param("ii", $branch_id, $pid);
                $stmt->execute();
                $stockRow = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($item['qty'] < $stockRow['stock']) {
                    $item['qty']++;
                } else {
                    $errorMessage = "⚠️ Cannot add more. Stock limit reached!";
                }
                break;
            }
        }
        unset($item);
        header("Location: pos.php"); exit;
    }

    // Decrease
    if (isset($_POST['decrease'])) {
        $pid = (int)($_POST['product_id'] ?? 0);
        foreach ($_SESSION['cart'] as $k => &$item) {
            if ($item['type']==='product' && $item['product_id']===$pid) {
                $item['qty']--;
                if ($item['qty'] <= 0) unset($_SESSION['cart'][$k]);
                break;
            }
        }
        unset($item);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        header("Location: pos.php"); exit;
    }

    // Add product quick
    if(isset($_POST['add_to_cart'])){
        $pid = (int)($_POST['product_id']??0);
        $qty = max(1,(int)($_POST['quantity']??1));
        if($pid>0) addToCart($pid,$qty);
        header("Location: pos.php"); exit;
    }

    // Add service
    if(isset($_POST['add_service'])){
        $sid = (int)($_POST['service_id']??0);
        $stmt=$conn->prepare("SELECT service_name,price FROM services WHERE service_id=?");
        $stmt->bind_param("i",$sid); $stmt->execute();
        $srv = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if($srv) addToCart($sid,1,'service',['name'=>$srv['service_name'],'price'=>$srv['price']]);
        header("Location: pos.php"); exit;
    }

    // Remove item
    if(isset($_POST['remove'])){
        $rid = $_POST['remove_id']??'';
        $type= $_POST['item_type']??'';
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], fn($i)=>!($i['type']===$type && $i[$type.'_id']==$rid)));
        header("Location: pos.php"); exit;
    }

    // Cancel order
    if(isset($_POST['cancel_order'])){
        $_SESSION['cart']=[]; header("Location: pos.php"); exit;
    }

    // --------------------- Checkout ---------------------
    if (isset($_POST['checkout'])) {
        // --- CSRF check ---
        if (!isset($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf_token'] ?? '')) {
            die("Invalid CSRF token");
        }

        // --- login & cart check ---
        if (empty($_SESSION['user_id'])) {
            $errorMessage = "You must be logged in to checkout.";
        } elseif (empty($_SESSION['cart'])) {
            $errorMessage = "Cart is empty!";
        } else {
            $user_id   = (int)$_SESSION['user_id'];
            $branch_id = (int)$_SESSION['branch_id'];
            $payment   = (float)($_POST['payment'] ?? 0);
            $discount  = (float)($_POST['discount'] ?? 0); // NEW
            $discount_type = $_POST['discount_type'] ?? 'amount'; // "amount" or "percent"

            // Recalculate total server-side to be safe
            $subtotal = 0.0;
            foreach ($_SESSION['cart'] as $item) {
                if ($item['type'] === 'product') {
                    $stmt = $conn->prepare("SELECT price, markup_price FROM products WHERE product_id=?");
                    $stmt->bind_param("i", $item['product_id']);
                    $stmt->execute();
                    $prod  = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    $price = finalPrice($prod['price'] ?? 0, $prod['markup_price'] ?? 0);
                } else { // service
                    $price = (float)$item['price'];
                }
                $subtotal += $price * (int)$item['qty'];
            }

            // discount calculation
            $discount_value = 0.0;
            if ($discount > 0) {
                if ($discount_type === 'percent') {
                    $discount_value = $subtotal * ($discount / 100.0);
                } else { // amount
                    $discount_value = min($discount, $subtotal);
                }
            }

            $after_discount = $subtotal - $discount_value;
            $vat   = $after_discount * 0.12;
            $grand = $after_discount + $vat;

            if ($payment < $grand) {
                $errorMessage = "Payment is less than total (₱" . number_format($grand, 2) . ")";
            } else {
                $change = $payment - $grand;

                $conn->begin_transaction();
                try {
                    // Insert sale
                    $stmt = $conn->prepare("
                        INSERT INTO sales 
                            (user_id, branch_id, total, discount, discount_type, vat, payment, change_given, processed_by, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')
                    ");
                    // types: ii d d s d d d i  => "iiddsdddi"
                    $stmt->bind_param(
                        "iiddsdddi",
                        $user_id,
                        $branch_id,
                        $subtotal,
                        $discount_value,
                        $discount_type,
                        $vat,
                        $payment,
                        $change,
                        $user_id
                    );
                    $stmt->execute();
                    $sale_id = $conn->insert_id;
                    $stmt->close();

                    // Insert items & atomically decrement stock
                    foreach ($_SESSION['cart'] as $item) {
                        if ($item['type'] === 'product') {
                            $qty  = (int)$item['qty'];
                            $pid  = (int)$item['product_id'];

                            // fetch price fresh
                            $stmt = $conn->prepare("SELECT price, markup_price FROM products WHERE product_id=?");
                            $stmt->bind_param("i", $pid);
                            $stmt->execute();
                            $prod  = $stmt->get_result()->fetch_assoc();
                            $stmt->close();
                            $price = finalPrice($prod['price'] ?? 0, $prod['markup_price'] ?? 0);

                            // Atomic update: only subtract if enough
                            $upd = $conn->prepare("
                                UPDATE inventory 
                                SET stock = stock - ? 
                                WHERE branch_id=? AND product_id=? AND stock >= ?
                            ");
                            $upd->bind_param("iiii", $qty, $branch_id, $pid, $qty);
                            $upd->execute();
                            if ($upd->affected_rows === 0) {
                                $conn->rollback();
                                $errorMessage = "Not enough stock for product ID {$pid}.";
                                throw new Exception($errorMessage);
                            }
                            $upd->close();

                            // insert item
                            $ins = $conn->prepare("
                                INSERT INTO sales_items(sale_id, product_id, quantity, price) 
                                VALUES (?, ?, ?, ?)
                            ");
                            $ins->bind_param("iiid", $sale_id, $pid, $qty, $price);
                            $ins->execute();
                            $ins->close();

                        } else { // service
                            $sid = (int)$item['service_id'];
                            $ins = $conn->prepare("
                                INSERT INTO sales_services(sale_id, service_id, price, quantity) 
                                VALUES (?, ?, ?, ?)
                            ");
                            $ins->bind_param("iidi", $sale_id, $sid, $item['price'], $item['qty']);
                            $ins->execute();
                            $ins->close();
                        }
                    }

                    $conn->commit();

                    // Clear cart and redirect (PRG)
                    $_SESSION['cart'] = [];
                    header("Location: pos.php?lastSale={$sale_id}");
                    exit;

                } catch (Exception $e) {
                    if ($conn->errno) $conn->rollback();
                    $errorMessage = "Checkout failed: " . $e->getMessage();
                }
            }
        }
    }
}

 // --- Quick add products by category
$category_products=[];
$stmt=$conn->prepare("SELECT p.product_id,p.product_name,p.price,p.markup_price,i.stock,p.category
                      FROM products p JOIN inventory i ON p.product_id=i.product_id
                      WHERE i.branch_id=? AND i.stock>0 ORDER BY p.category,p.product_name");
$stmt->bind_param("i",$branch_id); $stmt->execute(); $result=$stmt->get_result();
while($row=$result->fetch_assoc()) $category_products[$row['category']][]=$row;
$stmt->close();

// --- Services
$services=[]; $res=$conn->query("SELECT * FROM services"); while($s=$res->fetch_assoc()) $services[]=$s;

$pendingResetsCount = 0;
if ($role === 'admin') {
  $res = $conn->query("SELECT COUNT(*) AS c FROM password_resets WHERE status='pending'");
  $pendingResetsCount = $res ? (int)$res->fetch_assoc()['c'] : 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>RP Habana — POS</title>
<link rel="icon" href="img/R.P.png">

<!-- Bootstrap & FontAwesome -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/notifications.css">
<!-- Custom CSS -->
<link rel="stylesheet" href="css/pos.css?v=2">
<link rel="stylesheet" href="css/sidebar.css">
<audio id="notifSound" src="img/notif.mp3" preload="auto"></audio>

<style>
/* --- POS Layout --- */
.pos-wrapper { display:flex; flex:1; flex-wrap:wrap; padding:20px; gap:20px; }
.cart-section { flex:2; min-width:350px; }
.controls-section { flex:1; min-width:300px; }
.qty-btn { padding:4px 10px; background:#f7931e; color:white; border:none; border-radius:4px; cursor:pointer; transition:.2s; }
.qty-btn:hover { background:#e67e00; }
.remove-btn { background:#dc3545; color:white; border:none; padding:4px 10px; border-radius:4px; cursor:pointer; }
.remove-btn:hover { background:#b02a37; }
.quick-btn-form button { min-width:100px; text-align:center; white-space:normal; margin-bottom:5px; }
.table-wrapper { max-height:350px; overflow-y:auto; }
@media(max-width:1024px){ .pos-wrapper{flex-direction:column;} }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
 <h2>
    <?= strtoupper($role) ?>
    <span class="notif-wrapper">
        <i class="fas fa-bell" id="notifBell"></i>
        <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>>0</span>
    </span>
</h2>
  <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>
  <?php if($role==='admin'): ?>
    <a href="inventory.php?branch=<?= $branch_id ?>"><i class="fas fa-box"></i> Inventory</a>
    <a href="transfer.php"><i class="fas fa-box"></i> Transfer</a>
    <a href="accounts.php"><i class="fas fa-user"></i> Accounts</a>
  <?php endif; ?>
  <?php if($role==='staff'): ?>
    <a href="pos.php" class="active"><i class="fas fa-cash-register"></i> Point of Sale</a>
    <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
  <?php endif; ?>
  <a href="index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<!-- POS Wrapper -->
<div class="pos-wrapper">

  <!-- Cart Section -->
  <div class="cart-section" id="cartSection">
<?php if (!empty($nearingExpirationProducts)): ?>
    <div class="alert alert-warning mt-2">
        ⚠️ Nearly Expired Products in Cart: 
        <?= htmlspecialchars(implode(", ", $nearingExpirationProducts)) ?>
    </div>
<?php endif; ?>

<?php include 'pos_cart_partial.php'; ?>
</div>
<!-- Controls Section -->
<div class="controls-section">

  <!-- Search -->
  <div class="card mb-2">
    <form method="GET" class="d-flex gap-2">
      <input type="text" name="search" placeholder="🔍 Scan or search product..." 
             class="form-control" value="<?= htmlspecialchars($search) ?>" >
      <button class="btn btn-secondary">Search</button>
    </form>
  </div>

<input type="text" id="barcodeInput" autocomplete="off" autofocus style="opacity:0; position:absolute;">


  <!-- Quick Add Products by Category -->
  <?php foreach($category_products as $cat => $products): ?>
    <div class="card mb-2 p-2">
      <h5><?= htmlspecialchars($cat) ?></h5>
      <div class="quick-btn-form d-flex flex-wrap gap-2">
        <?php foreach($products as $p):
          $price = finalPrice($p['price'],$p['markup_price']);
        ?>
          <button class="btn btn-outline-primary quick-add-btn"
                  data-type="product"
                  data-id="<?= $p['product_id'] ?>"
                  data-qty="1">
            <?= htmlspecialchars($p['product_name']) ?>
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
                data-id="<?= $s['service_id'] ?>"
                data-qty="1">
          <?= htmlspecialchars($s['service_name']) ?><br>₱<?= number_format($s['price'],2) ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Checkout -->
  <div class="card checkout-buttons p-2 d-flex gap-2">
    <button type="button" id="openPaymentBtn" class="btn btn-success">💵 PAYMENT</button>

    <button type="button" class="btn btn-danger" id="cancelOrderBtn">❌ CANCEL</button>
  </div>

</div>


<!-- chekcout modal -->
<!-- Updated Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Enter Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="paymentForm">
        <div class="modal-body text-center">
          <h6 id="totalDueText">Total Due: ₱<?= number_format($total*1.12,2) ?></h6>

          <!-- Discount Input -->
          <div class="d-flex gap-2 mt-2">
            <input type="number" step="0.01" min="0" name="discount" id="discountInput" class="form-control" placeholder="Discount (₱)">
            <select name="discount_type" id="discountType" class="form-select" style="max-width:120px;">
              <option value="amount">₱</option>
              <option value="percent">%</option>
            </select>
          </div>

          <!-- Quick Cash Buttons -->
          <div class="d-flex flex-wrap gap-2 justify-content-center mt-2">
            <?php foreach ([50,100,200,500,1000] as $cash): ?>
              <button type="button" class="btn btn-outline-secondary quick-cash" data-value="<?= $cash ?>">₱<?= $cash ?></button>
            <?php endforeach; ?>
          </div>

          <!-- Payment Input -->
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="number" step="0.01" name="payment" id="paymentInput" class="form-control mt-3" placeholder="Enter cash received..." required>
        </div>
        <div class="modal-footer">
          <button type="submit" name="checkout" class="btn btn-success w-100">Confirm Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toasts (all stacked, top-right) -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
  <!-- Success Toast -->
  <div id="successToast" class="toast text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">✔ Item added to cart!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>

  <!-- Error Toast -->
  <div id="errorToast" class="toast text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">⚠ Item is out of stock!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>

  <!-- Expiration Toast -->
  <div id="expirationToast" class="toast text-bg-warning border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">⚠ This item is nearing expiration!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>


<!-- Receipt modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:15px;">
      <div class="receipt-header d-flex justify-content-between align-items-center" style="background-color: #f7931e; color: white; padding:10px; border-radius:5px;">
        <h5 class="modal-title m-0">🧾 Receipt</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <hr>
      <div class="modal-body p-3" style="background-color:#fff7e6;">
        <iframe id="receiptFrame" src="" style="width:100%; height:400px; border:none; border-radius:10px;"></iframe>
      </div>
      <div class="modal-footer" style="background-color:#fff3cd;">
        <button class="btn btn-outline-warning" onclick="printReceipt()">🖨️ Print</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Checkout confirmation -->
<div class="modal fade" id="checkoutConfirmModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning text-dark"><h5 class="modal-title">Confirm Checkout</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><p>Are you sure you want to proceed with this checkout?</p></div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" onclick="submitCheckout()">Yes, Checkout</button>
      </div>
    </div>
  </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="notifications.js"></script></script>
<script>
  
document.addEventListener('DOMContentLoaded', () => {

   // -----------------------------
// TOAST HELPER (flexible delay)
// -----------------------------
function showToast(title, message, type='primary', delay=4000) {
    // Make sure container exists
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
    }

    // Build toast
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

    // Insert toast at the TOP so it shows above others
    container.prepend(toastEl);

    // Auto-close
    const bsToast = new bootstrap.Toast(toastEl, {delay: delay});
    bsToast.show();

    // Cleanup after hidden
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

// -----------------------------
// NEAR EXPIRATION TOAST
// -----------------------------
function showNearExpiryToast(name, daysLeft, expired=false) {
    if (expired) {
        showToast('Expired Product', `❌ "${name}" has already expired!`, 'danger', 3000);
    } else {
        showToast('Near Expiration', `⚠️ "${name}" is near expiration (${daysLeft} days left)`, 'warning', 3000);
    }
}

// -----------------------------
// CHECK CART EXPIRATION
// -----------------------------
function checkCartExpiration() {
    document.querySelectorAll('tr[data-expiration]').forEach(row => {
        const expStr = row.dataset.expiration;
        if(!expStr) return;

        const expDate = new Date(expStr.replace(/-/g,'/'));
        const today = new Date();
        const diffDays = Math.ceil((expDate - today)/(1000*60*60*24));
        const productName = row.querySelector('td')?.textContent?.trim() || 'Product';

        if (diffDays <= 0) {
            showNearExpiryToast(productName, 0, true);
        } else if (diffDays <= 30) {
            showNearExpiryToast(productName, diffDays);
        }
    });
}


    // -----------------------------
    // CART UPDATE
    // -----------------------------
    function updateCart(html) {
        const cartSection = document.getElementById('cartSection') || document.querySelector('.cart-section');
        if(cartSection) cartSection.innerHTML = html;

        attachCartButtons();
        attachQuickAddButtons();
        attachCancelOrder();
        attachPaymentInputs();
        checkCartExpiration();
    }

    // -----------------------------
    // QUICK ADD BUTTONS
    // -----------------------------
    function attachQuickAddButtons() {
        document.querySelectorAll('.quick-add-btn').forEach(btn => {
            btn.removeEventListener('click', btn._listener);
            btn._listener = () => {
                const type = btn.dataset.type;
                const id = btn.dataset.id;
                const qty = parseInt(btn.dataset.qty) || 1;

                fetch('ajax_cart.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({
                        action: type === 'product' ? 'add_product' : 'add_service',
                        product_id: type === 'product' ? id : undefined,
                        service_id: type === 'service' ? id : undefined,
                        qty: qty
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        updateCart(data.cart_html);
                        checkExpirationToasts();
                        showToast('Added', `${type} added to cart`, 'success');
                    } else {
                        showToast('Error', data.message || 'Failed to add item', 'danger');
                    }
                })
                .catch(err => console.error(err));
            };
            btn.addEventListener('click', btn._listener);
        });
    }
document.getElementById('openPaymentBtn')?.addEventListener('click', () => {
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    paymentModal.show();
});

    // -----------------------------
    // CART BUTTONS (qty/remove)
    // -----------------------------
    function attachCartButtons() {
        document.querySelectorAll('.btn-increase, .btn-decrease, .btn-remove').forEach(btn => {
            btn.removeEventListener('click', btn._listener);
            btn._listener = () => {
                const id = btn.dataset.id;
                const type = btn.dataset.type;
                let action = '';
                if(btn.classList.contains('btn-increase')) action='increase';
                else if(btn.classList.contains('btn-decrease')) action='decrease';
                else action='remove';

                fetch('ajax_cart.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({
                        action: action==='remove' ? 'remove_item' : 'update_qty',
                        item_type: type,
                        item_id: id,
                        qty: action==='increase' ? 1 : action==='decrease' ? -1 : undefined
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        updateCart(data.cart_html);
                        if(action==='remove') showToast('Removed','Item removed from cart','danger');
                    }
                })
                .catch(err => console.error(err));
            };
            btn.addEventListener('click', btn._listener);
        });
    }

    // -----------------------------
    // CANCEL ORDER
    // -----------------------------
    function attachCancelOrder() {
        const cancelBtn = document.getElementById('cancelOrderBtn');
        if(cancelBtn){
            cancelBtn.removeEventListener('click', cancelBtn._listener);
            cancelBtn._listener = () => {
                if(!confirm('Are you sure you want to cancel this order?')) return;

                fetch('ajax_cart.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({action: 'cancel_order'})
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        updateCart(data.cart_html);
                        showToast('Canceled','Order has been canceled','success');
                    } else {
                        showToast('Error','Failed to cancel order','danger');
                    }
                })
                .catch(err => console.error(err));
            };
            cancelBtn.addEventListener('click', cancelBtn._listener);
        }
    }
    // -----------------------------
    // PAYMENT MODAL & DISCOUNT
    // -----------------------------
    function attachPaymentInputs() {
        const paymentInput = document.getElementById('paymentInput');
        const discountInput = document.getElementById('discountInput');
        const discountType = document.getElementById('discountType');
        const totalDueText = document.getElementById('totalDueText');
        const displayDiscount = document.getElementById('displayDiscount');
        const displayPayment = document.getElementById('displayPayment');
        const displayChange = document.getElementById('displayChange');

        function updateTotals() {
            const subtotal = parseFloat(document.querySelector('.subtotal')?.textContent.replace(/[^0-9.-]+/g,""))||0;
            const vat = parseFloat(document.querySelector('.vat')?.textContent.replace(/[^0-9.-]+/g,""))||0;
            let grandTotal = subtotal + vat;

            let discountVal = parseFloat(discountInput?.value)||0;
            if(discountType?.value==='percent') discountVal = subtotal*(discountVal/100);

            const paymentVal = parseFloat(paymentInput?.value)||0;
            const change = Math.max(0, paymentVal-(grandTotal-discountVal));

            if(displayDiscount) displayDiscount.textContent = `₱${discountVal.toFixed(2)}`;
            if(displayPayment) displayPayment.textContent = `₱${paymentVal.toFixed(2)}`;
            if(displayChange) displayChange.textContent = `₱${change.toFixed(2)}`;
            if(totalDueText) totalDueText.textContent = `Total Due: ₱${(grandTotal-discountVal).toFixed(2)}`;
        }

        discountInput?.addEventListener('input', updateTotals);
        discountType?.addEventListener('change', updateTotals);
        paymentInput?.addEventListener('input', updateTotals);

        updateTotals();
    }

    // -----------------------------
    // INITIALIZE EVERYTHING
    // -----------------------------
    attachCartButtons();
    attachQuickAddButtons();
    attachCancelOrder();
    attachPaymentInputs();
    checkCartExpiration();

});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const discountInput = document.getElementById('discountInput');
    const discountType = document.getElementById('discountType');
    const paymentInput = document.getElementById('paymentInput');

    const displayDiscount = document.getElementById('displayDiscount');
    const displayPayment = document.getElementById('displayPayment');
    const displayChange = document.getElementById('displayChange');

    function updatePaymentTotals() {
        // Re-query the subtotal, VAT, grand total from the current DOM
        const subtotalText = document.querySelector('.totals-box h5:nth-child(1) span');
        const vatText = document.querySelector('.totals-box h5:nth-child(2) span');
        const grandTotalText = document.querySelector('.totals-box h4.final-total span');

        let subtotal = parseFloat(subtotalText?.textContent.replace(/[^0-9.-]+/g,"")) || 0;
        let vat = parseFloat(vatText?.textContent.replace(/[^0-9.-]+/g,"")) || 0;

        let discountVal = parseFloat(discountInput.value) || 0;
        if(discountType && discountType.value === 'percent'){
            discountVal = subtotal * (discountVal / 100);
        }

        const newGrandTotal = subtotal + vat - discountVal;
        const paymentVal = parseFloat(paymentInput.value) || 0;
        const change = Math.max(0, paymentVal - newGrandTotal);

        displayDiscount.textContent = `₱${discountVal.toFixed(2)}`;
        displayPayment.textContent = `₱${paymentVal.toFixed(2)}`;
        displayChange.textContent = `₱${change.toFixed(2)}`;
        if(grandTotalText) grandTotalText.textContent = `₱${newGrandTotal.toFixed(2)}`;
    }

    discountInput?.addEventListener('input', updatePaymentTotals);
    discountType?.addEventListener('change', updatePaymentTotals);
    paymentInput?.addEventListener('input', updatePaymentTotals);

    // Initial calculation
    updatePaymentTotals();

    // Optional: call this after every cart update
    window.refreshPaymentTotals = updatePaymentTotals;
});
</script>
   <!-- barcode scanning -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  const barcodeInput = document.getElementById("barcodeInput");

  // keep focus on the hidden input for scanner
  window.addEventListener("click", () => barcodeInput.focus());
  window.addEventListener("keydown", () => barcodeInput.focus());

  barcodeInput.addEventListener("keydown", e => {
    if (e.key === "Enter") {
      e.preventDefault();
      const code = barcodeInput.value.trim();
      if (code !== "") {
        fetch("pos_add_barcode.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "barcode=" + encodeURIComponent(code)
        })
        .then(res => res.json())
        .then(data => {
          console.log("[DEBUG] barcode response:", data);

          if (data.success) {
            // update cart
            const cartBox = document.querySelector(".cart-section");
            if (cartBox) {
              cartBox.innerHTML = data.cart_html;
            }

            // success toast
            if (typeof showToast === "function") {
              showToast("Barcode Scan", "✔ Product added to cart", "success");
            }
          } else {
            // error toast
            if (typeof showToast === "function") {
              showToast("Error", data.message || "Failed to add barcode", "danger");
            }
          }

          barcodeInput.value = "";
          barcodeInput.focus();
        })
        .catch(err => {
          console.error("Barcode error:", err);
          if (typeof showToast === "function") {
            showToast("Error", "Server error during barcode add", "danger");
          }
        });
      }
    }
  });
});
</script>




</body>
</html>
