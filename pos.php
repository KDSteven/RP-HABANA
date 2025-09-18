<?php
session_start();
include 'config/db.php';
include 'functions.php';

// --------------------- Authorization ---------------------
if (!isset($_SESSION['role'])) {
    header("Location: index.html");
    exit;
}

// --------------------- INIT ---------------------
$user_id   = (int)($_SESSION['user_id'] ?? 0);
$role      = $_SESSION['role'];
$branch_id = (int)($_SESSION['branch_id'] ?? 0);
$search    = trim($_GET['search'] ?? '');
$errorMessage = '';
$showReceiptModal = false;
$lastSaleId = null;

// --------------------- Pending ---------------------
$pending = 0;
if ($role === 'admin') {
    $res = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'");
    if ($res) {
        $r = $res->fetch_assoc();
        $pending = (int)($r['pending'] ?? 0);
    }
}

// --------------------- Initialize / normalize cart ---------------------
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// --------------------- Helper functions ---------------------
function addToCart($product_id, $qty = 1, $type = 'product', $extra = []) {
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['type'] === $type && $item[$type.'_id'] == $product_id) {
            $item['qty'] += $qty;
            return;
        }
    }
    unset($item);
    $newItem = array_merge([
        'type' => $type,
        $type.'_id' => $product_id,
        'qty' => $qty
    ], $extra);
    $_SESSION['cart'][] = $newItem;
}

function finalPrice($price, $markup) {
    return $price + ($price * ($markup / 100));
}

// --------------------- Handle POST ---------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------- Barcode Scan --------
    if (!empty($_POST['scan_barcode'])) {
        $barcode = preg_replace('/\s+/', '', trim($_POST['scan_barcode']));
        $stmt = $conn->prepare("
            SELECT p.product_id, p.product_name, p.price, p.markup_price, IFNULL(i.stock,0) AS stock
            FROM products p
            JOIN inventory i ON p.product_id = i.product_id
            WHERE p.barcode = ? AND i.branch_id = ? LIMIT 1
        ");
        $stmt->bind_param("si", $barcode, $branch_id);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$prod) $errorMessage = "No product found for barcode: {$barcode}.";
        elseif ((int)$prod['stock'] <= 0) $errorMessage = "{$prod['product_name']} is out of stock.";
        else addToCart($prod['product_id'], 1);

        header("Location: pos.php");
        exit;
    }

    // -------- Increase / Decrease --------
    if (isset($_POST['increase']) || isset($_POST['decrease'])) {
        $pid = (int)($_POST['product_id'] ?? 0);
        foreach ($_SESSION['cart'] as $k => &$item) {
            if ($item['type'] === 'product' && $item['product_id'] === $pid) {
                if (isset($_POST['increase'])) $item['qty']++;
                else {
                    $item['qty']--;
                    if ($item['qty'] <= 0) array_splice($_SESSION['cart'], $k, 1);
                }
                break;
            }
        }
        unset($item);
        header("Location: pos.php");
        exit;
    }

    // -------- Add product from quick add --------
    if (isset($_POST['add_to_cart'])) {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        if ($pid > 0) addToCart($pid, $qty);
        header("Location: pos.php");
        exit;
    }

    // -------- Add service --------
    if (isset($_POST['add_service'])) {
        $service_id = (int)($_POST['service_id'] ?? 0);
        $stmt = $conn->prepare("SELECT service_name, price FROM services WHERE service_id = ?");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $service = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($service) addToCart($service_id, 1, 'service', ['name'=>$service['service_name'],'price'=>$service['price']]);
        header("Location: pos.php");
        exit;
    }

    // -------- Remove item --------
    if (isset($_POST['remove'])) {
        $rid = $_POST['remove_id'] ?? '';
        $type = $_POST['item_type'] ?? '';
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function($item) use($rid,$type){
            return !($item['type']===$type && $item[$type.'_id']==$rid);
        }));
        header("Location: pos.php");
        exit;
    }

    // -------- Cancel order --------
    if (isset($_POST['cancel_order'])) {
        $_SESSION['cart'] = [];
        header("Location: pos.php");
        exit;
    }

    // -------- Checkout --------
    if (isset($_POST['checkout'])) {
        $payment = floatval($_POST['payment'] ?? 0);
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            if ($item['type']==='product') {
                $stmt = $conn->prepare("SELECT price, markup_price FROM products WHERE product_id=?");
                $stmt->bind_param("i", $item['product_id']);
                $stmt->execute();
                $prod = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $price = finalPrice($prod['price'], $prod['markup_price']);
            } else $price = (float)$item['price'];
            $total += $price * $item['qty'];
        }

        if (empty($_SESSION['cart'])) $errorMessage="Cart empty!";
        elseif ($payment < $total) $errorMessage="Payment less than total!";
        else {
            $change = $payment-$total;
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO sales (branch_id,total,payment,change_given,processed_by) VALUES (?,?,?,?,?)");
                $stmt->bind_param("idddi",$branch_id,$total,$payment,$change,$user_id);
                $stmt->execute();
                $sale_id = $conn->insert_id;
                $stmt->close();

                foreach ($_SESSION['cart'] as $item) {
                    if ($item['type']==='product') {
                        $stmt = $conn->prepare("SELECT price, markup_price FROM products WHERE product_id=?");
                        $stmt->bind_param("i",$item['product_id']);
                        $stmt->execute();
                        $prod = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                        $price = finalPrice($prod['price'],$prod['markup_price']);
                        $qty = $item['qty'];

                        $update = $conn->prepare("UPDATE inventory SET stock=stock-? WHERE branch_id=? AND product_id=?");
                        $update->bind_param("iii",$qty,$branch_id,$item['product_id']);
                        $update->execute(); $update->close();

                        $insert = $conn->prepare("INSERT INTO sales_items (sale_id,product_id,quantity,price) VALUES (?,?,?,?)");
                        $insert->bind_param("iiid",$sale_id,$item['product_id'],$qty,$price);
                        $insert->execute(); $insert->close();

                        $notes = "Sale via POS (Sale ID: $sale_id)";
                        $mov = $conn->prepare("INSERT INTO inventory_movements (product_id,branch_id,quantity,movement_type,reference_id,user_id,notes) VALUES (?,?,?,?,?,?,?)");
                        $mov->bind_param("iiiisss",$item['product_id'],$branch_id,$qty,$sale,$sale_id,$user_id,$notes);
                        $mov->execute(); $mov->close();
                    } else {
                        $stmt = $conn->prepare("INSERT INTO sales_services (sale_id,service_id,price,quantity) VALUES (?,?,?,?)");
                        $stmt->bind_param("iidi",$sale_id,$item['service_id'],$item['price'],$item['qty']);
                        $stmt->execute(); $stmt->close();
                    }
                }

                $conn->commit();
                $_SESSION['cart'] = [];
                $showReceiptModal = true;
                $lastSaleId = $sale_id;
            } catch(Exception $e) {
                $conn->rollback();
                $errorMessage = "Checkout failed: ".$e->getMessage();
            }
        }
    }

}

