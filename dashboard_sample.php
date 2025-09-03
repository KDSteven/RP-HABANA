<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$role = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? null;

include 'config/db.php';

// Month selector
$selectedMonth = $_GET['month'] ?? date('Y-m'); // default: current month
$startDate = $selectedMonth . "-01";
$endDate = date("Y-m-t", strtotime($startDate)); // last day of the month

$filterBranch = $_GET['branch_id'] ?? '';
$branchCondition = '';
if ($filterBranch !== '') {
    $branchCondition = " AND s.branch_id = " . intval($filterBranch);
}

$reportType = $_GET['report'] ?? 'daily'; // daily | weekly | monthly | branch | category
$selectedMonth = $_GET['month'] ?? date('Y-m');
$startDate = $selectedMonth . "-01";
$endDate = date("Y-m-t", strtotime($startDate));

// Add role and branch condition
$branchCondition = '';
if ($role === 'admin') {
    if (!empty($_GET['branch_id']) && is_numeric($_GET['branch_id'])) {
        $branch_id = intval($_GET['branch_id']);
        $branchCondition = " AND s.branch_id = $branch_id";
    }
    // Show branch dropdown
    // Branch filter comes from user selection ($_GET['branch_id'])
} else if ($role === 'staff') {
    // No branch dropdown; filter by session branch_id automatically
    $branch_id = $_SESSION['branch_id'];
    $branchCondition = " AND s.branch_id = $branch_id";
}


// Get grouped label based on report type
switch ($reportType) {
    case 'weekly':
        $groupLabel = "CONCAT('Week ', WEEK(s.sale_date), ' - ', YEAR(s.sale_date))";
        break;
    case 'monthly':
        $groupLabel = "CONCAT(MONTHNAME(s.sale_date), ' ', YEAR(s.sale_date))";
        break;
    case 'branch':
        $groupLabel = "b.branch_name";
        break;
    case 'category':
        $groupLabel = "p.category";
        break;
    default: // daily
        $groupLabel = "DATE(s.sale_date)";
}

// Full sales report
$salesReportQuery = "
    SELECT 
        s.sale_id,
        s.sale_date,
        $groupLabel AS period,
        b.branch_name,
        p.product_name,
        p.category,
        si.quantity,
        si.price,
        (si.quantity * si.price) AS total_amount
    FROM sales s
    JOIN sales_items si ON s.sale_id = si.sale_id
    JOIN products p ON si.product_id = p.product_id
    LEFT JOIN branches b ON s.branch_id = b.branch_id
    WHERE s.sale_date BETWEEN '$startDate' AND '$endDate'
    $branchCondition
    ORDER BY s.sale_date DESC
";

$salesReportResult = $conn->query($salesReportQuery);


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

// Fetch fast moving products
$fastMovingQuery = "
SELECT p.product_name, SUM(si.quantity) AS total_qty, si.product_id
FROM sales_items si
JOIN products p ON si.product_id = p.product_id
JOIN sales s ON si.sale_id = s.sale_id
WHERE s.sale_date BETWEEN '$startDate' AND '$endDate'
$branchCondition
GROUP BY si.product_id
ORDER BY total_qty DESC
LIMIT 5
";
$fastMovingResult = $conn->query($fastMovingQuery);

$fastMovingProductIds = [];
$fastItems = [];
while ($row = $fastMovingResult->fetch_assoc()) {
    $fastItems[] = $row;
    $fastMovingProductIds[] = $row['product_id'];
}

// Prepare product IDs string to exclude from slow moving
$excludeFastIds = !empty($fastMovingProductIds) ? implode(',', $fastMovingProductIds) : '0';

// Fetch slow moving products excluding fast moving ones

$branchFilter = '';
if (isset($_GET['branch_id']) && is_numeric($_GET['branch_id'])) {
    $branchId = intval($_GET['branch_id']);
    $branchFilter = "AND s.branch_id = $branchId";
}
$slowMovingQuery = "
SELECT 
    p.product_name,
    SUM(CASE 
        WHEN s.sale_date BETWEEN '$startDate' AND '$endDate' THEN si.quantity 
        ELSE 0 
    END) AS total_qty
FROM products p
LEFT JOIN sales_items si ON p.product_id = si.product_id
LEFT JOIN sales s ON si.sale_id = s.sale_id
WHERE p.product_id NOT IN ($excludeFastIds)
" . ($branchFilter ? " AND s.branch_id = $branchId" : "") . "
GROUP BY p.product_id
ORDER BY total_qty ASC
LIMIT 5
";

