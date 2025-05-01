<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: index.html");
    exit;
}

if (!isset($_GET['sale_id'])) {
    echo "Sale ID is required.";
    exit;
}

$sale_id = (int)$_GET['sale_id'];

// Fetch sale info
$sale_stmt = $conn->prepare("SELECT sale_id, sale_date, total FROM sales WHERE sale_id = ?");
$sale_stmt->bind_param("i", $sale_id);
$sale_stmt->execute();
$sale_result = $sale_stmt->get_result();
$sale = $sale_result->fetch_assoc();

if (!$sale) {
    echo "Sale not found.";
    exit;
}

// Fetch sale items
$items_stmt = $conn->prepare("
    SELECT si.quantity, si.price, p.product_name 
    FROM sales_items si 
    JOIN products p ON si.product_id = p.product_id 
    WHERE si.sale_id = ?
");
$items_stmt->bind_param("i", $sale_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Receipt - Sale #<?= $sale_id ?></title>
  <style>
    body {
      font-family: Arial, sans-serif;
      max-width: 600px;
      margin: 20px auto;
      padding: 20px;
      border: 1px solid #ccc;
    }
    h1 {
      text-align: center;
      color: #f7931e;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 8px;
      text-align: left;
    }
    th {
      background-color: #f7931e;
      color: white;
    }
    .total {
      text-align: right;
      font-weight: bold;
      margin-top: 20px;
    }
    .print-btn {
      margin-top: 20px;
      display: block;
      width: 100%;
      padding: 10px;
      background-color: #f7931e;
      color: white;
      border: none;
      cursor: pointer;
      font-size: 16px;
      border-radius: 5px;
    }
    .print-btn:hover {
      background-color: #e67e00;
    }
  </style>
</head>
<body>
  <h1>Receipt - Sale #<?= $sale_id ?></h1>
  <p><strong>Date:</strong> <?= htmlspecialchars($sale['sale_date']) ?></p>
  <table>
    <thead>
      <tr>
        <th>Product Name</th>
        <th>Quantity</th>
        <th>Price (₱)</th>
        <th>Subtotal (₱)</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($item = $items_result->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($item['product_name']) ?></td>
        <td><?= (int)$item['quantity'] ?></td>
        <td><?= number_format($item['price'], 2) ?></td>
        <td><?= number_format($item['price'] * $item['quantity'], 2) ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <p class="total">Total: ₱<?= number_format($sale['total'], 2) ?></p>
  <button class="print-btn" onclick="window.print()">Print Receipt</button>
  <p><a href="pos.php">Back to POS</a></p>
</body>
</html>
