<?php
session_start();
include 'config/db.php';
include 'functions.php';

// Check login
if (!isset($_SESSION['role'])) {
    header("Location: index.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$branch_id = $_SESSION['branch_id'] ?? null;

$pending = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pending = $row['pending'] ?? 0;
    }
}


$pending = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pending = $row['pending'] ?? 0;
    }
}

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Search & category filter
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$categories_result = $conn->query("SELECT DISTINCT category FROM products");

// Fetch products
$query = "
  SELECT inventory.stock, products.product_id, products.product_name, products.price, products.markup_price, products.category
  FROM inventory 
  JOIN products ON inventory.product_id = products.product_id 
  WHERE inventory.branch_id = ?
";
$params = [$branch_id];
$types = "i";

if ($search) {
    $query .= " AND (products.product_name LIKE ? 
                     OR products.category LIKE ? 
                     OR products.barcode = ?)";
    $search_param = "%$search%";
    $params[] = $search_param;   // for product_name
    $params[] = $search_param;   // for category
    $params[] = $search;         // exact barcode match
    $types .= "sss";
}


if ($category_filter) {
    $query .= " AND products.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$products_result = $stmt->get_result();

// Initialize error and receipt variables
$errorMessage = '';
$showReceiptModal = false;
$lastSaleId = null;

// =================== Scan Barcode -> Auto Add ===================
if (!empty($_POST['scan_barcode'])) {
    // Normalize: trim and remove inner spaces often read by phone apps
    $raw = $_POST['scan_barcode'];
    $barcode = preg_replace('/\s+/', '', trim($raw));  // keep leading zeros by staying string

    // Find the product for this branch with available stock
    $scanStmt = $conn->prepare("
        SELECT p.product_id, p.product_name, IFNULL(i.stock,0) AS stock
        FROM products p
        JOIN inventory i ON i.product_id = p.product_id
        WHERE p.barcode = ? AND i.branch_id = ?
        LIMIT 1
    ");
    $scanStmt->bind_param("si", $barcode, $branch_id);
    $scanStmt->execute();
    $prod = $scanStmt->get_result()->fetch_assoc();
    $scanStmt->close();

    if (!$prod) {
        $errorMessage = "No product found for barcode: {$barcode}.";
    } elseif ((int)$prod['stock'] <= 0) {
        $errorMessage = "{$prod['product_name']} is out of stock.";
    } else {
        // Add 1 to cart (reuse your cart structure)
        $product_id = (int)$prod['product_id'];
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['type'] === 'product' && $item['product_id'] === $product_id) {
                $item['stock'] += 1;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = [
                'type' => 'product',
                'product_id' => $product_id,
                'stock' => 1
            ];
        }
        logAction($conn, "Barcode Scan", "Scanned {$barcode} -> {$prod['product_name']}", null, $branch_id);
    }

    // Redirect clears the scan box and preserves current filters
    header("Location: pos.php?search=" . urlencode($search) . "&category=" . urlencode($category_filter));
    exit;
}

// =================== Add Product to Cart ===================
if (isset($_POST['add'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['stock'] ?? 1));

    $stock_stmt = $conn->prepare("SELECT stock, product_name FROM inventory JOIN products USING(product_id) WHERE product_id = ? AND branch_id = ?");
    $stock_stmt->bind_param("ii", $product_id, $branch_id);
    $stock_stmt->execute();
    $stock_row = $stock_stmt->get_result()->fetch_assoc();
    $available_stock = $stock_row['stock'] ?? 0;
    $product_name = $stock_row['product_name'] ?? 'Unknown';

    if ($available_stock <= 0 || $quantity > $available_stock) {
        $errorMessage = "Cannot add $product_name. Out of stock.";
    } else {
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['type'] === 'product' && $item['product_id'] === $product_id) {
                $item['stock'] += $quantity;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = [
                'type' => 'product',
                'product_id' => $product_id,
                'stock' => $quantity
            ];
        }
        logAction($conn, "Add Product to Cart", "Added $quantity of $product_name to cart", null, $branch_id);
    }
    header("Location: pos.php?search=" . urlencode($search) . "&category=" . urlencode($category_filter));
    exit;
}

// =================== Add Service ===================
if (isset($_POST['add_service'])) {
    $service_id = (int)($_POST['service_id'] ?? 0);

    $service_stmt = $conn->prepare("SELECT service_name, price FROM services WHERE service_id = ?");
    $service_stmt->bind_param("i", $service_id);
    $service_stmt->execute();
    $service = $service_stmt->get_result()->fetch_assoc();
    $service_stmt->close();

    if ($service) {
        $_SESSION['cart'][] = [
            'type' => 'service',
            'service_id' => $service_id,
            'name' => $service['service_name'],
            'price' => $service['price'],
            'stock' => 1
        ];
        logAction($conn, "Add Service to Cart", "Added 1 service: {$service['service_name']} (ID: $service_id) to cart", null, $branch_id);
    }
    header("Location: pos.php");
    exit;
}

