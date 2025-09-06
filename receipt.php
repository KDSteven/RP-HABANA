<?php
session_start();
include 'config/db.php';

if (!isset($_GET['sale_id'])) {
    die("Sale ID not provided.");
}

$sale_id = (int)$_GET['sale_id'];

// ========================
// Fetch sale and branch details
// ========================
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

// ========================
// Fetch sale items (products)
// ========================
$item_stmt = $conn->prepare("
    SELECT p.product_name, si.quantity, si.price
    FROM sales_items si
    JOIN products p ON si.product_id = p.product_id
    WHERE si.sale_id = ?
");
$item_stmt->bind_param("i", $sale_id);
$item_stmt->execute();
$items_result = $item_stmt->get_result();

// ========================
// Fetch sale services
// ========================
$service_stmt = $conn->prepare("
    SELECT s.service_name, ss.price, 1 AS quantity
    FROM sales_services ss
    JOIN services s ON ss.service_id = s.service_id
    WHERE ss.sale_id = ?
");
$service_stmt->bind_param("i", $sale_id);
$service_stmt->execute();
$services_result = $service_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Receipt - Sale #<?= $sale_id ?></title>
  <style>
    body {
      font-family: monospace, 'Courier New', sans-serif;
      max-width: 350px;
      margin: 0 auto;
      padding: 10px;
      background: #fff;
      color: #000;
    }

    .receipt {
      border: 1px dashed #000;
      padding: 15px;
    }

    .header {
      text-align: center;
      margin-bottom: 15px;
    }

    .header h2 {
      margin: 0;
      font-size: 18px;
      text-transform: uppercase;
    }

    .header p {
      margin: 2px 0;
      font-size: 12px;
    }

    .info {
      font-size: 12px;
      margin-bottom: 10px;
    }

    .info p {
      margin: 2px 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
      font-size: 12px;
    }

    th, td {
      padding: 4px;
      text-align: left;
    }

    th {
      border-bottom: 1px dashed #000;
      font-size: 12px;
    }

    tfoot td {
      border-top: 1px dashed #000;
      font-weight: bold;
      font-size: 13px;
    }

    .thank-you {
      text-align: center;
      margin-top: 15px;
      font-size: 12px;
      font-style: italic;
    }

    .print-btn, .back-link {
      margin-top: 15px;
      display: block;
      text-align: center;
    }

    .print-btn button {
      padding: 8px 12px;
      font-size: 14px;
      cursor: pointer;
    }

    @media print {
      .print-btn, .back-link {
        display: none;
      }
      body {
        margin: 0;
        padding: 0;
      }
      .receipt {
        border: none;
      }
    }
  </style>
</head>
<body>

<div class="receipt">
  <div class="header">
    <h2><?= htmlspecialchars($sale['branch_name']) ?></h2>
    <p><?= htmlspecialchars($sale['branch_location']) ?></p>
    <p>📞 <?= htmlspecialchars($sale['branch_contact']) ?></p>
    <p><?= htmlspecialchars($sale['branch_email']) ?></p>
    <p>Sale #: <?= $sale_id ?> | Date: <?= date("Y-m-d H:i", strtotime($sale['sale_date'])) ?></p>
  </div>

  <div class="info">
    <p><strong>Cashier:</strong> <?= htmlspecialchars($sale['staff_name'] ?? 'N/A') ?></p>
    <p><strong>Payment:</strong> ₱<?= number_format($sale['payment'], 2) ?></p>
    <p><strong>Change:</strong> ₱<?= number_format($sale['change_given'], 2) ?></p>
  </div>

  <table>
    <thead>
      <tr>
        <th>Item/Service</th>
        <th>Qty</th>
        <th>Price</th>
        <th>Sub</th>
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
      <?php endif; ?>

      <?php if ($services_result->num_rows > 0): ?>
        <?php while ($service = $services_result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($service['service_name']) ?></td>
            <td>1</td>
            <td><?= number_format($service['price'], 2) ?></td>
            <td><?= number_format($service['price'], 2) ?></td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>

      <?php if ($items_result->num_rows === 0 && $services_result->num_rows === 0): ?>
        <tr>
          <td colspan="4" style="text-align:center;">No items or services</td>
        </tr>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3">TOTAL</td>
        <td>₱<?= number_format($sale['total'], 2) ?></td>
      </tr>
    </tfoot>
  </table>

  <p class="thank-you">*** Thank you for your purchase! ***</p>
</div>

<div class="print-btn">
  <button onclick="window.print()">🖨️ Print Receipt</button>
</div>

<div class="back-link">
  <a href="pos.php">← Back to POS</a>
</div>

</body>
</html>
