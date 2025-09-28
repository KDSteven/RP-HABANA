<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions.php';


// Redirect to login if user not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}


$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$branch_id = $_SESSION['branch_id'] ?? null;

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['op']??'')==='add_stock') {
  require __DIR__.'/add_stock.php';
  exit;
}


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


// Handle current branch selection (from query string or session)
if (isset($_GET['branch'])) {
    $current_branch_id = intval($_GET['branch']);
    $_SESSION['current_branch_id'] = $current_branch_id;
} else {
    $current_branch_id = $_SESSION['current_branch_id'] ?? $branch_id;
}
// Get filters
$branchId = $_GET['branch_id'] ?? null;
$search   = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "
  SELECT i.inventory_id, p.product_id, p.product_name, p.category, 
         p.price, p.markup_price, 
         p.ceiling_point, p.critical_point,
         IFNULL(i.stock, 0) AS stock, i.branch_id
  FROM products p
  LEFT JOIN inventory i 
    ON p.product_id = i.product_id 
";

$params = [];
$types  = [];
$conditions = ["i.archived = 0"];

// Role-based branch filtering
if ($role === 'staff') {
    // Staff always locked to their branch
    $conditions[] = "i.branch_id = ?";
    $params[] = $branch_id; // staff’s session branch_id
    $types[] = "i";
} elseif (!empty($branchId)) {
    // Admin clicked a branch in dropdown/filter
    $conditions[] = "i.branch_id = ?";
    $params[] = $branchId;
    $types[] = "i";
} elseif (!empty($current_branch_id)) {
    // fallback to current branch (like when admin dashboard sets one)
    $conditions[] = "i.branch_id = ?";
    $params[] = $current_branch_id;
    $types[] = "i";
}

// Category filter
if (!empty($category)) {
    $conditions[] = "p.category = ?";
    $params[] = $category;
    $types[] = "s";
}

// Search filter
if (!empty($search)) {
    $conditions[] = "p.product_name LIKE ?";
    $params[] = "%$search%";
    $types[] = "s";
}

// Finalize query
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$stmt = $conn->prepare($sql);

