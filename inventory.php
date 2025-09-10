<?php
session_start();

// Redirect to login if user not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

include 'config/db.php';
include 'functions.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$branch_id = $_SESSION['branch_id'] ?? null;


$pending = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pending = $row['pending'] ?? 0;
    }
}

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

    // Fetch product name and branch_id from inventory
    $stmt = $conn->prepare("
        SELECT i.inventory_id, p.product_name, i.branch_id
        FROM inventory i
        JOIN products p ON i.product_id = p.product_id
        WHERE i.inventory_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $inventory_id);
    $stmt->execute();
    $stmt->bind_result($inv_id, $product_name, $branch_id);
    
    if ($stmt->fetch()) {
        $stmt->close();

        // Archive this inventory row only
        $stmt = $conn->prepare("UPDATE inventory SET archived = 1 WHERE inventory_id = ?");
        $stmt->bind_param("i", $inventory_id);
        $stmt->execute();
        $stmt->close();

        // Log action
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

// stock log
if (isset($_POST['add_stock'])) {
    $inventory_id = (int) $_POST['inventory_id'];
    $added_qty    = (int) $_POST['quantity'];

    // Update stock
    $stmt = $conn->prepare("UPDATE inventory SET stock = stock + ? WHERE inventory_id = ?");
    $stmt->bind_param("ii", $added_qty, $inventory_id);
    $stmt->execute();
    $stmt->close();

    // Get product info
    $row = $conn->query("
        SELECT p.product_name, i.branch_id 
        FROM inventory i
        JOIN products p ON i.product_id = p.product_id
        WHERE i.inventory_id = $inventory_id
    ")->fetch_assoc();

    // Log action
    logAction(
        $conn,
        "Add Stock",
        "Added $added_qty stocks to {$row['product_name']} (Inventory ID: $inventory_id)",
        null,
        $row['branch_id']
    );

    $_SESSION['stock_message'] = "Successfully added $added_qty stock(s)!";
    header("Location: inventory.php?stock=added");
    exit;
}
// request transfer log
if (isset($_POST['transfer_request'])) {
    $product_id  = (int) $_POST['product_id'];
    $from_branch = (int) $_POST['from_branch'];
    $to_branch   = (int) $_POST['to_branch'];
    $quantity    = (int) $_POST['quantity'];

    $stmt = $conn->prepare("
        INSERT INTO transfer_requests (product_id, from_branch, to_branch, quantity, status, requested_by, requested_at)
        VALUES (?, ?, ?, ?, 'Pending', ?, NOW())
    ");
    $stmt->bind_param("iiiis", $product_id, $from_branch, $to_branch, $quantity, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();

    $product = $conn->query("SELECT product_name FROM products WHERE product_id = $product_id")->fetch_assoc();

    logAction(
        $conn,
        "Stock Transfer Request",
        "Requested transfer of $quantity {$product['product_name']} from Branch $from_branch to Branch $to_branch"
    );

    $_SESSION['stock_message'] = "Transfer request sent successfully!";
    header("Location: inventory.php?transfer=requested");
    exit;
}

if (isset($_SESSION['stock_message'])): ?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
  <div class="toast align-items-center text-bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        <?= $_SESSION['stock_message']; ?>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<?php unset($_SESSION['stock_message']); ?>
<?php endif;


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
        <a href="inventory.php" class="active"><i class="fas fa-box"></i> Inventory</a>
        <a href="physical_inventory.php"><i class="fas fa-warehouse"></i> Physical Inventory</a>
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
        <a href="/config/admin/backup_admin.php"><i class="fa-solid fa-database"></i> Backup and Restore</a>
    <?php endif; ?>

    <!-- Stockman Linkssss -->
     <?php
      $transferNotif = $transferNotif ?? 0; // if not set, default to 0
      ?>
    <?php if ($role === 'stockman'): ?>
        <a href="inventory.php" class="active"><i class="fas fa-box"></i> Inventory
            <?php if ($transferNotif > 0): ?>
                <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;">
                    <?= $transferNotif ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="physical_inventory.php"><i class="fas fa-warehouse"></i> Physical Inventory</a>
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
      $cat_result = $conn->query("SELECT DISTINCT category FROM products WHERE archived = 0 ORDER BY category ASC");
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
              <th>ID</th>
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

<!-- Manage Services -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0"><i class="fa fa-wrench" aria-hidden="true"></i> Manage Services</h2>
      <button class="btn btn-create btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
        <i class="fa fa-wrench" aria-hidden="true"></i> Add Service
      </button>
    </div>

    <?php if (isset($services_result) && $services_result->num_rows > 0): ?>
      <div class="table-container">

        <!-- Header Table -->
        <table class="table table-header">
          <thead>
            <tr>
              <th>ID</th>
              <th>Service Name</th>
              <th>Price (₱)</th>
              <th>Description</th>
              <th>Action</th>
            </tr>
          </thead>
        </table>

        <!-- Scrollable Body -->
        <div class="table-body scrollable-list">
          <table class="table table-body-table">
            <tbody>
              <?php while ($service = $services_result->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($service['service_id']) ?></td>
                  <td><?= htmlspecialchars($service['service_name']) ?></td>
                  <td><?= number_format($service['price'], 2) ?></td>
                  <td><?= htmlspecialchars($service['description']) ?: '<em>No description</em>' ?></td>
                  <td class="text-center">
                    <div class="action-buttons">
                      <button onclick='openEditServiceModal(<?= json_encode($service) ?>)' class="btn-edit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <!-- Archive Service -->
                        <form id="archiveServiceForm-<?= $service['service_id'] ?>" method="POST" style="display:inline-block;">
                          <input type="hidden" name="service_id" value="<?= $service['service_id'] ?>">
                            <input type="hidden" name="archive_service" value="1">
                          <button
                            type="button"
                            name="archive_service"
                            class="btn-archive-unique"
                            data-archive-type="service"
                            data-archive-name="<?= htmlspecialchars($service['service_name']) ?>"
                          >
                            <i class="fas fa-archive"></i>
                          </button>
                        </form>
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
        No services available for this branch.
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ======================= ADD SERVICE MODAL ======================= -->
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form id="addServiceForm" action="add_service.php" method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold" id="addServiceModalLabel">
            <i class="bi bi-plus-circle me-2"></i> Add New Service
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

      <input type="hidden" name="branch_id" value="<?= htmlspecialchars($current_branch_id) ?>">


        <div class="modal-body p-4">
          <div class="mb-3">
            <label for="serviceName" class="form-label fw-semibold">Service Name</label>
            <input type="text" name="service_name" id="serviceName" class="form-control" placeholder="Enter service name" required>
          </div>

          <div class="mb-3">
            <label for="servicePrice" class="form-label fw-semibold">Price (₱)</label>
            <input type="number" step="0.01" name="price" id="servicePrice" class="form-control" placeholder="Enter price" required>
          </div>

          <div class="mb-3">
            <label for="serviceDescription" class="form-label fw-semibold">Description</label>
            <textarea name="description" id="serviceDescription" class="form-control" rows="3" placeholder="Optional"></textarea>
          </div>

          <!-- Inline confirmation area -->
          <div id="confirmSectionService" class="alert alert-warning mt-3 d-none">
            <p id="confirmMessageService">Are you sure you want to save this service?</p>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary btn-sm" id="cancelConfirmService">Cancel</button>
              <button type="submit" class="btn btn-success btn-sm">Yes, Save Service</button>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top-0">
          <!-- Trigger confirmation -->
          <button type="button" id="openConfirmService" class="btn btn-success fw-semibold">
            <i class="bi bi-save me-1"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

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
              <input type="number" class="form-control" id="price" name="price" step="0.01" required>
            </div>

            <div class="col-md-6">
              <label for="markupPrice" class="form-label">Markup (%)</label>
              <input type="number" class="form-control" id="markupPrice" name="markup_price" step="0.01" required>
            </div>

            <div class="col-md-6">
              <label for="retailPrice" class="form-label">Retail Price</label>
              <input type="number" class="form-control" id="retailPrice" name="retail_price" readonly>
            </div>

            <!-- Other product fields -->
            <div class="col-md-6">
              <label for="ceilingPoint" class="form-label">Ceiling Point</label>
              <input type="number" class="form-control" id="ceilingPoint" name="ceiling_point" required>
            </div>

            <div class="col-md-6">
              <label for="criticalPoint" class="form-label">Critical Point</label>
              <input type="number" class="form-control" id="criticalPoint" name="critical_point" required>
            </div>

            <div class="col-md-6">
              <label for="stocks" class="form-label">Stocks</label>
              <input type="number" class="form-control" id="stocks" name="stocks" required>
            </div>

            <div class="col-md-6">
              <label for="vat" class="form-label">VAT (%)</label>
              <input type="number" class="form-control" id="vat" name="vat" step="0.01" required>
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
          <button type="button" id="openConfirmProduct" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addStockForm" method="post" action="add_stock.php">
        <input type="hidden" name="branch_id" value="<?= $_SESSION['current_branch_id'] ?? $_SESSION['branch_id'] ?>">

        <div class="modal-header">
          <h5 class="modal-title">Add Stock</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <!-- ✅ New: Scan / type barcode -->
          <label class="mb-1">Scan / Type Barcode</label>
          <input type="text"
                 class="form-control mb-2"
                 id="addstock_barcode"
                 name="barcode"
                 placeholder="Scan or type barcode, then Enter"
                 autocomplete="off"
                 inputmode="numeric">

          <div class="form-text mb-2">
            Tip: You can leave product unselected if you scan a barcode.
          </div>

          <!-- Product (NOT required if barcode provided) -->
            <label>Select Product (optional if barcode used)</label>
            <select name="product_id" class="form-control" id="addstock_product">
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


          <label class="mt-3">Stock Amount</label>
          <input type="number" class="form-control" id="addstock_qty" name="stock_amount" min="1" required>

          <!-- Inline confirmation (kept as you had it) -->
          <div id="confirmSection" class=" d-none mx-auto text-center" style="max-width: 350px;">
            <p id="confirmMessage"></p>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary btn-sm" id="cancelConfirm">Cancel</button>
              <button type="submit" class="btn btn-success btn-sm">Yes, Add Stock</button>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" id="openConfirmStock" class="btn btn-success">Add</button>
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
              <input type="number" step="0.01" class="form-control" id="edit_price" name="price" required>
            </div>

            <!-- Markup -->
            <div class="col-md-6">
              <label class="form-label">Markup (%)</label>
              <input type="number" step="0.01" class="form-control" id="edit_markup" name="markup_price" required>
            </div>

            <!-- Retail Price (Readonly) -->
            <div class="col-md-6">
              <label class="form-label">Retail Price</label>
              <input type="number" class="form-control" id="edit_retail_price" name="retail_price" readonly>
            </div>

            <!-- Ceiling Point -->
            <div class="col-md-6">
              <label class="form-label">Ceiling Point</label>
              <input type="number" class="form-control" id="edit_ceiling_point" name="ceiling_point" required>
            </div>

            <!-- Critical Point -->
            <div class="col-md-6">
              <label class="form-label">Critical Point</label>
              <input type="number" class="form-control" id="edit_critical_point" name="critical_point" required>
            </div>

            <!-- Stock -->
            <div class="col-md-6">
              <label class="form-label">Stock</label>
              <input type="number" class="form-control" id="edit_stock" name="stock" disabled>
            </div>

            <!-- VAT -->
            <div class="col-md-6">
              <label class="form-label">VAT (%)</label>
              <input type="number" step="0.01" class="form-control" id="edit_vat" name="vat" required>
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
          <button type="submit" class="btn btn-primary">Save Changes</button>
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

<!-- Stock Transfer Request Modal -->
<!-- Stock Transfer Request (templated like Add Stock modal) -->
<div class="modal fade" id="transferModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="transferForm" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title">Stock Transfer Request</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <!-- Source Branch -->
          <label for="source_branch" class="form-label fw-semibold mb-1"><i class="fas fa-warehouse"></i> Source Branch</label>
          <select class="form-control mb-3" id="source_branch" name="source_branch" required>
            <option value="">Select source branch</option>
          </select>
          <div class="invalid-feedback">Please select a source branch.</div>

          <!-- Product -->
          <label for="product_id" class="form-label fw-semibold mb-1"><i class="fas fa-box"></i> Product</label>
          <select class="form-control mb-1" id="product_id" name="product_id" required disabled>
            <option value="">Select a branch first</option>
          </select>
          <div class="form-text mb-3">Select a source branch to load available products.</div>
          <div class="invalid-feedback">Please select a product.</div>

          <!-- Destination Branch -->
          <label for="destination_branch" class="form-label fw-semibold mb-1"><i class="fas fa-truck"></i> Destination Branch</label>
          <select class="form-control mb-3" id="destination_branch" name="destination_branch" required>
            <option value="">Select destination branch</option>
          </select>
          <div class="invalid-feedback">Please select a destination branch.</div>

          <!-- Quantity -->
          <label for="quantity" class="form-label fw-semibold mb-1"><i class="fas fa-sort-numeric-up"></i> Quantity</label>
          <input type="number" class="form-control" id="quantity" name="quantity" min="1" required placeholder="Enter quantity">
          <div class="invalid-feedback">Please enter a valid quantity.</div>

          <!-- Message / Feedback -->
          <div id="transferMsg" class="mt-3"></div>
        </div>

        <div class="modal-footer">
          <!-- Keep same submit button & IDs so existing JS (spinner/toast) works -->
          <button type="submit" class="btn btn-success" id="transferSubmit">
            <span class="btn-label">Submit Request</span>
            <span class="btn-spinner spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
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
          <h5 class="modal-title mb-0" id="transferstockLabel">Stock Transfer Request</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
          <form id="transferForm" autocomplete="off">
            
          <!-- Source Branch -->
          <div class="mb-3 px-3">
            <label for="source_branch" class="form-label fw-semibold">Source Branch</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-warehouse"></i></span>
              <select class="form-select" id="source_branch" name="source_id" required>
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
            <div class="invalid-feedback">Please enter a valid quantity.</div>
          </div>

          <!-- Message / Feedback -->
          <div id="transferMsg" class="mt-3 "></div>

          <!-- Submit -->
          <button type="submit" class="btn btn w-100 py-3" id="transferSubmit">
            <span class="btn-label">Submit Request</span>
            <span class="btn-spinner spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
          </button>
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
      <div class="modal-header bg-danger text-white">
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

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="notifications.js"></script>

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
  function openEditModal(id, name, category, price, stock, markup_price, ceiling_point, critical_point, branch_id) {
  document.getElementById('edit_product_id').value = id;
  document.getElementById('edit_product_name').value = name;
  document.getElementById('edit_category').value = category;
  document.getElementById('edit_price').value = price;
  document.getElementById('edit_markup').value = markup_price;
  document.getElementById('edit_retail_price').value = (parseFloat(price) + (parseFloat(price) * (parseFloat(markup_price) / 100))).toFixed(2);
  document.getElementById('edit_ceiling_point').value = ceiling_point;
  document.getElementById('edit_critical_point').value = critical_point;
  document.getElementById('edit_stock').value = stock;

  // ✅ Set branch_id correctly
  document.getElementById('edit_branch_id').value = branch_id;

  // ✅ Set the branch dropdown visually too (even if it's disabled)
  const branchDropdown = document.getElementById('edit_branch');
  if (branchDropdown) {
    branchDropdown.value = branch_id;
  }

  // Show the modal
  const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
  editModal.show();
}
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

//for toast container
function showToast(message, type = 'info') {
  const toastEl   = document.getElementById('appToast');
  const toastBody = document.getElementById('appToastBody');
  if (!toastEl || !toastBody) return;

  // reset classes
  toastEl.classList.remove('bg-success','bg-danger','bg-info','bg-warning');
  
  // map type to class
  const map = {
    success: 'bg-success',
    danger:  'bg-danger',
    info:    'bg-info',
    warning: 'bg-warning'
  };
  toastEl.querySelector('.toast-header').className = `toast-header text-white ${map[type] || 'bg-info'}`;
  
  toastBody.textContent = message;

  const bsToast = new bootstrap.Toast(toastEl);
  bsToast.show();
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
  const modalEl  = document.getElementById('addProductModal');
  const bcInput  = document.getElementById('barcode');
  const nameInput = document.getElementById('productName');

  let buffer = '';
  let lastTs = 0;
  let silenceTimer = null;
  let listening = false;

  const INTER_CHAR_MS = 50;
  const SILENCE_MS    = 120;

  function resetBuffer() { ... }
  function finalizeScan() { ... }
  function scheduleSilentFinalize() { ... }
  function onKey(e) { ... }

  modalEl.addEventListener('shown.bs.modal', () => {
    listening = true;
    resetBuffer();
    bcInput.focus();
  });
  modalEl.addEventListener('hidden.bs.modal', () => {
    listening = false;
    resetBuffer();
  });

  document.addEventListener('keydown', onKey);

  bcInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      buffer = bcInput.value;
      finalizeScan();
    }
  });
})();
</script>
</body>
</html>
