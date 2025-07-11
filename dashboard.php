<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$role = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? null;

include 'config/db.php';

// Queries based on role
if ($role === 'staff') {
    // Filter by branch for staff
    $totalProducts = $conn->query("SELECT COUNT(*) AS count FROM inventory WHERE branch_id = $branch_id")->fetch_assoc()['count'];

    // Low stocks (stock <= critical_point)
    $lowStocks = $conn->query("
        SELECT COUNT(*) AS count 
        FROM inventory 
        INNER JOIN products ON inventory.product_id = products.product_id
        WHERE inventory.branch_id = $branch_id 
        AND inventory.stock <= products.critical_point
    ")->fetch_assoc()['count'];

    // Out of stocks (stock = 0)
    $outOfStocks = $conn->query("
        SELECT COUNT(*) AS count 
        FROM inventory 
        INNER JOIN products ON inventory.product_id = products.product_id
        WHERE inventory.branch_id = $branch_id 
        AND inventory.stock = 0
    ")->fetch_assoc()['count'];

} else {
    // Admin sees all
    $totalProducts = $conn->query("SELECT COUNT(*) AS count FROM inventory")->fetch_assoc()['count'];

    // Low stocks (stock <= critical_point)
    $lowStocks = $conn->query("
        SELECT COUNT(*) AS count 
        FROM inventory 
        INNER JOIN products ON inventory.product_id = products.product_id
        WHERE inventory.stock <= products.critical_point
    ")->fetch_assoc()['count'];

    // Out of stocks (stock = 0)
    $outOfStocks = $conn->query("
        SELECT COUNT(*) AS count 
        FROM inventory 
        INNER JOIN products ON inventory.product_id = products.product_id
        WHERE inventory.stock = 0
    ")->fetch_assoc()['count'];
}

$role = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? null;

// Calculate total sales based on role
if ($role === 'staff') {
    $stmt = $conn->prepare("SELECT IFNULL(SUM(total), 0) AS total_sales FROM sales WHERE branch_id = ?");
    $stmt->bind_param("i", $branch_id);
} else {
    $stmt = $conn->prepare("SELECT IFNULL(SUM(total), 0) AS total_sales FROM sales");
}
$stmt->execute();
$result = $stmt->get_result();
$totalSales = 0;
if ($row = $result->fetch_assoc()) {
    $totalSales = $row['total_sales'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= strtoupper($role) ?> Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            cursor: pointer;
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

        /* Modal Styles */
        #productModal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 70%;
            max-height: 80%;
            overflow-y: auto;
        }

        #closeModal {
            font-size: 30px;
            color: #aaa;
            cursor: pointer;
        }

        #closeModal:hover {
            color: black;
        }
    </style>
</head>
<body>
<div class="sidebar">
  
    <h2><?= htmlspecialchars($_SESSION['username']) ?> (<?= ucfirst($_SESSION['role']) ?>)</h2>


    
    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>
    <a href="inventory.php?branch=<?= $branch_id ?>"><i class="fas fa-box"></i> Inventory</a>
    <a href="transfer.php"> <i class="fas fa-box"></i> Transfer</a>
  
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

    <div class="content">
        <div class="cards">
            <div class="card green">
                <h3>TOTAL PRODUCTS</h3>
                <p><?= $totalProducts ?></p>
            </div>
            <div class="card orange" onclick="showProducts('low_stocks')">
                <h3>LOW STOCKS</h3>
                <p><?= $lowStocks ?></p>
            </div>
            <div class="card red" onclick="showProducts('out_of_stocks')">
                <h3>OUT OF STOCKS</h3>
                <p><?= $outOfStocks ?></p>
            </div>
            <div class="card green" onclick="showSales()">
                <h3>TOTAL SALES</h3>
                <p><?= number_format($totalSales) ?></p>
            </div>
        </div>
    </div>

    <div id="productModal" style="display:none; position:fixed;top:0;left:0;width:100%;height:100%;
     background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
      <div class="modal-content" style="background:#fff;padding:20px;border-radius:8px; width:70%; max-height:80%; overflow:auto; position:relative;">
        <span id="closeModal" style="position:absolute;top:10px; right:15px; font-size:24px; cursor:pointer;">&times;</span>
        <h3 id="modalTitle">Products</h3>
        <table id="modalTable" style="width:100%; border-collapse:collapse; margin-top:15px;">
          <thead>
            <tr>
              <th style="border:1px solid #ccc;padding:8px;">Product Name</th>
              <th style="border:1px solid #ccc;padding:8px;">Category</th>
              <th style="border:1px solid #ccc;padding:8px;">Stock</th>
              <th style="border:1px solid #ccc;padding:8px;">Critical Point</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>

    <div id="salesModal" style="display:none; position:fixed;top:0;left:0;width:100%;height:100%;
     background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
      <div class="modal-content" style="background:#fff;padding:20px;border-radius:8px; width:70%; max-height:80%; overflow:auto; position:relative;">
        <span id="closeSalesModal" style="position:absolute;top:10px; right:15px; font-size:24px; cursor:pointer;">&times;</span>
        <h3>Sales History</h3>
        <table id="salesTable" style="width:100%; border-collapse:collapse; margin-top:15px;">
          <thead>
            <tr>
              <th style="border:1px solid #ccc;padding:8px;">Sale ID</th>
              <th style="border:1px solid #ccc;padding:8px;">Date</th>
              <th style="border:1px solid #ccc;padding:8px;">Total (₱)</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const productModal     = document.getElementById('productModal');
  const closeBtn  = document.getElementById('closeModal');
  const titleEl   = document.getElementById('modalTitle');
  const tbody     = document
                      .getElementById('modalTable')
                      .getElementsByTagName('tbody')[0];

  const salesModal = document.getElementById('salesModal');
  const closeSalesBtn = document.getElementById('closeSalesModal');
  const salesTbody = document.getElementById('salesTable').getElementsByTagName('tbody')[0];

  closeBtn.addEventListener('click', () => {
    productModal.style.display = 'none';
  });

  closeSalesBtn.addEventListener('click', () => {
    salesModal.style.display = 'none';
  });

  window.showProducts = function(viewType) {
    // clear table
    tbody.innerHTML = '';

    titleEl.textContent = viewType === 'low_stocks'
      ? 'Low Stock Products'
      : 'Out of Stock Products';

    fetch(`get_products.php?view=${viewType}&branch=<?= $branch_id ?>`)
      .then(r => r.json())
      .then(data => {
        data.forEach(prod => {
          const tr = tbody.insertRow();
          tr.insertCell().textContent = prod.product_name;
          tr.insertCell().textContent = prod.category;
          tr.insertCell().textContent = prod.stock;
          tr.insertCell().textContent = prod.critical_point ?? 'N/A';
        });
        productModal.style.display = 'flex';
      })
      .catch(err => {
        console.error('Fetch error:', err);
        alert('Could not load product data');
      });
  };

  window.showSales = function() {
    salesTbody.innerHTML = '';

    fetch('get_sales.php')
      .then(r => r.json())
      .then(data => {
        data.forEach(sale => {
          const tr = salesTbody.insertRow();
          tr.insertCell().textContent = sale.sale_id;
          tr.insertCell().textContent = sale.sale_date;
          tr.insertCell().textContent = sale.total.toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
        });
        salesModal.style.display = 'flex';
      })
      .catch(err => {
        console.error('Fetch error:', err);
        alert('Could not load sales data');
      });
  };
});
</script>
</body>
</html>