// Bind params only if not empty
if (!empty($params)) {
    $stmt->bind_param(implode("", $types), ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch branches
if ($role === 'staff') {
    $stmt = $conn->prepare("SELECT * FROM branches WHERE branch_id = ?");
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $branches_result = $stmt->get_result();
    $stmt->close();
} else {
    $branches_result = $conn->query("SELECT * FROM branches");
}

// Fetch brands
$brand_result = $conn->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");

$category_result = $conn->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
// Archive product for specific branch
if (isset($_POST['archive_product'])) {
    $inventory_id = (int) $_POST['inventory_id'];

    // Fetch product info from inventory
    $stmt = $conn->prepare("
        SELECT i.inventory_id, i.product_id, p.product_name, i.branch_id
        FROM inventory i
        JOIN products p ON i.product_id = p.product_id
        WHERE i.inventory_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $inventory_id);
    $stmt->execute();
    $stmt->bind_result($inv_id, $product_id, $product_name, $branch_id);

    if ($stmt->fetch()) {
        $stmt->close();

        // 1️⃣ Update inventory as archived
       $stmt = $conn->prepare("UPDATE inventory SET archived = 1, archived_at = NOW() WHERE inventory_id = ?");

        $stmt->bind_param("i", $inventory_id);
        $stmt->execute();
        $stmt->close();

        // 3️⃣ Log action
        logAction($conn, "Archive Product", "Archived product: $product_name (Inventory ID: $inventory_id)", null, $branch_id);

        header("Location: inventory.php?archived=success");
        exit;
    } else {
        $stmt->close();
        echo "Inventory not found!";
    }
}


// Determine current branch for services
$current_branch_id = $_GET['branch'] ?? $_SESSION['current_branch_id'] ?? $branch_id ?? 0;
$_SESSION['current_branch_id'] = $current_branch_id;

// Fetch services for the current branch
$services_stmt = $conn->prepare("
    SELECT service_id, service_name, price, description, branch_id
    FROM services
    WHERE branch_id = ? AND archived = 0
    ORDER BY service_name ASC
");
$services_stmt->bind_param("i", $current_branch_id);
$services_stmt->execute();
$services_result = $services_stmt->get_result();

// Handle archive service
if (isset($_POST['archive_service'])) {
    $service_id = (int) $_POST['service_id'];

    $stmt = $conn->prepare("SELECT service_name, branch_id FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $stmt->bind_result($service_name, $service_branch_id);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE services SET archived = 1 WHERE service_id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $stmt->close();

    logAction($conn, "Archive Service", "Archived service: $service_name (ID: $service_id)", null, $service_branch_id);
    header("Location: inventory.php?archived=service");
    exit;
}

// ===== Add Stock or Stock-In Request (same modal, role decides) =====
// Form posts: op=add_stock, product_id (optional if barcode finds it), branch_id, stock_amount, (optional) remarks
if (isset($_POST['op']) && $_POST['op'] === 'add_stock') {
    $product_id     = (int)($_POST['product_id'] ?? 0);
    $branch_for_form= isset($_POST['branch_id']) && $_POST['branch_id'] !== '' ? (int)$_POST['branch_id'] : 0;
    $qty            = (int)($_POST['stock_amount'] ?? 0);
    $remarks        = trim($_POST['remarks'] ?? '');
    $barcode        = trim($_POST['barcode'] ?? '');

    // 1) Resolve product by barcode if no product_id selected
    if ($product_id <= 0 && $barcode !== '') {
        $stmt = $conn->prepare("SELECT product_id FROM products WHERE barcode = ? LIMIT 1");
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $product_id = (int)$row['product_id'];
        }
        $stmt->close();
    }

    // 2) Validate product and qty
    if ($product_id <= 0 || $qty <= 0) {
        $_SESSION['stock_message'] = "Please select a product (or scan a valid barcode) and enter a quantity.";
        header("Location: inventory.php");
        exit;
    }

    // 3) Compute a safe target branch
    $target_branch = $branch_for_form ?: (int)($_SESSION['current_branch_id'] ?? $_SESSION['branch_id'] ?? 0);
    if ($target_branch <= 0) {
        $_SESSION['stock_message'] = "Missing branch. Please pick a branch tab and try again.";
        header("Location: inventory.php");
        exit;
    }


  if ($role === 'admin') {
    $conn->begin_transaction();
    try {
        // First, get ceiling_point from products and current stock from inventory
        $stmt = $conn->prepare("
            SELECT p.ceiling_point, IFNULL(i.stock, 0) AS stock
            FROM products p
            LEFT JOIN inventory i ON p.product_id = i.product_id AND i.branch_id = ?
            WHERE p.product_id = ?
            FOR UPDATE
        ");
        $stmt->bind_param("ii", $target_branch, $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();

        if (!$res || $res->num_rows === 0) {
            throw new Exception("Product not found.");
        }

        $row = $res->fetch_assoc();
        $current_stock = (int)$row['stock'];
        $ceiling_point = (int)$row['ceiling_point'];
        $final_stock   = $current_stock + $qty;

        // 🚫 Block if final stock exceeds ceiling
        if ($final_stock > $ceiling_point) {
            $conn->rollback();
            $_SESSION['stock_message'] = "❌ Cannot add {$qty} stock. Final stock ({$final_stock}) exceeds ceiling point ({$ceiling_point}).";
            header("Location: inventory.php?stock=exceeded");
            exit;
        }

        // ✅ Safe to add
        if ($current_stock > 0) {
            $stmt = $conn->prepare("UPDATE inventory SET stock = ? WHERE product_id = ? AND branch_id = ?");
            $stmt->bind_param("iii", $final_stock, $product_id, $target_branch);
        } else {
            $stmt = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock, archived) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("iii", $product_id, $target_branch, $qty);
        }
        $stmt->execute();
        $stmt->close();

        // Log action
        $p = $conn->query("SELECT product_name FROM products WHERE product_id = {$product_id}")->fetch_assoc();
        logAction($conn, "Add Stock", "Added {$qty} stocks to {$p['product_name']} (Branch {$target_branch})", null, $target_branch);

        $conn->commit();

        $_SESSION['stock_message'] = "✅ Successfully added {$qty} stock(s). Final stock: {$final_stock} / Ceiling: {$ceiling_point}";
        header("Location: inventory.php?stock=added");
        exit;

    } catch (Throwable $e) {
        $conn->rollback();
        $_SESSION['stock_message'] = "❌ Add stock failed: " . $e->getMessage();
        header("Location: inventory.php");
        exit;
    }

    } else {
        // === STOCKMAN (or staff): create Stock-In Request ===
        $stmt = $conn->prepare("
            INSERT INTO stock_in_requests
                (product_id, branch_id, quantity, remarks, status, requested_by, request_date)
            VALUES (?, ?, ?, ?, 'pending', ?, NOW())
        ");
        $stmt->bind_param("iiisi", $product_id, $target_branch, $qty, $remarks, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        // Log
        $p = $conn->query("SELECT product_name FROM products WHERE product_id = {$product_id}")->fetch_assoc();
        logAction($conn, "Stock-In Request", "Requested stock-in of {$qty} {$p['product_name']} to Branch {$target_branch}");

        $_SESSION['stock_message'] = "Stock-In request submitted!";
        header("Location: inventory.php?sir=requested");
        exit;
    }
}

// ===== Admin: approve/reject Stock-In Requests =====
if ($role === 'admin' && isset($_POST['sir_action'], $_POST['sir_id'])) {
    $sir_action = $_POST['sir_action']; // 'approved' | 'rejected'
    $sir_id     = (int) $_POST['sir_id'];

    if (in_array($sir_action, ['approved', 'rejected'], true)) {

        if ($sir_action === 'approved') {
            $stmt = $conn->prepare("
                SELECT product_id, branch_id, quantity
                FROM stock_in_requests
                WHERE id = ?
            ");
            $stmt->bind_param("i", $sir_id);
            $stmt->execute();
            $req = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($req) {
                $product_id = (int)$req['product_id'];
                $branch_id  = (int)$req['branch_id'];
                $qty        = (int)$req['quantity'];

                $conn->begin_transaction();
                try {
                    $stmt = $conn->prepare("SELECT stock FROM inventory WHERE product_id = ? AND branch_id = ? FOR UPDATE");
                    $stmt->bind_param("ii", $product_id, $branch_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $stmt->close();

                    if ($res && $res->num_rows > 0) {
                        $stmt = $conn->prepare("UPDATE inventory SET stock = stock + ? WHERE product_id = ? AND branch_id = ?");
                        $stmt->bind_param("iii", $qty, $product_id, $branch_id);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock, archived) VALUES (?, ?, ?, 0)");
                        $stmt->bind_param("iii", $product_id, $branch_id, $qty);
                    }
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $conn->prepare("
                        UPDATE stock_in_requests
                        SET status = 'approved', decision_date = NOW(), decided_by = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ii", $_SESSION['user_id'], $sir_id);
                    $stmt->execute();
                    $stmt->close();

                    $conn->commit();
                    header("Location: inventory.php?sir=approved");
                    exit;
                } catch (Throwable $e) {
                    $conn->rollback();
                    $_SESSION['stock_message'] = "Stock-In approval failed.";
                    header("Location: inventory.php?sir=error");
                    exit;
                }
            }
        }

        if ($sir_action === 'rejected') {
            $stmt = $conn->prepare("
                UPDATE stock_in_requests
                SET status = 'rejected', decision_date = NOW(), decided_by = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $_SESSION['user_id'], $sir_id);
            $stmt->execute();
            $stmt->close();

            header("Location: inventory.php?sir=rejected");
            exit;
        }
    }
}



// request transfer log
// if (isset($_POST['transfer_request'])) {
//     $product_id  = (int) $_POST['product_id'];
//     $from_branch = (int) $_POST['from_branch'];
//     $to_branch   = (int) $_POST['to_branch'];
//     $quantity    = (int) $_POST['quantity'];

//     $stmt = $conn->prepare("
//         INSERT INTO transfer_requests (product_id, from_branch, to_branch, quantity, status, requested_by, requested_at)
//         VALUES (?, ?, ?, ?, 'Pending', ?, NOW())
//     ");
//     $stmt->bind_param("iiiis", $product_id, $from_branch, $to_branch, $quantity, $_SESSION['user_id']);
//     $stmt->execute();
//     $stmt->close();

//     $product = $conn->query("SELECT product_name FROM products WHERE product_id = $product_id")->fetch_assoc();

//     logAction(
//         $conn,
//         "Stock Transfer Request",
//         "Requested transfer of $quantity {$product['product_name']} from Branch $from_branch to Branch $to_branch"
//     );

//     $_SESSION['stock_message'] = "Transfer request sent successfully!";
//     header("Location: inventory.php?transfer=requested");
//     exit;
// }


// compute $pendingResets count (put near the query)
$pendingResetsCount = 0;
if ($role === 'admin') {
  $res = $conn->query("SELECT COUNT(*) AS c FROM password_resets WHERE status='pending'");
  $pendingResetsCount = $res ? (int)$res->fetch_assoc()['c'] : 0;
}


//Transfer Request
//request transfer log
if (isset($_POST['transfer_request'])) {
    $product_id       = (int) $_POST['product_id'];
    $source_branch    = (int) $_POST['from_branch'];        // keep incoming field name if your form posts from_branch
    $destination_branch = (int) $_POST['to_branch'];        // keep incoming field name if your form posts to_branch
    $quantity         = (int) $_POST['quantity'];

    // If your front-end already posts source_branch/destination_branch, swap these two lines:
    // $source_branch       = (int) $_POST['source_branch'];
    // $destination_branch  = (int) $_POST['destination_branch'];

    $stmt = $conn->prepare("
        INSERT INTO transfer_requests
            (product_id, source_branch, destination_branch, quantity, status, requested_by, request_date)
        VALUES (?, ?, ?, ?, 'pending', ?, NOW())
    ");
    $stmt->bind_param("iiiis", $product_id, $source_branch, $destination_branch, $quantity, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();

    $product = $conn->query("SELECT product_name FROM products WHERE product_id = $product_id")->fetch_assoc();

    logAction(
        $conn,
        "Stock Transfer Request",
        "Requested transfer of $quantity {$product['product_name']} from Branch $source_branch to Branch $destination_branch"
    );

    $_SESSION['stock_message'] = "Transfer request sent successfully!";
    header("Location: inventory.php?transfer=requested");
    exit;
}

// ===== Admin: approve/reject transfer requests (moved from approvals.php) =====
if ($role === 'admin' && isset($_POST['action'], $_POST['request_id'])) {
    $action     = $_POST['action'];
    $request_id = (int) $_POST['request_id'];

    if (in_array($action, ['approved', 'rejected'], true)) {

        if ($action === 'approved') {
            // Get transfer details
            $stmt = $conn->prepare("
                SELECT product_id, source_branch, destination_branch, quantity
                FROM transfer_requests
                WHERE request_id = ?
            ");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $transfer = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($transfer) {
                $product_id         = (int)$transfer['product_id'];
                $source_branch      = (int)$transfer['source_branch'];
                $destination_branch = (int)$transfer['destination_branch'];
                $quantity           = (int)$transfer['quantity'];

                // Decrease from source
                $stmt = $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE product_id = ? AND branch_id = ?");
                $stmt->bind_param("iii", $quantity, $product_id, $source_branch);
                $stmt->execute();
                $stmt->close();

                // Add to destination (upsert)
                $stmt = $conn->prepare("SELECT stock FROM inventory WHERE product_id = ? AND branch_id = ?");
                $stmt->bind_param("ii", $product_id, $destination_branch);
                $stmt->execute();
                $res = $stmt->get_result();
                $stmt->close();

                if ($res && $res->num_rows > 0) {
                    $stmt = $conn->prepare("UPDATE inventory SET stock = stock + ? WHERE product_id = ? AND branch_id = ?");
                    $stmt->bind_param("iii", $quantity, $product_id, $destination_branch);
                } else {
                    $stmt = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
                    $stmt->bind_param("iii", $product_id, $destination_branch, $quantity);
                }
                $stmt->execute();
                $stmt->close();

                // Set request approved
                $stmt = $conn->prepare("
                  UPDATE transfer_requests
                  SET status = 'approved', decision_date = NOW(), decided_by = ?
                  WHERE request_id = ?
                ");
                $stmt->bind_param("ii", $_SESSION['user_id'], $request_id);
                $stmt->execute();
                $stmt->close();

                header("Location: inventory.php?tr=approved");
                exit;
            }
        }

        if ($action === 'rejected') {
            $stmt = $conn->prepare("
              UPDATE transfer_requests
              SET status = 'rejected', decision_date = NOW(), decided_by = ?
              WHERE request_id = ?
            ");
            $stmt->bind_param("ii", $_SESSION['user_id'], $request_id);
            $stmt->execute();
            $stmt->close();

            header("Location: inventory.php?tr=rejected");
            exit;
        }
    }
}

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

$flag  = $_GET['stock'] ?? '';
$flash = $_SESSION['stock_message'] ?? '';
if ($flag || $flash) {
  // Prefer explicit mapping by flag; fall back to the session message
  $safeFlash = htmlspecialchars($flash, ENT_QUOTES);
  echo "<script>
    document.addEventListener('DOMContentLoaded', function () {
      if ('$flag') {
        showStockToast('$flag', '$safeFlash');
      } else if ('$safeFlash') {
        showToast('$safeFlash', 'info');
      }
    });
  </script>";
  unset($_SESSION['stock_message']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php $pageTitle = 'Inventory'; ?>
<title><?= htmlspecialchars("RP Habana — $pageTitle") ?></title>
<link rel="icon" href="img/R.P.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" >
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
 <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/notifications.css">
  <link rel="stylesheet" href="css/inventory.css?>=v2">
<audio id="notifSound" src="notif.mp3" preload="auto"></audio>


</head>
<body class="inventory-page">
<div class="sidebar">
  <h2 class="user-heading">
    <span class="role"><?= htmlspecialchars(strtoupper($role), ENT_QUOTES) ?></span>
    <?php if ($currentName !== ''): ?>
      <span class="name"> (<?= htmlspecialchars($currentName, ENT_QUOTES) ?>)</span>
    <?php endif; ?>
    <span class="notif-wrapper">
      <i class="fas fa-bell" id="notifBell"></i>
      <span id="notifCount" <?= $pendingTotalInventory > 0 ? '' : 'style="display:none;"' ?>><?= (int)$pendingTotalInventory ?></span>
    </span>
  </h2>


    <!-- Common -->
    <a href="dashboard.php" ><i class="fas fa-tv"></i> Dashboard</a>

    <!-- Admin Links -->
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

    <!-- Current page -->
    <a href="services.php" class="<?= $self === 'services.php' ? 'active' : '' ?>">
      <i class="fa fa-wrench" aria-hidden="true"></i> Services
    </a>

  <!-- Sales (normal link with active state) -->
  <a href="sales.php" class="<?= $self === 'sales.php' ? 'active' : '' ?>">
    <i class="fas fa-receipt"></i> Sales
  </a>

  <a href="accounts.php" class="<?= $self === 'accounts.php' ? 'active' : '' ?>">
    <i class="fas fa-users"></i> Accounts & Branches
    <?php if ($pendingResetsCount > 0): ?>
      <span class="badge-pending"><?= $pendingResetsCount ?></span>
    <?php endif; ?>
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
        
    <!-- Stockman Linkssss -->
     <?php
      $transferNotif = $transferNotif ?? 0; // if not set, default to 0
      ?>
    <?php if ($role === 'stockman'): ?>
          <!-- Inventory group (unchanged) -->
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
          <a href="barcode-print.php<?php 
        $b = (int)($_SESSION['current_branch_id'] ?? 0);
        echo $b ? ('?branch='.$b) : '';?>" class="<?= $self === 'barcode-print.php' ? 'active' : '' ?>">
        <i class="fas fa-barcode"></i> Barcode Labels
    </a>
    </div>
  </div>
    <?php endif; ?>

    <!-- Staff Links -->
    <?php if ($role === 'staff'): ?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- Branch Navigation -->
<div class="content">
  <div class="branches modern-tabs">
    <?php if ($role === 'stockman'): 
        // Only show the stockman's branch
        $stockmanBranch = $conn->query("SELECT branch_name, branch_location, branch_id FROM branches WHERE branch_id = $branch_id")->fetch_assoc();
    ?>
        <a href="inventory.php?branch=<?= $branch_id ?>" class="active">
            <?= htmlspecialchars($stockmanBranch['branch_name']) ?> 
            <small class="text-muted"><?= htmlspecialchars($stockmanBranch['branch_location']) ?></small>
        </a>
    <?php else: ?>
        <?php while ($branch = $branches_result->fetch_assoc()): ?>
            <a href="inventory.php?branch=<?= $branch['branch_id'] ?>" 
               class="<?= ($branch['branch_id'] == $current_branch_id) ? 'active' : '' ?>">
               <?= htmlspecialchars($branch['branch_name']) ?> 
               <small class="text-muted"><?= htmlspecialchars($branch['branch_location']) ?></small>
            </a>
        <?php endwhile; ?>
    <?php endif; ?>
  </div>


<div class="search-box modern-search">
  <form method="GET" action="inventory.php" class="search-form d-flex align-items-center gap-2">
    <input type="hidden" name="branch" value="<?= htmlspecialchars($_GET['branch'] ?? '') ?>">

    <div class="search-input">
      <i class="fas fa-search"></i>
      <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Search items...">
    </div>

    <select name="category" onchange="this.form.submit()">
  <option value="">All Categories</option>
  <?php
  $cat_result = $conn->query("
      SELECT DISTINCT category 
      FROM products 
      WHERE archived = 0 
        AND category IS NOT NULL 
        AND TRIM(category) <> ''
      ORDER BY category ASC
  ");
  while ($cat = $cat_result->fetch_assoc()):
      $selected = ($_GET['category'] ?? '') === $cat['category'] ? 'selected' : '';
  ?>
  <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $selected ?>>
      <?= htmlspecialchars($cat['category']) ?>
  </option>
  <?php endwhile; ?>
</select>


    <button type="submit" class="btn btn-primary">Search</button>
  </form>

  <div class="legend mt-2">
    <span class="badge critical">Critical Stocks</span>
    <span class="badge sufficient">Sufficient Stocks</span>
  </div>
</div>
<!-- Manage Products -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0"><i class="fas fa-box me-2"></i> Manage Products</h2>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
          <i class="fas fa-plus"></i> Add Product
        </button>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addStockModal">
          <i class="fas fa-boxes"></i> Add Stock
        </button>
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#transferModal">
          <i class="fas fa-exchange-alt me-1"></i> Request Transfer
        </button>
      </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
      <div class="table-container">

        <!-- Header Table -->
        <table class="table table-header">
          <thead>
            <tr>
              <th>PRODUCT ID</th>
              <th>PRODUCT</th>
              <th>CATEGORY</th>
              <th>PRICE</th>
              <th>MARKUP (%)</th>
              <th>RETAIL PRICE</th>
              <th>CEILING POINT</th>
              <th>CRITICAL POINT</th>
              <th>STOCKS</th>
              <th>ACTION</th>
            </tr>
          </thead>
        </table>

        <!-- Scrollable Body -->
        <div class="table-body scrollable-list">
          <table class="table table-body-table">
            <tbody>
              <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                  $inventory_id = $row['inventory_id'] ?? 0;
                  $isCritical = ($row['stock'] <= $row['critical_point']);
                  $rowClass = $isCritical ? 'table-danger' : 'table-success';
                  $retailPrice = $row['price'] + ($row['price'] * ($row['markup_price'] / 100));
                ?>
                <tr class="<?= $rowClass ?>">
                  <td><?= $row['product_id'] ?></td>
                  <td><?= htmlspecialchars($row['product_name']) ?></td>
                  <td><?= htmlspecialchars($row['category']) ?></td>
                  <td><?= number_format($row['price'], 2) ?></td>
                  <td><?= number_format($row['markup_price'], 2) ?>%</td>
                  <td><?= number_format($retailPrice, 2) ?></td>
                  <td><?= $row['ceiling_point'] ?></td>
                  <td><?= $row['critical_point'] ?></td>
                  <td><?= $row['stock'] ?></td>
                  <td class="text-center">
                    <div class="action-buttons">
                      <button onclick='openEditModal(
                        <?= json_encode($row["product_id"]) ?>,
                        <?= json_encode($row["product_name"]) ?>,
                        <?= json_encode($row["category"]) ?>,
                        <?= json_encode($row["price"]) ?>,
                        <?= json_encode($row["stock"]) ?>,
                        <?= json_encode($row["markup_price"]) ?>,
                        <?= json_encode($row["ceiling_point"]) ?>,
                        <?= json_encode($row["critical_point"]) ?>,
                        <?= json_encode($row["branch_id"] ?? null) ?>
                      )' class="btn-edit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <!-- Archive Products -->
                      <?php if ($inventory_id): ?>
                        <form id="archiveForm-<?= $inventory_id ?>" method="POST" style="display:inline-block;">
                          <input type="hidden" name="inventory_id" value="<?= $inventory_id ?>">
                            <input type="hidden" name="archive_product" value="1">
                          <button
                            type="button"
                            name="archive_product"
                            class="btn-archive-unique"
                            data-archive-type="product"
                            data-archive-name="<?= htmlspecialchars($row['product_name']) ?>"
                          >
                            <i class="fas fa-archive"></i>
                          </button>
                        </form>

                      <?php else: ?>
                        <span class="text-muted">No Inventory</span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

      </div>
    <?php else: ?>
      <div class="text-center text-muted py-4">
        <i class="bi bi-info-circle fs-4 mb-2"></i>
        No products found for this branch.
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- Stock transfer table -->
<?php if ($role === 'admin'): ?>
  <?php
    $requests = $conn->query("
        SELECT tr.request_id, tr.product_id, tr.quantity, tr.request_date,
               p.product_name,
               sb.branch_name AS source_name,
               db.branch_name AS dest_name,
               u.username AS requested_by_user
        FROM transfer_requests tr
        JOIN products p  ON tr.product_id = p.product_id
        JOIN branches sb ON tr.source_branch = sb.branch_id
        JOIN branches db ON tr.destination_branch = db.branch_id
        JOIN users u     ON tr.requested_by = u.id
        WHERE tr.status = 'pending'
        ORDER BY tr.request_date ASC
    ");
  ?>
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="fas fa-exchange-alt me-2 text-warning"></i> Pending Transfer Requests</h2>
      </div>

      <?php if ($requests && $requests->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-header table-hover align-middle">
            <thead class="table-dark">
              <tr>
                <th>TRANSFER ID</th>
                <th>PRODUCT</th>
                <th>QUANTITY</th>
                <th>SOURCE</th>
                <th>DESTINATION</th>
                <th>REQUESTED BY</th>
                <th>REQUESTED AT</th>
                <th style="width:220px;">ACTION</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($r = $requests->fetch_assoc()): ?>
                <tr>
                  <td><?= (int)$r['request_id'] ?></td>
                  <td><?= htmlspecialchars($r['product_name']) ?></td>
                  <td><?= (int)$r['quantity'] ?></td>
                  <td><?= htmlspecialchars($r['source_name']) ?></td>
                  <td><?= htmlspecialchars($r['dest_name']) ?></td>
                  <td><?= htmlspecialchars($r['requested_by_user']) ?></td>
                  <td><?= date('Y-m-d H:i', strtotime($r['request_date'])) ?></td>
                  <td>
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
                      <button type="submit" name="action" value="approved" class="btn btn-sm btn-success">
                        <i class="fas fa-check"></i> Approve
                      </button>
                    </form>
                    <form method="POST" class="d-inline ms-1">
                      <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
                      <button type="submit" name="action" value="rejected" class="btn btn-sm btn-danger">
                        <i class="fas fa-times"></i> Reject
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted mb-0">No pending transfer requests.</p>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Stock-In Request Table -->
<?php if ($role === 'admin'): ?>
  <?php
    $sir = $conn->query("
      SELECT s.id, s.quantity, s.request_date, s.remarks,
             p.product_name,
             b.branch_name,
             u.username AS requested_by_user
      FROM stock_in_requests s
      JOIN products p ON s.product_id = p.product_id
      JOIN branches b ON s.branch_id = b.branch_id
      JOIN users u    ON s.requested_by = u.id
      WHERE s.status = 'pending'
      ORDER BY s.request_date ASC
    ");
  ?>
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="fas fa-arrow-down me-2 text-success"></i> Pending Stock-In Requests</h2>
      </div>

      <?php if ($sir && $sir->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-header table-hover align-middle">
            <thead class="table-dark">
              <tr>
                <th>STOCK_IN ID</th>
                <th>PRODUCT</th>
                <th>QUANTITY</th>
                <th>BRANCH</th>
                <th>REQUESTED BY</th>
                <th>REQUESTED AT</th>
                <th style="width:220px;">ACTION</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($x = $sir->fetch_assoc()): ?>
                <tr>
                  <td><?= (int)$x['id'] ?></td>
                  <td><?= htmlspecialchars($x['product_name']) ?></td>
                  <td><?= (int)$x['quantity'] ?></td>
                  <td><?= htmlspecialchars($x['branch_name']) ?></td>
                  <td><?= htmlspecialchars($x['requested_by_user']) ?></td>
                  <td><?= date('Y-m-d H:i', strtotime($x['request_date'])) ?></td>
                  <td>
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="sir_id" value="<?= (int)$x['id'] ?>">
                      <button type="submit" name="sir_action" value="approved" class="btn btn-sm btn-success">
                        <i class="fas fa-check"></i> Approve
                      </button>
                    </form>
                    <form method="POST" class="d-inline ms-1">
                      <input type="hidden" name="sir_id" value="<?= (int)$x['id'] ?>">
                      <button type="submit" name="sir_action" value="rejected" class="btn btn-sm btn-danger">
                        <i class="fas fa-times"></i> Reject
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted mb-0">No pending stock-in requests.</p>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<!-- ======================= ADD PRODUCT MODAL ======================= -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addProductForm" method="POST" action="add_product.php">
        <div class="modal-header">
          <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <!-- Brand -->
            <div class="col-md-6">
              <label for="brand" class="form-label">Brand</label>
              <select class="form-select" id="brand" name="brand_name" required>
                <option value="">-- Select Brand --</option>
                <?php while($brand = $brand_result->fetch_assoc()): ?>
                  <option value="<?= htmlspecialchars($brand['brand_name']) ?>">
                    <?= htmlspecialchars($brand['brand_name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
              <button type="button" class="btn btn-link p-0 mt-1" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                + Add New Brand
              </button>
            </div>

            <!-- Barcode -->
            <div class="col-md-6">
              <label for="barcode" class="form-label">Barcode</label>
              <input type="text" class="form-control" id="barcode" name="barcode" autofocus>
              <div class="form-text">Scan or type the product barcode</div>
            </div>

            
            <!-- Product Name -->
            <div class="col-md-6">
              <label for="productName" class="form-label">Product Name</label>
              <input type="text" class="form-control" id="productName" name="product_name" required>
            </div>

            <!-- Category -->
            <div class="col-md-6">
              <label for="category" class="form-label">Category</label>
              <select class="form-select" id="category" name="category_id" required>
                <option value="">-- Select Category --</option>
                <?php 
                  $category_result = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
                  while($category = $category_result->fetch_assoc()): 
                ?>
                  <option value="<?= $category['category_id'] ?>">
                    <?= htmlspecialchars($category['category_name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
              <button type="button" class="btn btn-link p-0 mt-1" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                + Add New Category
              </button>
            </div>

            <!-- Price & Markup -->
            <div class="col-md-6">
              <label for="price" class="form-label">Price</label>
              <input type="number" class="form-control" id="price" name="price"
                    step="0.01" min="0" inputmode="decimal" required>
            </div>

            <div class="col-md-6">
              <label for="markupPrice" class="form-label">Markup (%)</label>
              <input type="number" class="form-control" id="markupPrice" name="markup_price"
                    step="0.01" min="0" inputmode="decimal" required>
            </div>

            <div class="col-md-6">
              <label for="retailPrice" class="form-label">Retail Price</label>
              <input type="number" class="form-control" id="retailPrice" name="retail_price"
                    step="0.01" min="0" inputmode="decimal" readonly>
            </div>

            <!-- Other product fields -->
            <div class="col-md-6">
              <label for="ceilingPoint" class="form-label">Ceiling Point</label>
              <input type="number" class="form-control" id="ceilingPoint" name="ceiling_point"
                    step="1" min="0" inputmode="numeric" required>
            </div>

            <div class="col-md-6">
              <label for="criticalPoint" class="form-label">Critical Point</label>
              <input type="number" class="form-control" id="criticalPoint" name="critical_point"
                    step="1" min="0" inputmode="numeric" required>
            </div>

            <div class="col-md-6">
              <label for="stocks" class="form-label">Stocks</label>
              <input type="number" class="form-control" id="stocks" name="stocks"
                    step="1" min="0" inputmode="numeric" required>
            </div>

            <div class="col-md-6">
              <label for="vat" class="form-label">VAT (%)</label>
              <input type="number" class="form-control" id="vat" name="vat"
                    step="0.01" min="0" inputmode="decimal" required>
            </div>


            <div class="col-md-6">
              <label for="expiration" class="form-label">Expiration Date</label>
              <input type="date" class="form-control" id="expiration" name="expiration_date">
              <div class="form-text">Leave blank if none</div>
            </div>

            <div class="col-md-6">
              <label for="branch" class="form-label">Branch</label>
              <select name="branch_id" id="branch" class="form-select" required>
                <option value="">-- Select Branch --</option>
                <?php
                  $branches = $conn->query("SELECT branch_id, branch_name FROM branches");
                  while ($row = $branches->fetch_assoc()) {
                    echo "<option value='{$row['branch_id']}'>{$row['branch_name']}</option>";
                  }
                ?>
              </select>
            </div>
          </div>

          <!-- Inline confirmation area -->
          <div id="confirmSectionProduct" class="alert alert-warning mt-3 d-none">
            <p id="confirmMessageProduct">Are you sure you want to save this product?</p>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary btn-sm" id="cancelConfirmProduct">Cancel</button>
              <button type="submit" class="btn btn-success btn-sm">Yes, Save Product</button>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" id="openConfirmProduct" class="btn btn-success">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ======================= ADD STOCK MODAL ======================= -->
<div class="modal fade" id="addStockModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content fp-card">
      <div class="modal-header fp-header">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-boxes"></i>
          <h5 class="modal-title mb-0">Add Stock</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="addStockForm" method="post" action="inventory.php" autocomplete="off">
        <input type="hidden" name="op" value="add_stock">
        <input type="hidden" name="branch_id" value="<?= $_SESSION['current_branch_id'] ?? $_SESSION['branch_id'] ?>">

        <!-- Barcode -->
        <div class="mb-3 px-3">
          <label class="form-label fw-semibold" for="addstock_barcode">Scan / Type Barcode</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-barcode"></i></span>
            <input
              type="text"
              class="form-control"
              id="addstock_barcode"
              name="barcode"
              placeholder="Scan or type barcode, then Enter"
              autocomplete="off"
              inputmode="numeric">
          </div>
          <div class="form-text mt-1">Tip: You can leave product unselected if you scan a barcode.</div>
        </div>

        <!-- Product -->
        <div class="mb-3 px-3">
          <label class="form-label fw-semibold" for="addstock_product">Select Product (optional if barcode used)</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-box"></i></span>
            <select name="product_id" class="form-select" id="addstock_product">
              <option value="">-- Choose Product --</option>
              <?php
                $branch_id = $_SESSION['current_branch_id'] ?? $_SESSION['branch_id'] ?? 0;
                $stmt = $conn->prepare("
                  SELECT p.product_id, p.product_name, p.barcode, i.stock
                  FROM products p
                  INNER JOIN inventory i ON p.product_id = i.product_id
                  WHERE i.branch_id = ? AND i.archived = 0
                  ORDER BY p.product_name ASC
                ");
                $stmt->bind_param("i", $branch_id);
                $stmt->execute();
                $prodRes = $stmt->get_result();
                while ($p = $prodRes->fetch_assoc()):
              ?>
                <option value="<?= $p['product_id'] ?>"
                        data-barcode="<?= htmlspecialchars($p['barcode'] ?? '') ?>">
                  <?= htmlspecialchars($p['product_name']) ?> (Stock: <?= $p['stock'] ?>)
                </option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>
          <!-- Expiration Date -->
       <div class="mb-3 px-3" id="expiryWrapper">
          <label class="form-label fw-semibold" for="expiry_date">Expiry Date (this batch)</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
            <input type="date"
                  class="form-control"
                  name="expiry_date"
                  id="expiry_date"
                  min="<?= date('Y-m-d') ?>">
          </div>
        <div class="form-text" id="expiryHint">
          Optional for most products. If the product requires expiry, this will be enforced automatically.
        </div>
      </div>

        <!-- Quantity -->
        <div class="mb-3 px-3">
          <label class="form-label fw-semibold" for="addstock_qty">Stock Amount</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-sort-numeric-up"></i></span>
            <input type="number" class="form-control" id="addstock_qty" name="stock_amount" min="1" required placeholder="Enter quantity">
          </div>

          <?php if ($role === 'stockman'): ?>
            <p class="form-text text-active mt-2">
              This request will be sent for admin approval.
            </p>
          <?php endif; ?>

          <!-- Inline confirmation area (kept for your existing JS) -->
          <div id="confirmSection" class="d-none mx-auto text-center mt-3" style="max-width: 350px;">
            <p id="confirmMessage"></p>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary btn-sm" id="cancelConfirm">Cancel</button>
              <button type="submit" class="btn btn-success btn-sm">Yes, Add Stock</button>
            </div>
          </div>
        </div>

        <!-- Submit -->
        <div class="modal-footer px-3 pb-3">
          <button type="button" id="openConfirmStock" class="btn btn-sucess w-100 py-3">
            <span class="btn-label">Add</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>



<!-- Modal for Deleting Branches -->
<div class="modal" id="deleteSelectionModal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">🗑️ Select Branches to Delete</div>
    <form id="deleteBranchesForm" method="POST">
      <?php
      $branches_result = $conn->query("SELECT * FROM branches");
      if ($branches_result->num_rows > 0):
          while ($branch = $branches_result->fetch_assoc()):
      ?>
        <label>
          <input type="checkbox" name="branches_to_delete[]" value="<?= $branch['branch_number'] ?>">
          <?= $branch['branch_name'] ?> - <?= $branch['branch_location'] ?>
        </label>
      <?php endwhile; else: ?>
        <p>No branches available for deletion.</p>
      <?php endif; ?>
      <button type="button" onclick="openDeleteConfirmationModal()">Delete Selected</button>
    </form>
  </div>
</div>

<!-- MODAL FOR BRAND -->
<div class="modal fade" id="addBrandModal" tabindex="-1" aria-labelledby="addBrandModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addBrandModalLabel">Add New Brand</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="add_brand.php">
        <div class="modal-body">
          <input type="text" name="brand_name" class="form-control" placeholder="Brand Name" required>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Add Brand</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="add_brand.php">
        <div class="modal-header">
          <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <label for="category_name" class="form-label">Category Name</label>
          <input type="text" class="form-control" id="category_name" name="category_name" required>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Category</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Stock Confirmation Modal -->
   <div class="modal fade" id="confirmAddStock" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:15px;">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title">Confirm Add Stock</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p id="confirmMessage">Are you sure you want to add this stock?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" form="addStockForm" class="btn btn-success">Yes, Add Stock</button>
          </div>
        </div>
      </div>
    </div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      
      <form id="editProductForm" method="POST" action="update_product.php" onsubmit="return validateEditForm()">
        <div class="modal-header">
          <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body">
          <!-- Hidden Fields -->
          <input type="hidden" name="product_id" id="edit_product_id">
          <input type="hidden" name="branch_id" id="edit_branch_id">
          
          <div class="row g-3">
            <!-- Brand (Disabled) -->
            <div class="col-md-6">
              <label for="edit_brand" class="form-label">Brand</label>
              <select class="form-select" id="edit_brand" name="brand_name" disabled>
                <option value="">-- Select Brand --</option>
                <?php
                  $brands = $conn->query("SELECT brand_name FROM brands");
                  while ($brand = $brands->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($brand['brand_name']) . "'>" . htmlspecialchars($brand['brand_name']) . "</option>";
                  }
                ?>
              </select>
            </div>

            <!-- Product Name -->
            <div class="col-md-6">
              <label class="form-label">Product Name</label>
              <input type="text" class="form-control" id="edit_product_name" name="product_name" required>
            </div>

           <!-- Category -->
<div class="col-md-6">
  <label class="form-label">Category</label>
  <select class="form-select" id="edit_category" name="category" required>
    <option value="">-- Select Category --</option>
    <?php
      // Fetch categories from DB
      $categories = $conn->query("SELECT category_name FROM categories"); // Adjust table/column names
      while ($cat = $categories->fetch_assoc()) {
          $selected = ($cat['category_name'] == $product['category']) ? 'selected' : '';
          echo "<option value='" . htmlspecialchars($cat['category_name']) . "' $selected>" . htmlspecialchars($cat['category_name']) . "</option>";
      }
    ?>
  </select>
</div>

            <!-- Price -->
            <div class="col-md-6">
              <label class="form-label">Price</label>
              <input type="number" step="0.01" min="0" inputmode="decimal"
                    class="form-control" id="edit_price" name="price" required>
            </div>

            <!-- Markup -->
            <div class="col-md-6">
              <label class="form-label">Markup (%)</label>
              <input type="number" step="0.01" min="0" inputmode="decimal"
                    class="form-control" id="edit_markup" name="markup_price" required>
            </div>

            <!-- Retail Price (Readonly) -->
            <div class="col-md-6">
              <label class="form-label">Retail Price</label>
              <input type="number" step="0.01" min="0" inputmode="decimal"
                    class="form-control" id="edit_retail_price" name="retail_price" readonly>
            </div>

            <!-- Ceiling Point -->
            <div class="col-md-6">
              <label class="form-label">Ceiling Point</label>
              <input type="number" step="1" min="0" inputmode="numeric"
                    class="form-control" id="edit_ceiling_point" name="ceiling_point" required>
            </div>

            <!-- Critical Point -->
            <div class="col-md-6">
              <label class="form-label">Critical Point</label>
              <input type="number" step="1" min="0" inputmode="numeric"
                    class="form-control" id="edit_critical_point" name="critical_point" required>
            </div>

            <!-- VAT -->
            <div class="col-md-6">
              <label class="form-label">VAT (%)</label>
              <input type="number" step="0.01" min="0" inputmode="decimal"
                    class="form-control" id="edit_vat" name="vat" required>
            </div>

            <div class="col-md-6">
            <label class="form-label">Stock</label>
            <input type="number"
                  class="form-control"
                  id="edit_stock"
                  name="stock"
                  value=""
                  disabled>
            </div>

            <!-- Expiration Date -->
            <div class="col-md-6">
              <label class="form-label">Expiration Date</label>
              <input type="date" class="form-control" id="edit_expiration_date" name="expiration_date">
              <div class="form-text">Leave blank if none</div>
            </div>

            <!-- Branch (Disabled) -->
            <div class="col-md-6">
              <label for="edit_branch" class="forms-label">Branch</label>
              <select name="disabled_branch" id="edit_branch" class="form-select" disabled>
                <option value="">-- Select Branch --</option>
                <?php
                  $branches = $conn->query("SELECT branch_id, branch_name FROM branches");
                  while ($row = $branches->fetch_assoc()) {
                    echo "<option value='{$row['branch_id']}'>{$row['branch_name']}</option>";
                  }
                ?>
              </select>
            </div>
          </div>
        </div>
         <!-- Modal Footer with Cancel and Save buttons -->
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- ======================= EDIT SERVICE MODAL ======================= -->
<div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form id="editServiceForm" action="update_service.php" method="POST">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title fw-bold" id="editServiceModalLabel">
            <i class="bi bi-pencil-square me-2"></i> Edit Service
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Hidden input for service ID -->
        <input type="hidden" name="service_id" id="edit_service_id">
       <input type="hidden" name="branch_id" value="<?= htmlspecialchars($current_branch_id) ?>">


        <div class="modal-body p-4">
          <div class="mb-3">
            <label for="editServiceName" class="form-label fw-semibold">Service Name</label>
            <input type="text" name="service_name" id="editServiceName" class="form-control" placeholder="Enter service name" required>
          </div>

          <div class="mb-3">
            <label for="editServicePrice" class="form-label fw-semibold">Price (₱)</label>
            <input type="number" step="0.01" name="price" id="editServicePrice" class="form-control" placeholder="Enter price" required>
          </div>

          <div class="mb-3">
            <label for="editServiceDescription" class="form-label fw-semibold">Description</label>
            <textarea name="description" id="editServiceDescription" class="form-control" rows="3" placeholder="Optional"></textarea>
          </div>

          <!-- Inline confirmation area -->
          <div id="confirmSectionEditService" class="alert alert-warning mt-3 d-none">
            <p id="confirmMessageEditService">Are you sure you want to save changes to this service?</p>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary btn-sm" id="cancelConfirmEditService">Cancel</button>
              <button type="submit" class="btn btn-success btn-sm">Yes, Save Changes</button>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top-0">
          <!-- Trigger confirmation -->
          <button type="button" id="openConfirmEditService" class="btn btn-success fw-semibold">
            <i class="bi bi-save me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- Toast container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100">
  <div id="appToast" class="toast border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-primary text-white">
      <i class="fas fa-info-circle me-2"></i>
      <strong class="me-auto">System Notice</strong>
      <small>just now</small>
      <button type="button" class="btn-close btn-close-white ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="appToastBody">
      Action completed.
    </div>
  </div>
</div>
<!-- Stock Transfer Request Modal -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content fp-card">
      <div class="modal-header fp-header">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-exchange-alt"></i>
          <h5 class="modal-title mb-0" id="transferstockLabel">Stock Transfer</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
          <form id="transferForm" autocomplete="off">
            
          <!-- Source Branch -->
          <div class="mb-3 px-3">
            <label for="source_branch" class="form-label fw-semibold">Source Branch</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-warehouse"></i></span>
              <select class="form-select" id="source_branch" name="source_branch" required>
                <option value="">Select source branch</option>
                
              </select>
            </div>
            <div class="invalid-feedback">Please select a source branch.</div>
          </div>

          <!-- Product -->
          <div class="mb-3 px-3">
            <label for="product_id" class="form-label fw-semibold">Product</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-box"></i></span>
              <select class="form-select" id="product_id" name="product_id" required disabled>
                <option value="">Select a branch first</option>
              </select>
            </div>
            <div class="form-text">Select a source branch to load available products.</div>
            <div class="invalid-feedback">Please select a product.</div>
          </div>

          <!-- Destination Branch -->
          <div class="mb-3 px-3">
            <label for="destination_branch" class="form-label fw-semibold">Destination Branch</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-truck"></i></span>
              <select class="form-select" id="destination_branch" name="destination_branch" required>
                <option value="">Select destination branch</option>
              </select>
            </div>
            <div class="invalid-feedback">Please select a destination branch.</div>
          </div>

          <!-- Quantity -->
          <div class="mb-3 px-3">
            <label for="quantity" class="form-label fw-semibold">Quantity</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-sort-numeric-up"></i></span>
              <input type="number" class="form-control" id="quantity" name="quantity" min="1" required placeholder="Enter quantity">
            </div>
            <?php if ($role === 'stockman'): ?>
              <p class="form-text text-active">
                This request will be sent for admin approval.
              </p>
            <?php endif; ?>
            <div class="invalid-feedback">Please enter a valid quantity.</div>
          </div>

          <!-- Message / Feedback -->
          <div id="transferMsg" class="mt-3 "></div>

          <!-- Submit -->
           <div class="modal-footer ">
          <button type="submit" class="btn btn w-100 py-3" id="transferSubmit">
            <span class="btn-label">Proceed</span>
            <span class="btn-spinner spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
          </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Toast container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100">
  <div id="appToast" class="toast border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-primary text-white">
      <i class="fas fa-info-circle me-2"></i>
      <strong class="me-auto">System Notice</strong>
      <small>just now</small>
      <button type="button" class="btn-close btn-close-white ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="appToastBody">
      Action completed.
    </div>
  </div>
</div>

<!-- Archive Service Modal -->
<div class="modal fade" id="archiveServiceModal" tabindex="-1" aria-labelledby="archiveServiceLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="archiveServiceLabel">
          <i class="fa-solid fa-box-archive me-2"></i> Archive Service
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        You’re about to archive <strong id="archiveServiceName">this service</strong> for this branch.
        <div class="small text-muted mt-2">
          This hides the service from selection but keeps history/logs.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmArchiveServiceBtn">
          <i class="fa-solid fa-archive me-1"></i> Yes, Archive
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Archive Product Modal -->
<div class="modal fade" id="archiveProductModal" tabindex="-1" aria-labelledby="archiveProductLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger text-white" id="archiveProductHead">
        <h5 class="modal-title" id="archiveProductLabel">
          <i class="fa-solid fa-box-archive me-2"></i> Archive Product
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        You’re about to archive <strong id="archiveProductName">this product</strong> for this branch.
        <div class="small text-muted mt-2">
          This hides the product from inventory operations for this branch but keeps history/logs.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmArchiveProductBtn">
          <i class="fa-solid fa-archive me-1"></i> Yes, Archive
        </button>
      </div>
    </div>
  </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function () {
  function setupConfirm(openBtnId, formId, sectionId, messageId, cancelBtnId, label) {
    const openBtn = document.getElementById(openBtnId);
    const form = document.getElementById(formId);
    const confirmSection = document.getElementById(sectionId);
    const confirmMessage = document.getElementById(messageId);
    const cancelBtn = document.getElementById(cancelBtnId);

    if (!openBtn || !form || !confirmSection || !confirmMessage || !cancelBtn) return;

    openBtn.addEventListener("click", function () {
      const requiredFields = form.querySelectorAll("input[required], select[required], textarea[required]");
      const emptyField = Array.from(requiredFields).some(f => !f.value.trim());
      if (emptyField) {
        alert("Please fill in all required fields.");
        return;
      }
      const values = Array.from(requiredFields).map(f => f.value.trim());
      confirmMessage.textContent = `Confirm adding ${label}: ${values.join(" - ")} ?`;
      confirmSection.classList.remove("d-none");
    });

    cancelBtn.addEventListener("click", function () {
      confirmSection.classList.add("d-none");
    });
  }

  setupConfirm("openConfirmStock", "addStockForm", "confirmSection", "confirmMessage", "cancelConfirm", "Stock");
  setupConfirm("openConfirmProduct", "addProductForm", "confirmSectionProduct", "confirmMessageProduct", "cancelConfirmProduct", "Product");
  setupConfirm("openConfirmService", "addServiceForm", "confirmSectionService", "confirmMessageService", "cancelConfirmService", "Service");
});

</script>

<script>
function openAddProductModal() {
  document.getElementById('addProductModal').style.display = 'flex';
}

function closeAddProductModal() {
  document.getElementById('addProductModal').style.display = 'none';
}

// Optional: Click outside to close
window.onclick = function(event) {
  const modal = document.getElementById('addProductModal');
  if (event.target === modal) modal.style.display = "none";
}

</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const ceilingInput = document.getElementById('ceiling_point');
  const criticalInput = document.getElementById('critical_point');
  const form = document.getElementById('your-form-id'); // Replace with your actual form ID

  if (form && ceilingInput && criticalInput) {
    form.addEventListener('submit', function (e) {
      const ceiling = parseFloat(ceilingInput.value);
      const critical = parseFloat(criticalInput.value);
      if (!isNaN(ceiling) && !isNaN(critical) && critical > ceiling) {
        e.preventDefault();
        alert("❌ Critical Point cannot be greater than Ceiling Point.");
        criticalInput.focus();
      }
    });
  }
});

</script>

<script>
function openEditModal(
  id, name, category, price, stock, markup_price, ceiling_point, critical_point, branch_id
) {
  const need = [
    "edit_product_id","edit_product_name","edit_category","edit_price",
    "edit_markup","edit_retail_price","edit_ceiling_point",
    "edit_critical_point","edit_branch_id","edit_branch" // edit_stock optional
  ];

  const missing = need.filter(x => !document.getElementById(x));
  if (missing.length) console.error("Edit modal missing element IDs:", missing);

  const setVal = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.value = (val ?? "").toString();
  };

  setVal("edit_product_id", id);
  setVal("edit_product_name", name);
  setVal("edit_category", category);
  setVal("edit_price", price);
  setVal("edit_markup", markup_price);

  // calculate retail = price + price * (markup / 100)
  const p  = parseFloat(price || 0);
  const mu = parseFloat(markup_price || 0);
  const rp = p + (p * (mu / 100));
  setVal("edit_retail_price", Number.isFinite(rp) ? rp.toFixed(2) : "0.00");

  setVal("edit_ceiling_point", ceiling_point);
  setVal("edit_critical_point", critical_point);

  const stockEl = document.getElementById("edit_stock");
  if (stockEl) stockEl.value = stock;

  setVal("edit_branch_id", branch_id);
  const branchDropdown = document.getElementById("edit_branch");
  if (branchDropdown) branchDropdown.value = branch_id;

  const modalEl = document.getElementById("editProductModal");
  if (!modalEl) { console.error("editProductModal not found"); return; }
  new bootstrap.Modal(modalEl).show();
}

// 🚫 Prevent typing "-" or "+" in Edit modal numeric fields
document.addEventListener("DOMContentLoaded", function () {
  const blockKeys = e => {
    if (["-","+"].includes(e.key)) e.preventDefault();
  };

  const ids = [
    "edit_price","edit_markup","edit_retail_price",
    "edit_ceiling_point","edit_critical_point","edit_vat"
  ];
  ids.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener("keydown", blockKeys);
  });
});
</script>

<script>
// Accept a single service object
function openEditServiceModal(service) {
  console.log(service); // Debug: check values

  document.getElementById('edit_service_id').value = service.service_id;
  document.getElementById('editServiceName').value = service.service_name;
  document.getElementById('editServicePrice').value = service.price;
  document.getElementById('editServiceDescription').value = service.description;

  const editModal = new bootstrap.Modal(document.getElementById('editServiceModal'));
  editModal.show();
}

// Confirmation logic for Edit Service
document.addEventListener("DOMContentLoaded", function () {
  const openBtn = document.getElementById('openConfirmEditService');
  const cancelBtn = document.getElementById('cancelConfirmEditService');
  const confirmSection = document.getElementById('confirmSectionEditService');

  if (openBtn && cancelBtn && confirmSection) {
    openBtn.addEventListener('click', function () {
      confirmSection.classList.remove('d-none');
    });
    cancelBtn.addEventListener('click', function () {
      confirmSection.classList.add('d-none');
    });
  }
});
</script>

<script>
function validateEditForm() {
  const stock = parseInt(document.getElementById('edit_stock').value);
  const ceiling = parseInt(document.getElementById('edit_ceiling_point').value);

  if (stock > ceiling) {
    alert('Stock cannot exceed Ceiling Point.');
    return false; // prevent submission
  }
  return true; // allow submission
}
</script>


 <!-- calculate retail -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const priceInput = document.getElementById('price');
    const markupInput = document.getElementById('markupPrice');
    const retailInput = document.getElementById('retailPrice');

    function calculateRetail() {
        const price = parseFloat(priceInput.value) || 0;
        const markup = parseFloat(markupInput.value) || 0;
        const retail = price + (price * (markup / 100));
        retailInput.value = retail.toFixed(2);
    }

    priceInput.addEventListener('input', calculateRetail);
    markupInput.addEventListener('input', calculateRetail);
});
</script>



<script>
/** Prevent negative/invalid characters and auto-clamp < 0 to 0 */
(function () {
  const ids = ["price","markupPrice","retailPrice","ceilingPoint","criticalPoint","stocks","vat"];

  function blockKeys(e) {
    // Disallow minus, plus, and exponent chars on number inputs
    if (["-","+","e","E"].includes(e.key)) e.preventDefault();
  }
  function clampNonNegative(el) {
    const v = el.value.trim();
    if (v === "") return; // allow empty while typing
    const num = Number(v);
    if (isNaN(num) || num < 0) el.value = "0";
  }
  function attach(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener("keydown", blockKeys);
    el.addEventListener("input", () => {
      // If user pasted a negative, clamp on input
      if (el.value.includes("-")) el.value = el.value.replace("-", "");
      clampNonNegative(el);
    });
    el.addEventListener("blur", () => clampNonNegative(el));
  }
  ids.forEach(attach);

  // Guard the Add Product "Save" click too
  const addBtn = document.getElementById("openConfirmProduct");
  const addForm = document.getElementById("addProductForm");
  if (addBtn && addForm) {
    addBtn.addEventListener("click", () => {
      const fields = ids.map(id => document.getElementById(id)).filter(Boolean);
      for (const f of fields) {
        if (f.value === "") continue; // let HTML5 required handle empties
        const n = Number(f.value);
        if (isNaN(n) || n < 0) {
          alert("Negative values are not allowed.");
          f.focus();
          throw new Error("Blocked negative");
        }
      }
      // extra logical check
      const crit = Number((document.getElementById("criticalPoint")||{}).value || 0);
      const ceil = Number((document.getElementById("ceilingPoint")||{}).value || 0);
      if (crit > ceil) {
        alert("❌ Critical Point cannot be greater than Ceiling Point.");
        (document.getElementById("criticalPoint")||{}).focus?.();
        throw new Error("Blocked invalid thresholds");
      }
    });
  }
})();
</script>

<script>
(() => {
  const modalEl   = document.getElementById('transferModal');
  const form      = document.getElementById('transferForm');
  const msg       = document.getElementById('transferMsg');
  const btn       = document.getElementById('transferSubmit');
  const spin      = btn.querySelector('.btn-spinner');
  const label     = btn.querySelector('.btn-label');

  const srcSel    = document.getElementById('source_branch');
  const dstSel    = document.getElementById('destination_branch');
  const prodSel   = document.getElementById('product_id');

  let branchesLoaded = false;

  // Load branches once when modal opens
  modalEl.addEventListener('shown.bs.modal', () => {
    if (branchesLoaded) return;

    fetch('get_branches.php')
      .then(r => r.json())
      .then(list => {
        srcSel.innerHTML = '<option value="">Select Branch</option>';
        dstSel.innerHTML = '<option value="">Select Branch</option>';
        (list || []).forEach(b => {
          const o1 = new Option(b.branch_name, b.branch_id);
          const o2 = new Option(b.branch_name, b.branch_id);
          srcSel.add(o1);
          dstSel.add(o2);
        });
        branchesLoaded = true;
      })
      .catch(() => {
        srcSel.innerHTML = '<option value="">Failed to load</option>';
        dstSel.innerHTML = '<option value="">Failed to load</option>';
      });
  });

  // Prevent same source/destination & load products
  srcSel.addEventListener('change', () => {
    const branchId = srcSel.value;

    // Disable same branch in destination
    const selectedSrc = parseInt(branchId || 0, 10);
    Array.from(dstSel.options).forEach(opt => {
      if (!opt.value) return;
      opt.disabled = parseInt(opt.value, 10) === selectedSrc;
    });

    // Reset product select
    prodSel.disabled = true;
    prodSel.size = 1;
    prodSel.innerHTML = '<option value="">Select a branch first</option>';
    if (!branchId) return;

    fetch('get_products_by_branch.php?branch_id=' + encodeURIComponent(branchId))
      .then(r => r.json())
      .then(data => {
        prodSel.disabled = false;
        prodSel.innerHTML = '';
        if (!Array.isArray(data) || !data.length) {
          prodSel.innerHTML = '<option value="">No products available</option>';
          return;
        }
        data.forEach(p => {
          // Skip products with 0 or null/undefined stock
          if ((p.stock ?? 0) <= 0) return;

          const opt = document.createElement('option');
          opt.value = p.product_id;
          opt.textContent = `${p.product_name} (Stock: ${p.stock})`;
          prodSel.appendChild(opt);
        });
      })
      .catch(() => {
        prodSel.disabled = true;
        prodSel.innerHTML = '<option value="">Failed to load products</option>';
      });
  });

// Expand product select (no overlay)
// Only expand if you explicitly opt-in with a class
(() => {
  const prodSel = document.getElementById('product_id');
  if (!prodSel || !prodSel.classList.contains('expand-on-focus')) return;
  const expand = () => { const n = Math.min(6, prodSel.options.length || 6); if (n>1) prodSel.size = n; };
  const collapse = () => { prodSel.size = 1; };
  prodSel.addEventListener('focus', expand);
  prodSel.addEventListener('blur', collapse);
  prodSel.addEventListener('change', collapse);
})();

  // Submit via AJAX
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    msg.innerHTML = '';
    spin.classList.remove('d-none');
    btn.disabled = true; label.textContent = 'Submitting...';

    fetch('transfer_request_create.php', {
      method: 'POST',
      body: new FormData(form)
    })
    .then(r => r.json())
    .then(d => {
      const success = d.status === 'success';
      showToast(d.message, success ? 'success' : 'danger');

      if (success) {
        form.reset();
        prodSel.disabled = true;
        prodSel.innerHTML = '<option value="">Select a branch first</option>';
        setTimeout(() => {
          const m = bootstrap.Modal.getInstance(modalEl);
          m?.hide();
        }, 900);
      }
    })
    .catch(() => {
      showToast('Something went wrong. Please try again.', 'danger');
    })
    .finally(() => {
      spin.classList.add('d-none');
      btn.disabled = false; label.textContent = 'Submit Request';
    });
  });

  // Hard reset on close
  modalEl.addEventListener('hidden.bs.modal', () => {
    form.reset();
    msg.innerHTML = '';
    prodSel.disabled = true;
    prodSel.size = 1;
    prodSel.innerHTML = '<option value="">Select a branch first</option>';
    // re-enable all dest options
    Array.from(dstSel.options).forEach(opt => opt.disabled = false);
  });
})();

function showToast(message, type = 'info', delay = 3000) {
  const toastEl   = document.getElementById('appToast');
  const toastBody = document.getElementById('appToastBody');
  const headerEl  = toastEl ? toastEl.querySelector('.toast-header') : null;
  if (!toastEl || !toastBody || !headerEl) return;

  // reset header bg classes
  headerEl.classList.remove('bg-success','bg-danger','bg-info','bg-warning');

  // map type -> bootstrap bg class
  const color = ({
    success: 'bg-success',
    danger:  'bg-danger',
    error:   'bg-danger',   // alias
    warning: 'bg-warning',
    info:    'bg-info',
  }[type] || 'bg-info');

  headerEl.classList.add('text-white', color);
  toastBody.textContent = message;

  const bsToast = new bootstrap.Toast(toastEl, { delay });
  bsToast.show();
}

/** Use this to show toasts for stock actions by "flag" */
function showStockToast(flag, fallbackMessage = '') {
  const spec = {
    added:    ['Successfully added stock.', 'success'],
    success:  ['Successfully added stock.', 'success'], // if you use ?stock=success
    requested:['Stock-in request submitted for approval.', 'info'],
    exceeded: ['Cannot add stock. Final stock exceeds ceiling point.', 'danger'],
    rejected: ['Stock-in request was rejected.', 'danger'],
    approved: ['Stock-in request approved and applied.', 'success'],
  };
  const [msg, type] = spec[flag] || [fallbackMessage || 'Done.', 'info'];
  showToast(msg, type);
}
</script>

<script>
function selectByBarcode(code) {
  const prodSel = document.getElementById('addstock_product');
  const clean   = (code || '').replace(/\s+/g, '');

  let match = null;
  for (const opt of prodSel.options) {
    const bc = (opt.dataset.barcode || '').replace(/\s+/g, '');
    if (bc && bc === clean) { match = opt; break; }
  }

  if (match) {
    prodSel.value = match.value;
    // Optional success toast
    const nameOnly = match.textContent.split('(')[0].trim();
    showToast(`✅ ${nameOnly} selected via barcode`, 'success');
    return true;
  } else {
    prodSel.value = '';
    showToast(`❌ No product found with barcode: ${clean}`, 'danger');
    return false;
  }
}
</script>

<!-- Barcode Script -->
<script>
(() => {
  const modalEl  = document.getElementById('addStockModal');
  const form     = document.getElementById('addStockForm');
  const bcInput  = document.getElementById('addstock_barcode');
  const qtyInput = document.getElementById('addstock_qty');

  // Tuning
  const INTER_CHAR_MS = 50;     // typical scan gap
  const SILENCE_MS    = 120;    // submit/finalize after this pause if no Enter

  let buffer = '';
  let lastTs = 0;
  let silenceTimer = null;
  let listening = false;

  function resetBuffer() {
    buffer = '';
    lastTs = 0;
    if (silenceTimer) { clearTimeout(silenceTimer); silenceTimer = null; }
  }

  function finalizeScan() {
    if (!buffer || buffer.length < 4) { resetBuffer(); return; }
    const code = buffer.replace(/\s+/g, '');
    bcInput.value = code;

    // NEW: select product by barcode + show toast
    const matched = selectByBarcode(code);

    // UX after matching
    if (matched) {
      if (!qtyInput.value) qtyInput.focus();
    }

    resetBuffer();
  }

  function scheduleSilentFinalize() {
    if (silenceTimer) clearTimeout(silenceTimer);
    silenceTimer = setTimeout(finalizeScan, SILENCE_MS);
  }

  function onKey(e) {
    if (!listening) return;

    // If typing inside inputs, don't hijack except barcode field itself
    const tag = (e.target.tagName || '').toUpperCase();
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
      // Allow normal typing; barcode scanners will usually not focus fields anyway
      return;
    }

    const now = Date.now();
    if (now - lastTs > INTER_CHAR_MS) buffer = '';
    lastTs = now;

    if (e.key === 'Enter') {
      e.preventDefault();
      finalizeScan();
      return;
    }

    if (e.key && e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
      buffer += e.key;
      scheduleSilentFinalize();
    }
  }

  // Activate only while the modal is shown
  modalEl.addEventListener('shown.bs.modal', () => {
    listening = true;
    resetBuffer();
    // If cashier will type, focus the barcode field
    bcInput.focus();
  });
  modalEl.addEventListener('hidden.bs.modal', () => {
    listening = false;
    resetBuffer();
  });

  document.addEventListener('keydown', onKey);

  // Also support manual Enter in the visible barcode field
  bcInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      buffer = bcInput.value;
      finalizeScan();
    }
  });
})();

// add barcode when adding product
document.addEventListener("DOMContentLoaded", function() {
  const barcodeInput = document.getElementById("barcode");

  // Prevent form from auto-submitting when scanner presses Enter
  barcodeInput.addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
      e.preventDefault();
      // Move focus to Product Name after scanning
      document.getElementById("productName").focus();
    }
  });
});


</script>
<!-- dropdown script -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  const bcInput = document.getElementById("addstock_barcode");
  const prodSel = document.getElementById("addstock_product");

  // When cashier scans or types and presses Enter
  bcInput.addEventListener("keydown", e => {
    if (e.key === "Enter") {
      e.preventDefault(); // prevent form auto-submit
      const code = bcInput.value.trim();

      if (!code) return;

      let found = false;
      for (const opt of prodSel.options) {
        if ((opt.dataset.barcode || "") === code) {
          prodSel.value = opt.value;
          found = true;
          break;
        }
      }

      if (!found) {
        selectByBarcode(code); // handles both success & error toasts internally

        prodSel.value = ""; // reset selection
      }

      // clear barcode input for next scan
      bcInput.value = "";
    }
  });
});
</script>

<!-- Function for Archive Modals -->
<script>
(() => {
  let pendingArchiveForm = null;      // which form to submit on confirm
  let pendingArchiveType = null;      // "product" | "service"

  // Open modal when clicking any archive button
  document.querySelectorAll('.btn-archive-unique').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const form = e.currentTarget.closest('form');
      const type = e.currentTarget.dataset.archiveType;
      const name = e.currentTarget.dataset.archiveName || (type === 'product' ? 'this product' : 'this service');

      pendingArchiveForm = form;
      pendingArchiveType = type;

      if (type === 'product') {
        document.getElementById('archiveProductName').textContent = name;
        new bootstrap.Modal(document.getElementById('archiveProductModal')).show();
      } else {
        document.getElementById('archiveServiceName').textContent = name;
        new bootstrap.Modal(document.getElementById('archiveServiceModal')).show();
      }
    });
  });

  // Confirm buttons submit the stored form
  const confirmProductBtn = document.getElementById('confirmArchiveProductBtn');
  const confirmServiceBtn = document.getElementById('confirmArchiveServiceBtn');

  if (confirmProductBtn) {
    confirmProductBtn.addEventListener('click', () => {
      if (pendingArchiveForm && pendingArchiveType === 'product') {
        // ensure the correct POST name is present
        if (!pendingArchiveForm.querySelector('[name="archive_product"]')) {
          const hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = 'archive_product';
          hidden.value = '1';
          pendingArchiveForm.appendChild(hidden);
        }
        pendingArchiveForm.submit();
      }
    });
  }

  if (confirmServiceBtn) {
    confirmServiceBtn.addEventListener('click', () => {
      if (pendingArchiveForm && pendingArchiveType === 'service') {
        if (!pendingArchiveForm.querySelector('[name="archive_service"]')) {
          const hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = 'archive_service';
          hidden.value = '1';
          pendingArchiveForm.appendChild(hidden);
        }
        pendingArchiveForm.submit();
      }
    });
  }
})();
</script>

