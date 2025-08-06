<?php
session_start();
include 'config/db.php';

// Check login
if (!isset($_SESSION['role'])) {
    header("Location: index.html");
    exit;
}

$role = $_SESSION['role'];
$branch_id = (int)($_SESSION['branch_id'] ?? 0);

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
    $query .= " AND (products.product_name LIKE ? OR products.category LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
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

// ✅ Add Product to Cart
if (isset($_POST['add'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['stock'] ?? 1);
    if ($quantity < 1) $quantity = 1;

    // Check stock
    $stock_stmt = $conn->prepare("SELECT stock FROM inventory WHERE product_id = ? AND branch_id = ?");
    $stock_stmt->bind_param("ii", $product_id, $branch_id);
    $stock_stmt->execute();
    $stock_row = $stock_stmt->get_result()->fetch_assoc();
    $available_stock = $stock_row['stock'] ?? 0;
error_log("Redirecting due to out of stock: $available_stock");

    if ($available_stock <= 0 || $quantity > $available_stock) {
    header("Location: pos.php?error=outofstock&search=" . urlencode($search) . "&category=" . urlencode($category_filter));
    exit;
}


    // Check existing in cart
    $current_quantity = 0;
    foreach ($_SESSION['cart'] as $item) {
        if ($item['type'] === 'product' && $item['product_id'] === $product_id) {
            $current_quantity = $item['stock'];
            break;
        }
    }

    if ($quantity + $current_quantity > $available_stock) {
        $quantity = max(0, $available_stock - $current_quantity);
    }

    if ($quantity > 0) {
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
    }

    header("Location: pos.php?added=true&search=" . urlencode($search) . "&category=" . urlencode($category_filter));
exit;

}

// ✅ Add Service
if (isset($_POST['add_service'])) {
    $service_id = (int)($_POST['service_id'] ?? 0);
    $service_stmt = $conn->prepare("SELECT service_name, price FROM services WHERE service_id = ?");
    $service_stmt->bind_param("i", $service_id);
    $service_stmt->execute();
    $service = $service_stmt->get_result()->fetch_assoc();

    if ($service) {
        $_SESSION['cart'][] = [
            'type' => 'service',
            'service_id' => $service_id,
            'name' => $service['service_name'],
            'price' => $service['price'],
            'stock' => 1
        ];
    }
    header("Location: pos.php");
    exit;
}

// ✅ Remove Item
if (isset($_POST['remove'])) {
    $remove_id = $_POST['remove_id'];
    $item_type = $_POST['item_type'];

    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function ($item) use ($remove_id, $item_type) {
        if ($item_type === 'product' && $item['type'] === 'product') {
            return $item['product_id'] != $remove_id;
        }
        if ($item_type === 'service' && $item['type'] === 'service') {
            return $item['service_id'] != $remove_id;
        }
        return true;
    }));

    header("Location: pos.php");
    exit;
}


// ✅ Cancel Order
if (isset($_POST['cancel_order'])) {
    $_SESSION['cart'] = [];
    header("Location: pos.php");
    exit;
}

// ✅ Calculate Total
$total_check = 0;
foreach ($_SESSION['cart'] as $item) {
    if ($item['type'] === 'product') {
        $product_id = (int)$item['product_id'];
        $quantity = (int)$item['stock'];

        $price_stmt = $conn->prepare("SELECT price, markup_price FROM products WHERE product_id = ?");
        $price_stmt->bind_param("i", $product_id);
        $price_stmt->execute();
        $price_result = $price_stmt->get_result();
        $product = $price_result->fetch_assoc();

        if ($product) {
            $full_price = $product['price'] + $product['markup_price'];
            $subtotal = $full_price * $quantity;
            $total_check += $subtotal;
        }
    } elseif ($item['type'] === 'service') {
        $subtotal = $item['price'] * $item['stock']; // stock is 1 for services
        $total_check += $subtotal;
    }
}


$payment = isset($_POST['payment']) ? floatval($_POST['payment']) : 0;

// ✅ Checkout
if (isset($_POST['checkout'])) {
    if ($payment < $total_check) {
        echo "<script>alert('Payment is less than total.'); window.location='pos.php';</script>";
        exit;
    }

    $change = $payment - $total_check;
    $conn->begin_transaction();

    try {
        $staff_id = $_SESSION['user_id'] ?? null;
        $sale_stmt = $conn->prepare("INSERT INTO sales (branch_id, total, payment, change_given, processed_by) VALUES (?, ?, ?, ?, ?)");
        $sale_stmt->bind_param("idddi", $branch_id, $total_check, $payment, $change, $staff_id);
        $sale_stmt->execute();
        $sale_id = $conn->insert_id;

        foreach ($_SESSION['cart'] as $item) {
            if ($item['type'] === 'product') {
                $price_stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
                $price_stmt->bind_param("i", $item['product_id']);
                $price_stmt->execute();
                $product = $price_stmt->get_result()->fetch_assoc();
                $price = $product['price'];
                $conn->query("UPDATE inventory SET stock = stock - {$item['stock']} WHERE product_id = {$item['product_id']} AND branch_id = $branch_id");
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
        header("Location: receipt.php?sale_id=$sale_id");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "Checkout failed: " . htmlspecialchars($e->getMessage());
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Point of Sale - Staff</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <link rel="stylesheet" href="css/notifications.css">
  <link rel="stylesheet" href="css/pos.css">
<audio id="notifSound" src="img/notif.mp3" preload="auto"></audio>

</head>
<body>
 <div class="sidebar"> 
  <h2>
    <?= strtoupper($role) ?>
    
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
    <div class="product-price">₱<?= number_format($row['price'] + $row['markup_price'], 2) ?></div>
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
    <div class="cart-section">
        <div class="cart-box">
            <h3>Cart</h3>

            <?php if (empty($_SESSION['cart'])): ?>
                <p>Your cart is empty.</p>
            <?php else: ?>
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
                                $price = $product_data['price'] + $product_data['markup_price'];
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
            <?php endif; ?>

         <!-- ✅ Add Service Dropdown -->
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

<!-- ✅ Payment and Checkout -->
<form method="POST">
    <label for="payment"><strong>Payment (₱):</strong></label>
    <input id="payment" type="number" name="payment" step="0.01" min="0" required>
    <button type="submit" name="checkout">Checkout</button>
</form>
            <form method="POST">
                <button type="submit" name="cancel_order">Cancel Entire Order</button>
            </form>
        </div>
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

  <script src="notifications.js"></script>
  
  
<script>
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);

    // ✅ Check if item added
    if (params.has('added')) {
        const toast = new bootstrap.Toast(document.getElementById('successToast'), { delay: 2000 });
        toast.show();
        playNotifSound();
    }
    // ✅ Remove query params after showing toast
    if (params.has('added') || params.has('error')) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    function playNotifSound() {
        const audio = document.getElementById('notifSound');
        if (audio) audio.play();
    }
});
</script>
<script>
function showOutOfStockToast() {
    const toast = new bootstrap.Toast(document.getElementById('errorToast'), { delay: 2500 });
    toast.show();
    const audio = document.getElementById('notifSound');
    if (audio) audio.play();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



</body>
</html>
