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
           s.discount, s.discount_type, s.vat,
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

// Fetch current user's full name
$currentName = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($fetchedName);
    if ($stmt->fetch()) {
        $currentName = $fetchedName;
    }
    $stmt->close();
}

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
/* ===== Thermal receipt simulation (80mm) ===== */
.receipt-80{ width:72mm; margin:0 auto; }
.mono{ font:12px/1.35 "Courier New", ui-monospace, monospace; color:#000; }
#receipt h2, #receipt h3, #receipt h4{ margin:0; text-align:center; }
#receipt .header p, #receipt .info p{ margin:2px 0; text-align:center; }

/* table: compact, clean, right-aligned numbers */
#receipt table{ width:100%; border-collapse:collapse; margin-top:6px; }
#receipt thead th{ border-top:1px dashed #000; border-bottom:1px dashed #000; padding:3px 0; font-weight:700; }
#receipt td, #receipt th{ padding:2px 0; white-space:nowrap; }
#receipt td:nth-child(2),
#receipt td:nth-child(3),
#receipt td:nth-child(4),
#receipt tfoot td{ text-align:right; }
#receipt td:first-child{ width:100%; text-align:left; padding-right:6px; }
#receipt tfoot td{ padding-top:4px; }
#receipt tfoot tr:first-child td{ border-top:1px dashed #000; }
#receipt .grand{ border-top:2px solid #000; border-bottom:2px solid #000; padding:4px 0; font-weight:700; }
#receipt .thank-you{ text-align:center; margin:8px 0 0; }

/* Print rules */
@media print{
  html,body{ margin:0; padding:0; background:#fff; }
  body *{ visibility:hidden !important; }
  #receipt, #receipt *{ visibility:visible !important; }
  #receipt{ margin:0 auto; }
}
@page{ size:80mm auto; margin:0; }

/* Logo optimized for 80mm thermal */
.receipt-logo {
  display: block;
  margin: 0 auto 6px auto;
  width: 140px;        /* safe width for thermal */
  max-width: 70%;      /* ensures no overflow */
  image-rendering: pixelated;
}

</style>

  </style>
</head>
<body>

<div id="receipt" class="receipt-80 mono">
  <div class="header">
    <img src="/img/tire.png" class="receipt-logo" alt="Logo">
    <h2><?= htmlspecialchars($sale['branch_name']) ?></h2>
    <p><?= htmlspecialchars($sale['branch_location']) ?></p>
    <p>📞 <?= htmlspecialchars($sale['branch_contact']) ?></p>
    <p><?= htmlspecialchars($sale['branch_email']) ?></p>
    <p>Sale #: <?= $sale_id ?> | Date: <?= date("Y-m-d H:i", strtotime($sale['sale_date'])) ?></p>
  </div>

  <div class="info">
      <p><strong>Printed By:</strong> <?= htmlspecialchars($currentName ?: 'N/A') ?></p>
     <p><strong>Cashier of Record:</strong> <?= htmlspecialchars($sale['staff_name'] ?? 'N/A') ?></p>
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
  <?php if ($sale['discount'] > 0): ?>
  <tr>
    <td colspan="3">Discount (<?= htmlspecialchars($sale['discount_type']) ?>)</td>
    <td>-₱<?= number_format($sale['discount'], 2) ?></td>
  </tr>
  <?php endif; ?>

  <?php if ($sale['vat'] > 0): ?>
  <tr>
    <td colspan="3">VAT</td>
    <td>₱<?= number_format($sale['vat'], 2) ?></td>
  </tr>
  <?php endif; ?>

  <tr>
    <td colspan="3"><strong>GRAND TOTAL</strong></td>
    <td><strong>₱<?= number_format($sale['total'] + $sale['vat'] - $sale['discount'], 2) ?></strong></td>
  </tr>
</tfoot>

  </table>

  <p class="thank-you">*** Thank you for your purchase! ***</p>
</div>

<script>
  // In receipt.php
  window.addEventListener('load', () => {
    const url = new URL(window.location.href);
    if (url.searchParams.get('autoprint') === '1') {
      setTimeout(() => window.print(), 300); // small delay for logos/QR to render
    }
  });
</script>


</body>
</html>
