<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}
include 'config/db.php';
include 'functions.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$branch_id = $_SESSION['branch_id'] ?? null;

// Pending notifications
$pending = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'");
    $pending = $result ? (int)($result->fetch_assoc()['pending'] ?? 0) : 0;
}

// Branch selection
$branches = $conn->query("SELECT branch_id, branch_name FROM branches");
$selected_branch = ($role === 'admin') ? ($_GET['branch'] ?? $branch_id) : $branch_id;
$branchCondition = " AND i.branch_id = " . intval($selected_branch);

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $role === 'admin') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=physical_inventory_log.csv');
    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, [
        'Product ID',
        'Product Name',
        'Category',
        'System Stock',
        'Physical Count',
        'Discrepancy',
        'Status',
        'Counted By',
        'Branch',
        'Count Date'
    ]);

    // Fetch data
    $queryCSV = "
        SELECT 
            p.product_id,
            p.product_name,
            p.category,
            i.stock AS system_stock,
            COALESCE(pi.physical_count, i.stock) AS physical_count,
            COALESCE(pi.discrepancy, 0) AS discrepancy,
            COALESCE(pi.status, 'Pending') AS status,
            u.username AS counted_by,
            b.branch_name AS branch,
            pi.count_date
        FROM products p
        JOIN inventory i ON p.product_id = i.product_id AND i.branch_id = ".intval($selected_branch)."
        LEFT JOIN (
            SELECT t1.*
            FROM physical_inventory t1
            INNER JOIN (
                SELECT product_id, MAX(count_date) AS latest
                FROM physical_inventory
                WHERE branch_id = ".intval($selected_branch)."
                GROUP BY product_id
            ) t2 ON t1.product_id = t2.product_id AND t1.count_date = t2.latest
        ) pi ON p.product_id = pi.product_id
        LEFT JOIN users u ON pi.counted_by = u.id
        LEFT JOIN branches b ON i.branch_id = b.branch_id
        ORDER BY p.product_name ASC
    ";

    $resultCSV = $conn->query($queryCSV);

    if ($resultCSV && $resultCSV->num_rows > 0) {
        while ($row = $resultCSV->fetch_assoc()) {
            fputcsv($output, $row);
        }
    } else {
        // If no data, you can still write an empty row or leave CSV empty
        fputcsv($output, ['No data found']);
    }

    fclose($output);
    exit;
}


// Fetch inventory data
$query = "
    SELECT 
        p.product_id,
        p.product_name,
        p.category,
        i.stock AS system_stock,
        COALESCE(pi.physical_count, '') AS physical_count,
        COALESCE(pi.discrepancy, 0) AS discrepancy,
        COALESCE(pi.status, 'Pending') AS status
    FROM products p
    JOIN inventory i ON p.product_id = i.product_id AND i.branch_id = " . intval($selected_branch) . "
    LEFT JOIN (
        SELECT t1.*
        FROM physical_inventory t1
        INNER JOIN (
            SELECT product_id, MAX(count_date) AS latest
            FROM physical_inventory
            WHERE branch_id = " . intval($selected_branch) . "
            GROUP BY product_id
        ) t2 ON t1.product_id = t2.product_id AND t1.count_date = t2.latest
    ) pi ON p.product_id = pi.product_id
    ORDER BY p.product_name ASC
";
$result = $conn->query($query);

// Get last saved timestamp for branch
$lastSavedRow = $conn->query("SELECT MAX(count_date) AS last_saved FROM physical_inventory WHERE branch_id = ".intval($selected_branch))->fetch_assoc();
$lastSaved = $lastSavedRow['last_saved'] ?? 'Never';

// Compute inventory stats
$totalProducts = $match = $mismatch = $pendingCount = 0;
while($rowTemp = $result->fetch_assoc()){
    $totalProducts++;
    if($rowTemp['status']==='Match') $match++;
    elseif($rowTemp['status']==='Mismatch') $mismatch++;
    else $pendingCount++;
}
// Reset pointer to reuse $result for table display
$result->data_seek(0);


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Physical Inventory</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/notifications.css">
<link rel="stylesheet" href="css/physical_inventory.css?>v2">
</head>
<body class="inventory-page">

<div class="sidebar">
   <h2>
    <?= strtoupper($role) ?>
    <span class="notif-wrapper">
        <i class="fas fa-bell" id="notifBell"></i>
        <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>>0</span>
    </span>
