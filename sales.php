<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$branch_id = $_SESSION['branch_id'] ?? null;

// Pending notifications (only for admin)
$pending = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE LOWER(status) = 'pending'");
    $pending = $result ? (int)($result->fetch_assoc()['pending'] ?? 0) : 0;
}

// Report filters
$reportType = $_GET['report'] ?? 'itemized'; // daily | weekly | monthly | itemized
$selectedMonth = $_GET['month'] ?? date('Y-m');
$startDate = $selectedMonth . "-01";
$endDate = date("Y-m-t", strtotime($startDate));

// Branch filter
$branchCondition = '';
if ($role === 'admin') {
    if (!empty($_GET['branch_id']) && is_numeric($_GET['branch_id'])) {
        $branch_id = intval($_GET['branch_id']);
        $branchCondition = " AND s.branch_id = $branch_id";
    }
} else if ($role === 'staff') {
    // Staff can only see their branch
    $branchCondition = " AND s.branch_id = $branch_id";
}

// Determine period/group
switch($reportType){
    case 'weekly':
        $periodLabel = "CONCAT('Week ', WEEK(s.sale_date,1),' - ',YEAR(s.sale_date))";
        $groupBy = "YEAR(s.sale_date), WEEK(s.sale_date,1)";
        break;
    case 'monthly':
        $periodLabel = "CONCAT(MONTHNAME(s.sale_date),' ',YEAR(s.sale_date))";
        $groupBy = "YEAR(s.sale_date), MONTH(s.sale_date)";
        break;
    case 'daily':
        $periodLabel = "DATE(s.sale_date)";
        $groupBy = "DATE(s.sale_date)";
        break;
    default: // itemized
        $periodLabel = "DATE(s.sale_date)";
        $groupBy = null;
}

// Build query
if($reportType === 'itemized'){
    $query = "
        SELECT 
            s.sale_id,
            s.sale_date,
            $periodLabel AS period,
            b.branch_name,
            p.product_name,
            p.category,
            si.quantity,
            si.price,
            (si.quantity*si.price) AS total_amount,
            IFNULL(r.refund_amount,0) AS refunded_amount,
            IFNULL(r.refund_reason,'') AS refund_reason
        FROM sales s
        JOIN sales_items si ON s.sale_id = si.sale_id
        JOIN products p ON si.product_id = p.product_id
        LEFT JOIN branches b ON s.branch_id = b.branch_id
        LEFT JOIN sales_refunds r ON r.sale_id = s.sale_id
        WHERE s.sale_date BETWEEN '$startDate' AND '$endDate'
        $branchCondition
        ORDER BY s.sale_date DESC
    ";
}else{
    $query = "
        SELECT 
            $periodLabel AS period,
            SUM(si.quantity*si.price) AS total_sales,
            COUNT(DISTINCT s.sale_id) AS total_transactions
        FROM sales s
        JOIN sales_items si ON s.sale_id = si.sale_id
        JOIN products p ON si.product_id = p.product_id
        WHERE s.sale_date BETWEEN '$startDate' AND '$endDate'
        $branchCondition
        GROUP BY $groupBy
        ORDER BY $groupBy DESC
    ";
}

$salesReportResult = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/notifications.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/sales.css?>v2">
    <audio id="notifSound" src="notif.mp3" preload="auto"></audio>
    <title>Sales</title>
</head>
<body>
    
<div class="sidebar">
    <h2>
        <?= strtoupper($role) ?>
        <span class="notif-wrapper">
            <i class="fas fa-bell" id="notifBell"></i>
            <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>><?= $pending ?></span>
        </span>
    </h2>

    <!-- Common -->
    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>

    <!-- Admin Links -->
    <?php if ($role === 'admin'): ?>
        <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
        <a href="physical_inventory.php" class="active"><i class="fas fa-warehouse"></i> Physical Inventory</a>
        <a href="sales.php"><i class="fas fa-receipt"></i> Sales</a>
        <a href="approvals.php"><i class="fas fa-check-circle"></i> Approvals
            <?php if ($pending > 0): ?>
                <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;"><?= $pending ?></span>
            <?php endif; ?>
        </a>
        <a href="accounts.php"><i class="fas fa-users"></i> Accounts</a>
        <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>
        <a href="logs.php"><i class="fas fa-file-alt"></i> Logs</a>
        <a href="/config/admin/backup_admin.php"><i class="fa-solid fa-database"></i> Backup and Restore</a>
