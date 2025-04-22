<?php
include 'config/db.php';

// Handle Create Branch Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_branch'])) {
    $branch_number = $_POST['branch_number'];  // Changed from branch_id to branch_number
    $branch_name = $_POST['branch_name'];
    $branch_location = $_POST['branch_location'];
    $branch_email = $_POST['branch_email'];
    $branch_contact = $_POST['branch_contact'];

    // Insert the new branch into the database
    $sql_create = "INSERT INTO branches (branch_number, branch_name, branch_location, branch_email, branch_contact) 
                   VALUES ('$branch_number', '$branch_name', '$branch_location', '$branch_email', '$branch_contact')";
    if ($conn->query($sql_create) === TRUE) {
        echo "<script>alert('Branch created successfully!'); window.location.href = 'inventory.php';</script>";
    } else {
        echo "<script>alert('Error creating branch: " . $conn->error . "');</script>";
    }
}


if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'yes' && isset($_POST['branches_to_delete'])) {
    $branches_to_delete = $_POST['branches_to_delete'];

    // Assuming $conn is your database connection
    foreach ($branches_to_delete as $branch_id) {
        $branch_id = (int)$branch_id; // Ensure it's an integer to prevent SQL injection
        $delete_query = "DELETE FROM branches WHERE branch_number = ?";
        
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $branch_id);
        if ($stmt->execute()) {
            echo "Branch with ID $branch_id deleted successfully.";
        } else {
            echo "Error deleting branch with ID $branch_id.";
        }
        $stmt->close();
    }

    // Redirect or reload page after deletion
    header("Location: your_page.php");
    exit;
}



$branch_number = isset($_GET['branch']) ? intval($_GET['branch']) : 1; // default to branch 1 if no branch is passed
$sql = "SELECT product_id, product_name, category, price, branch_{$branch_number}_stock AS stock FROM inventory";
$result = $conn->query($sql);  // Execute the query and assign the result to $result

// Fetch branches for delete modal
$branches_result = $conn->query("SELECT * FROM branches");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Branch Inventory</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* General Styles */
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
    body { display: flex; background: #f4f4f4; height: 100vh; }
    .sidebar {
      width: 220px;
      background: #f7931e;
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
    .sidebar a:hover, .sidebar a.active { background-color: #e67e00; }
    .sidebar a i { margin-right: 10px; }
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
  z-index: 999;
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

.modal-footer button {
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

    
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h2>ADMIN</h2>
    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>
    <a href="#" class="active"><i class="fas fa-box"></i> Inventory</a>
    <a href="#"><i class="fas fa-user"></i> Accounts</a>
    <a href="#"><i class="fas fa-archive"></i> Archive</a>
    <a href="#"><i class="fas fa-calendar-alt"></i> Logs</a>
    <a href="index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <!-- Content -->
  <div class="content">
    <div class="search-box">
      <i class="fas fa-search" style="margin-right: 10px;"></i>
      <input type="text" placeholder="SEARCH ITEM">
    </div>

   <div class="branches">
   <?php while ($branch = $branches_result->fetch_assoc()): ?>
  <a href="inventory.php?branch=<?= $branch['branch_number'] ?>" class="<?= ($branch['branch_number'] == $branch_number) ? 'active' : '' ?>">
    <?= $branch['branch_name'] ?> - <?= $branch['branch_location'] ?>
  </a>
<?php endwhile; ?>
    
    </div>
    

    <div class="table">
      <table>
        <thead>
          <tr>
            <th></th>
            <th>ID</th>
            <th>PRODUCT</th>
            <th>CATEGORY</th>
            <th>PRICE</th>
            <th>STOCKS</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><i class="fas fa-ellipsis-v"></i></td>
                <td><?= $row['product_id'] ?></td>
                <td><?= $row['product_name'] ?></td>
                <td><?= $row['category'] ?></td>
                <td><?= number_format($row['price'], 2) ?></td>
                <td><?= $row['stock'] ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6">No products found for this branch.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="actions">
      <button class="btn btn-create" onclick="openCreateModal()"><i class="fas fa-plus-circle"></i> CREATE BRANCH</button>
      <button class="btn btn-delete" onclick="openDeleteModal()"><i class="fas fa-trash"></i> DELETE BRANCH</button>
    </div>
  </div>

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
</body>
</html>
