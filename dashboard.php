<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

include 'config/db.php';


// -------------------- USER INFO --------------------
$user_id   = $_SESSION['user_id'];
$role      = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? null;

include 'config/db.php';

/* =========================
   PERIOD RESOLUTION (NEW)
   ========================= */
$FISCAL_START_MONTH = 1; // 1=Jan; change to 7 if FY starts in July
$mode = $_GET['period'] ?? 'month'; // 'month' | 'range' | 'fiscal'

// Month selector (kept for compatibility; used when $mode === 'month')
$selectedMonth = $_GET['month'] ?? date('Y-m');

$startDate = null;
$endDate   = null;
$periodLabel = '';

if ($mode === 'range') {
    $from = $_GET['from'] ?? '';
    $to   = $_GET['to']   ?? '';
    $fromOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from);
    $toOk   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to);

    if ($fromOk && $toOk) {
        $startDate = $from;
        $endDate   = $to;
        $periodLabel = ' — ' . date('M d, Y', strtotime($startDate)) . ' to ' . date('M d, Y', strtotime($endDate));
    } else {
        $startDate = $selectedMonth . "-01";
        $endDate = date("Y-m-t", strtotime($startDate));
        $periodLabel = ' — ' . date('F Y', strtotime($startDate));
    }
} elseif ($mode === 'fiscal') {
    $fy = (int)($_GET['fy'] ?? date('Y'));
    $fyStart = DateTime::createFromFormat('Y-n-j', $fy . '-' . $FISCAL_START_MONTH . '-1');
    $fyEnd   = (clone $fyStart)->modify('+1 year')->modify('-1 day');
    $startDate = $fyStart->format('Y-m-d');
    $endDate   = $fyEnd->format('Y-m-d');
    $periodLabel = sprintf(
        ' — FY%d (%s–%s)',
        $fy,
        $fyStart->format('M d, Y'),
        $fyEnd->format('M d, Y')
    );
} else {
    // default: current month
    $startDate = $selectedMonth . "-01";
    $endDate = date("Y-m-t", strtotime($startDate)); // last day of the month
    $periodLabel = ' — ' . date('F Y', strtotime($startDate));
}

/* Snapshot the resolved period for SALES (so later filter helpers can’t overwrite it) */
$resolvedStart = $startDate;
$resolvedEnd   = $endDate;

/* Normalized date bounds for SALES queries (half-open: [from, nextDay)) */
$salesFrom = (new DateTime($resolvedStart))->format('Y-m-d 00:00:00');
$salesTo   = (new DateTime($resolvedEnd))->modify('+1 day')->format('Y-m-d 00:00:00');

/* Inventory label:
 * - fiscal mode: show "(Jan 01, 2025–Dec 31, 2025)" (no FY prefix)
 * - other modes: show “as of <end date>”
 */
$inventoryAsOfLabel = ' — as of ' . date('M d, Y', strtotime($endDate));
$inventoryLabel = ($mode === 'fiscal')
    ? ' — (' . date('M d, Y', strtotime($startDate)) . '–' . date('M d, Y', strtotime($endDate)) . ')'
    : $inventoryAsOfLabel;



$filterBranch = $_GET['branch_id'] ?? '';
$branchCondition = '';
if ($filterBranch !== '') {
    $branchCondition = " AND s.branch_id = " . intval($filterBranch);
}



// Summary stats (non-overlapping: OOS = stock=0; Low = stock>0 and <= critical_point>0)
// Detect historical columns once (safe if they don't exist)
$hasCreatedAt   = false;
$hasArchivedAt  = false;
if ($res = $conn->query("SHOW COLUMNS FROM inventory LIKE 'created_at'")) {
    $hasCreatedAt = ($res->num_rows > 0);
    $res->free();
}
if ($res = $conn->query("SHOW COLUMNS FROM inventory LIKE 'archived_at'")) {
    $hasArchivedAt = ($res->num_rows > 0);
    $res->free();
}

$asOf = $endDate . ' 23:59:59';

// Prefer product-level visibility (created_at / archived / archived_at on p.*)
$hasPArchived   = false;
$hasPCreatedAt  = false;
$hasPArchivedAt = false;

if ($res = $conn->query("SHOW COLUMNS FROM products LIKE 'archived'"))    { $hasPArchived   = ($res->num_rows > 0); $res->free(); }
if ($res = $conn->query("SHOW COLUMNS FROM products LIKE 'created_at'"))  { $hasPCreatedAt  = ($res->num_rows > 0); $res->free(); }
if ($res = $conn->query("SHOW COLUMNS FROM products LIKE 'archived_at'")) { $hasPArchivedAt = ($res->num_rows > 0); $res->free(); }

$visibilityWhere = "1=1";
$bindTypes = "";
$bindVals  = [];

// Always exclude archived products if the flag exists
if ($hasPArchived) {
  $visibilityWhere .= " AND p.archived = 0";
}

// As-of end date logic (use product timestamps if present)
if ($hasPCreatedAt) {
  $visibilityWhere .= " AND p.created_at <= ?";
  $bindTypes .= "s";
  $bindVals[] = $asOf;
}
if ($hasPArchivedAt) {
  $visibilityWhere .= " AND (p.archived_at IS NULL OR p.archived_at > ?)";
  $bindTypes .= "s";
  $bindVals[] = $asOf;
}