// =================== Remove Item ===================
if (isset($_POST['remove'])) {
    $remove_id = $_POST['remove_id'];
    $item_type = $_POST['item_type'];
    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function ($item) use ($remove_id, $item_type) {
        if ($item_type === 'product' && $item['type'] === 'product') return $item['product_id'] != $remove_id;
        if ($item_type === 'service' && $item['type'] === 'service') return $item['service_id'] != $remove_id;
        return true;
    }));
    header("Location: pos.php");
    exit;
}

// =================== Cancel Order ===================
if (isset($_POST['cancel_order'])) {
    $_SESSION['cart'] = [];
    header("Location: pos.php");
    exit;
}

// =================== Calculate Total ===================
$total_check = 0;
foreach ($_SESSION['cart'] as $item) {
    if ($item['type'] === 'product') {
        $product_stmt = $conn->prepare("SELECT price, markup_price FROM products WHERE product_id = ?");
        $product_stmt->bind_param("i", $item['product_id']);
        $product_stmt->execute();
        $product = $product_stmt->get_result()->fetch_assoc();
        $price = $product['price'] * (1 + $product['markup_price'] / 100);
        $total_check += $price * $item['stock'];
    } else {
        $total_check += $item['price'] * $item['stock'];
    }
}

// =================== Checkout ===================
if (isset($_POST['checkout'])) {
    $payment = floatval($_POST['payment'] ?? 0);

    if (empty($_SESSION['cart'])) {
        $errorMessage = "Your cart is empty. Please add items before checking out.";
    } elseif ($payment < $total_check) {
        $errorMessage = "Payment is less than total. Please check the amount.";
    } else {
        $change = $payment - $total_check;
        $conn->begin_transaction();
        try {
            $staff_id = $_SESSION['user_id'] ?? null;

            // Step 1: Insert sale
            $sale_stmt = $conn->prepare("INSERT INTO sales (branch_id, total, payment, change_given, processed_by) VALUES (?, ?, ?, ?, ?)");
            $sale_stmt->bind_param("idddi", $branch_id, $total_check, $payment, $change, $staff_id);
            $sale_stmt->execute();
            $sale_id = $conn->insert_id;

            // Step 2: Insert sale items/services & update stock
            foreach ($_SESSION['cart'] as $item) {
                if ($item['type'] === 'product') {
                    $price_stmt = $conn->prepare("SELECT price, markup_price FROM products WHERE product_id = ?");
                    $price_stmt->bind_param("i", $item['product_id']);
                    $price_stmt->execute();
                    $product = $price_stmt->get_result()->fetch_assoc();
                    $price = $product['price'] * (1 + $product['markup_price'] / 100);

                    $update_stock = $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE product_id = ? AND branch_id = ?");
                    $update_stock->bind_param("iii", $item['stock'], $item['product_id'], $branch_id);
                    $update_stock->execute();

                    $item_stmt = $conn->prepare("INSERT INTO sales_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $item_stmt->bind_param("iiid", $sale_id, $item['product_id'], $item['stock'], $price);
                    $item_stmt->execute();
                } else {
                    $service_stmt = $conn->prepare("INSERT INTO sales_services (sale_id, service_id, price) VALUES (?, ?, ?)");
                    $service_stmt->bind_param("iid", $sale_id, $item['service_id'], $item['price']);
                    $service_stmt->execute();
                }
            }

            $conn->commit();
            $_SESSION['cart'] = [];
            $showReceiptModal = true;
            $lastSaleId = $sale_id;

        } catch (Exception $e) {
            $conn->rollback();
            $errorMessage = "Checkout failed: " . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Point of Sale - Staff</title>
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" >
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/notifications.css">
  <link rel="stylesheet" href="css/pos.css?>v2">
  <link rel="stylesheet" href="css/sidebar.css">
<audio id="notifSound" src="img/notif.mp3" preload="auto"></audio>
</head>

<body>
 <div class="sidebar"> 
 
    <h2>
    <?= strtoupper($role) ?>
    <span class="notif-wrapper">
        <i class="fas fa-bell" id="notifBell"></i>
        <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>>0</span>
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
<div class="pos-wrapper">
    <!-- ✅ Products Section -->
    <div class="products-section">
        <h2>Point of Sale</h2>

        <!-- ✅ Search & Filter -->
        <div class="search-bar">
            <form method="GET" action="pos.php" style="flex-grow:1; display:flex; gap:10px;">
                <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>" />
                <select name="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php while ($cat = $categories_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $cat['category'] === $category_filter ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit">Filter</button>
            </form>
           <!-- Hidden scan form (stays invisible) -->
<form id="scanForm" method="POST" action="pos.php">
  <input type="hidden" name="scan_barcode" id="scan_barcode">
  <input type="hidden" name="from_scan" value="1">
</form>
<div id="lastScan" style="font-size:12px;color:#666;"></div>

<script>
(() => {
  const MAX_GAP_MS       = 40;   // time between scan keystrokes
  const SILENCE_FINAL_MS = 120;  // finalize if scanner doesn't send Enter
  const MIN_LEN          = 3;    // <-- your barcodes are 3 digits

  const form   = document.getElementById('scanForm');
  const hidden = document.getElementById('scan_barcode');
  const lastUI = document.getElementById('lastScan');

  let buf = '';
  let lastTs = 0;
  let timer = null;

  function reset(){ buf=''; lastTs=0; if(timer){clearTimeout(timer); timer=null;} }
  function finalize(){
    if (buf.length < MIN_LEN) return reset();
    const code = buf.replace(/\s+/g,'');
    hidden.value = code;
    if (lastUI) lastUI.textContent = 'Scanned: ' + code;
    form.submit();
    reset();
  }
  function schedule(){ if(timer) clearTimeout(timer); timer=setTimeout(finalize, SILENCE_FINAL_MS); }

  document.addEventListener('keydown', (e) => {
    if (e.ctrlKey || e.metaKey || e.altKey || e.isComposing) return;

    const now = Date.now();
    if (now - lastTs > MAX_GAP_MS) buf = '';
    lastTs = now;

    if (e.key === 'Enter') { if (buf.length >= MIN_LEN) finalize(); else reset(); return; }
    if (e.key && e.key.length === 1) { buf += e.key; schedule(); }
  });
})();
</script>

        </div>

        <!-- ✅ Products Grid -->
        <h3>Products</h3>
        <div class="products-grid">
            <?php while ($row = $products_result->fetch_assoc()):   
                $available_stock = $row['stock'];
             // Subtract quantities from cart
            foreach ($_SESSION['cart'] as $item) {
            if ($item['type'] === 'product' && $item['product_id'] == $row['product_id']) {
                $available_stock -= $item['stock'];} 
            }
            if ($available_stock < 0) {
                $available_stock = 0;
                      }?>
                
                <div class="product-card">
    <div class="product-name"><?= htmlspecialchars($row['product_name']) ?></div>
    <div class="product-price">₱<?=$row['price'] + ($row['price'] * ($row['markup_price'] / 100))
 ?></div>
    <small style="color:gray;">Orig: ₱<?= number_format($row['price'], 2) ?></small>
    <div class="product-stock">Stock: <?= (int)$available_stock ?></div>
    <form class="add-to-cart-form" method="POST" action="pos.php?search=<?= urlencode($search) ?>&category=<?= urlencode($category_filter) ?>">
        <input type="hidden" name="product_id" value="<?= (int)$row['product_id'] ?>" />
        <input type="number" name="stock" min="1" max="<?= (int)$available_stock ?>" value="1" required <?= $available_stock == 0 ? 'disabled' : '' ?> />
        <?php if ($available_stock == 0): ?>
    <button type="button" class="btn btn-secondary" onclick="showOutOfStockToast()">Out of Stock</button>
<?php else: ?>
    <button type="submit" name="add">Add to Cart</button>
<?php endif; ?>

    </form>
</div>

            <?php endwhile; ?>
        </div>
    </div>

    <!-- ✅ Cart Section -->
   <!-- ✅ Cart Section -->
<div class="cart-section">
    <div class="cart-box">
        <h3>Cart</h3>

        <?php if (empty($_SESSION['cart'])): ?>
            <p>Your cart is empty.</p>
        <?php else: ?>
            <div class="table-wrapper"><!-- NEW WRAPPER -->
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total = 0;
                        foreach ($_SESSION['cart'] as $item):
                            if ($item['type'] === 'product') {
                                $product_stmt = $conn->prepare("SELECT product_name, price, markup_price FROM products WHERE product_id = ?");
                                $product_stmt->bind_param("i", $item['product_id']);
                                $product_stmt->execute();
                                $product_data = $product_stmt->get_result()->fetch_assoc();
                                $price = $product_data['price'] + ($product_data['price'] * ($product_data['markup_price'] / 100));
                                $name = $product_data['product_name'];
                            } else {
                                $price = $item['price'];
                                $name = $item['name'];
                            }
                            $subtotal = $price * $item['stock'];
                            $total += $subtotal;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($name) ?></td>
                            <td><?= $item['stock'] ?></td>
                            <td><?= number_format($price, 2) ?></td>
                            <td><?= number_format($subtotal, 2) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="remove_id" value="<?= $item['type'] === 'product' ? $item['product_id'] : $item['service_id'] ?>">
                                    <input type="hidden" name="item_type" value="<?= $item['type'] ?>">
                                    <button type="submit" name="remove" class="remove-btn">Remove</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" style="text-align:right;">Total:</th>
                            <th><?= number_format($total, 2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div><!-- END TABLE WRAPPER -->
        <?php endif; ?>

        <!-- Add Service Dropdown -->
        <form method="POST">
            <label for="service_id"><strong>Add Service:</strong></label>
            <select name="service_id" id="service_id" required>
                <option value="">Select a service</option>
                <?php
                $services_result = $conn->query("SELECT * FROM services");
                while ($service = $services_result->fetch_assoc()):
                ?>
                    <option value="<?= $service['service_id'] ?>"><?= htmlspecialchars($service['service_name']) ?> - ₱<?= number_format($service['price'], 2) ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="add_service">Add</button>
        </form>

        <!-- Payment and Checkout -->
      <!-- Payment and Checkout -->
<form id="checkoutForm" method="POST">
    <label for="payment"><strong>Payment (₱):</strong></label>
    <input id="payment" type="number" name="payment" step="0.01" min="0" required>
    <!-- Trigger Modal Instead of Submit -->
    <button type="button" class="btn btn-primary" onclick="confirmCheckout()">Checkout</button>
    <input type="hidden" name="checkout" value="1">
</form>


        <form method="POST">
            <button type="submit" name="cancel_order">Cancel Entire Order</button>
        </form>
    </div>
</div>

<!-- ✅ Toast Container (Top Right) -->
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
</div>

<!-- Error Modal Cart-->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fas fa-exclamation-circle me-2"></i>Error</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="errorMessage"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>


<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:15px;">
      <div class="receipt-header d-flex justify-content-between align-items-center" 
           style="background-color: #f7931e; color: white; padding:10px; border-radius:5px;">
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

<!-- Checkout Confirmation Modal -->
<div class="modal fade" id="checkoutConfirmModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title">Confirm Checkout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to proceed with this checkout?</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" onclick="submitCheckout()">Yes, Checkout</button>
      </div>
    </div>
  </div>
</div>

  <script src="notifications.js"></script>
  <!-- error popup-->
  <script>
document.addEventListener('DOMContentLoaded', function() {
    // Show error modal if there is an error
    <?php if (!empty($errorMessage)): ?>
        document.getElementById('errorMessage').innerText = "<?= addslashes($errorMessage) ?>";
        new bootstrap.Modal(document.getElementById('errorModal')).show();
    <?php endif; ?>

    // Show receipt modal if checkout succeeded
    <?php if (!empty($showReceiptModal) && !empty($lastSaleId)): ?>
        document.getElementById('receiptFrame').src = "receipt.php?sale_id=<?= $lastSaleId ?>";
        new bootstrap.Modal(document.getElementById('receiptModal')).show();
    <?php endif; ?>
});

function printReceipt() {
    var frame = document.getElementById('receiptFrame').contentWindow;
    frame.focus();
    frame.print();
}
</script>
<!-- receipt popup -->
<?php if (!empty($showReceiptModal) && !empty($lastSaleId)): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var modal = new bootstrap.Modal(document.getElementById('receiptModal'));
    document.getElementById('receiptFrame').src = "receipt.php?sale_id=<?= $lastSaleId ?>";
    modal.show();
});

function printReceipt() {
    var frame = document.getElementById('receiptFrame').contentWindow;
    frame.focus();
    frame.print();
}
</script>
<?php endif; ?>
<!-- checkout confirmation-->
  <script>
function confirmCheckout() {
    var modal = new bootstrap.Modal(document.getElementById('checkoutConfirmModal'));
    modal.show();
}

function submitCheckout() {
    document.getElementById("checkoutForm").submit();
}
</script>
<script>
const serviceJobData = <?= json_encode($serviceJobData ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

if (serviceJobData.length > 0) {
    const ctx = document.getElementById('serviceJobChart').getContext('2d');
    ctx.canvas.height = serviceJobData.length * 50; // dynamic height

    const labels = serviceJobData.map(item => item.service_name);
    const data = serviceJobData.map(item => item.count);

    const colors = labels.map(() => `hsl(${Math.floor(Math.random() * 360)}, 70%, 60%)`);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Services Sold',
                data: data,
                backgroundColor: colors
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true },
                y: { ticks: { autoSkip: false } }
            }
        }
    });
}

// Show error modal if PHP error exists
<?php if (!empty($errorMessage)): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('errorMessage').innerText = "<?= addslashes($errorMessage) ?>";
    var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
    errorModal.show();
});
<?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



</body>
</html>
