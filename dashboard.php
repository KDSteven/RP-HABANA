<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$role = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? null;

include 'config/db.php';

// Summary stats
if ($role === 'staff') {
    $totalProducts = $conn->query("SELECT COUNT(*) AS count FROM inventory WHERE branch_id = $branch_id")->fetch_assoc()['count'];
    $lowStocks = $conn->query("
        SELECT COUNT(*) AS count 
        FROM inventory 
        INNER JOIN products ON inventory.product_id = products.product_id
        WHERE inventory.branch_id = $branch_id AND inventory.stock <= products.critical_point
    ")->fetch_assoc()['count'];
    $outOfStocks = $conn->query("
        SELECT COUNT(*) AS count 
        FROM inventory 
        INNER JOIN products ON inventory.product_id = products.product_id
        WHERE inventory.branch_id = $branch_id AND inventory.stock = 0
    ")->fetch_assoc()['count'];
} else {
    $totalProducts = $conn->query("SELECT COUNT(*) AS count FROM inventory")->fetch_assoc()['count'];
    $lowStocks = $conn->query("
        SELECT COUNT(*) AS count 
        FROM inventory 
        INNER JOIN products ON inventory.product_id = products.product_id
        WHERE inventory.stock <= products.critical_point
    ")->fetch_assoc()['count'];
    $outOfStocks = $conn->query("
        SELECT COUNT(*) AS count 
        FROM inventory 
        INNER JOIN products ON inventory.product_id = products.product_id
        WHERE inventory.stock = 0
    ")->fetch_assoc()['count'];
}

// Total Sales
if ($role === 'staff') {
    $stmt = $conn->prepare("SELECT IFNULL(SUM(total), 0) AS total_sales FROM sales WHERE branch_id = ?");
    $stmt->bind_param("i", $branch_id);
} else {
    $stmt = $conn->prepare("SELECT IFNULL(SUM(total), 0) AS total_sales FROM sales");
}
$stmt->execute();
$result = $stmt->get_result();
$totalSales = $result->fetch_assoc()['total_sales'] ?? 0;

// Fast moving items
$fastMovingQuery = "
SELECT p.product_name, SUM(si.quantity) AS total_qty
FROM sales_items si
JOIN products p ON si.product_id = p.product_id
JOIN sales s ON si.sale_id = s.sale_id
";
if ($role === 'staff') {
    $fastMovingQuery .= " WHERE s.branch_id = $branch_id ";
}
$fastMovingQuery .= " GROUP BY si.product_id ORDER BY total_qty DESC LIMIT 5";
$fastMovingResult = $conn->query($fastMovingQuery);


// Slow Moving Items (Bottom 5)
$slowMovingQuery = "
SELECT p.product_name, SUM(si.quantity) AS total_qty
FROM sales_items si
JOIN products p ON si.product_id = p.product_id
JOIN sales s ON si.sale_id = s.sale_id
";
if ($role === 'staff') {
    $slowMovingQuery .= " WHERE s.branch_id = $branch_id ";
}
$slowMovingQuery .= " GROUP BY si.product_id ORDER BY total_qty ASC LIMIT 5";
$slowMovingResult = $conn->query($slowMovingQuery);

// Notifications (Pending Approvals)
$pending = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'")->fetch_assoc()['pending'];

// Recent Sales (last 5)
$recentSalesQuery = "SELECT sale_id, sale_date, total FROM sales";
if ($role === 'staff') {
    $recentSalesQuery .= " WHERE branch_id = $branch_id";
}
$recentSalesQuery .= " ORDER BY sale_date DESC LIMIT 5";
$recentSales = $conn->query($recentSalesQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= strtoupper($role) ?> Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet"href="notifs.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{display:flex;min-height:100vh;background:#f5f5f5;color:#333;}
.sidebar{width:220px;background:#f7931e;padding:30px 15px;color:white;flex-shrink:0;}
.sidebar h2{text-align:center;margin-bottom:30px;position:relative;}
.sidebar a{display:flex;align-items:center;text-decoration:none;color:white;padding:12px;margin:6px 0;border-radius:8px;transition:.3s;}
.sidebar a:hover,.sidebar a.active{background:#e67e00;}
.sidebar a i{margin-right:10px;}
.content{flex:1;padding:20px;overflow-y:auto;}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:20px;}
.card{padding:20px;border-radius:10px;color:white;text-align:center;box-shadow:0 4px 8px rgba(0,0,0,.1);}
.green{background:#28a745}.orange{background:#fd7e14}.red{background:#dc3545}.blue{background:#007bff}
.card h3{margin-bottom:10px;font-size:16px;font-weight:500;}
.card p{font-size:22px;font-weight:bold;}
.flex-sections{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;margin-bottom:20px;}
section{background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.1);}
section h2{margin-bottom:10px;font-size:18px;color:#333;}
ul{list-style:none;padding-left:0;}
ul li{padding:10px 0;border-bottom:1px solid #eee;}
ul li:last-child{border-bottom:none;}
.item-row{display:flex;justify-content:space-between;font-size:14px;margin-bottom:6px;}
.progress-bar{background:#e9ecef;height:8px;border-radius:4px;overflow:hidden;}
.progress{height:100%;border-radius:4px;}
table{width:100%;border-collapse:collapse;}
th,td{padding:8px 12px;border-bottom:1px solid #ddd;font-size:14px;text-align:left;}
th{background:#f7f7f7;font-weight:600;}

</style>
</head>
<body>
<div class="sidebar">
    <h2><?= strtoupper($role) ?>
        <i class="fas fa-bell" id="notifBell" style="font-size: 24px; cursor: pointer;"></i>
<span id="notifCount" style="
    background:red; color:white; border-radius:50%; padding:2px 8px;
    font-size:12px;  position:absolute;display:none;">
0</span>
    </h2>
    <a href="dashboard.php" class="active"><i class="fas fa-tv"></i> Dashboard</a>
    <?php if($role==='admin'):?>
        <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
        <a href="approvals.php"><i class="fas fa-check-circle"></i> Approvals
            <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;"><?= $pending ?></span>
        </a>
        <a href="accounts.php"><i class="fas fa-users"></i> Accounts</a>
        <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>
        <a href="logs.php"><i class="fas fa-file-alt"></i> Logs</a>
    <?php endif;?>
    <?php if($role==='stockman'):?><a href="transfer.php"><i class="fas fa-exchange-alt"></i> Transfer</a><?php endif;?>
    <?php if($role==='staff'):?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif;?>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<div class="content">
    <!-- Summary Cards -->
    <div class="cards">
        <div class="card green"><h3>Total Products</h3><p><?= $totalProducts ?></p></div>
        <div class="card orange"><h3>Low Stocks</h3><p><?= $lowStocks ?></p></div>
        <div class="card red"><h3>Out of Stocks</h3><p><?= $outOfStocks ?></p></div>
        <div class="card blue"><h3>Total Sales</h3><p>₱<?= number_format($totalSales,2) ?></p></div>
    </div>

   
    <!-- Fast & Slow Moving -->
    <div style="display:flex; gap:20px; flex-wrap:wrap;">
        <!-- Fast Moving Items -->
        <section style="flex:1; min-width:300px;">
            <h2>🔥 Fast Moving Items</h2>
            <ul>
                <?php 
                $maxQty = 0;
                $fastItems = [];
                while($item = $fastMovingResult->fetch_assoc()) {
                    $fastItems[] = $item;
                    if($item['total_qty'] > $maxQty) $maxQty = $item['total_qty'];
                }
                foreach($fastItems as $item): 
                    $percentage = ($maxQty > 0) ? ($item['total_qty'] / $maxQty) * 100 : 0;
                ?>
                <li>
                    <div style="display:flex;justify-content:space-between;">
                        <span><?= htmlspecialchars($item['product_name']) ?></span>
                        <span><?= $item['total_qty'] ?> sold</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width:<?= round($percentage) ?>%;background:#28a745;"></div>
                    </div>
                </li>
                <?php endforeach;?>
            </ul>
        </section>

        <!-- Slow Moving Items -->
        <section style="flex:1; min-width:300px;">
            <h2>🐢 Slow Moving Items</h2>
            <ul>
                <?php 
                $slowItems = [];
                $slowMax = 0;
                while($item = $slowMovingResult->fetch_assoc()) {
                    $slowItems[] = $item;
                    if($item['total_qty'] > $slowMax) $slowMax = $item['total_qty'];
                }
                foreach($slowItems as $item): 
                    $percentage = ($slowMax > 0) ? ($item['total_qty'] / $slowMax) * 100 : 0;
                ?>
                <li>
                    <div style="display:flex;justify-content:space-between;">
                        <span><?= htmlspecialchars($item['product_name']) ?></span>
                        <span><?= $item['total_qty'] ?> sold</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width:<?= round($percentage) ?>%;background:#ffc107;"></div>
                    </div>
                </li>
                <?php endforeach;?>
            </ul>
        </section>
    </div>

    <!-- Recent Sales -->
    <section>
        <h2>Recent Sales</h2>
        <table>
            <thead><tr><th>ID</th><th>Date</th><th>Total</th></tr></thead>
            <tbody>
                <?php while($sale=$recentSales->fetch_assoc()):?>
                    <tr>
                        <td><?= $sale['sale_id']?></td>
                        <td><?= $sale['sale_date']?></td>
                        <td>₱<?= number_format($sale['total'],2)?></td>
                    </tr>
                <?php endwhile;?>
            </tbody>
        </table>
    </section>

    <!-- Sales Chart -->
    <section>
        <h2>Monthly Sales Overview</h2>
        <canvas id="salesChart" height="120"></canvas>

    </section>
</div>



<!-- NOTIFICATIONS -->
<script src="notifications.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Fetch Monthly Sales for Chart
fetch('monthly_sale.php')
.then(r => r.json())
.then(data => {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: { labels: data.months, datasets: [{ label: 'Sales (₱)', data: data.sales, backgroundColor: '#f7931e' }] },
        options: { responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true } } }
    });

    // Toggle Bar/Line
    document.getElementById('salesChart').addEventListener('dblclick', () => {
        chart.config.type = (chart.config.type === 'bar') ? 'line' : 'bar';
        chart.update();
    });
});
</script>

</body>
</html>
