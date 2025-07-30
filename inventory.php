<?php
session_start();

// Redirect to login if user not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

include 'config/db.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$branch_id = $_SESSION['branch_id'] ?? null;

// Handle current branch selection (from query string or session)
if (isset($_GET['branch'])) {
    $current_branch_id = intval($_GET['branch']);
    $_SESSION['current_branch_id'] = $current_branch_id;
} else {
    $current_branch_id = $_SESSION['current_branch_id'] ?? $branch_id;
}

// Search filter
$search = $_GET['search'] ?? '';
$searchQuery = '';
if ($search) {
    $searchQuery = " AND (p.product_name LIKE '%" . $conn->real_escape_string($search) . "%' 
                     OR p.category LIKE '%" . $conn->real_escape_string($search) . "%')";
}// Build base query
$sql = "
    SELECT p.product_id, p.product_name, p.category, p.price, p.markup_price,
           p.ceiling_point, p.critical_point, IFNULL(i.stock, 0) AS stock
    FROM products p
    LEFT JOIN inventory i ON p.product_id = i.product_id
";

// Add conditions
$conditions = ["p.archived = 0"];

if ($role === 'staff') {
    $conditions[] = "i.branch_id = " . (int)$branch_id;
} elseif ($current_branch_id) {
    $conditions[] = "i.branch_id = " . (int)$current_branch_id;
}

if (!empty($searchQuery)) {
    $conditions[] = $searchQuery;
}

// Combine conditions
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$result = $conn->query($sql);

// Handle Create Branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_branch'])) {
    $branch_number   = $_POST['branch_number'];
    $branch_name     = $_POST['branch_name'];
    $branch_location = $_POST['branch_location'];
    $branch_email    = $_POST['branch_email'];
    $branch_contact  = $_POST['branch_contact'];

    $stmt = $conn->prepare("INSERT INTO branches (branch_id, branch_name, branch_location, branch_email, branch_contact) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $branch_number, $branch_name, $branch_location, $branch_email, $branch_contact);

    if ($stmt->execute()) {
        header("Location: inventory.php?success=branch_created");
        exit;
    } else {
        header("Location: inventory.php?error=branch_creation_failed");
        exit;
    }
    $stmt->close();
}

// Handle Delete Branch
if (isset($_POST['archive_branch'])) {
    $branch_id = (int) $_POST['branch_id'];
    $stmt = $conn->prepare("UPDATE branches SET archived = 1 WHERE branch_id = ?");
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    header("Location: inventory.php?archived=branch");
    exit;
}


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