// Optional fallback: if product timestamps don't exist but inventory ones do
if (!$hasPCreatedAt && $hasCreatedAt) {
  $visibilityWhere .= " AND i.created_at <= ?";
  $bindTypes .= "s";
  $bindVals[] = $asOf;
}
if (!$hasPArchivedAt && $hasArchivedAt) {
  $visibilityWhere .= " AND (i.archived_at IS NULL OR i.archived_at > ?)";
  $bindTypes .= "s";
  $bindVals[] = $asOf;
}
if (!$hasPArchived) {
  // if products.archived doesn't exist, fall back to inventory flag if present
  if ($res = $conn->query("SHOW COLUMNS FROM inventory LIKE 'archived'")) {
    if ($res->num_rows > 0) $visibilityWhere .= " AND i.archived = 0";
    $res->free();
  }
}


$baseSql = "
  SELECT
    COUNT(*) AS totalProducts,
    SUM(CASE WHEN i.stock = 0 THEN 1 ELSE 0 END) AS outOfStocks,
    SUM(CASE 
          WHEN i.stock > 0 
           AND i.stock <= GREATEST(COALESCE(p.critical_point, 0), 0)
           AND GREATEST(COALESCE(p.critical_point, 0), 0) > 0
        THEN 1 ELSE 0 END) AS lowStocks
  FROM inventory i
  JOIN products p ON p.product_id = i.product_id
  WHERE $visibilityWhere
";

$types = $bindTypes;
$vals  = $bindVals;

// branch scope
if ($role === 'staff' || $role === 'stockman') {
    $baseSql .= " AND i.branch_id = ?";
    $types   .= "i";
    $vals[]   = (int)$branch_id;
} elseif (!empty($filterBranch)) {
    $baseSql .= " AND i.branch_id = ?";
    $types   .= "i";
    $vals[]   = (int)$filterBranch;
}

$stmt = $conn->prepare($baseSql);
if ($types !== "") { $stmt->bind_param($types, ...$vals); }
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc() ?: ['totalProducts'=>0,'outOfStocks'=>0,'lowStocks'=>0];
$stmt->close();

$totalProducts = (int)$row['totalProducts'];
$outOfStocks   = (int)$row['outOfStocks'];
$lowStocks     = (int)$row['lowStocks'];



