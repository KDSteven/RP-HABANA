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

// Queries based on role
// Display products and their inventory in a branch
if ($role === 'staff') {
  $result = $conn->query("SELECT p.product_id, p.product_name, p.category, p.price, p.markup_price, p.ceiling_point, p.critical_point, i.stock 
                          FROM products p 
                          LEFT JOIN inventory i ON p.product_id = i.product_id 
                          WHERE i.branch_id = $branch_id");
} else {
  // Admin can select any brancha
  if ($current_branch_id) {
      $result = $conn->query("SELECT p.product_id, p.product_name, p.category, p.price, p.markup_price, p.ceiling_point, p.critical_point, i.stock 
                              FROM products p 
                              LEFT JOIN inventory i ON p.product_id = i.product_id 
                              WHERE i.branch_id = $current_branch_id");
  } else {
      $result = $conn->query("SELECT p.product_id, p.product_name, p.category, p.price, p.markup_price, p.ceiling_point, p.critical_point, i.stock 
                              FROM products p 
                              LEFT JOIN inventory i ON p.product_id = i.product_id");
  }
}


// Handle Create Branch Request (SAFE with prepared statements)
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

// Handle Delete Branches
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    if (!empty($_POST['branches_to_delete'])) {
        foreach ($_POST['branches_to_delete'] as $branch_id_to_delete) {
            $branch_id_to_delete = (int)$branch_id_to_delete;
            $stmt = $conn->prepare("DELETE FROM branches WHERE branch_id = ?");
            $stmt->bind_param("i", $branch_id_to_delete);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: inventory.php?success=branches_deleted");
        exit;
    } else {
        header("Location: inventory.php?error=no_branch_selected");
        exit;
    }
}
// Fix branch_id usage for update and navigation
$branch_id = isset($_GET['branch']) ? intval($_GET['branch']) : ($branch_id ?? null);

// Fetch branches for navigation
if ($role === 'staff') {
  $stmt = $conn->prepare("SELECT * FROM branches WHERE branch_id = ?");
  $stmt->bind_param("i", $branch_id);
  $stmt->execute();
  $branches_result = $stmt->get_result();
  $stmt->close();
} else {
  $branches_result = $conn->query("SELECT * FROM branches");
}

// Searches product
$search = $_GET['search'] ?? '';
$searchQuery = '';

if ($search) {
    $searchQuery = " AND (p.product_name LIKE '%" . $conn->real_escape_string($search) . "%' 
                     OR p.category LIKE '%" . $conn->real_escape_string($search) . "%')";
}

// Queries based on role
if ($role === 'staff') {
  $result = $conn->query("SELECT p.product_id, p.product_name, p.category, p.price, p.markup_price, p.ceiling_point, p.critical_point, i.stock 
                          FROM products p 
                          LEFT JOIN inventory i ON p.product_id = i.product_id 
                          WHERE i.branch_id = $branch_id" . $searchQuery);
} else {
  // Admin can select any branch
  if ($current_branch_id) {
      $result = $conn->query("SELECT p.product_id, p.product_name, p.category, p.price, p.markup_price, p.ceiling_point, p.critical_point, i.stock 
                              FROM products p 
                              LEFT JOIN inventory i ON p.product_id = i.product_id 
                              WHERE i.branch_id = $current_branch_id" . $searchQuery);
  } else {
      $result = $conn->query("SELECT p.product_id, p.product_name, p.category, p.price, p.markup_price, p.ceiling_point, p.critical_point, i.stock 
                              FROM products p 
                              LEFT JOIN inventory i ON p.product_id = i.product_id" . $searchQuery);
  }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Branch Inventory</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

  
<style>
    /* General Styles */
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
    body { display: flex; background: #f4f4f4; height: 100vh; }
    .sidebar {
      width: 220px;
      background-color: #f7931e;
      color: white;
      padding: 30px 10px;
    }

    .sidebar h2 { margin-bottom: 40px; }

    .sidebar a {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: white;
      padding: 10px 20px;
      margin: 5px 0;
      border-radius: 5px;
    }

    .sidebar a:hover, .sidebar a.active {
      background-color: #e67e00;
    }

    .sidebar a i { margin-right: 10px; }

    .content {
      flex: 1;
      padding: 40px;
  
    }

    input, select, button {
      padding: 10px;
      width: 250px;
      margin-bottom: 15px;
      border: 1px solid #aaa;
      border-radius: 5px;
    }

    button {
      background-color: #f7931e;
      color: white;
      font-weight: bold;
      border: none;
      cursor: pointer;
    }

    button:hover {
      background-color: #e67e00;
    }
    .content { flex: 1; padding: 30px; }
    .search-box {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }
    .search-box input {
      padding: 10px;
      width: 100%;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    .branches {
      background: white;
      border-radius: 5px;
      padding: 10px;
      margin-bottom: 20px;
    }
    .branches a {
      display: block;
      padding: 15px;
      border-bottom: 1px solid #ddd;
      color: #333;
      font-weight: bold;
      text-decoration: none;
    }
    .branches a:hover, .branches a.active { background: #f2f2f2; }
    .table {
      background: white;
      border-radius: 5px;
      overflow: hidden;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    table thead { background: #eee; }
    table th, table td {
      padding: 10px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    .actions {
      margin-top: 20px;
      display: flex;
      gap: 10px;
    }
    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      color: white;
      font-weight: bold;
      cursor: pointer;
    }
    .btn-create { background: #28a745; }
    .btn-delete { background: #dc3545; }
    
    .modal {
  display: none;
  position: fixed;
  z-index: 9999;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  justify-content: center;
  align-items: center;
  font-family: Arial, sans-serif;
}

.modal-content {
  background-color: #e0e0e0;
  padding: 30px;
  border-radius: 10px;
  width: 500px;
  max-width: 90%;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.modal-header {
  font-size: 22px;
  font-weight: bold;
  margin-bottom: 20px;
}

.modal form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.modal input,
.modal label {
  font-size: 14px;
}

.modal input[type="text"],
.modal input[type="email"] {
  width: 100%;
  padding: 10px;
  border-radius: 6px;
  border: none;
  background-color: #d9d9d9;
}

.modal-footer {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 15px;
}

.modal-footer : {
  background-color: #28a745;
  color: white;
  padding: 12px;
  font-size: 15px;
  font-weight: bold;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.modal-footer button:hover {
  background-color: #218838;
}

.modal-footer button.cancel {
  background-color: #c4c4c4;
  color: black;
}

.modal-footer button.cancel:hover {
  background-color: #aaa;
}

/* Danger Modal (Delete Confirmation) */
#deleteConfirmationModal{
    display: none;
  position: fixed;
  padding-top: 250px;
 padding-left: 650px;
  z-index: 999;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  justify-content: center;
  align-items: center;
  font-family: Arial, sans-serif;
}

#deleteConfirmationModal .modal-content {
  background-color: #fff;
  padding: 35px;
  border-radius: 12px;
  width: 500px;
  max-width: 90%;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
  text-align: center;
  border-top: 6px solid #d32f2f;
}

#deleteConfirmationModal .modal-header {
  font-size: 24px;
  font-weight: bold;
  color: #d32f2f;
  margin-bottom: 15px;
}

#deleteConfirmationModal p {
  font-size: 16px;
  color: #333;
  margin-bottom: 25px;
  line-height: 1.6;
}

#deleteConfirmationModal form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

#deleteConfirmationModal button {
  padding: 14px;
  font-size: 16px;
  font-weight: bold;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  transition: background-color 0.2s;
}

#deleteConfirmationModal button[name="confirm_delete"] {
  background-color: #d32f2f;
  color: white;
}

#deleteConfirmationModal button[name="confirm_delete"]:hover {
  background-color: #b71c1c;
}

#deleteConfirmationModal button[type="button"] {
  background-color: #f0f0f0;
  color: #333;
}

#deleteConfirmationModal button[type="button"]:hover {
  background-color: #ddd;
}


/* Delete Selection Modal */
#deleteSelectionModal{
display: flex;
  z-index: 999;
  top: 0; left: 0;
 padding-top: 250px;
 padding-left: 650px;
  background-color: rgba(0, 0, 0, 0.5);
  justify-content: center;
  align-items: center;
  font-family: Arial, sans-serif;
}

#deleteSelectionModal .modal-content {
  background-color: #fff8f7;
  padding: 30px 24px;
  border-radius: 10px;
  max-width: 90%;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    
}

#deleteSelectionModal .modal-header {
  font-size: 22px;
  font-weight: bold;
  color: #d32f2f;
  margin-bottom: 20px;
  text-align: center;
}

#deleteSelectionModal label {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  font-size: 15px;
  color: #444;
}

#deleteSelectionModal button {
  width: 100%;
  padding: 14px;
  margin-top: 20px;
  background-color: #d32f2f;
  color: white;
  font-weight: bold;
  font-size: 15px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.3s;
}

#deleteSelectionModal button:hover {
  background-color: #b71c1c;
}
.branches a {
  display: inline-block;
  margin: 5px;
  padding: 10px 15px;
  background-color: #eee;
  color: #000;
  text-decoration: none;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.branches a.active {
  background-color: #4CAF50;
  color: white;
  font-weight: bold;
}
.branches a {
  display: flex;
  margin: 5px;
  padding: 10px 15px;
  background-color: #eee;
  color: #000;
  text-decoration: none;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.branches a.active {
  background-color: #4CAF50;
  color: white;
  font-weight: bold;
}

    
  </style>
</head>
<body>
<div class="sidebar">
    <h2><?= strtoupper($role) ?></h2>
    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>
    <a href="inventory.php?branch=<?= $branch_id ?>"><i class="fas fa-box"></i> Inventory</a>
    <?php if ($role !== 'admin'): ?>
      <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
    <?php endif; ?>
    <a href="history.php"><i class="fas fa-history"></i> Sales History</a>

    <?php if ($role === 'admin'): ?>
      <a href="accounts.php"><i class="fas fa-user"></i> Accounts</a>
      <a href=""><i class="fas fa-archive"></i> Archive</a>
      <a href=""><i class="fas fa-calendar-alt"></i> Logs</a>


    <?php endif; ?>
    <a href="index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <!-- Content -->
  <div class="content">
  <div class="search-box">
  <i class="fas fa-search" style="margin-right: 10px;"></i>
  <form method="GET" action="inventory.php">
    <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="SEARCH ITEM">
  </form>
</div>

    <div class="branches">
      <?php while ($branch = $branches_result->fetch_assoc()): ?>
        <a href="inventory.php?branch=<?= $branch['branch_id'] ?>" class="<?= ($branch['branch_id'] == $branch_id) ? 'active' : '' ?>">
          <?= $branch['branch_name'] ?> - <?= $branch['branch_location'] ?>
        </a>
      <?php endwhile; ?>
    </div>

    <div class="table">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>PRODUCT</th>
        <th>CATEGORY</th>
        <th>PRICE</th>
        <th>MARKUP PRICE</th>
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
        // determine if this row is critical
        $isCritical = ($row['stock'] <= $row['critical_point']);
        // choose a Bootstrap table class
        $rowClass = $isCritical ? 'table-danger' : 'table-success';
      ?>
      <tr class="<?= $rowClass ?>">
        <td><?= $row['product_id'] ?></td>
        <td><?= htmlspecialchars($row['product_name']) ?></td>
        <td><?= htmlspecialchars($row['category']) ?></td>
        <td><?= number_format($row['price'], 2) ?></td>
        <td><?= number_format($row['markup_price'], 2) ?></td>
        <td><?= $row['ceiling_point'] ?></td>
        <td><?= $row['critical_point'] ?></td>
        <td><?= $row['stock'] ?></td>
        <td>
          <button onclick="openEditModal(
            <?= $row['product_id'] ?>,
            '<?= htmlspecialchars($row['product_name'], ENT_QUOTES) ?>',
            '<?= htmlspecialchars($row['category'], ENT_QUOTES) ?>',
            <?= $row['price'] ?>,
            <?= $row['stock'] ?>,
            <?= $row['markup_price'] ?>,
            <?= $row['ceiling_point'] ?>,
            <?= $row['critical_point'] ?>
          )">Edit</button>
        </td>
      </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="9">No products found for this branch.</td></tr>
  <?php endif; ?>
</tbody>

  </table>
</div>

    
<!-- Button to trigger modal -->
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
  Add Product
</button>

<!-- Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addProductForm" method="POST" action="add_product.php">
          <div class="mb-3">
            <label for="productName" class="form-label">Product Name</label>
            <input type="text" class="form-control" id="productName" name="product_name" required>
          </div>
          <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <input type="text" class="form-control" id="category" name="category" required>
          </div>
          <div class="mb-3">
            <label for="price" class="form-label">Price</label>
            <input type="number" class="form-control" id="price" name="price" required>
          </div>
          <div class="mb-3">
            <label for="markupPrice" class="form-label">Markup Price</label>
            <input type="number" class="form-control" id="markupPrice" name="markup_price" required>
          </div>
          <div class="mb-3">
            <label for="ceilingPoint" class="form-label">Ceiling Point</label>
            <input type="number" class="form-control" id="ceilingPoint" name="ceiling_point" required>
          </div>
          <div class="mb-3">
            <label for="criticalPoint" class="form-label">Critical Point</label>
            <input type="number" class="form-control" id="criticalPoint" name="critical_point" required>
          </div>
          <div class="mb-3">
            <label for="stocks" class="form-label">Stocks</label>
            <input type="number" class="form-control" id="stocks" name="stocks" required>
          </div>
          <select name="branch_id" required>
          <?php
            $result = $conn->query("SELECT branch_id, branch_name FROM branches");
            while ($row = $result->fetch_assoc()) {
              echo "<option value='{$row['branch_id']}'>{$row['branch_name']}</option>";
            }
          ?>
        </select>
          <button type="submit" class="btn btn-primary">Save Product</button>
        </form>
      </div>
    </div>
  </div>
</div>


  <div class="modal" id="editModal">
    <div class="modal-content">
      <div class="modal-header">Edit Product</div>
      <form id="editProductForm" method="POST" action="update_product.php?branch=<?= $branch_id ?>">
        <input type="hidden" name="product_id" id="edit_product_id">
        <input type="text" name="product_name" id="edit_product_name" required>
        <input type="text" name="category" id="edit_category" required>
        <input type="number" name="price" id="edit_price" step="0.01" required>
        <input type="number" name="markup_price" id="edit_markup_price" step="0.01" required>
        <input type="number" name="ceiling_point" id="edit_ceiling_point" required>
        <input type="number" name="critical_point" id="edit_critical_point" required>
        <input type="number" name="stock" id="edit_stock" required>

        <button type="submit">Save Changes</button>
      </form>
    </div>
  </div>
  
    <div class="actions">
      <button class="btn btn-create" onclick="openCreateModal()">Create Branch</button>
      <button class="btn btn-delete" onclick="openDeleteModal()">Delete Branch</button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Modal for Creating Branch -->
  <div class="modal" id="createModal">
    <div class="modal-content">
      <div class="modal-header">Create Branch</div>
      <form method="POST">
      <input type="text" name="branch_number" placeholder="Branch Number" required>
        <input type="text" name="branch_name" placeholder="Branch Name" required>
        <input type="text" name="branch_location" placeholder="Branch Location" required>
        <input type="email" name="branch_email" placeholder="Branch Email" required>
        <input type="text" name="branch_contact" placeholder="Branch Contact" required>
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
  

  <script>function openEditModal(id, name, category, price, stock, markup_price, ceiling_point, critical_point) {
  document.getElementById("edit_product_id").value = id;
  document.getElementById("edit_product_name").value = name;
  document.getElementById("edit_category").value = category;
  document.getElementById("edit_price").value = price;
  document.getElementById("edit_stock").value = stock;

  document.getElementById("edit_markup_price").value = markup_price;
  document.getElementById("edit_ceiling_point").value = ceiling_point;
  document.getElementById("edit_critical_point").value = critical_point;

  // Set the form action with the current branch_id to ensure correct update
  const branchId = <?= json_encode($branch_id) ?>;
  const form = document.getElementById("editProductForm");
  form.action = `update_product.php?branch=${branchId}`;

  document.getElementById("editModal").style.display = "block";
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
a            document.getElementById('deleteConfirmationModal').style.display = 'block';
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
</body>
</html>
