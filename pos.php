<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: dashboard.php");
    exit;
}

// Initialize cart session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Sanitize and cast branch_id
$branch_id = isset($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : 0;

// Handle search and category filter inputs
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Fetch distinct categories for filter dropdown
$categories_result = $conn->query("SELECT DISTINCT category FROM products");

// Prepare base query for products available for the staff's branch
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

// Handle Add to Cart
if (isset($_POST['add'])) {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity < 1) {
        $quantity = 1;
    }

    // Check available stock
    $stock_stmt = $conn->prepare("SELECT stock FROM inventory WHERE product_id = ? AND branch_id = ?");
    $stock_stmt->bind_param("ii", $product_id, $branch_id);
    $stock_stmt->execute();
    $stock_result = $stock_stmt->get_result();
    $stock_row = $stock_result->fetch_assoc();
    $available_stock = $stock_row ? (int)$stock_row['stock'] : 0;

    // Calculate current quantity in cart
    $current_quantity = 0;
    foreach ($_SESSION['cart'] as $item) {
        if ($item['product_id'] === $product_id) {
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
            if ($item['product_id'] === $product_id) {
                $item['stock'] += $quantity;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = ['product_id' => $product_id, 'stock' => $quantity];
        }
    }

    header("Location: pos.php?search=" . urlencode($search) . "&category=" . urlencode($category_filter));
    exit;
}

// Handle Remove from Cart
if (isset($_POST['remove'])) {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function ($item) use ($product_id) {
        return $item['product_id'] !== $product_id;
    }));
    header("Location: pos.php?search=" . urlencode($search) . "&category=" . urlencode($category_filter));
    exit;
}
// Handle Cancel Entire Order
if (isset($_POST['cancel_order'])) {
    $_SESSION['cart'] = [];
    header("Location: pos.php");
    exit;
}

//payment
$payment = isset($_POST['payment']) ? floatval($_POST['payment']) : 0;

// Calculate total first (again, outside try block temporarily)
$total_check = 0;
foreach ($_SESSION['cart'] as $item) {
    $product_id = (int)$item['product_id'];
    $quantity = (int)$item['stock'];

    $price_stmt = $conn->prepare("SELECT price, markup_price FROM products WHERE product_id = ?");
    
    $price_stmt->bind_param("i", $product_id);
    $price_stmt->execute();
    $price_result = $price_stmt->get_result();
    $product = $price_result->fetch_assoc();

    if (!$product) continue;

    $full_price = $product['price'] + $product['markup_price'];
    $subtotal = $full_price * $quantity;
    $total_check += $subtotal;
}