// Total Sales (sum only within selected period and (optionally) branch) — half-open range
if ($role === 'staff') {
    $stmt = $conn->prepare("
        SELECT IFNULL(SUM(total), 0) AS total_sales
        FROM sales
        WHERE branch_id = ? AND sale_date >= ? AND sale_date < ?
    ");
    $stmt->bind_param("iss", $branch_id, $salesFrom, $salesTo);
} else {
    if (!empty($filterBranch)) {
        $stmt = $conn->prepare("
            SELECT IFNULL(SUM(total), 0) AS total_sales
            FROM sales
            WHERE branch_id = ? AND sale_date >= ? AND sale_date < ?
        ");
        $stmt->bind_param("iss", $filterBranch, $salesFrom, $salesTo);
    } else {
        $stmt = $conn->prepare("
            SELECT IFNULL(SUM(total), 0) AS total_sales
            FROM sales
            WHERE sale_date >= ? AND sale_date < ?
        ");
        $stmt->bind_param("ss", $salesFrom, $salesTo);
    }
}

$stmt->execute();
$result = $stmt->get_result();
$totalSales = $result->fetch_assoc()['total_sales'] ?? 0;


// Fetch fast moving products
$WINDOW_DAYS = (new DateTime($resolvedStart))->diff(new DateTime($resolvedEnd))->days + 1;
// ----- Tunable thresholds (fixed) -----
$FAST_MIN_QTY = 6; // Fast = sold 6 or more in the period
$SLOW_MAX_QTY = 5; // Slow = sold 1..5 in the period


// FAST
$fastSql = "
SELECT p.product_name, SUM(si.quantity) AS total_qty, si.product_id
FROM sales_items si
JOIN products p ON si.product_id = p.product_id
JOIN sales s    ON si.sale_id = s.sale_id
WHERE s.sale_date >= ? AND s.sale_date < ?
" . (!empty($filterBranch) ? " AND s.branch_id = ?" : ($role === 'staff' ? " AND s.branch_id = ?" : "")) . "
GROUP BY si.product_id
HAVING SUM(si.quantity) >= ?
ORDER BY total_qty DESC
LIMIT 5
";

if ($role === 'staff' || !empty($filterBranch)) {
    $stmt = $conn->prepare($fastSql);
    $b = ($role === 'staff') ? (int)$branch_id : (int)$filterBranch;
    $stmt->bind_param("ssii", $salesFrom, $salesTo, $b, $FAST_MIN_QTY);
} else {
    $stmt = $conn->prepare($fastSql);
    $stmt->bind_param("ssi", $salesFrom, $salesTo, $FAST_MIN_QTY);
}

$stmt->execute();
$fastMovingResult = $stmt->get_result();

$fastMovingProductIds = [];
$fastItems = [];
while ($row = $fastMovingResult->fetch_assoc()) {
    $fastItems[] = $row;
    $fastMovingProductIds[] = (int)$row['product_id'];
}
$excludeFastIds = !empty($fastMovingProductIds) ? implode(',', $fastMovingProductIds) : '0';


// === Slow Moving Items (sold > 0 in selected period, not in fast list, branch-aware) ===
$branchJoin = '';
$params = [$salesFrom, $salesTo];
$types  = 'ss';

if ($role === 'staff') {
    $branchJoin = " AND s.branch_id = ? ";
    $params[] = (int)$branch_id; 
    $types   .= 'i';
} elseif (!empty($filterBranch)) {
    $branchJoin = " AND s.branch_id = ? ";
    $params[] = (int)$filterBranch; 
    $types   .= 'i';
}

// ---- Slow Moving: sold in period but below fast threshold ----
// SLOW = sold between 1 and SLOW_MAX_QTY (inclusive)
$slowSql = "
  SELECT 
    p.product_id,
    p.product_name,
    SUM(si.quantity) AS total_qty
  FROM sales s
  JOIN sales_items si ON si.sale_id = s.sale_id
  JOIN products p     ON p.product_id = si.product_id
  WHERE s.sale_date >= ? AND s.sale_date < ?
";

$slowTypes  = 'ss';
$slowParams = [$salesFrom, $salesTo];

if ($role === 'staff') {
  $slowSql .= " AND s.branch_id = ? ";
  $slowTypes .= 'i';
  $slowParams[] = (int)$branch_id;
} elseif (!empty($filterBranch)) {
  $slowSql .= " AND s.branch_id = ? ";
  $slowTypes .= 'i';
  $slowParams[] = (int)$filterBranch;
}

$slowSql .= "
  GROUP BY p.product_id, p.product_name
  HAVING SUM(si.quantity) BETWEEN 1 AND ?
  ORDER BY total_qty ASC, p.product_name
  LIMIT 5
";

$slowTypes .= 'i';
$slowParams[] = $SLOW_MAX_QTY;

$stmt = $conn->prepare($slowSql);
$stmt->bind_param($slowTypes, ...$slowParams);
$stmt->execute();
$slowMovingResult = $stmt->get_result();

$slowItems = [];
while ($row = $slowMovingResult->fetch_assoc()) {
  $slowItems[] = $row;
}



$nonSql = "
SELECT DISTINCT p.product_id, p.product_name
FROM inventory i
JOIN products p ON p.product_id = i.product_id
LEFT JOIN (
    SELECT si.product_id
    FROM sales_items si
    JOIN sales s ON s.sale_id = si.sale_id
    WHERE s.sale_date >= ? AND s.sale_date < ?
";

// --- sales branch filter ---
if ($role === 'staff') {
    $nonSql .= " AND s.branch_id = ? ";
} elseif (!empty($filterBranch)) {
    $nonSql .= " AND s.branch_id = ? ";
}

$nonSql .= "
) sold ON sold.product_id = i.product_id
WHERE $visibilityWhere
  AND sold.product_id IS NULL
";

// --- inventory branch filter ---
if ($role === 'staff') {
    $nonSql .= " AND i.branch_id = ? ";
} elseif (!empty($filterBranch)) {
    $nonSql .= " AND i.branch_id = ? ";
}

$nonSql .= " ORDER BY p.product_name";

// --------------------
// Build bindings
// --------------------
$nonTypes = "";
$nonVals  = [];

// always 2 for from/to
$nonTypes .= "ss";
$nonVals[] = $salesFrom;
$nonVals[] = $salesTo;

// optional sales branch
if ($role === 'staff') {
    $nonTypes .= "i"; $nonVals[] = (int)$branch_id;
} elseif (!empty($filterBranch)) {
    $nonTypes .= "i"; $nonVals[] = (int)$filterBranch;
}

// visibility binds (created_at / archived_at)
$nonTypes .= $bindTypes;
$nonVals   = array_merge($nonVals, $bindVals);

// optional inventory branch
if ($role === 'staff') {
    $nonTypes .= "i"; $nonVals[] = (int)$branch_id;
} elseif (!empty($filterBranch)) {
    $nonTypes .= "i"; $nonVals[] = (int)$filterBranch;
}

$stmt = $conn->prepare($nonSql);
$stmt->bind_param($nonTypes, ...$nonVals);
$stmt->execute();
$res = $stmt->get_result();

$notMovingItems = [];
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $notMovingItems[] = $row['product_name']; // or ['id'=>$row['product_id'], 'name'=>$row['product_name']]
  }
}
$stmt->close(); // optional but good practice


// Notifications (Pending Approvals)
$pending = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'")->fetch_assoc()['pending'];

$pendingTransfers = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pendingTransfers = (int)($row['pending'] ?? 0);
    }
}

$pendingStockIns = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM stock_in_requests WHERE status='pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pendingStockIns = (int)($row['pending'] ?? 0);
    }
}

$pendingTotalInventory = $pendingTransfers + $pendingStockIns;


