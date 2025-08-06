<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$role = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? null;

// Fetch all users (not archived)
$users = $conn->query("
    SELECT users.id, users.username, users.role, branches.branch_name
    FROM users
    LEFT JOIN branches ON users.branch_id = branches.branch_id
    WHERE users.archived = 0
");

// Archive user
if (isset($_POST['archive_user_id'])) {
    $uid = (int) $_POST['archive_user_id'];
    $stmt = $conn->prepare("UPDATE users SET archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    header("Location: accounts.php?archived=success");
    exit;
}

// Update user
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

// Fetch branches
$branches = $conn->query("SELECT * FROM branches");


// Notifications (Pending Approvals)
$pending = 0;
if ($role === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='Pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pending = $row['pending'] ?? 0;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - Accounts</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/notifications.css">
  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/accounts.css">
  <audio id="notifSound" src="notif.mp3" preload="auto"></audio>
</head>
<body class="accounts-page">
<div class="sidebar">
    <h2>
    <?= strtoupper($role) ?>
    <span class="notif-wrapper">
        <i class="fas fa-bell" id="notifBell"></i>
        <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>>0</span>
    </span>
</h2>


    <!-- Common -->
    <a href="dashboard.php" class="active"><i class="fas fa-tv"></i> Dashboard</a>

    <!-- Admin Links -->
    <?php if ($role === 'admin'): ?>
        <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
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
    <?php if ($role === 'stockman'): ?>
        <a href="transfer.php"><i class="fas fa-exchange-alt"></i> Transfer
            <?php if ($transferNotif > 0): ?>
                <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;">
                    <?= $transferNotif ?>
                </span>
            <?php endif; ?>
        </a>
    <?php endif; ?>

    <!-- Staff Links -->
    <?php if ($role === 'staff'): ?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
  <div class="content">
    <!-- Create Account -->
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

        <div id="branchRadioGroup" class="radio-group" style="display:none;">
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

    <!-- Existing Accounts -->
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
          <?php while ($user = $users->fetch_assoc()): ?>
          <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= ucfirst($user['role']) ?></td>
            <td><?= $user['role'] === 'staff' ? htmlspecialchars($user['branch_name']) : 'N/A' ?></td>
            <td>
              <!-- Edit Button -->
              <button type="button" class="btn-edit"
                data-id="<?= $user['id'] ?>"
                data-username="<?= htmlspecialchars($user['username']) ?>"
                data-role="<?= $user['role'] ?>"
                data-branch="<?= $user['branch_name'] ?>"
                onclick="openEditModal(this)">Edit</button>

              <!-- Archive Button -->
              <form method="POST" style="display:inline-block;">
                <input type="hidden" name="archive_user_id" value="<?= $user['id'] ?>">
                <button type="submit" onclick="return confirm('Archive this account?')" class="btn-archive">Archive</button>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="edit-modal" id="editModal">
    <div class="edit-modal-content">
      <span class="edit-modal-close" onclick="closeModal()">&times;</span>
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

        <div style="margin-top:15px;">
          <button type="submit" name="update_user" class="btn-save">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script src="notifications.js"></script>
  <script>
    function openEditModal(button) {
      document.getElementById('editModal').classList.add('active');
      document.getElementById('editUserId').value = button.getAttribute('data-id');
      document.getElementById('editUsername').value = button.getAttribute('data-username');
      document.getElementById('editRole').value = button.getAttribute('data-role');
      toggleEditBranch();
    }

    function closeModal() {
      document.getElementById('editModal').classList.remove('active');
    }

    function toggleEditBranch() {
      const role = document.getElementById('editRole').value;
      document.getElementById('editBranchGroup').style.display = (role === 'staff') ? 'block' : 'none';
    }

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
          if (radio.checked) { selected = true; break; }
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
