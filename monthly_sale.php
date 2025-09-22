<?php
// monthly_sale.php
declare(strict_types=1);
session_start();
require 'config/db.php';

header('Content-Type: application/json');

// ---- Session scope ----
$role         = $_SESSION['role']      ?? '';
$userBranchId = $_SESSION['branch_id'] ?? null;

// ---- Inputs ----
$period = $_GET['period'] ?? 'month'; // month | range | fiscal
$month  = $_GET['month']  ?? date('Y-m');
$from   = $_GET['from']   ?? '';
$to     = $_GET['to']     ?? '';
$fy     = (int)($_GET['fy'] ?? date('Y'));
$FISCAL_START_MONTH = 1; // keep in sync with dashboard

// Optional branch filter (admin only); for staff/stockman we’ll force below
$branchIdParam = (isset($_GET['branch_id']) && $_GET['branch_id'] !== '')
  ? (int)$_GET['branch_id']
  : null;

// ---- Resolve date range ----
$startDate = '';
$endDate   = '';

if ($period === 'range') {
  $fromOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from);
  $toOk   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to);
  $startDate = $fromOk ? $from : date('Y-m-01');
  $endDate   = $toOk   ? $to   : date('Y-m-t', strtotime($startDate));
} elseif ($period === 'fiscal') {
  $fyStart = DateTime::createFromFormat('Y-n-j', $fy . '-' . $FISCAL_START_MONTH . '-1');
  if (!$fyStart) {
    // Fallback to current FY if bad input
    $fy = (int)date('Y');
    $fyStart = DateTime::createFromFormat('Y-n-j', $fy . '-' . $FISCAL_START_MONTH . '-1');
  }
  $fyEnd = (clone $fyStart)->modify('+1 year')->modify('-1 day');
  $startDate = $fyStart->format('Y-m-d');
  $endDate   = $fyEnd->format('Y-m-d');
} else {
  // month mode
  if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
  $startDate = $month . '-01';
  $endDate   = date('Y-m-t', strtotime($startDate));
}

// ---- Determine branch scope ----
// staff/stockman: always restricted to their branch
// admin: use ?branch_id=… if provided, else all branches
$scopeBranchId = null;
if ($role === 'staff' || $role === 'stockman') {
  $scopeBranchId = (int)$userBranchId;
} elseif (!is_null($branchIdParam)) {
  $scopeBranchId = $branchIdParam;
}

// ---- Query: sum of sales per month inside the window (optionally per branch) ----
$sql = "
  SELECT 
    DATE_FORMAT(s.sale_date, '%Y-%m') AS ym,
    DATE_FORMAT(s.sale_date, '%b %Y') AS label,
    SUM(s.total) AS sum_total
  FROM sales s
  WHERE s.sale_date BETWEEN ? AND ?
";
$types  = "ss";
$params = [$startDate, $endDate];

if (!is_null($scopeBranchId)) {
  $sql   .= " AND s.branch_id = ? ";
  $types .= "i";
  $params[] = $scopeBranchId;
}

$sql .= " GROUP BY ym, label ORDER BY ym ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['months' => [], 'sales' => [], 'error' => $conn->error]);
  exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

// Build arrays
$months = [];
$sales  = [];
while ($row = $res->fetch_assoc()) {
  $months[] = $row['label'];           // e.g., "Sep 2025"
  $sales[]  = (float)$row['sum_total'];
}
$stmt->close();

// Return JSON
echo json_encode(['months' => $months, 'sales' => $sales]);