// -------------------- FILTERS --------------------
$filters = [
    'fiscal_year' => $_GET['fiscal_year'] ?? '',
    'month'       => $_GET['month'] ?? '',
    'as_of_date'  => $_GET['as_of_date'] ?? date('Y-m-d'),
    'branch_id'   => $_GET['branch_id'] ?? $branch_id,
];

$years = range(date('Y') - 5, date('Y')); // last 5 years
// -------------------- DATE RANGE HELPER --------------------
function getDateRange($type, $value = '') {
    switch ($type) {
        case 'fiscal_year':
            if ($value) {
                $start = $value . '-01-01';
                $end   = $value . '-12-31';
            } else {
                $start = date('Y') . '-01-01';
                $end   = date('Y') . '-12-31';
            }
            break;

        case 'month':
            if ($value) {
                $start = $value . '-01'; // e.g. 2025-09
            } else {
                $start = date('Y-m') . '-01';
            }
            $end = date("Y-m-t", strtotime($start));
            break;

        case 'as_of_date':
            $date  = $value ?: date('Y-m-d');
            $start = $date . ' 00:00:00';
            $end   = $date . ' 23:59:59';
            break;

        default: // current month (fallback)
            $start = date('Y-m-01');
            $end   = date('Y-m-t');
            break;
    }
    return [$start, $end];
}

// -------------------- DATE RANGE --------------------
// NOTE: We intentionally DO NOT overwrite the sales snapshot.
// This block is kept for inventory/as-of computations only.
if (!empty($filters['fiscal_year'])) {
    [$startDate, $endDate] = getDateRange('fiscal_year', $filters['fiscal_year']);
} elseif (!empty($filters['month'])) {
    [$startDate, $endDate] = getDateRange('month', $filters['month']);
} else {
    [$startDate, $endDate] = getDateRange('month'); // default current month
}

[, $asOfDateEnd] = getDateRange('as_of_date', $filters['as_of_date']);


// -------------------- HELPER FUNCTIONS --------------------
function branchCondition($branchId, $alias = '') {
    if (!$branchId) return '';
    $prefix = $alias ? $alias . '.' : '';
    return " AND {$prefix}branch_id=" . intval($branchId);
}

function getInventoryCount($conn, $asOfDate, $branchId = null, $mode = 'total') {
    $sql = "SELECT COUNT(*) AS count FROM inventory i";
    if ($mode !== 'total') $sql .= " JOIN products p ON i.product_id = p.product_id";

    $sql .= " WHERE (i.archived = 0 OR (i.archived = 1 AND i.archived_at > ?))";

    if ($mode === 'low') $sql .= " AND i.stock <= p.critical_point";
    if ($mode === 'out') $sql .= " AND i.stock = 0";

    if ($branchId) $sql .= branchCondition($branchId);

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $asOfDate);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();
    return $count;
}

function getTotalSales($conn, $startDate, $endDate, $branchId = null) {
    // Normalize inside as well to accept raw Y-m-d inputs
    $from = (new DateTime($startDate))->format('Y-m-d 00:00:00');
    $to   = (new DateTime($endDate))->modify('+1 day')->format('Y-m-d 00:00:00');

    $sql = "SELECT IFNULL(SUM(total),0) AS total_sales FROM sales
            WHERE sale_date >= ? AND sale_date < ?";
    if ($branchId) $sql .= branchCondition($branchId);

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total_sales'] ?? 0;
    $stmt->close();
    return $total;
}
function getFastItemIds($conn, $start, $end, $branchId = null) {
    $sql = "
        SELECT p.product_id
        FROM products p
        LEFT JOIN sales_items si ON si.product_id = p.product_id
        LEFT JOIN sales s ON s.sale_id = si.sale_id
        WHERE s.sale_date >= ? AND s.sale_date < ?
        " . ($branchId ? "AND s.branch_id = ?" : "") . "
        GROUP BY p.product_id
        HAVING SUM(si.quantity) >= 3
    ";
    $stmt = $conn->prepare($sql);
    if ($branchId) {
        $stmt->bind_param("ssi", $start, $end, $branchId);
    } else {
        $stmt->bind_param("ss", $start, $end);
    }
    $stmt->execute();
    $ids = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'product_id');
    $stmt->close();
    return $ids;
}

