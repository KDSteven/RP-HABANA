<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$role = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? null;

$users = $conn->query("
    SELECT users.id, users.username, users.role, users.password, branches.branch_name
    FROM users
    LEFT JOIN branches ON users.branch_id = branches.branch_id
    WHERE users.archived = 0
");


if (isset($_POST['archive_user_id'])) {
    $uid = (int) $_POST['archive_user_id'];
    $stmt = $conn->prepare("UPDATE users SET archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    header("Location: accounts.php?archived=success");
    exit;
}


if (isset($_POST['update_user'])) {
    $id = (int) $_POST['edit_user_id'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $branch_id = ($role === 'staff') ? (int) $_POST['branch_id'] : null;
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, password=?, role=?, branch_id=? WHERE id=?");
        $stmt->bind_param("sssii", $username, $hashedPassword, $role, $branch_id, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, role=?, branch_id=? WHERE id=?");
        $stmt->bind_param("ssii", $username, $role, $branch_id, $id);
    }
    $stmt->execute();
    header("Location: accounts.php?updated=success");
    exit;
}

// Fetch all branches
$branches = $conn->query("SELECT * FROM branches");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - Accounts</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * {
      margin: 0; padding: 0; box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      display: flex;
      height: 100vh;
      background: #f5f5f5;
      color: #333;
    }

    .sidebar {
      width: 220px;
      background-color: #f7931e;
      padding: 30px 15px;
      color: white;
    }

    .sidebar h2 {
      margin-bottom: 30px;
      font-size: 22px;
      text-align: center;
    }

    .sidebar a {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: white;
      padding: 12px 15px;
      margin: 6px 0;
      border-radius: 8px;
      transition: background 0.2s;
    }

    .sidebar a:hover, .sidebar a.active {
      background-color: #e67e00;
    }

    .sidebar a i {
      margin-right: 10px;
      font-size: 16px;
    }

    .content {
      flex: 1;
      padding: 40px;
      overflow-y: auto;
    }

    .card {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }

    .card h2 {
      margin-bottom: 20px;
      font-size: 22px;
      color: #333;
    }

    form input, form select {
      width: 100%;
      padding: 10px;
      margin: 8px 0 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .radio-group {
      margin-bottom: 20px;
    }

    .radio-group label {
      display: block;
      margin-bottom: 5px;
    }

    button {
      background-color: #f7931e;
      color: white;
      padding: 10px 20px;
      font-weight: bold;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    button:hover {
      background-color: #e67e00;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    table, th, td {
      border: 1px solid #ccc;
    }

    th, td {
      padding: 12px;
      text-align: left;
    }

    th {
      background-color: #f7931e;
      color: white;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .logout-link {
      margin-top: 50px;
    }
    .modal-content {
  background-color: #f9f9f9;
  padding: 25px 30px;
  border-radius: 12px;
  width: 100%;
  max-width: 800px;
  text-align: left;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
}

.modal-header {
  font-size: 22px;
  font-weight: bold;
  margin-bottom: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body form {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.modal-body input,
.modal-body select,
.modal-body label {
  font-size: 14px;
}

.modal-body input[type="text"],
.modal-body input[type="number"],
.modal-body input[type="email"],
.modal-body input[type="date"],
.modal-body select {
  width: 100%;
  padding: 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
  background-color: #f1f1f1;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.modal-footer button {
  background-color: #28a745;
  color: white;
  padding: 10px 16px;
  font-size: 15px;
  font-weight: bold;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.modal-footer button:hover {
  background-color: #218838;
}

.modal-footer .cancel {
  background-color: #c4c4c4;
  color: black;
}

.modal-footer .cancel:hover {
  background-color: #aaa;
}
  </style>
</head>
<body>
  <div class="sidebar">
    <h2><?= strtoupper($role) ?><i class="fas fa-bell" id="notifBell" style="font-size: 24px; cursor: pointer;"></i>
<span id="notifCount" style="
    background:red; color:white; border-radius:50%; padding:2px 8px;
    font-size:12px;  position:absolute;display:none;">
0</span></h2>

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


  <div class="content">
    <div class="card">
      <h2>Create New Account</h2>
      <form method="POST" action="register_user.php" onsubmit="return validateForm()">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        
        <select name="role" id="roleSelect" onchange="toggleBranchDropdown()">
          <option value="admin">Admin</option>
          <option value="staff">Staff</option>
          <option value="stockman">Stockman</option>
        </select>

        <div id="branchRadioGroup" class="radio-group" style="display: none;">
          <p><strong>Select Branch:</strong></p>
          <?php while ($row = $branches->fetch_assoc()): ?>
            <label>
              <input type="radio" name="branch_id" value="<?= $row['branch_id'] ?>">
              <?= htmlspecialchars($row['branch_name']) ?>
            </label>
          <?php endwhile; ?>
        </div>

        <button type="submit">Create Account</button>
      </form>
    </div>

    <div class="card">
      <h2>Existing Accounts</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Branch</th>
            <th>Action</th>
          </tr>
          
        </thead>
        <tbody>
          <?php
          $users = $conn->query("
            SELECT users.id, users.username, users.role, branches.branch_name
            FROM users
            LEFT JOIN branches ON users.branch_id = branches.branch_id
          ");
          while ($user = $users->fetch_assoc()):
          ?>
          <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= ucfirst($user['role']) ?></td>
            <td><?= $user['role'] === 'staff' ? htmlspecialchars($user['branch_name']) : 'N/A' ?></td>
            <td><form method="POST" style="display:inline-block;">
    <input type="hidden" name="archive_user_id" value="<?= $user['id'] ?>">
    <button type="button" class="btn-edit"
    data-id="<?= $user['id'] ?>"
    data-username="<?= htmlspecialchars($user['username']) ?>"
    data-role="<?= $user['role'] ?>"
    data-branch="<?= $user['branch_name'] ?>"
    onclick="openEditModal(this)">Edit</button>


    <form method="POST" style="display:inline-block;">
        <input type="hidden" name="archive_user_id" value="<?= $user['id'] ?>">
        <button type="submit" onclick="return confirm('Archive this account?')" class="btn-archive">Archive</button>
    </form>
</td>

          </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>


  <div class="modal" id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
  <div class="modal-content" style="background:#fff; padding:20px; max-width:500px; margin:80px auto; border-radius:8px;">
    <span style="float:right; cursor:pointer; font-size:20px;" onclick="closeModal()">&times;</span>
    <h2>Edit Account</h2>
    <form method="POST">
      <input type="hidden" name="edit_user_id" id="editUserId">
      <label>Username</label>
      <input type="text" name="username" id="editUsername" required>

      <label>Password (leave blank to keep current)</label>
      <input type="password" name="password" id="editPassword">

      <label>Role</label>
      <select name="role" id="editRole" onchange="toggleEditBranch()">
        <option value="admin">Admin</option>
        <option value="staff">Staff</option>
        <option value="stockman">Stockman</option>
      </select>

      <div id="editBranchGroup" style="display:none; margin-top:10px;">
        <p>Select Branch:</p>
        <?php
        $branchList = $conn->query("SELECT * FROM branches");
        while ($row = $branchList->fetch_assoc()): ?>
          <label>
            <input type="radio" name="branch_id" value="<?= $row['branch_id'] ?>"> <?= $row['branch_name'] ?>
          </label><br>
        <?php endwhile; ?>
      </div>

      <button type="submit" name="update_user" style="margin-top:15px;">Save Changes</button>
    </form>
  </div>
</div>

<script src="notifications.js"></script>
<script>
function openEditModal(button) {
    document.getElementById('editModal').style.display = 'block';
    document.getElementById('editUserId').value = button.getAttribute('data-id');
    document.getElementById('editUsername').value = button.getAttribute('data-username');
    document.getElementById('editRole').value = button.getAttribute('data-role');
    toggleEditBranch();
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

function toggleEditBranch() {
    const role = document.getElementById('editRole').value;
    document.getElementById('editBranchGroup').style.display = (role === 'staff') ? 'block' : 'none';
}
</script>


  <script>
    function toggleBranchDropdown() {
      const role = document.getElementById('roleSelect').value;
      const branchGroup = document.getElementById('branchRadioGroup');
      branchGroup.style.display = (role === 'staff') ? 'block' : 'none';
    }

    function validateForm() {
      const role = document.getElementById('roleSelect').value;
      if (role === 'staff') {
        const radios = document.getElementsByName('branch_id');
        let selected = false;
        for (let radio of radios) {
          if (radio.checked) {
            selected = true;
            break;
          }
        }
        if (!selected) {
          alert('Please select a branch for the staff account.');
          return false;
        }
      }
      return true;
    }
  </script>
</body>
</html>
