<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header("Location: index.html");
    exit;
}

$role = $_SESSION['role'];
$branch_id = isset($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : 0;

// Fetch sales history based on role
if ($role === 'staff') {
  // Staff sees only sales from their own branch
  $stmt = $conn->prepare("
      SELECT s.sale_id, s.sale_date, s.total, b.branch_name 
      FROM sales s
      JOIN branches b ON s.branch_id = b.branch_id
      WHERE s.branch_id = ?
      ORDER BY s.sale_date DESC
  ");
  $stmt->bind_param("i", $branch_id);
} else {
  // Admin sees all sales
  $stmt = $conn->prepare("
      SELECT s.sale_id, s.sale_date, s.total, b.branch_name 
      FROM sales s
      JOIN branches b ON s.branch_id = b.branch_id
      ORDER BY s.sale_date DESC
  ");
}
$stmt->execute();
$sales_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Sales History</title>
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

    h1 {
      color: #f7931e;
      text-align: center;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: white;
      border-radius: 5px;
      overflow: hidden;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
    a.view-link {
      color: #f7931e;
      text-decoration: none;
      font-weight: bold;
      transition: color 0.3s ease;
    }
    a.view-link:hover {
      text-decoration: underline;
      color: #e67e00;
    }
    .content {
      flex: 1;
      padding: 40px;
      overflow-y: auto;
    }
  </style>
</head>
<body>
  <div class="sidebar">
 
  <h2><?= strtoupper($role) ?></h2>
    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>
    <a href="inventory.php?branch=<?= $branch_id ?> "><i class="fas fa-box"></i> Inventory</a>
    <?php if ($role !== 'admin'): ?>
      <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
    <?php endif; ?>
    <a href="history.php"  class="active"><i class="fas fa-history" ></i> Sales History</a>

    <?php if ($role === 'admin'): ?>
      <a href="accounts.php"><i class="fas fa-user"></i> Accounts</a>
      <a href=""><i class="fas fa-archive"></i> Archive</a>
      <a href=""><i class="fas fa-calendar-alt"></i> Logs</a>


    <?php endif; ?>
    <a href="index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="content">
    <h1>Sales History</h1>
    <?php if ($sales_result->num_rows === 0): ?>
      <p>No sales history found.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Sale ID</th>
            <th>Branch</th>
            <th>Date</th>
            <th>Total (₱)</th>
            <th>Receipt</th>

          </tr>
        </thead>
        <tbody>
          <?php while ($sale = $sales_result->fetch_assoc()): ?>
          <tr>
            <td><?= (int)$sale['sale_id'] ?></td>
            <td><?= htmlspecialchars($sale['branch_name']) ?></td>
            <td><?= htmlspecialchars($sale['sale_date']) ?></td>
            <td><?= number_format($sale['total'], 2) ?></td>
            <td><a class="view-link" href="receipt.php?sale_id=<?= (int)$sale['sale_id'] ?>" target="_blank">View Receipt</a></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>
