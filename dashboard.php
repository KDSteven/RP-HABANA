<?php
include 'config/db.php';

// Placeholder queries (replace with your actual logic)
$totalProducts = $conn->query("SELECT COUNT(*) AS count FROM inventory")->fetch_assoc()['count'];
$lowStocks = $conn->query("
  SELECT COUNT(*) AS count FROM inventory 
  WHERE 
    (branch_1_stock BETWEEN 1 AND 9) OR 
    (branch_2_stock BETWEEN 1 AND 9) OR 
    (branch_3_stock BETWEEN 1 AND 9) OR
    (branch_4_stock BETWEEN 1 AND 9) OR
    (branch_5_stock BETWEEN 1 AND 9) OR 
    (branch_6_stock BETWEEN 1 AND 9) OR
    (branch_7_stock BETWEEN 1 AND 9)
")->fetch_assoc()['count'];

$outOfStocks = $conn->query("
  SELECT COUNT(*) AS count FROM inventory 
  WHERE 
    branch_1_stock = 0 AND 
    branch_2_stock = 0 AND 
    branch_3_stock = 0 AND 
    branch_4_stock = 0 AND 
    branch_5_stock = 0 AND 
    branch_6_stock = 0 AND 
    branch_4_stock = 0
")->fetch_assoc()['count'];

$totalSales = 123456; // Example static number, replace with real calculation if needed
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
    body { display: flex; height: 100vh; background: #ddd; }

    .sidebar {
      width: 220px;
      background-color: #f7931e;
      color: white;
      padding: 30px 10px;
    }

    .sidebar h2 { margin-bottom: 40px; }

    .sidebar a {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: white;
      padding: 10px 20px;
      margin: 5px 0;
      border-radius: 5px;
    }

    .sidebar a:hover, .sidebar a.active {
      background-color: #e67e00;
    }

    .sidebar a i { margin-right: 10px; }

    .content {
      flex: 1;
      padding: 40px;
      background-color: #ccc;
    }

    .cards {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 25px;
    }

    .card {
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 5px 10px rgba(0,0,0,0.15);
      color: white;
      font-weight: bold;
      text-align: center;
      position: relative;
    }

    .card.green { background-color: #28a745; }
    .card.orange { background-color: #fd7e14; }
    .card.red { background-color: #dc3545; }

    .card h3 { font-size: 20px; margin-bottom: 10px; }
    .card p { font-size: 32px; }

    .card::after {
      content: "● ● ●";
      position: absolute;
      bottom: 15px;
      left: 0;
      right: 0;
      text-align: center;
      font-size: 18px;
      color: #fff;
      opacity: 0.8;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>ADMIN</h2>
    <a href="dashboard.php" class="active"><i class="fas fa-tv"></i> Dashboard</a>
    <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
    <a href="#"><i class="fas fa-user"></i> Accounts</a>
    <a href="#"><i class="fas fa-archive"></i> Archive</a>
    <a href="#"><i class="fas fa-calendar-alt"></i> Logs</a>
    <a href="index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="content">
    <div class="cards">
      <div class="card green">
        <h3>TOTAL PRODUCTS</h3>
        <p><?= $totalProducts ?></p>
      </div>
      <div class="card orange">
        <h3>LOW STOCKS</h3>
        <p><?= $lowStocks ?></p>
      </div>
      <div class="card red">
        <h3>OUT OF STOCKS</h3>
        <p><?= $outOfStocks ?></p>
      </div>
      <div class="card green">
        <h3>TOTAL SALES</h3>
        <p><?= number_format($totalSales) ?></p>
      </div>
    </div>
  </div>
</body>
</html>

<?php $conn->close(); ?>