function getMovingItems($conn, $start, $end, $branchId = null, $type = 'fast', $limit = 5) {
    $excludeFastIds = '0';

    if ($type === 'slow' || $type === 'notmoving') {
        $fastIds = getFastItemIds($conn, $start, $end, $branchId);
        $excludeFastIds = !empty($fastIds) ? implode(',', array_map('intval', $fastIds)) : '0';
    }

    switch ($type) {
        case 'fast': 
            $order = "DESC"; 
            $having = "HAVING SUM(si.quantity) >= 3"; 
            break;
        case 'slow': 
            $order = "ASC"; 
            $having = "HAVING SUM(si.quantity) > 0"; 
            break;
        case 'notmoving': 
            $order = "ASC"; 
            $having = ""; 
            break;
    }

    $notInClause = ($type === 'slow' || $type === 'notmoving')
        ? " AND p.product_id NOT IN ($excludeFastIds)" : "";

    if ($type === 'notmoving') {
        $sql = "
            SELECT p.product_id, p.product_name, COALESCE(SUM(si.quantity),0) AS total_qty
            FROM products p
            LEFT JOIN sales_items si ON si.product_id = p.product_id
            LEFT JOIN sales s 
                ON s.sale_id = si.sale_id 
                AND s.sale_date >= ? AND s.sale_date < ?
                " . ($branchId ? "AND s.branch_id = ?" : "") . "
            WHERE 1 $notInClause
            GROUP BY p.product_id
            ORDER BY total_qty ASC
            LIMIT $limit
        ";
    } else {
        $sql = "
            SELECT p.product_id, p.product_name, COALESCE(SUM(si.quantity),0) AS total_qty
            FROM products p
            LEFT JOIN sales_items si ON si.product_id = p.product_id
            LEFT JOIN sales s ON s.sale_id = si.sale_id
            WHERE s.sale_date >= ? AND s.sale_date < ? $notInClause
            " . ($branchId ? "AND s.branch_id = ?" : "") . "
            GROUP BY p.product_id
            $having
            ORDER BY total_qty $order
            LIMIT $limit
        ";
    }

    $stmt = $conn->prepare($sql);
    if ($branchId) {
        $stmt->bind_param("ssi", $start, $end, $branchId);
    } else {
        $stmt->bind_param("ss", $start, $end);
    }
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $items;
}

// -------------------- DASHBOARD DATA --------------------
$totalProducts  = getInventoryCount($conn, $asOfDateEnd, $filters['branch_id'], 'total');
$lowStocks      = getInventoryCount($conn, $asOfDateEnd, $filters['branch_id'], 'low');
$outOfStocks    = getInventoryCount($conn, $asOfDateEnd, $filters['branch_id'], 'out');

$totalSales     = getTotalSales($conn, $resolvedStart, $resolvedEnd, $filters['branch_id']);
$fastItems      = getMovingItems($conn, $salesFrom, $salesTo, $filters['branch_id'], 'fast', 5);
$slowItems      = getMovingItems($conn, $salesFrom, $salesTo, $filters['branch_id'], 'slow', 5);
$notMovingItems = getMovingItems($conn, $salesFrom, $salesTo, $filters['branch_id'], 'notmoving', 5);

// -------------------- NOTIFICATIONS --------------------
$pending = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'")->fetch_assoc()['pending'] ?? 0;

// -------------------- SERVICE JOBS --------------------
$serviceJobQuery = "
    SELECT s.service_name, COUNT(*) as count
    FROM sales_services ss
    JOIN services s ON ss.service_id = s.service_id
    JOIN sales sa ON ss.sale_id = sa.sale_id
    WHERE sa.sale_date >= ? AND sa.sale_date < ?".branchCondition($filters['branch_id'],'sa')."
    GROUP BY s.service_name
    ORDER BY count DESC
";
$stmt = $conn->prepare($serviceJobQuery);
$stmt->bind_param("ss",$salesFrom,$salesTo);
$stmt->execute();
$serviceJobData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
if(empty($serviceJobData)) $serviceJobData[]=['service_name'=>'No Services Rendered','count'=>0];


// Fetch current user's full name
$currentName = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($fetchedName);
    if ($stmt->fetch()) {
        $currentName = $fetchedName;
    }
    $stmt->close();
}

function fmt_date_label(string $ymd): string {
  $dt = DateTime::createFromFormat('Y-m-d', $ymd);
  return $dt ? $dt->format('M d, Y') : htmlspecialchars($ymd, ENT_QUOTES);
}

$salesTitleBase = ($mode === 'fiscal')
  ? 'Fiscal Year Sales Overview'
  : (($mode === 'range') ? 'From–To Sales Overview' : 'Monthly Sales Overview');

if ($mode === 'month') {
  // e.g., "September 2025"
  $salesDetail = date('F Y', strtotime($resolvedStart));
} elseif ($mode === 'range') {
  $salesDetail = fmt_date_label($resolvedStart) . ' to ' . fmt_date_label($resolvedEnd);
} else { // fiscal
  $fy = (int)($_GET['fy'] ?? date('Y'));
  $salesDetail = 'FY ' . $fy;
}

$salesTitle = $salesTitleBase . ' — ' . $salesDetail;
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<?php $pageTitle =''; ?>
<title><?= htmlspecialchars("RP Habana — $pageTitle") ?><?= strtoupper($role) ?> Dashboard</title>
<link rel="icon" href="img/R.P.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/dashboard.css?v=<?= filemtime('css/dashboard.css') ?>">
<link rel="stylesheet" href="css/notifications.css">
<link rel="stylesheet" href="css/sidebar.css">
<audio id="notifSound" src="img/notif.mp3" preload="auto"></audio>
</head>
<body class="dashboard-page">