<script>
(() => {
  const modalEl = document.getElementById('addProductModal');
  if (!modalEl) return;

  const bcInput   = document.getElementById('barcode');
  const nameInput = document.getElementById('productName');

  const INTER_CHAR_MS = 50;
  const SILENCE_MS    = 120;

  let buffer = '';
  let lastTs = 0;
  let silenceTimer = null;
  let listening = false;

  function resetBuffer() {
    buffer = '';
    lastTs = 0;
    if (silenceTimer) { clearTimeout(silenceTimer); silenceTimer = null; }
  }

  function finalizeScan() {
    if (!buffer || buffer.length < 3) { resetBuffer(); return; }
    const code = buffer.replace(/\s+/g, '');
    if (bcInput) bcInput.value = code;
    resetBuffer();
    nameInput?.focus(); // move to Product Name after scanning
  }

  function scheduleSilentFinalize() {
    if (silenceTimer) clearTimeout(silenceTimer);
    silenceTimer = setTimeout(finalizeScan, SILENCE_MS);
  }

  function onKey(e) {
    if (!listening) return;

    // don't hijack when typing in inputs/selects/textareas
    const tag = (e.target.tagName || '').toUpperCase();
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;

    const now = Date.now();
    if (now - lastTs > INTER_CHAR_MS) buffer = '';
    lastTs = now;

    if (e.key === 'Enter') {
      e.preventDefault();
      finalizeScan();
      return;
    }

    if (e.key && e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
      buffer += e.key;
      scheduleSilentFinalize();
    }
  }

  modalEl.addEventListener('shown.bs.modal', () => {
    listening = true;
    resetBuffer();
    bcInput?.focus();
  });

  modalEl.addEventListener('hidden.bs.modal', () => {
    listening = false;
    resetBuffer();
  });

  document.addEventListener('keydown', onKey);

  // allow manual Enter in the barcode field too
  bcInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      buffer = bcInput.value || '';
      finalizeScan();
    }
  });
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
<!-- Success message for Stock Transfer Approval -->
<script>
  // Show toast based on URL (?tr=approved|rejected)
  document.addEventListener('DOMContentLoaded', function () {
    const url = new URL(window.location.href);
    const tr  = url.searchParams.get('tr');
    if (tr === 'approved') {
      showToast('Transfer request approved.', 'success');
    } else if (tr === 'rejected') {
      showToast('Transfer request rejected.', 'danger');
    }
    if (tr) {
      url.searchParams.delete('tr');
      history.replaceState({}, '', url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : ''));
    }
  });