// Archive product
if (isset($_POST['archive_product'])) {
    $product_id = (int) $_POST['product_id'];
    $stmt = $conn->prepare("UPDATE products SET archived = 1 WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    header("Location: inventory.php?archived=success");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Branch Inventory</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" >
  <link rel="stylesheet" href="css/notifications.css">
  <link rel="stylesheet" href="css/inventory.css">
<audio id="notifSound" src="notif.mp3" preload="auto"></audio>


</head>
<body>
<div class="sidebar">
    <h2><?= strtoupper($role) ?><i class="fas fa-bell" id="notifBell" style="font-size: 24px; cursor: pointer;"></i>
<span id="notifCount" style="
    background:red; color:white; border-radius:50%; padding:2px 8px;
    font-size:12px;  position:absolute;display:none;">
0</span>

</h2>

    <!-- Common for all -->
    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>

    <?php if ($role === 'admin'): ?>
        <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
        <a href="approvals.php"><i class="fas fa-check-circle"></i> Approvals</a>
        <a href="accounts.php"><i class="fas fa-users"></i> Accounts</a>
        <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>
        <a href="logs.php"><i class="fas fa-file-alt"></i> Logs</a>
    <?php endif; ?>

    <?php if ($role === 'stockman'): ?>
        <a href="transfer.php"><i class="fas fa-exchange-alt"></i> Transfer Request</a>
    <?php endif; ?>

    <?php if ($role === 'staff'): ?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>


  <!-- Content -->
  <div class="content">
  <div class="search-box">
  <i class="fas fa-search" style="margin-right: 10px;"></i>
  <form method="GET" action="inventory.php">
    <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="SEARCH ITEM">
  </form>
</div>

    <!-- Branch Navigation -->
<div class="branches">
    <?php while ($branch = $branches_result->fetch_assoc()): ?>
        <a href="inventory.php?branch=<?= $branch['branch_id'] ?>" 
           class="<?= ($branch['branch_id'] == $branch_id) ? 'active' : '' ?>">
           <?= htmlspecialchars($branch['branch_name']) ?> - <?= htmlspecialchars($branch['branch_location']) ?>
        </a>
    <?php endwhile; ?>
</div>

<!-- Product Table -->
<div class="table mt-4">
    <table class="table table-bordered table-striped">
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
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php 
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
                        <td>
                            <!-- Archive Product Button -->
                            <form method="POST" style="display:inline-block;" 
                                  onsubmit="return confirm('Are you sure you want to archive this product?');">
                                <input type="hidden" name="product_id" value="<?= $row['product_id'] ?>">
                                <button type="submit" name="archive_product" class="btn btn-warning btn-sm">
                                    Archive
                                </button>
                            </form>

                            <!-- Edit Button -->
                            <button onclick="openEditModal(
                                <?= $row['product_id'] ?>,
                                '<?= htmlspecialchars($row['product_name'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($row['category'], ENT_QUOTES) ?>',
                                <?= $row['price'] ?>,
                                <?= $row['stock'] ?>,
                                <?= $row['markup_price'] ?>,
                                <?= $row['ceiling_point'] ?>,
                                <?= $row['critical_point'] ?>
                            )" class="btn btn-primary btn-sm">Edit</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="10">No products found for this branch.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Add Product Modal Button -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
        Add Product
    </button>
</div>

<!-- Branch Management (Admin Only) -->
<?php if ($role === 'admin'): ?>
<div class="mt-4">
  
    <h3>Manage Branches</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Branch Name</th>
                <th>Location</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $branch_query = $conn->query("SELECT * FROM branches WHERE archived = 0");
            while ($branch = $branch_query->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($branch['branch_name']) ?></td>
                    <td><?= htmlspecialchars($branch['branch_location']) ?></td>
                    <td><?= htmlspecialchars($branch['branch_email']) ?></td>
                    <td><?= htmlspecialchars($branch['branch_contact']) ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Archive this branch?');" style="display:inline-block;">
                            <input type="hidden" name="branch_id" value="<?= $branch['branch_id'] ?>">
                            <button type="submit" name="archive_branch" class="btn btn-danger btn-sm">Archive</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
      <button class="btn btn-create" onclick="openCreateModal()">Create Branch</button>
</div>
<?php endif; ?>

<!-- Button to trigger modal -->
</div><!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"> <!-- Use modal-lg for more space -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="addProductForm" method="POST" action="add_product.php">
        <div class="modal-body">
          <div class="row g-3">

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

            <div class="col-md-6">
              <label for="productName" class="form-label">Product Name</label>
              <input type="text" class="form-control" id="productName" name="product_name" required>
            </div>

            <div class="col-md-6">
              <label for="category" class="form-label">Category</label>
              <select name="category" id="category" class="form-select" required>
                <option value="">-- Select Category --</option>
                <option value="Solid">Tire</option>
                <option value="Liquid">Liquid</option>
              </select>
            </div>

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
              <input type="number" class="form-control" id="retailPrice" name="retail_price" class="form-control" readonly>
            </div>

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
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Product</button>
        </div>
      </form>
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
          <input type="hidden" name="product_id" id="edit_product_id">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Product Name</label>
              <input type="text" class="form-control" id="edit_product_name" name="product_name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <select class="form-select" id="edit_category" name="category">
                <option value="Solid">Solid</option>
                <option value="Liquid">Liquid</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Price</label>
              <input type="number" step="0.01" class="form-control" id="edit_price" name="price" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Markup (%)</label>
              <input type="number" step="0.01" class="form-control" id="edit_markup" name="markup_price" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Retail Price</label>
              <input type="number" class="form-control" id="edit_retail_price" name="retail_price" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ceiling Point</label>
              <input type="number" class="form-control" id="edit_ceiling_point" name="ceiling_point" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Critical Point</label>
              <input type="number" class="form-control" id="edit_critical_point" name="critical_point" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Stock</label>
              <input type="number" class="form-control" id="edit_stock" name="stock" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">VAT (%)</label>
              <input type="number" step="0.01" class="form-control" id="edit_vat" name="vat" value="12">
            </div>
            <div class="col-md-6">
              <label class="form-label">Expiration Date</label>
              <input type="date" class="form-control" id="edit_expiration_date" name="expiration_date">
              <small class="text-muted">Leave blank if none</small>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>

    </div>
  </div>
</div>


</body>
</html>
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
    if (event.target === modal) {
      modal.style.display = "none";
    }
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


  <!-- Modal for Creating Branch -->
  <div class="modal" id="createModal">
    <div class="modal-content">
      <div class="modal-header">Create Branch</div>
      <form method="POST">
      <input type="text" name="branch_number" placeholder="Branch Number" required pattern="\d+" title="Branch Number must be numeric">
      <input type="text" name="branch_name" placeholder="Branch Name" required pattern="^[A-Za-z0-9\s\-']+$" title="Branch name must only contain letters, numbers, spaces, hyphens, or apostrophes">
      <input type="text" name="branch_location" placeholder="Branch Location">
      <input type="email" name="branch_email" placeholder="Branch Email" required>
      <input type="text" name="branch_contact" placeholder="Branch Contact">
      <input type="text" name="branch_contact_number" placeholder="Branch Contact number">
        <div class="modal-footer">
          <button type="button" onclick="closeModal()">Cancel</button>
          <button type="submit" name="create_branch">Create Branch</button>
        </div>
      </form>
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


<!-- Modal for Confirming Deletion -->
<div class="modal" id="deleteConfirmationModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">Confirm Deletion</div>
        <form method="POST">
            <p>Are you sure you want to proceed with deleting this branch? This action cannot be undone and may impact related records.</p>
            <button type="submit" name="confirm_delete" value="yes">Yes, Delete</button>
            <button type="button" onclick="closeModals()">Cancel</button>
        </form>
    </div>
</div>
<script src="notifications.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const ceilingInput = document.getElementById('ceiling_point');
  const criticalInput = document.getElementById('critical_point');
  const form = document.getElementById('your-form-id'); // Replace with your actual form ID

  if (form && ceilingInput && criticalInput) {
    form.addEventListener('submit', function (e) {
      const ceiling = parseFloat(ceilingInput.value);
      const critical = parseFloat(criticalInput.value);

      // Ensure both values are numbers
      if (!isNaN(ceiling) && !isNaN(critical)) {
        if (critical > ceiling) {
          e.preventDefault();
          alert("❌ Critical Point cannot be greater than Ceiling Point.");
          criticalInput.focus();
        }
      }
    });
  }
});
</script>
<!-- <script>
document.addEventListener('DOMContentLoaded', function () {
  const ceilingInput = document.getElementById('ceiling_point');
  const criticalInput = document.getElementById('critical_point');
  const form = document.getElementById('your-form-id'); // Replace with your form ID

  form.addEventListener('submit', function (e) {
    const ceiling = parseInt(ceilingInput.value);
    const critical = parseInt(criticalInput.value);

    if (critical > ceiling) {
      e.preventDefault();
      alert("Critical Point cannot be greater than Ceiling Point.");
      criticalInput.focus();
    }
  });
});
</script> -->
  <script>
    function openCreateModal() {
      document.getElementById('createModal').style.display = 'flex';
    }

    function openDeleteModal() {
      document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeModal() {
      document.getElementById('createModal').style.display = 'none';
      document.getElementById('deleteModal').style.display = 'none';
    }
  </script>

<script>
function openEditModal(id, name, category, price, stock, markup_price, ceiling_point, critical_point) {
  // Fill modal fields
  document.getElementById('edit_product_id').value = id;
  document.getElementById('edit_product_name').value = name;
  document.getElementById('edit_category').value = category;
  document.getElementById('edit_price').value = price;
  document.getElementById('edit_markup').value = markup_price;
  document.getElementById('edit_retail_price').value = (parseFloat(price) + (parseFloat(price) * (parseFloat(markup_price) / 100))).toFixed(2);
  document.getElementById('edit_ceiling_point').value = ceiling_point;
  document.getElementById('edit_critical_point').value = critical_point;
  document.getElementById('edit_stock').value = stock;

  // Set form action with branch ID
  const branchId = <?= json_encode($branch_id) ?>;
  document.getElementById("editProductForm").action = `update_product.php?branch=${branchId}`;

  // Show Bootstrap modal
  const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
  editModal.show();
}
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


  <script>
    // Open the Delete Branch Modal
    function openDeleteModal() {
        document.getElementById('deleteSelectionModal').style.display = 'block';
    }

    // Open the confirmation modal after selecting branches to delete
    function openDeleteConfirmationModal() {
        const checkboxes = document.querySelectorAll('input[name="branches_to_delete[]"]:checked');
        if (checkboxes.length > 0) {
            document.getElementById('deleteSelectionModal').style.display = 'none';
            document.getElementById('deleteConfirmationModal').style.display = 'block';
        } else {
            alert('Please select at least one branch to delete.');
        }
    }

    // Close all modals
    function closeModals() {
        document.getElementById('deleteSelectionModal').style.display = 'none';
        document.getElementById('deleteConfirmationModal').style.display = 'none';
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

</body>
</html>
