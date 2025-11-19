<?php
declare(strict_types=1);
session_start();
require 'config/db.php';

header('Content-Type: application/json');

$role         = $_SESSION['role']      ?? '';
$userBranchId = $_SESSION['branch_id'] ?? null;

$period = $_GET['period'] ?? 'month';
$month  = $_GET['month']  ?? date('Y-m');
$from   = $_GET['from']   ?? '';
$to     = $_GET['to']     ?? '';
$fy     = (int)($_GET['fy'] ?? date('Y'));

$branchParam = isset($_GET['branch_id']) && $_GET['branch_id'] !== ''
  ? (int)$_GET['branch_id']
  : null;

// ----- Resolve date range -----
if ($period === 'range') {

    $startDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : date('Y-m-01');
    $endDate   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)   ? $to   : date('Y-m-t', strtotime($startDate));

} elseif ($period === 'fiscal') {

    $fyStart = DateTime::createFromFormat('Y-n-j', "$fy-1-1");
    $fyEnd   = (clone $fyStart)->modify('+1 year')->modify('-1 day');
    $startDate = $fyStart->format('Y-m-d');
    $endDate   = $fyEnd->format('Y-m-d');

} else {

    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
      $month = date('Y-m');
    }

    $startDate = $month . "-01";
    $endDate   = date('Y-m-t', strtotime($startDate));
}

// ----- Branch Scope -----
$scopeBranchId = null;

if ($role === 'staff' || $role === 'stockman') {
    $scopeBranchId = (int)$userBranchId;
} elseif (!is_null($branchParam)) {
    $scopeBranchId = (int)$branchParam;
}

// -----------------------------
// MODE A — RANGE = DAILY TOTALS
// -----------------------------
if ($period === 'range' || $period === 'month') {

    // If month mode, convert selected month into a from/to range
    if ($period === 'month') {
        $startDate = $month . "-01";
        $endDate   = date("Y-m-t", strtotime($startDate));
    }

    $sql = "
      SELECT 
        DATE(s.sale_date) AS d,
        SUM(s.total) AS total
      FROM sales s
      WHERE s.sale_date BETWEEN ? AND ?
    ";

    $types = "ss";
    $params = [$startDate, $endDate];

    if (!is_null($scopeBranchId)) {
        $sql .= " AND s.branch_id = ?";
        $types .= "i";
        $params[] = $scopeBranchId;
    }

    $sql .= " GROUP BY DATE(s.sale_date) ORDER BY d ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    // Fill map
    $dataMap = [];
    while ($row = $res->fetch_assoc()) {
        $dataMap[$row['d']] = (float)$row['total'];
    }

    // Build daily labels
    $labels = [];
    $sales  = [];

    $periodObj = new DatePeriod(
        new DateTime($startDate),
        new DateInterval('P1D'),
        (new DateTime($endDate))->modify('+1 day')
    );

    foreach ($periodObj as $day) {
        $dStr = $day->format("Y-m-d");
        $labels[] = $day->format("M d");
        $sales[]  = $dataMap[$dStr] ?? 0;
    }

    echo json_encode(["months" => $labels, "sales" => $sales]);
    exit;
}


// -------------------------------------------------
// MODE B — MONTH or FISCAL = MONTHLY TOTALS (FIXED)
// -------------------------------------------------

$sql = "
  SELECT 
    DATE_FORMAT(s.sale_date, '%Y-%m') AS ym,
    SUM(s.total) AS sum_total
  FROM sales s
  WHERE s.sale_date BETWEEN ? AND ?
";

$types  = "ss";
$params = [$startDate, $endDate];

if (!is_null($scopeBranchId)) {
    $sql .= " AND s.branch_id = ?";
    $types .= "i";
    $params[] = $scopeBranchId;
}

$sql .= " GROUP BY ym ORDER BY ym ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

// Map DB results
$dbMap = [];
while ($row = $res->fetch_assoc()) {
    $dbMap[$row['ym']] = (float)$row['sum_total'];
}

// Build full month list
$labels = [];
$sales  = [];

$periodObj = new DatePeriod(
    new DateTime(date("Y-m-01", strtotime($startDate))),
    new DateInterval("P1M"),
    (new DateTime(date("Y-m-01", strtotime($endDate))))->modify("+1 month")
);

foreach ($periodObj as $m) {
    $key = $m->format("Y-m");
    $labels[] = $m->format("M Y");
    $sales[]  = $dbMap[$key] ?? 0; // Auto-0 if no sales
}

echo json_encode(["months" => $labels, "sales" => $sales]);
exit;