</script>v

<!-- Toast Message for Add Stocks -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const url = new URL(window.location.href);
  const qp  = url.searchParams;

  // Map query params -> [message, type]
  const flashMap = {
    stock: {
      added: ['Successfully added stock.', 'success'],
       exceeded: ['❌ Cannot add stock. Final stock exceeds ceiling point.', 'danger'],
    },
    sir: {
      requested: ['Stock-In request submitted.', 'success'],
      approved:  ['Stock-In request approved.', 'success'],
      rejected:  ['Stock-In request rejected.', 'danger'],
      error:     ['There was an error approving stock-in.', 'danger'],
    },
    transfer: {
      requested: ['Transfer request submitted.', 'success'],
    },
    tr: {
      approved: ['Transfer request approved.', 'success'],
      rejected: ['Transfer request rejected.', 'danger'],
    },
    archived: {
      success:  ['Product archived for this branch.', 'success'],
      service:  ['Service archived for this branch.', 'success'],
    },
    
    // 👇 Add these two blocks
    ap: { // add product
      added: ['Product added successfully.', 'success'],
      error: ['There was an error adding the product.', 'danger'],
    },
    as: { // add service
      added: ['Service added successfully.', 'success'],
      error: ['There was an error adding the service.', 'danger'],
    },
    up: { // update product
  updated: ['Product updated successfully.', 'success'],
  error:   ['There was an error updating the product.', 'danger'],
    },
    us: { // update service
      updated: ['Service updated successfully.', 'success'],
      error:   ['There was an error updating the service.', 'danger'],
    },
    
  };

  // Show toast for the first matching param
  for (const key in flashMap) {
    const val = qp.get(key);
    if (!val) continue;

    const entry = flashMap[key][val];
    if (entry) {
      const [msg, type] = entry;
      showToast(msg, type);
    }

    // Clean URL
    qp.delete(key);
  }
  const cleanUrl = url.pathname + (qp.toString() ? '?' + qp.toString() : '');
  history.replaceState({}, '', cleanUrl);
});
</script>