// --------------------- Fetch products for quick-add ---------------------
$category_products = [];
$stmt = $conn->prepare("
    SELECT p.product_id,p.product_name,p.price,p.markup_price,i.stock,p.category
    FROM products p
    JOIN inventory i ON p.product_id=i.product_id
    WHERE i.branch_id=? AND i.stock>0
    ORDER BY p.category,p.product_name
");
$stmt->bind_param("i",$branch_id);
$stmt->execute();
$result = $stmt->get_result();
while($row=$result->fetch_assoc()){
    $category_products[$row['category']][] = $row;
}
$stmt->close();

// --------------------- Fetch services ---------------------
$services = [];
$res = $conn->query("SELECT * FROM services");
while($s=$res->fetch_assoc()) $services[]=$s;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<?php $pageTitle = 'POS'; ?>
<title><?= htmlspecialchars("RP Habana — $pageTitle") ?></title>
<link rel="icon" href="img/R.P.png">

<!-- Bootstrap & FontAwesome -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Custom CSS -->
<link rel="stylesheet" href="css/notifications.css">
<link rel="stylesheet" href="css/pos.css?v=2">
<link rel="stylesheet" href="css/sidebar.css">
<audio id="notifSound" src="img/notif.mp3" preload="auto"></audio>

<style>
/* POS Layout */
.pos-wrapper { display: flex; gap: 20px; padding: 20px; flex-wrap: wrap; }
.cart-section { flex: 2; min-width: 400px; }
.controls-section { flex: 1; min-width: 300px; }
.qty-btn { padding: 4px 10px; margin: 0 4px; background:#f7931e; color:white; border:none; border-radius:4px; cursor:pointer; transition:0.2s; }
.qty-btn:hover { background:#e67e00; }
.remove-btn { background:#dc3545; color:white; border:none; padding:4px 10px; border-radius:4px; cursor:pointer; }
.remove-btn:hover { background:#b02a37; }
.quick-btn-form button { min-width: 100px; text-align:center; white-space:normal; }
.table-wrapper { max-height: 400px; overflow-y:auto; }
.search-bar input { width: 100%; }
.cart-box h3 { margin-bottom: 15px; }
.controls-section h3 { margin-bottom: 15px; }
.toast-container { z-index: 1055; }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <h2><?= strtoupper($role) ?>
    <span class="notif-wrapper" style="float:right">
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
    <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
  <?php endif; ?>
  <?php if ($role === 'admin'): ?>
    <a href="accounts.php"><i class="fas fa-user"></i> Accounts</a>
    <a href=""><i class="fas fa-archive"></i> Archive</a>
    <a href=""><i class="fas fa-calendar-alt"></i> Logs</a>
  <?php endif; ?>
  <a href="index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- POS Wrapper -->
<div class="pos-wrapper">

  <!-- CART / TRANSACTION -->
  <div class="cart-section">
    <div class="cart-box card shadow-sm p-3">
      <h3>🛒 Current Transaction</h3>

      <?php if (empty($_SESSION['cart'])): ?>
        <p class="text-muted">Your cart is empty.</p>
      <?php else: ?>
        <div class="table-wrapper">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Item</th>
                <th style="width:110px">Qty</th>
                <th style="width:110px">Price</th>
                <th style="width:140px">Subtotal</th>
                <th style="width:110px"></th>
              </tr>
            </thead>
            <tbody>
              <?php
                $total = 0.0;
                foreach ($_SESSION['cart'] as $item):
                    if ($item['type'] === 'product') {
                        $product_stmt = $conn->prepare("SELECT product_name, price, markup_price FROM products WHERE product_id = ?");
                        $product_stmt->bind_param("i", $item['product_id']);
                        $product_stmt->execute();
                        $product_data = $product_stmt->get_result()->fetch_assoc();
                        $product_stmt->close();
                        $price = (float)$product_data['price'] * (1 + ((float)$product_data['markup_price'] / 100));
                        $name = $product_data['product_name'];
                    } else {
                        $price = (float)$item['price'];
                        $name = $item['name'];
                    }
                    $qty = (int)($item['qty'] ?? 1);
                    $subtotal = $price * $qty;
                    $total += $subtotal;
              ?>
                <tr>
                  <td><?= htmlspecialchars($name) ?></td>
                  <td style="vertical-align:middle">
  <div class="qty-control">
    <form method="POST">
      <input type="hidden" name="product_id" value="<?= $item['type'] === 'product' ? (int)$item['product_id'] : '' ?>">
      <button type="submit" name="decrease" class="qty-btn">-</button>
    </form>
    <span class="qty-display"><?= $qty ?></span>
    <form method="POST">
      <input type="hidden" name="product_id" value="<?= $item['type'] === 'product' ? (int)$item['product_id'] : '' ?>">
      <button type="submit" name="increase" class="qty-btn">+</button>
    </form>
  </div>
</td>

                  <td><?= number_format($price, 2) ?></td>
                  <td><?= number_format($subtotal, 2) ?></td>
                  <td>
                    <form method="POST">
                      <input type="hidden" name="remove_id" value="<?= $item['type'] === 'product' ? (int)$item['product_id'] : (int)$item['service_id'] ?>">
                      <input type="hidden" name="item_type" value="<?= $item['type'] ?>">
                      <button type="submit" name="remove" class="remove-btn">Remove</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="2" class="text-end">Total:</th>
                <th colspan="3">₱<?= number_format($total, 2) ?></th>
              </tr>
            </tfoot>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- CONTROLS / PAYMENT -->
  <div class="controls-section">
    <h3>Cashier Controls</h3>

    <!-- SEARCH BAR -->
    <div class="search-bar mb-3">
      <form method="GET" action="pos.php" class="d-flex gap-2">
        <input autofocus type="text" name="search" placeholder="Scan or search product..." 
               value="<?= htmlspecialchars($search) ?>" class="form-control">
        <button class="btn btn-secondary" type="submit">Search</button>
      </form>
      <?php if ($search !== '' && !$exact_result): ?>
        <p class="mt-1 mb-0"><strong>Search results for:</strong> "<?= htmlspecialchars($search) ?>"</p>
      <?php endif; ?>
      <form id="barcodeForm" method="POST" style="display:none;">
        <input type="text" name="scan_barcode" id="scanBarcode" autocomplete="off" />
      </form>
    </div>

    <!-- QUICK-ADD PRODUCTS -->
    <div class="quick-add-products mb-3">
      <?php foreach ($category_products as $cat => $products): ?>
        <h5><?= htmlspecialchars($cat) ?></h5>
        <div class="d-flex flex-wrap gap-2 mb-2">
          <?php foreach ($products as $p): 
              $price = $p['price'] * (1 + $p['markup_price']/100);
          ?>
            <form method="POST" class="quick-btn-form">
              <input type="hidden" name="add_to_cart" value="1">
              <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
              <input type="hidden" name="quantity" value="1">
              <button type="submit" class="btn btn-outline-primary">
                <?= htmlspecialchars($p['product_name']) ?><br>₱<?= number_format($price, 2) ?>
              </button>
            </form>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- SERVICES -->
    <form method="POST" class="mb-3">
      <label for="service_id"><strong>Add Service</strong></label>
      <select name="service_id" id="service_id" class="form-select" required>
        <option value="">Select a service</option>
        <?php
          $services_result = $conn->query("SELECT * FROM services");
          while ($service = $services_result->fetch_assoc()):
        ?>
          <option value="<?= (int)$service['service_id'] ?>">
            <?= htmlspecialchars($service['service_name']) ?> - ₱<?= number_format($service['price'], 2) ?>
          </option>
        <?php endwhile; ?>
      </select>
      <button class="btn btn-outline-primary mt-2 w-100" type="submit" name="add_service">Add Service</button>
    </form>

    <!-- PAYMENT -->
    <form id="checkoutForm" method="POST" class="mb-3">
      <label for="payment"><strong>Payment (₱)</strong></label>
      <input id="payment" name="payment" type="number" step="0.01" min="0" class="form-control" required>
      <div class="mt-2 d-grid gap-2">
        <button type="button" class="btn btn-success" onclick="confirmCheckout()">Checkout</button>
        <input type="hidden" name="checkout" value="1">
      </div>
    </form>

    <!-- CANCEL ORDER -->
    <form method="POST">
      <button class="btn btn-danger w-100" name="cancel_order" type="submit">Cancel Order</button>
    </form>

  </div>
</div>



<!-- Toasts & Modals (unchanged) -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
  <div id="successToast" class="toast text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex"><div class="toast-body">✔ Item added to cart!</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
  </div>
  <div id="errorToast" class="toast text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex"><div class="toast-body">⚠ Item is out of stock!</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
  </div>
</div>

<!-- Error modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-exclamation-circle me-2"></i>Error</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="errorMessage"></div>
      <div class="modal-footer"><button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button></div>
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

<script>
let barcodeBuffer = '';
let lastKeyTime = Date.now();

document.addEventListener('keydown', function(e) {
    const now = Date.now();

    // Reset buffer if delay is too long
    if (now - lastKeyTime > 100) {
        barcodeBuffer = '';
    }

    lastKeyTime = now;

    if (e.key === 'Enter') {
        if (barcodeBuffer.length > 0) {
            // Set value and submit form
            const input = document.getElementById('scanBarcode');
            input.value = barcodeBuffer;
            document.getElementById('barcodeForm').submit();
            barcodeBuffer = '';
        }
        e.preventDefault();
    } else {
        // Append key to buffer
        barcodeBuffer += e.key;
    }
});
</script>
<script>
/* Checkout modal + submit */
function confirmCheckout() {
  const modal = new bootstrap.Modal(document.getElementById('checkoutConfirmModal'));
  modal.show();
}
function submitCheckout() {
  document.getElementById("checkoutForm").submit();
}

/* Receipt print */
function printReceipt() {
  var frame = document.getElementById('receiptFrame').contentWindow;
  frame.focus();
  frame.print();
}

/* show error modal if PHP set it */
document.addEventListener('DOMContentLoaded', function() {
  <?php if (!empty($errorMessage)): ?>
    document.getElementById('errorMessage').innerText = "<?= addslashes($errorMessage) ?>";
    new bootstrap.Modal(document.getElementById('errorModal')).show();
  <?php endif; ?>

  <?php if (!empty($showReceiptModal) && !empty($lastSaleId)): ?>
    document.getElementById('receiptFrame').src = "receipt.php?sale_id=<?= $lastSaleId ?>";
    new bootstrap.Modal(document.getElementById('receiptModal')).show();
  <?php endif; ?>
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
