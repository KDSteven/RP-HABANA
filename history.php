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
    $stmt = $conn->prepare("SELECT sale_id, sale_date, total FROM sales WHERE branch_id = ? ORDER BY sale_date DESC");
    $stmt->bind_param("i", $branch_id);
} else {
    // Admin sees all sales
    $stmt = $conn->prepare("SELECT sale_id, sale_date, total FROM sales ORDER BY sale_date DESC");
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
    body {
      font-family: Arial, sans-serif;
      max-width: 900px;
      margin: 20px auto;
      padding: 20px;
      background: #f5f5f5;a
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
    .sidebar {
      width: 220px;
      background-color: #f7931e;
      color: white;
      padding: 30px 10px;
      position: fixed;
      height: 100vh;
      box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }
    .sidebar h2 {
      margin-bottom: 40px;
      font-weight: 700;
      font-size: 1.8rem;
      letter-spacing: 1px;
    }
    .sidebar a {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: white;
      padding: 10px 20px;
      margin: 5px 0;
      border-radius: 5px;
      font-weight: 600;
      font-size: 1.1rem;
      transition: background-color 0.3s ease;
    }
    .sidebar a:hover, .sidebar a.active {
      background-color: #e67e00;
    }
    .sidebar a i {
      margin-right: 10px;
      font-size: 1.2rem;
    }
    .content {
      margin-left: 240px;
      padding: 40px;
      background: #f5f5f5;
      min-height: 100vh;
      box-shadow: inset 0 0 10px rgba(0,0,0,0.05);
      border-radius: 8px;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>STAFF</h2>
    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>
    <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
    <a href="history.php" class="active"><i class="fas fa-history"></i> Sales History</a>
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
            <th>Date</th>
            <th>Total (₱)</th>
            <th>Receipt</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($sale = $sales_result->fetch_assoc()): ?>
          <tr>
            <td><?= (int)$sale['sale_id'] ?></td>
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