$slowMovingResult = $conn->query($slowMovingQuery);
$slowItems = [];
while ($row = $slowMovingResult->fetch_assoc()) {
    $slowItems[] = $row;
}


// Not Moving Items (not sold in selected month)
$notMovingQuery = "
SELECT p.product_name
FROM inventory i
JOIN products p ON i.product_id = p.product_id
WHERE i.product_id NOT IN (
    SELECT si.product_id
    FROM sales_items si
    JOIN sales s ON si.sale_id = s.sale_id
    WHERE s.sale_date BETWEEN '$startDate' AND '$endDate'
    " . ($role === 'staff' ? " AND s.branch_id = $branch_id" : (!empty($filterBranch) ? " AND s.branch_id = $filterBranch" : "")) . "
)
";
if ($role === 'staff') {
    $notMovingQuery .= " AND i.branch_id = $branch_id";
} elseif (!empty($filterBranch)) {
    $notMovingQuery .= " AND i.branch_id = $filterBranch";
}

$notMovingResult = $conn->query($notMovingQuery);
$notMovingItems = [];
while ($row = $notMovingResult->fetch_assoc()) {
    $notMovingItems[] = $row['product_name'];
}

// Notifications (Pending Approvals)
$pending = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'")->fetch_assoc()['pending'];

// SALES
  $catView = $_GET['cat_view'] ?? 'daily';

    switch ($catView) {
        case 'weekly':
            $groupBy = "p.category, YEAR(s.sale_date), WEEK(s.sale_date, 1)";
            $selectDate = "CONCAT('Week ', WEEK(s.sale_date, 1), ' - ', YEAR(s.sale_date)) AS period";
            break;
        case 'monthly':
            $groupBy = "p.category, YEAR(s.sale_date), MONTH(s.sale_date)";
            $selectDate = "CONCAT(MONTHNAME(s.sale_date), ' ', YEAR(s.sale_date)) AS period";
            break;
        case 'daily':
        default:
            $groupBy = "p.category, DATE(s.sale_date)";
            $selectDate = "DATE(s.sale_date) AS period";
            break;
    }

    $categorySalesQuery = "
        SELECT p.category, $selectDate, SUM(si.quantity * si.price) AS total_sales
        FROM sales s
        JOIN sales_items si ON s.sale_id = si.sale_id
        JOIN products p ON si.product_id = p.product_id
        WHERE s.sale_date BETWEEN '$startDate' AND '$endDate'
    ";

    if ($role === 'staff') {
        $categorySalesQuery .= " AND s.branch_id = $branch_id";
    } elseif (!empty($filterBranch)) {
        $categorySalesQuery .= " AND s.branch_id = $filterBranch";
    }

    $categorySalesQuery .= " GROUP BY $groupBy ORDER BY s.sale_date DESC LIMIT 10";
    $categorySalesResult = $conn->query($categorySalesQuery);


// Recent Sales (last 5)
$recentSalesQuery = "
SELECT sale_id, sale_date, total FROM sales
WHERE sale_date BETWEEN '$startDate' AND '$endDate'
";
if ($role === 'staff') {
    $recentSalesQuery .= " AND branch_id = $branch_id";
}
$recentSalesQuery .= " ORDER BY sale_date DESC LIMIT 5";
$recentSales = $conn->query($recentSalesQuery);