<script>
async function refreshExpiryVisibility({ productId = null, barcode = '' } = {}) {
  const params = new URLSearchParams();
  if (productId) params.set('product_id', productId);
  if (barcode) params.set('barcode', barcode);

  try {
    const res = await fetch('get_product_meta.php?' + params.toString(), { cache: 'no-store' });
    const data = await res.json();

    const input = document.getElementById('expiry_date');
    const hint  = document.getElementById('expiryHint');

    if (data.ok && data.expiry_required) {
      input.setAttribute('required', 'required');
      if (hint) hint.textContent = 'Required for this product.';
    } else {
      input.removeAttribute('required');
      if (hint) hint.textContent = 'Optional — fill to create/track a batch for this stock-in.';
    }
  } catch (e) {
    // fail-safe: optional
    const input = document.getElementById('expiry_date');
    input && input.removeAttribute('required');
  }
}

// Bindings for your IDs
document.addEventListener('DOMContentLoaded', () => {
  const productSelect = document.getElementById('addstock_product');
  const barcodeInput  = document.getElementById('addstock_barcode');

  productSelect?.addEventListener('change', e => {
    const pid = e.target.value;
    if (pid) refreshExpiryVisibility({ productId: pid });
  });

  barcodeInput?.addEventListener('blur', e => {
    const code = e.target.value.trim();
    if (code) refreshExpiryVisibility({ barcode: code });
  });
});
</script>

<!-- Bootstrap 5.3.2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"
        referrerpolicy="no-referrer"></script>

<script src="notifications.js"></script>

</body>
</html>
