<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: dashboard.php");
  exit;
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
  echo "Invalid user ID.";
  exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$branches = $conn->query("SELECT * FROM branches");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];
  $role = $_POST['role'];
  $branch_id = ($role === 'staff') ? $_POST['branch_id'] : null;

  // Update with hashed password only if new one is provided
  if (!empty($password)) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE users SET username=?, password=?, role=?, branch_id=? WHERE id=?");
    $update->bind_param("sssii", $username, $hashed, $role, $branch_id, $user_id);
  } else {
    $update = $conn->prepare("UPDATE users SET username=?, role=?, branch_id=? WHERE id=?");
    $update->bind_param("ssii", $username, $role, $branch_id, $user_id);
  }

  $update->execute();
  header("Location: accounts.php"); // Go back to the account list
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Account</title>
</head>
<body>
  <h2>Edit Account</h2>
  <form method="POST">
    <label>Username:</label><br>
    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required><br><br>

    <label>New Password (leave blank to keep current):</label><br>
    <input type="password" name="password"><br><br>

    <label>Role:</label><br>
    <select name="role" onchange="toggleBranch(this.value)">
      <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
      <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
    </select><br><br>

    <div id="branchGroup" style="display: <?= $user['role'] === 'staff' ? 'block' : 'none' ?>;">
      <label>Branch:</label><br>
      <?php while ($row = $branches->fetch_assoc()): ?>
        <label>
          <input type="radio" name="branch_id" value="<?= $row['branch_id'] ?>" <?= ($user['branch_id'] == $row['branch_id']) ? 'checked' : '' ?>>
          <?= htmlspecialchars($row['branch_name']) ?>
        </label><br>
      <?php endwhile; ?>
    </div>

    <br><button type="submit">Update Account</button>
  </form>

  <script>
    function toggleBranch(role) {
      document.getElementById('branchGroup').style.display = (role === 'staff') ? 'block' : 'none';
    }
  </script>
</body>
</html>
s