// Handle Checkout
if (isset($_POST['checkout'])) {
    if ($payment < $total_check) {
        echo "<script>alert('Payment is less than total.'); window.location='pos.php';</script>";
        exit;
    }

    $change = $payment - $total_check;

    $conn->begin_transaction();
    try {
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $product_id = (int)$item['product_id'];
            $quantity = (int)$item['stock'];

            // Get product price
            $price_stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
            $price_stmt->bind_param("i", $product_id);
            $price_stmt->execute();
            $price_result = $price_stmt->get_result();
            $product = $price_result->fetch_assoc();
            if (!$product) {
                throw new Exception("Product not found.");
            }
            $price = $product['price'] + $product['markup_price'];
            $subtotal = $price * $quantity;
            $total += $subtotal;

            // Update inventory stock only if enough stock is available
            $update_stmt = $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE product_id = ? AND branch_id = ? AND stock >= ?");
            $update_stmt->bind_param("iiii", $quantity, $product_id, $branch_id, $quantity);
            $update_stmt->execute();

            if ($update_stmt->affected_rows === 0) {
                throw new Exception("Insufficient stock for product ID $product_id.");
            }
        
}
$change = $payment - $total_check;
if ($change < 0) $change = 0; // prevent negative change

        // Insert into sales table
       $staff_id = $_SESSION['user_id'] ?? null; // or $_SESSION['id'] depending on your login system

        $sale_stmt = $conn->prepare("INSERT INTO sales (branch_id, total, payment, change_given, processed_by) VALUES (?, ?, ?, ?, ?)");
        $sale_stmt->bind_param("idddi", $branch_id, $total_check, $payment, $change, $staff_id);

        $sale_stmt->execute();
        $sale_id = $conn->insert_id;

        // Insert into sales_items table
        $item_stmt = $conn->prepare("INSERT INTO sales_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($_SESSION['cart'] as $item) {
            $product_id = (int)$item['product_id'];
            $quantity = (int)$item['stock'];

            // Get product price again (or reuse previous)
            $price_stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
            $price_stmt->bind_param("i", $product_id);
            $price_stmt->execute();
            $price_result = $price_stmt->get_result();
            $product = $price_result->fetch_assoc();
            $price = $product['price'];

            $item_stmt->bind_param("iiid", $sale_id, $product_id, $quantity, $price);
            $item_stmt->execute();
        }

        $conn->commit();
        $_SESSION['cart'] = [];
        header("Location: receipt.php?sale_id=$sale_id");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "Checkout failed: " . htmlspecialchars($e->getMessage());
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Point of Sale - Staff</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <style>
     * {
      margin: 0; padding: 0; box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      display: flex;
      height: 100vh;
      background: #f5f5f5;
      color: #333;
    }

    .sidebar {
      width: 220px;
      background-color: #f7931e;
      padding: 30px 15px;
      color: white;
    }

    .sidebar h2 {
      margin-bottom: 30px;
      font-size: 22px;
      text-align: center;
    }

    .sidebar a {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: white;
      padding: 12px 15px;
      margin: 6px 0;
      border-radius: 8px;
      transition: background 0.2s;
    }

    .sidebar a:hover, .sidebar a.active {
      background-color: #e67e00;
    }

    .sidebar a i {
      margin-right: 10px;
      font-size: 16px;
    }
    .content {
      flex: 1;
      padding: 40px;
      background: #f5f5f5;
      overflow-y: auto;
    }

    input, select, button {
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #aaa;
      border-radius: 5px;
    }

    button {
      background-color: #f7931e;
      color: white;
      font-weight: bold;
      border: none;
      cursor: pointer;
    }

    button:hover {
      background-color: #e67e00;
    }

    .search-bar {
      margin-bottom: 20px;
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .search-bar input[type="text"] {
      flex-grow: 1;
      padding: 10px;
      font-size: 1rem;
    }

    .category-filter {
      padding: 10px;
      font-size: 1rem;
      border: 1px solid #aaa;
      border-radius: 5px;
      background: white;
      color: black;
    }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill,minmax(180px,1fr));
      gap: 15px;
    }

    .product-card {
      background: white;
      border-radius: 8px;
      padding: 15px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .product-name {
      font-weight: bold;
      margin-bottom: 10px;
      font-size: 1.1rem;
    }

    .product-price {
      color: #f7931e;
      font-weight: bold;
      margin-bottom: 10px;
    }

    .product-stock {
      font-size: 0.9rem;
      margin-bottom: 10px;
      color: #555;
    }

    .add-to-cart-form {
      display: flex;
      gap: 5px;
      align-items: center;
    }

    .add-to-cart-form input[type="number"] {
      width: 60px;
      padding: 5px;
      font-size: 1rem;
      border: 1px solid #aaa;
      border-radius: 5px;
    }

    .add-to-cart-form button {
      flex-grow: 1;
      padding: 8px;
      font-weight: bold;
      background-color: #f7931e;
      border: none;
      color: white;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .add-to-cart-form button:hover {
      background-color: #e67e00;
    }

    .cart-box {
      background: white;
      padding: 20px;
      margin-top: 20px;
      border-radius: 5px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    th, td {
      border: 1px solid #ccc;
      padding: 10px;
      text-align: left;
    }

    th {
      background-color: #f7931e;
      color: white;
    }

    .remove-btn {
      background-color: #e74c3c;
      padding: 5px 10px;
      border: none;
      color: white;
      border-radius: 3px;
      cursor: pointer;
    }

    .remove-btn:hover {
      background-color: #c0392b;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>STAFF</h2>
    <a href="dashboard.php" class=""><i class="fas fa-tv"></i> Dashboard</a>
    <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
    <a href="pos.php" class="active"><i class="fas fa-cash-register"></i> Point of Sale</a>
    <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <a href="index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="content">
    <h2>Point of Sale</h2>

    <div class="search-bar">
      <form method="GET" action="pos.php" style="flex-grow:1; display:flex; gap:10px;">
        <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>" />
        <select name="category" class="category-filter" onchange="this.form.submit()">
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

    <div class="products-grid">
      <?php while ($row = $products_result->fetch_assoc()): ?>
        <div class="product-card">
          <div class="product-name"><?= htmlspecialchars($row['product_name']) ?></div>
          <div class="product-price">₱<?= number_format($row['price'] + $row['markup_price'], 2) ?> <small style="color:gray">(Orig: ₱<?= number_format($row['price'], 2) ?>)</small></div>
          <div class="product-stock">Stock: <?= (int)$row['stock'] ?></div>
          <form class="add-to-cart-form" method="POST" action="pos.php?search=<?= urlencode($search) ?>&category=<?= urlencode($category_filter) ?>">
            <input type="hidden" name="product_id" value="<?= (int)$row['product_id'] ?>" />
            <input type="number" name="stock" min="1" max="<?= (int)$row['stock'] ?>" value="1" required />
            <button type="submit" name="add">Add to Cart</button>
          </form>
        </div>
      <?php endwhile; ?>
    </div>

   <!-- Cart Display -->
<div class="cart-box">
  
  <h3>Cart</h3>
  <?php if (empty($_SESSION['cart'])): ?>
    <p>Your cart is empty.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Product Name</th>
          <th>Quantity</th>
          <th>Price (₱)</th>
          <th>Subtotal (₱)</th>
          <th>Action</th>
          <form method="POST" style="margin-top: 15px;">
    

     </form>
        </tr>
        
      </thead>
      <tbody>
        <?php
        $total = 0;
        foreach ($_SESSION['cart'] as $item):
            $product_id = (int)$item['product_id'];
            $quantity = (int)$item['stock'];

            // Fetch both price and markup_price
            $product_stmt = $conn->prepare("SELECT product_name, price, markup_price FROM products WHERE product_id = ?");
            $product_stmt->bind_param("i", $product_id);
            $product_stmt->execute();
            $product_result = $product_stmt->get_result();
            $product = $product_result->fetch_assoc();

            if (!$product) continue;

            // Calculate full price and subtotal
            $full_price = $product['price'] + $product['markup_price'];
            $subtotal = $full_price * $quantity;
            $total += $subtotal;
        ?>
        <tr>
          <td><?= htmlspecialchars($product['product_name']) ?></td>
          <td><?= $quantity ?></td>
          <td><?= number_format($full_price, 2) ?></td>
          <td><?= number_format($subtotal, 2) ?></td>
          <td>
            <form method="POST" style="margin:0;">
              <input type="hidden" name="product_id" value="<?= $product_id ?>" />
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
    <form method="POST" style="margin-top: 10px;">
      
    <form method="POST" action="pos.php" style="margin-top: 15px;">

  <label for="payment"><strong>Enter Payment (₱):</strong></label>
  <input type="number" name="payment" id="payment" step="0.01" min="0" required style="margin-left:10px; padding:5px; width:120px;">
  <br><br>
  <button type="submit" name="checkout">Checkout</button>
</form>



  <button type="submit" name="cancel_order" style="background:red;color:white;">Cancel Entire Order</button>
</form>

  <?php endif; ?>
</div>
  </div>
</body>
</html>