<!-- Sidebar -->
<div class="sidebar" id="mainSidebar">
  <!-- Toggle button always visible on the rail -->
  <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="false">
    <i class="fas fa-bars" aria-hidden="true"></i>
  </button>

  <!-- Wrap existing sidebar content so we can hide/show it cleanly -->
  <div class="sidebar-content">
    <h2 class="user-heading">
      <span class="role"><?= htmlspecialchars(strtoupper($role), ENT_QUOTES) ?></span>
      <?php if ($currentName !== ''): ?>
        <span class="name">(<?= htmlspecialchars($currentName, ENT_QUOTES) ?>)</span>
      <?php endif; ?>
      <span class="notif-wrapper">
        <i class="fas fa-bell" id="notifBell"></i>
        <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>><?= (int)$pending ?></span>
      </span>
    </h2>

        <!-- Common -->
    <a href="dashboard.php" class="active"><i class="fas fa-tv"></i> Dashboard</a>

    <?php
// put this once before the sidebar (top of file is fine)
$self = strtolower(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
$isArchive = substr($self, 0, 7) === 'archive'; // matches archive.php, archive_view.php, etc.
$invOpen   = in_array($self, ['inventory.php','physical_inventory.php'], true);
$toolsOpen = ($self === 'backup_admin.php' || $isArchive);
?>

<!-- Admin Links -->
<?php if ($role === 'admin'): ?>

  <!-- Inventory group (unchanged) -->
<div class="menu-group has-sub">
  <button class="menu-toggle" type="button" aria-expanded="<?= $invOpen ? 'true' : 'false' ?>">
  <span><i class="fas fa-box"></i> Inventory
    <?php if ($pendingTotalInventory > 0): ?>
      <span class="badge-pending"><?= $pendingTotalInventory ?></span>
    <?php endif; ?>
  </span>
    <i class="fas fa-chevron-right caret"></i>
  </button>
  <div class="submenu" <?= $invOpen ? '' : 'hidden' ?>>
    <a href="inventory.php#pending-requests" class="<?= $self === 'inventory.php#pending-requests' ? 'active' : '' ?>">
      <i class="fas fa-list"></i> Inventory List
        <?php if ($pendingTotalInventory > 0): ?>
          <span class="badge-pending"><?= $pendingTotalInventory ?></span>
        <?php endif; ?>
    </a>
    <a href="physical_inventory.php" class="<?= $self === 'physical_inventory.php' ? 'active' : '' ?>">
      <i class="fas fa-warehouse"></i> Physical Inventory
    </a>
        <a href="barcode-print.php<?php 
        $b = (int)($_SESSION['current_branch_id'] ?? 0);
        echo $b ? ('?branch='.$b) : '';?>" class="<?= $self === 'barcode-print.php' ? 'active' : '' ?>">
        <i class="fas fa-barcode"></i> Barcode Labels
    </a>
  </div>
</div>

    <a href="services.php" class="<?= $self === 'services.php' ? 'active' : '' ?>">
      <i class="fa fa-wrench" aria-hidden="true"></i> Services
    </a>

  <!-- Sales (normal link with active state) -->
  <a href="sales.php" class="<?= $self === 'sales.php' ? 'active' : '' ?>">
    <i class="fas fa-receipt"></i> Sales
  </a>


<a href="accounts.php" class="<?= $self === 'accounts.php' ? 'active' : '' ?>">
  <i class="fas fa-users"></i> Accounts & Branches
</a>

  <!-- NEW: Backup & Restore group with Archive inside -->
  <div class="menu-group has-sub">
    <button class="menu-toggle" type="button" aria-expanded="<?= $toolsOpen ? 'true' : 'false' ?>">
      <span><i class="fas fa-screwdriver-wrench me-2"></i> Data Tools</span>
      <i class="fas fa-chevron-right caret"></i>
    </button>
    <div class="submenu" <?= $toolsOpen ? '' : 'hidden' ?>>
      <a href="/config/admin/backup_admin.php" class="<?= $self === 'backup_admin.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-database"></i> Backup & Restore
      </a>
      <a href="archive.php" class="<?= $isArchive ? 'active' : '' ?>">
        <i class="fas fa-archive"></i> Archive
      </a>
    </div>
  </div>

  <a href="logs.php" class="<?= $self === 'logs.php' ? 'active' : '' ?>">
    <i class="fas fa-file-alt"></i> Logs
  </a>

<?php endif; ?>



   <!-- Stockman Links -->
  <?php if ($role === 'stockman'): ?>
    <div class="menu-group has-sub">
      <button class="menu-toggle" type="button" aria-expanded="<?= $invOpen ? 'true' : 'false' ?>">
        <span><i class="fas fa-box"></i> Inventory</span>
        <i class="fas fa-chevron-right caret"></i>
      </button>
      <div class="submenu" <?= $invOpen ? '' : 'hidden' ?>>
        <a href="inventory.php" class="<?= $self === 'inventory.php' ? 'active' : '' ?>">
          <i class="fas fa-list"></i> Inventory List
        </a>
        <a href="physical_inventory.php" class="<?= $self === 'physical_inventory.php' ? 'active' : '' ?>">
          <i class="fas fa-warehouse"></i> Physical Inventory
        </a>
        <!-- Stockman can access Barcode Labels; server forces their branch -->
        <a href="barcode-print.php" class="<?= $self === 'barcode-print.php' ? 'active' : '' ?>">
          <i class="fas fa-barcode"></i> Barcode Labels
        </a>
      </div>
    </div>
  <?php endif; ?>
    <!-- Staff Links -->
    <?php if ($role === 'staff'): ?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
  </div>
</div>

<div class="content">
    <div class="Report">
    <!-- FILTER FORM (UPDATED TO SUPPORT PERIOD) -->
    <form method="GET" id="reportFilters" style="display:flex; gap:8px; flex-wrap:wrap; align-items:end">
        <div>
            <label>Period</label>
            <select name="period" id="period">
                <option value="month"  <?= ($mode==='month' ? 'selected' : '') ?>>By Month</option>
                <option value="range"  <?= ($mode==='range' ? 'selected' : '') ?>>From–To</option>
                <option value="fiscal" <?= ($mode==='fiscal'? 'selected' : '') ?>>Fiscal Year</option>
            </select>
        </div>

        <div data-period="month">
            <label for="month">View Reports for:</label>
            <input type="month" id="month" name="month" value="<?= htmlspecialchars($_GET['month'] ?? date('Y-m')) ?>">
        </div>

        <div data-period="range" style="display:none">
            <label for="from">From</label>
            <input type="date" id="from" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
            <label for="to">To</label>
            <input type="date" id="to" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>">
        </div>

        <div data-period="fiscal" style="display:none">
            <label for="fy">Fiscal Year</label>
            <input type="number" id="fy" name="fy" min="2000" value="<?= htmlspecialchars($_GET['fy'] ?? date('Y')) ?>">
        </div>

    <div>
        <label for="branch">Branch:</label>
        <?php if ($role === 'stockman' || $role === 'staff'): 
            $branchData = $conn->query("SELECT branch_name FROM branches WHERE branch_id = $branch_id")->fetch_assoc();
            $branchName = $branchData ? htmlspecialchars($branchData['branch_name']) : 'Your Branch';
        ?>
            <input type="hidden" name="branch_id" value="<?= $branch_id ?>">
            <select id="branch" name="branch_id" disabled>
                <option value="<?= $branch_id ?>" selected><?= $branchName ?></option>
            </select>
        <?php else: 
            $branches = $conn->query("SELECT branch_id, branch_name FROM branches");
        ?>
            <select id="branch" name="branch_id">
                <option value="">All Branches</option>
                <?php while ($b = $branches->fetch_assoc()): ?>
                    <option value="<?= $b['branch_id'] ?>" <?= (isset($_GET['branch_id']) && $_GET['branch_id'] == $b['branch_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['branch_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        <?php endif; ?>
    </div>

        <button type="submit">Filter</button>
    </form>
</div>

    <!-- Summary Cards -->
    <div class="cards">
        <div class="card green"><h3>Total Products<span style="font-weight:400"><?= htmlspecialchars($inventoryLabel) ?></span></h3><p><?= $totalProducts ?></p></div>
        <div class="card orange"><h3>Low Stocks<span style="font-weight:400"><?= htmlspecialchars($inventoryLabel) ?></span></h3><p><?= $lowStocks ?></p></div>
        <div class="card red"><h3>Out of Stocks<span style="font-weight:400"><?= htmlspecialchars($inventoryLabel) ?></span></h3><p><?= $outOfStocks ?></p></div>
        <div class="card blue"><h3>Total Sales<span style="font-weight:400"><?= htmlspecialchars($periodLabel) ?></span></h3><p>₱<?= number_format($totalSales,2) ?></p></div>
    </div>

<div class="sections" style="display:flex; gap:20px; flex-wrap:wrap; align-items:flex-start;">
    <!-- Monthly Sales Overview -->
    <section style="flex:1 1 250px; min-width:150px;">
        <h2 id="salesOverviewTitle"><?= htmlspecialchars($salesTitle, ENT_QUOTES) ?></h2>
        <canvas id="salesChart" style="width:100%; height:150px;"></canvas>
    </section>

    <!-- Service Jobs -->
    <section style="flex:1 1 250px; min-width:200px;">
        <h2>Service Jobs</h2>
        <canvas id="serviceJobChart" style="width:100%; height:150px;"></canvas>
    </section>

</div>


<div class="dashboard-page bottom flex-sections">
    <!-- Fast Moving Items -->
    <section class="fast-moving">
        <h2>Fast Moving Items</h2>
        <div class="scrollable-list">
            <ul>
                <?php 
                $maxQty = max(array_column($fastItems, 'total_qty') ?: [0]);
                foreach ($fastItems as $item):
                    $percentage = ($maxQty > 0) ? ($item['total_qty'] / $maxQty) * 100 : 0;
                ?>
                <li class="item-card">
                    <div class="item-row">
                        <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                        <span class="item-qty"><?= $item['total_qty'] ?> sold</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width:<?= round($percentage) ?>%; background: #28a745;"></div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- Slow Moving Items -->
    <section class="slow-moving">
        <h2>Slow Moving Items</h2>
        <div class="scrollable-list">
            <ul>
                <?php 
                $slowMax = max(array_column($slowItems, 'total_qty') ?: [0]);
                foreach ($slowItems as $item):
                    $percentage = ($slowMax > 0) ? ($item['total_qty'] / $slowMax) * 100 : 0;
                ?>
                <li class="item-card">
                    <div class="item-row">
                        <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                        <span class="item-qty"><?= $item['total_qty'] ?> sold</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width:<?= round($percentage) ?>%; background: #ffc107;"></div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

  <!-- Not Moving Items -->
<section class="not-moving">
    <h2>Not Moving Items</h2>
    <div class="scrollable-list">
        <ul>
            <?php if (!empty($notMovingItems)): ?>
                <?php foreach ($notMovingItems as $item): ?>
                    <li class="item-card">
                        <div class="item-row">
                            <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                            <span class="item-qty"><?= htmlspecialchars($item['total_qty']) ?> sold</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress" style="width:<?= intval($item['total_qty']) ?>%; background: #dc3545;"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="item-card">No items found.</li>
            <?php endif; ?>
        </ul>
    </div>
</section>


<!-- NOTIFICATIONS -->
<script src="notifications.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Fetch Sales for Chart -->
<script>
const periodSel = document.getElementById('period');
const branchEl  = document.getElementById('branch');

// Robust branch value: if the select is disabled/not present, fall back to PHP session
const branchVal = (branchEl && branchEl.value !== undefined && branchEl.value !== '')
  ? branchEl.value
  : <?= json_encode(($role === 'staff' || $role === 'stockman') ? (string)$branch_id : (string)($_GET['branch_id'] ?? '')) ?>;

const period = periodSel.value;

let qs = new URLSearchParams({ period });

// Only include branch_id if we actually have one (admin may choose “All Branches” = empty)
if (branchVal !== '') qs.set('branch_id', branchVal);

if (period === 'month') {
  qs.set('month', document.getElementById('month').value);
} else if (period === 'range') {
  qs.set('from', document.getElementById('from').value);
  qs.set('to',   document.getElementById('to').value);
} else if (period === 'fiscal') {
  qs.set('fy', document.getElementById('fy').value);
}

// cache-buster so old data isn’t reused by the browser/CDN
qs.set('_', Date.now());

fetch(`monthly_sale.php?${qs.toString()}`)
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
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
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
console.log(<?= json_encode($serviceJobData) ?>);
</script>

<script>
const serviceJobData = <?= json_encode($serviceJobData) ?> || [];

if (serviceJobData.length > 0) {
    const ctx = document.getElementById('serviceJobChart').getContext('2d');
    ctx.canvas.height = serviceJobData.length * 50; // adjust height

    const labels = serviceJobData.map(item => item.service_name);
    const data = serviceJobData.map(item => item.count || 0);

    const colors = labels.map(() => `hsl(${Math.floor(Math.random()*360)}, 70%, 60%)`);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Services',
                data: data,
                backgroundColor: colors
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true },
                y: { ticks: { autoSkip: false } }
            }
        }
    });
}
</script>