<?php endif; ?>

    <!-- Stockman Links -->
    <?php if ($role === 'stockman'): ?>
        <a href="inventory.php"><i class="fas fa-box"></i> Inventory
            <?php if ($transferNotif > 0): ?>
                <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;"><?= $transferNotif ?></span>
            <?php endif; ?>
        </a>
        <a href="physical_inventory.php" class="active"><i class="fas fa-warehouse"></i> Physical Inventory</a>
    <?php endif; ?>

    <!-- Staff Links -->
    <?php if ($role === 'staff'): ?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

    <!-- Logout -->
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>


<div class="content">
<h2>📊 Sales Report</h2>

<!-- Filters -->
<form method="get" class="mb-3">
    <select name="report" onchange="this.form.submit()">
        <option value="itemized" <?= $reportType==='itemized'?'selected':'' ?>>Itemized</option>
        <option value="daily" <?= $reportType==='daily'?'selected':'' ?>>Daily</option>
        <option value="weekly" <?= $reportType==='weekly'?'selected':'' ?>>Weekly</option>
        <option value="monthly" <?= $reportType==='monthly'?'selected':'' ?>>Monthly</option>

    </select>
    <input type="month" name="month" value="<?= $selectedMonth ?>" onchange="this.form.submit()">
</form>

<!-- Table -->
<div class="table-responsive">
<table class="table table-bordered">
<thead>
<?php if($reportType==='itemized'): ?>
<tr>
<th>Sale ID</th><th>Date</th><th>Period</th><th>Branch</th><th>Product</th><th>Category</th><th>Qty</th><th>Price</th><th>Total</th> <th>Refunded Amount</th>
    <th>Refund Reason</th>
</tr>
<?php else: ?>
<tr>
<th>Period</th><th>Total Sales</th><th>Transactions</th>
</tr>
<?php endif; ?>
</thead>
<tbody>
<?php
$salesDataArr = [];
if ($salesReportResult && $salesReportResult->num_rows > 0) {
    $salesReportResult->data_seek(0); // reset pointer
    while($row = $salesReportResult->fetch_assoc()){
        $salesDataArr[] = $row;
    }
}
?>
<?php foreach($salesDataArr as $row): ?>
<tr>
<?php if($reportType==='itemized'): ?>
<td><?= $row['sale_id'] ?></td>
<td><?= $row['sale_date'] ?></td>
<td><?= $row['period'] ?></td>
<td><?= $row['branch_name'] ?? 'N/A' ?></td>
<td><?= $row['product_name'] ?></td>
<td><?= $row['category'] ?></td>
<td><?= $row['quantity'] ?></td>
<td>₱<?= number_format($row['price'],2) ?></td>
<td>₱<?= number_format($row['total_amount'],2) ?></td>
<td><?php if($row['refunded_amount'] > 0): ?>
        <span class="text-danger fw-bold">₱<?= number_format($row['refunded_amount'],2) ?></span>
    <?php else: ?>
        ₱0.00
    <?php endif; ?></td>
    <td><?= htmlspecialchars($row['refund_reason'] ?? '') ?></td>
<?php else: ?>
    
<td><?= $row['period'] ?></td>
<td>₱<?= number_format($row['total_sales'],2) ?></td>
<td><?= $row['total_transactions'] ?></td>
<?php endif; ?>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>
<script src="notifications.js"></script>
</body>
</html>