// pie chart 
$serviceJobData = [];
$serviceJobResult = $conn->query("
    SELECT s.service_name, COUNT(*) as count
    FROM sales_services ss
    JOIN services s ON ss.service_id = s.service_id
    JOIN sales sa ON ss.sale_id = sa.sale_id
    WHERE sa.sale_date BETWEEN '$startDate' AND '$endDate'
    " . ($role === 'staff' ? " AND sa.branch_id = $branch_id" : (!empty($filterBranch) ? " AND sa.branch_id = $filterBranch" : "")) . "
    GROUP BY s.service_name
");

while ($row = $serviceJobResult->fetch_assoc()) {
    $serviceJobData[] = $row;
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= strtoupper($role) ?> Dashboard</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/notifications.css">
<link rel="stylesheet" href="css/sidebar.css">
<audio id="notifSound" src="img/notif.mp3" preload="auto"></audio>
</head>
<body class="dashboard-page">
<div class="sidebar" >
<h2>
    <?= strtoupper($role) ?>
    <span class="notif-wrapper">
        <i class="fas fa-bell" id="notifBell"></i>
        <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>>0</span>
    </span>
</h2>


    <!-- Common -->
    <a href="dashboard.php" class="active"><i class="fas fa-tv"></i> Dashboard</a>

    <!-- Admin Links -->
    <?php if ($role === 'admin'): ?>
        <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
        <a href="approvals.php"><i class="fas fa-check-circle"></i> Approvals
            <?php if ($pending > 0): ?>
                <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;">
                    <?= $pending ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="accounts.php"><i class="fas fa-users"></i> Accounts</a>
        <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>
        <a href="logs.php"><i class="fas fa-file-alt"></i> Logs</a>
    <?php endif; ?>

    <!-- Stockman Links -->
    <?php if ($role === 'stockman'): ?>
        <a href="transfer.php"><i class="fas fa-exchange-alt"></i> Transfer
            <?php if ($transferNotif > 0): ?>
                <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;">
                    <?= $transferNotif ?>
                </span>
            <?php endif; ?>
        </a>
    <?php endif; ?>
    <!-- Staff Links -->
    <?php if ($role === 'staff'): ?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

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

<div class="Report">
   <form method="GET" style="margin-bottom:5px;">
    <?php
        // Convert YYYY-MM to readable Month Year
        $monthLabel = date("F Y", strtotime($selectedMonth . "-01"));

        // Get branch name if filtered
        $branchName = "All Branches";
        if (!empty($filterBranch)) {
            $bName = $conn->query("SELECT branch_name FROM branches WHERE branch_id = " . intval($filterBranch))->fetch_assoc();
            if ($bName) {
                $branchName = htmlspecialchars($bName['branch_name']);
            }
        }
        ?>
    <label for="month">View Reports for:</label>
    <input type="month" id="month" name="month" value="<?= htmlspecialchars($_GET['month'] ?? date('Y-m')) ?>">

    <?php if ($role === 'staff'): ?>
        <?php
        // Fetch the staff's branch name
        $staffBranch = $conn->query("SELECT branch_name FROM branches WHERE branch_id = $branch_id")->fetch_assoc();
        $staffBranchName = $staffBranch ? htmlspecialchars($staffBranch['branch_name']) : 'Your Branch';
        ?>
        <input type="hidden" name="branch_id" value="<?= $branch_id ?>">
        <select id="branch" name="branch_id" disabled>
            <option value="<?= $branch_id ?>" selected><?= $staffBranchName ?></option>
        </select>
    <?php else: ?>
        <select id="branch" name="branch_id">
            <option value="">All Branches</option>
            <?php
            $branches = $conn->query("SELECT branch_id, branch_name FROM branches");
            while ($b = $branches->fetch_assoc()):
            ?>
                <option value="<?= $b['branch_id'] ?>" <?= (isset($_GET['branch_id']) && $_GET['branch_id'] == $b['branch_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['branch_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    <?php endif; ?>

    <button type="submit">Filter</button>
</form>
</div>

<div class="sections" style="display:flex; gap:20px; flex-wrap:wrap; align-items:flex-start;">
    <!-- Monthly Sales Overview -->
    <section style="flex:1 1 250px; min-width:150px;">
        <h2>Monthly Sales Overview</h2>
        <canvas id="salesChart" style="width:100%; height:150px;"></canvas>
    </section>

    <!-- Service Jobs -->
    <section style="flex:1 1 250px; min-width:200px;">
        <h2>Service Jobs</h2>
        <canvas id="serviceJobChart" style="width:100%; height:150px;"></canvas>
    </section>

</div>



<!-- Bottom Sections -->
<div class="bottom" style="display:flex; gap:20px; flex-wrap:wrap; padding-top:20px;">
<!-- Fast Moving Items -->
    <section style="flex:1; min-width:300px;">
        <h2>Fast Moving Items</h2>
        <div class="scrollable-list">
            <ul>
                <?php 
                $maxQty = max(array_column($fastItems, 'total_qty') ?: [0]);
                foreach ($fastItems as $item):
                    $percentage = ($maxQty > 0) ? ($item['total_qty'] / $maxQty) * 100 : 0;
                ?>
                <li>
                    <div style="display:flex; justify-content:space-between;">
                        <span><?= htmlspecialchars($item['product_name']) ?></span>
                        <span><?= $item['total_qty'] ?> sold</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width:<?= round($percentage) ?>%; background:#28a745;"></div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- Slow Moving Items -->
    <section style="flex:1; min-width:300px;">
        <h2>Slow Moving Items</h2>
        <div class="scrollable-list">
            <ul>
                <?php 
                $slowMax = max(array_column($slowItems, 'total_qty') ?: [0]);
                foreach ($slowItems as $item):
                    $percentage = ($slowMax > 0) ? ($item['total_qty'] / $slowMax) * 100 : 0;
                ?>
                <li>
                    <div style="display:flex; justify-content:space-between;">
                        <span><?= htmlspecialchars($item['product_name']) ?></span>
                        <span><?= $item['total_qty'] ?> sold</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width:<?= round($percentage) ?>%; background:#ffc107;"></div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <!-- Not Moving Items -->
        <section style="flex: 1; min-width: 300px;">
        <h2>Not Moving Items</h2>
            <div class="scrollable-list">
        <ul>
            
            <?php if (!empty($notMovingItems)): ?>
            <?php foreach ($notMovingItems as $item): ?>
                <li><?= htmlspecialchars($item) ?></li>
            <?php endforeach; ?>
            <?php else: ?>
            <li>No items found.</li>
            <?php endif; ?>
        </ul>
        </div>
        </section>
    
</div>
 <!-- Sales Report -->
      <section style="width:100%; margin-top:20px;">
        <h2>Sales Report</h2>
        <div class="scrollable-list">
            <form method="get" style="margin-bottom:10px;">
                <label for="report">View:</label>
                <select name="report" id="report" onchange="this.form.submit()" style="padding:3px 6px; font-size:14px;">
                    <option value="daily" <?= $reportType === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="weekly" <?= $reportType === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                    <option value="monthly" <?= $reportType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                </select>

                <?php if (!empty($_GET['month'])): ?>
                    <input type="hidden" name="month" value="<?= htmlspecialchars($_GET['month']) ?>">
                <?php endif; ?>
                <?php if (!empty($_GET['branch_id'])): ?>
                    <input type="hidden" name="branch_id" value="<?= htmlspecialchars($_GET['branch_id']) ?>">
                <?php endif; ?>
            </form>

            <table border="1" cellpadding="5" cellspacing="0" style="width:100%; font-size:14px; border-collapse:collapse;">
                <thead style="background-color:#f2f2f2;">
                    <tr>
                        <th>Sale ID</th>
                        <th>Date</th>
                        <th>Period</th>
                        <th>Branch Name</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Quantity Sold</th>
                        <th>Price</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($salesReportResult && $salesReportResult->num_rows > 0): ?>
                        <?php while ($row = $salesReportResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['sale_id']) ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d', strtotime($row['sale_date']))) ?></td>
                                <td><?= htmlspecialchars($row['period']) ?></td>
                                <td><?= htmlspecialchars($row['branch_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= (int)$row['quantity'] ?></td>
                                <td>₱<?= number_format($row['price'],2) ?></td>
                                <td>₱<?= number_format($row['total_amount'],2) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center;">No sales data found for this period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<!-- NOTIFICATIONS -->
<script src="notifications.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Fetch Monthly Sales for Chart 
// FIX BY BRANCH
// Pass selected month and branch to the request
const selectedMonth = document.getElementById('month').value;
const selectedBranch = document.getElementById('branch').value;

fetch(`monthly_sale.php?month=${selectedMonth}&branch_id=${selectedBranch}`)
.then(r => r.json())
.then(data => {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.months,
            datasets: [{
                label: 'Sales (₱)',
                data: data.sales,
                backgroundColor: '#f7931e'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Toggle Bar/Line
    document.getElementById('salesChart').addEventListener('dblclick', () => {
        chart.config.type = (chart.config.type === 'bar') ? 'line' : 'bar';
        chart.update();
    });
});

</script>

<script>
const serviceJobData = <?= json_encode($serviceJobData) ?>;

if (serviceJobData && serviceJobData.length > 0) {
    const ctx = document.getElementById('serviceJobChart').getContext('2d');
    const labels = serviceJobData.map(item => item.service_name);
    const data = serviceJobData.map(item => item.count);

    new Chart(ctx, {
        type: 'bar',           // Change from 'pie' to 'bar'
        data: {
            labels: labels,
            datasets: [{
                label: 'Service Jobs',
                data: data,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56',
                    '#8e44ad', '#2ecc71', '#e67e22'
                ]
            }]
        },
        options: {
            indexAxis: 'y',   // Makes the bars horizontal
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                x: { beginAtZero: true },
                y: { ticks: { autoSkip: false } }
            }
        }
    });
}
</script>


</body>
</html>