<script>
// Show/hide period-specific inputs
(function(){
  const periodSel = document.getElementById('period');
  const sections = document.querySelectorAll('[data-period]');
  function sync() {
    const val = periodSel.value;
    sections.forEach(sec => {
      sec.style.display = (sec.getAttribute('data-period') === val) ? '' : 'none';
    });
  }
  periodSel.addEventListener('change', sync);
  sync(); // initial
})();
</script>

<script>
(function(){
  const groups = document.querySelectorAll('.menu-group.has-sub');

  groups.forEach((g, idx) => {
    const btn = g.querySelector('.menu-toggle');
    const panel = g.querySelector('.submenu');
    if (!btn || !panel) return;

    // Optional: restore last state from localStorage
    const key = 'sidebar-sub-' + idx;
    const saved = localStorage.getItem(key);
    if (saved === 'open') {
      btn.setAttribute('aria-expanded', 'true');
      panel.hidden = false;
    }

    btn.addEventListener('click', () => {
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!expanded));
      panel.hidden = expanded;
      localStorage.setItem(key, expanded ? 'closed' : 'open');
    });
  });
})();
</script>
<!-- For Responsive Chart -->
<script>
(function() {
  const form     = document.getElementById('reportFilters');
  const titleEl  = document.getElementById('salesOverviewTitle');
  const periodEl = document.getElementById('period');
  const monthEl  = document.getElementById('month');
  const fromEl   = document.getElementById('from');
  const toEl     = document.getElementById('to');
  const fyEl     = document.getElementById('fy');

  function updateTitle() {
    const period = periodEl.value;
    let title = '';

    if (period === 'month') {
      const val = monthEl.value || '';
      const monthLabel = val ? new Date(val + "-01").toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : '';
      title = "Monthly Sales Overview" + (monthLabel ? ` — ${monthLabel}` : '');
    } else if (period === 'range') {
      const from = fromEl.value;
      const to   = toEl.value;
      title = "From–To Sales Overview";
      if (from && to) {
        title += ` — ${from} to ${to}`;
      }
    } else if (period === 'fiscal') {
      const fy = fyEl.value || new Date().getFullYear();
      title = `Fiscal Year Sales Overview — FY ${fy}`;
    }

    titleEl.textContent = title;
  }

  // Only update when the Filter form is submitted
  if (form) {
    form.addEventListener('submit', function(e) {
      updateTitle();
      // allow normal form submit + reload
    });
  }
})();
</script>

<script src="sidebar.js"></script>

</body>
</html>
