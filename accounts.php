<?php
session_start();
include 'config/db.php';

// Only allow admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: dashboard.php");
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
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
    body { display: flex; height: 100vh; background: #ddd; }

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
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>ADMIN</h2>
    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>
    <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
  
      <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <a href="#" class="active"><i class="fas fa-user"></i> Accounts</a>
    <a href="#"><i class="fas fa-archive"></i> Archive</a>
    <a href="#"><i class="fas fa-calendar-alt"></i> Logs</a>


    <a href="index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="content">
    <h2>Create New Account</h2>
    <form method="POST" action="register_user.php" onsubmit="return validateForm()">
      <input type="text" name="username" placeholder="Username" required><br>
      <input type="password" name="password" placeholder="Password" required><br>

      <select name="role" id="roleSelect" onchange="toggleBranchDropdown()">
        <option value="admin">Admin</option>
        <option value="staff">Staff</option>
      </select><br>
      <h2>Existing Accounts</h2>
<table border="1" cellpadding="10" cellspacing="0" style="background:#fff; border-collapse: collapse;">
  <thead>
    <tr>
      <th>ID</th>
      <th>Username</th>
      <th>Role</th>
      <th>Branch</th>
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
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

      <div id="branchRadioGroup" style="display: none;">
        <p>Select Branch:</p>
        <?php while ($row = $branches->fetch_assoc()): ?>
          <label>
            <input type="radio" name="branch_id" value="<?= $row['branch_id'] ?>"> <?= htmlspecialchars($row['branch_name']) ?>
          </label><br>
        <?php endwhile; ?>
      </div>

      <button type="submit">Create Account</button>
    </form>
  </div>



  <script>
    function toggleBranchDropdown() {
      const role = document.getElementById('roleSelect').value;
      const radioGroup = document.getElementById('branchRadioGroup');
      radioGroup.style.display = (role === 'staff') ? 'block' : 'none';
    }

    function validateForm() {
      const role = document.getElementById('roleSelect').value;
      if (role === 'staff') {
        const radios = document.getElementsByName('branch_id');
        let isSelected = false;
        for (let i = 0; i < radios.length; i++) {
          if (radios[i].checked) {
            isSelected = true;
            break;
          }
        }
        if (!isSelected) {
          alert('Please select a branch for the staff account.');
          return false;
        }
      }
      return true;
    }
  </script>
</body>
</html>
