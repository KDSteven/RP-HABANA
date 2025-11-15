<?php
require 'config/db.php';

$current_branch_id = $_GET['branch'] ?? 1;

// ✅ Correct query — uses i.branch_id from your existing structure
$sql = "
  SELECT 
    i.inventory_id, 
    p.product_id, 
    p.product_name, 
    p.category, 
    p.price, 
    p.markup_price, 
    p.ceiling_point, 
    p.critical_point,
    IFNULL(i.stock, 0) AS stock, 
    i.branch_id
  FROM products p
  LEFT JOIN inventory i ON p.product_id = i.product_id
  WHERE i.branch_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_branch_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="table-wrap">
  <table class="inventory-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Product Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>Markup %</th>
        <th>Ceiling</th>
        <th>Critical</th>
        <th>Stock</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['product_id']) ?></td>
          <td><?= htmlspecialchars($row['product_name']) ?></td>
          <td><?= htmlspecialchars($row['category']) ?></td>
          <td><?= number_format($row['price'], 2) ?></td>
          <td><?= number_format($row['markup_price'], 2) ?></td>
          <td><?= htmlspecialchars($row['ceiling_point']) ?></td>
          <td><?= htmlspecialchars($row['critical_point']) ?></td>
          <td><?= htmlspecialchars($row['stock']) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
