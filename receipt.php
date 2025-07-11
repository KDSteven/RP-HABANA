<?php
session_start();
include 'config/db.php';

if (!isset($_GET['sale_id'])) {
    die("Sale ID not provided.");
}

$sale_id = (int)$_GET['sale_id'];

// Fetch sale and branch details
$stmt = $conn->prepare("
    SELECT s.sale_id, s.sale_date, s.total, s.payment, s.change_given, 
           b.branch_name, b.branch_location, b.branch_contact, b.branch_email,
           u.username AS staff_name
    FROM sales s
    JOIN branches b ON s.branch_id = b.branch_id
    LEFT JOIN users u ON s.processed_by = u.id
    WHERE s.sale_id = ?
");

$stmt->bind_param("i", $sale_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Sale not found.");
}

$sale = $result->fetch_assoc();

// Fix the table name if it is not `sales_items`
$item_stmt = $conn->prepare("
    SELECT p.product_name, si.quantity, si.price
    FROM sales_items si
    JOIN products p ON si.product_id = p.product_id
    WHERE si.sale_id = ?
");
$item_stmt->bind_param("i", $sale_id);
$item_stmt->execute();
$items_result = $item_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Receipt - Sale #<?= $sale_id ?></title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      max-width: 750px;
      margin: 30px auto;
      padding: 30px;
      background-color: #f9f9f9;
      border-radius: 8px;
      border: 1px solid #ddd;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    h1 {
      text-align: center;
      color: #f7931e;
      margin-bottom: 5px;
    }
    .info, .branch-info {
      margin-bottom: 20px;
    }
    .info p, .branch-info p {
      margin: 4px 0;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 12px;
      text-align: left;
    }
    th {
      background-color: #f7931e;
      color: white;
    }
    tr:nth-child(even) {
      background-color: #fff4e8;
    }
    .total {
      text-align: right;
      font-weight: bold;
      font-size: 18px;
      margin-top: 20px;
    }
    .print-btn {
      margin-top: 30px;
      display: block;
      width: 100%;
      padding: 14px;
      background-color: #f7931e;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .print-btn:hover {
      background-color: #e67e00;
    }
    .back-link {
      text-align: center;
      margin-top: 20px;
    }
    .back-link a {
      color: #555;
      text-decoration: none;
    }
    .back-link a:hover {
      text-decoration: underline;
    }
    .thank-you {
      text-align: center;
      margin-top: 30px;
      font-style: italic;
      color: #666;
    }
    @media print {
      .print-btn, .back-link {
        display: none;
      }
      body {
        margin: 0;
        box-shadow: none;
      }
    }
  </style>
</head>
<body>

  <h1>Receipt - Sale #<?= $sale_id ?></h1>

  <div class="branch-info">
    <p><strong>Branch:</strong> <?= htmlspecialchars($sale['branch_name']) ?></p>
    <p><strong>Location:</strong> <?= htmlspecialchars($sale['branch_location']) ?></p>
    <p><strong>Contact:</strong> <?= htmlspecialchars($sale['branch_contact']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($sale['branch_email']) ?></p>
  </div>

  <div class="info">
    <p><strong>Processed By:</strong> <?= htmlspecialchars($sale['staff_name'] ?? 'N/A') ?></p>
<p><strong>Payment Received:</strong> ₱<?= number_format($sale['payment'], 2) ?></p>
<p><strong>Change Given:</strong> ₱<?= number_format($sale['change_given'], 2) ?></p>

  </div>

  <table>
    <thead>
      <tr>
        <th>Product</th>
        <th>Qty</th>
        <th>Price (₱)</th>
        <th>Subtotal (₱)</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($items_result->num_rows > 0): ?>
        <?php while ($item = $items_result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($item['product_name']) ?></td>
          <td><?= (int)$item['quantity'] ?></td>
          <td><?= number_format($item['price'], 2) ?></td>
          <td><?= number_format($item['price'] * $item['quantity'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="4" style="text-align:center;">No items found for this sale.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <p class="total">Total Amount: ₱<?= number_format($sale['total'], 2) ?></p>

  <button class="print-btn" onclick="window.print()">🖨️ Print Receipt</button>

  <div class="back-link">
    <p><a href="pos.php">← Back to POS</a></p>
  </div>

  <p class="thank-you">Thank you for your purchase!</p>

</body>
</html>