</h2>


    <!-- Common -->
    <a href="dashboard.php" ><i class="fas fa-tv"></i> Dashboard</a>

    <!-- Admin Links -->
    <?php if ($role === 'admin'): ?>
        <a href="inventory.php" ><i class="fas fa-box"></i> Inventory</a>
        <a href="physical_inventory.php" class="active"><i class="fas fa-warehouse"></i> Physical Inventory</a>
        <a href="sales.php"><i class="fas fa-receipt"></i> Sales</a>
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
     <?php
      $transferNotif = $transferNotif ?? 0; // if not set, default to 0
      ?>
    <?php if ($role === 'stockman'): ?>
        <a href="inventory.php" ><i class="fas fa-box"></i> Inventory
            <?php if ($transferNotif > 0): ?>
                <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;">
                    <?= $transferNotif ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="physical_inventory.php" class="active"><i class="fas fa-warehouse"></i> Physical Inventory</a>
    <?php endif; ?>

    <!-- Staff Links -->
    <?php if ($role === 'staff'): ?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>


<div class="container py-4">
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <!-- Branch Selector Card -->
    <div class="card shadow-sm p-3 flex-grow-1" style="min-width:200px;">
        <label class="fw-bold mb-2">Select Branch:</label>
        <?php if($role==='admin'): ?>
            <form method="GET" class="d-flex gap-2">
                <select name="branch" class="form-select" onchange="this.form.submit()">
                    <?php
                    $branches = $conn->query("SELECT branch_id, branch_name FROM branches");
                    while($b=$branches->fetch_assoc()):
                    ?>
                        <option value="<?= $b['branch_id'] ?>" <?= ($selected_branch==$b['branch_id'])?'selected':'' ?>>
                            <?= htmlspecialchars($b['branch_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        <?php else: ?>
            <input type="text" class="form-control" value="<?= htmlspecialchars($branches->fetch_assoc()['branch_name'] ?? 'Your Branch') ?>" disabled>
        <?php endif; ?>
    </div>

    <!-- Last Saved Card -->
    <div class="card shadow-sm p-3 text-center flex-grow-1" style="min-width:180px; background: linear-gradient(135deg, #6c757d, #343a40); color:white;">
        <i class="fas fa-clock me-2"></i>
        <strong>Last Saved:</strong> <?= $lastSaved ?>
    </div>
</div>

<!-- Stats Cards -->
<div class="d-flex flex-wrap gap-3 mb-3">
    <div class="card shadow-sm p-3 flex-fill text-white" style="background: linear-gradient(135deg,#007bff,#00c6ff);">
        <h6>Total Products <i class="fas fa-box"></i></h6>
        <h4><?= $totalProducts ?></h4>
    </div>
    <div class="card shadow-sm p-3 flex-fill text-white" style="background: linear-gradient(135deg,#28a745,#85e085);">
        <h6>Match <i class="fas fa-check-circle"></i></h6>
        <h4><?= $match ?></h4>
    </div>
    <div class="card shadow-sm p-3 flex-fill text-white" style="background: linear-gradient(135deg,#dc3545,#ff7b7b);">
        <h6>Mismatch <i class="fas fa-exclamation-circle"></i></h6>
        <h4><?= $mismatch ?></h4>
    </div>
    <div class="card shadow-sm p-3 flex-fill text-white" style="background: linear-gradient(135deg,#6c757d,#adb5bd);">
        <h6>Pending <i class="fas fa-hourglass-half"></i></h6>
        <h4><?= $pendingCount ?></h4>
    </div>
</div>

<div class="d-flex gap-2 mb-3 flex-wrap align-items-center shadow-sm p-2 rounded" style="background-color:#f8f9fa; position: sticky; top:0; z-index: 10;">
    <input type="text" id="searchInput" class="form-control w-auto" placeholder="Search products..." onkeyup="filterTable()">
    
    <?php if($role==='admin'): ?>
        <button id="exportBtn" class="btn btn-success">
            <i class="fas fa-file-csv"></i> Export CSV
        </button>
    <?php endif; ?>
    
    <div class="ms-auto fw-bold">
        Total Mismatch: <span id="totalMismatch"><?= $mismatch ?></span>
    </div>
</div>
<!-- TABLE -->
<div class="card shadow-sm mb-4">
  <div class="card-body table-responsive">
    <form id="physicalInventoryForm">
      <input type="hidden" name="branch_id" value="<?= intval($selected_branch) ?>">

      <table class="table table-bordered align-middle" id="inventoryTable">
        <thead class="table-dark sticky-top">
          <tr>
            <th>Product ID</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>System Stock</th>
            <th>Physical Count</th>
            <th>Discrepancy</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row=$result->fetch_assoc()): ?>
          <tr>
            <td>
              <?= $row['product_id'] ?>
              <input type="hidden" name="product_id[]" value="<?= $row['product_id'] ?>">
            </td>
            <td><?= htmlspecialchars($row['product_name']) ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td class="system-stock"><?= $row['system_stock'] ?></td>
            <td>
              <input type="number" class="form-control physical-count"
                     value="<?= htmlspecialchars($row['physical_count']) ?>" min="0"
                     oninput="updateDiscrepancy(this); markChanged(this); updateMismatchCount();">
            </td>
            <td class="discrepancy"><?= $row['discrepancy'] ?></td>
            <td>
              <?php
                $status = $row['status'];
                $badge = ($status==='Match')?'success':(($status==='Mismatch')?'danger':'secondary');
              ?>
              <span class="badge bg-<?= $badge ?>"><?= $status ?></span>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

      <div class="mt-3 d-flex gap-2">
        <button type="button" class="btn btn-primary" onclick="saveChanges()">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>


<script>
// ===== Filter/Search =====
function filterTable() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#physicalInventoryForm tbody tr');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}

// ===== Update Discrepancy & Badge =====
function updateDiscrepancy(input) {
    const row = input.closest('tr');
    const systemStock = parseInt(row.querySelector('.system-stock').innerText) || 0;
    const physicalCount = parseInt(input.value) || 0;
    const discrepancy = physicalCount - systemStock;

    // Update discrepancy cell
    row.querySelector('.discrepancy').innerText = discrepancy;

    // Update status badge
    const badge = row.querySelector('.badge');
    if(input.value === '') {
        badge.innerText = 'Pending';
        badge.className = 'badge bg-secondary';
    } else if(discrepancy === 0) {
        badge.innerText = 'Match';
        badge.className = 'badge bg-success';
    } else {
        badge.innerText = 'Mismatch';
        badge.className = 'badge bg-danger';
    }

    // Highlight row and update mismatch count
    markChanged(input);
    updateMismatchCount();
}

// ===== Highlight Changed Rows =====
function markChanged(input) {
    const row = input.closest('tr');
    row.dataset.changed = 'true';
    row.classList.add('changed'); // Use .changed CSS for background
}

// ===== Update Total Mismatch Count =====
function updateMismatchCount() {
    let total = 0;
    document.querySelectorAll('#inventoryTable tbody tr').forEach(row => {
        if(row.querySelector('.badge').classList.contains('bg-danger')) total++;
    });
    document.getElementById('totalMismatch').innerText = total;
}

// ===== Save Only Changed Rows =====
function saveChanges() {
    const form = document.getElementById('physicalInventoryForm');
    const changedRows = [...form.querySelectorAll('tr[data-changed="true"]')];
    if(changedRows.length === 0){
        alert('No changes detected.');
        return;
    }

    const formData = new FormData();
    formData.append('branch_id', form.querySelector('input[name="branch_id"]').value);

    changedRows.forEach(row => {
        const productId = row.querySelector('input[name="product_id[]"]').value;
        const physicalCount = row.querySelector('input.physical-count').value.trim();
        formData.append(`physical_count[${productId}]`, physicalCount);
    });

    fetch('save_physical_inventory.php', { method:'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            alert(data.message || 'Saved successfully.');
            location.reload();
        })
        .catch(err => console.error(err));
}

// ===== Initialize mismatch count on page load =====
document.addEventListener('DOMContentLoaded', () => {
    updateMismatchCount();
});
</script>


<script>
document.getElementById('exportBtn').addEventListener('click', function() {
    const table = document.getElementById('inventoryTable');
    let csv = [];
    
    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push('"' + th.innerText.trim() + '"');
    });
    csv.push(headers.join(','));

    // Get rows
    table.querySelectorAll('tbody tr').forEach(row => {
        const rowData = [];
        row.querySelectorAll('td').forEach(td => {
            let text = '';

            // If input exists, take its value
            const input = td.querySelector('input');
            if(input) {
                text = input.value;
            } else {
                text = td.innerText.trim();
            }
            // Escape double quotes
            text = text.replace(/"/g, '""');
            rowData.push('"' + text + '"');
        });
        csv.push(rowData.join(','));
    });

    // Create CSV Blob and trigger download
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = 'physical_inventory_current.csv';
    downloadLink.click();
});
</script>
<script src="notifications.js"></script>
</body>
</html>
