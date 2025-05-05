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
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>ADMIN</h2>
    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>
    <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
    <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <a class="active" href="#"><i class="fas fa-user"></i> Accounts</a>
    <a href="#"><i class="fas fa-archive"></i> Archive</a>
    <a href="#"><i class="fas fa-calendar-alt"></i> Logs</a>
    <a class="logout-link" href="index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
            <td>
          <a href="edit_account.php?id=<?= $user['id'] ?>">Edit</a>
          </td>
          </tr>
          <?php endwhile; ?>
          

        </tbody>
      </table>
    </div>
  </div>

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
