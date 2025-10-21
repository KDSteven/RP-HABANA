<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: dashboard.php");
  exit;
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
  echo "Invalid user ID.";
  exit;
}

$stmt = $conn->prepare("SELECT id, username, role, branch_id, phone_number FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
  echo "User not found.";
  exit;
}

$branches = $conn->query("SELECT branch_id, branch_name FROM branches WHERE archived = 0 ORDER BY branch_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username      = trim($_POST['username'] ?? '');
  $password      = $_POST['password'] ?? '';
  $role          = $_POST['role'] ?? 'admin';
  $phone_number  = trim($_POST['phone_number'] ?? '');

  // normalize role
  if (!in_array($role, ['admin','staff','stockman'], true)) {
    $role = 'admin';
  }

  // branch_id only for staff/stockman
  $branch_id = null;
  if ($role === 'staff' || $role === 'stockman') {
    $branch_id = isset($_POST['branch_id']) && $_POST['branch_id'] !== '' ? (int)$_POST['branch_id'] : null;
  }

  // basic guards
  if ($username === '') {
    echo "Username is required.";
    exit;
  }

  // validate phone (PH: 09XXXXXXXXX or +639XXXXXXXXX)
  if (!preg_match('/^(?:\+639\d{9}|09\d{9})$/', $phone_number)) {
    echo "Invalid phone number format. Use 09123456789 or +639123456789.";
    exit;
  }

  // Build UPDATE with proper NULL handling for branch_id
  if (!empty($password)) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    if ($branch_id === null) {
      $sql  = "UPDATE users SET username = ?, password = ?, role = ?, branch_id = NULL, phone_number = ? WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssi", $username, $hashed, $role, $phone_number, $user_id);
    } else {
      $sql  = "UPDATE users SET username = ?, password = ?, role = ?, branch_id = ?, phone_number = ? WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssisi", $username, $hashed, $role, $branch_id, $phone_number, $user_id);
    }
  } else {
    if ($branch_id === null) {
      $sql  = "UPDATE users SET username = ?, role = ?, branch_id = NULL, phone_number = ? WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssi", $username, $role, $phone_number, $user_id);
    } else {
      $sql  = "UPDATE users SET username = ?, role = ?, branch_id = ?, phone_number = ? WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssisi", $username, $role, $branch_id, $phone_number, $user_id);
    }
  }

  $stmt->execute();
  $stmt->close();

  header("Location: accounts.php"); // Back to list
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Account</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;line-height:1.35;margin:24px;}
    label{font-weight:600;display:block;margin-top:12px;}
    input,select{padding:8px;width:320px;max-width:100%;}
    .muted{color:#666;font-size:.9em;}
    .group{margin:8px 0;}
    button{margin-top:16px;padding:10px 16px;cursor:pointer;}
  </style>
  <script>
    function toggleBranch(role) {
      const grp = document.getElementById('branchGroup');
      grp.style.display = (role === 'staff' || role === 'stockman') ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', () => {
      toggleBranch(document.getElementById('role').value);
    });
  </script>
</head>
<body>
  <h2>Edit Account</h2>

  <form method="POST">
    <!-- Username -->
    <label>Username:</label>
    <input
      type="text"
      name="username"
      value="<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>"
      required
      pattern="^(?=.*[A-Za-z])[A-Za-z0-9._-]{4,20}$"
      title="4–20 chars, at least one letter. Allowed: letters, numbers, dot, underscore, hyphen.">

    <!-- Phone Number -->
    <label>Phone Number:</label>
    <input
      type="text"
      name="phone_number"
      value="<?= htmlspecialchars($user['phone_number'] ?? '', ENT_QUOTES) ?>"
      placeholder="+639123456789 or 09123456789"
      pattern="^(?:\+639\d{9}|09\d{9})$"
      title="Use 09123456789 or +639123456789"
      required>

    <!-- Password -->
    <label>New Password <span class="muted">(leave blank to keep current)</span>:</label>
    <input type="password" name="password" placeholder="Optional">

    <!-- Role -->
    <label>Role:</label>
    <select name="role" id="role" onchange="toggleBranch(this.value)">
      <option value="admin"    <?= $user['role'] === 'admin'    ? 'selected' : '' ?>>Admin</option>
      <option value="staff"    <?= $user['role'] === 'staff'    ? 'selected' : '' ?>>Staff</option>
      <option value="stockman" <?= $user['role'] === 'stockman' ? 'selected' : '' ?>>Stockman</option>
    </select>

    <!-- Branch -->
    <div id="branchGroup" style="display: <?= in_array($user['role'], ['staff','stockman'], true) ? 'block' : 'none' ?>;">
      <label>Branch:</label>
      <?php if ($branches && $branches->num_rows): ?>
        <?php while ($row = $branches->fetch_assoc()): ?>
          <label class="group">
            <input
              type="radio"
              name="branch_id"
              value="<?= (int)$row['branch_id'] ?>"
              <?= ((int)$user['branch_id'] === (int)$row['branch_id']) ? 'checked' : '' ?>>
            <?= htmlspecialchars($row['branch_name'], ENT_QUOTES) ?>
          </label>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="muted">No active branches found.</div>
      <?php endif; ?>
    </div>

    <button type="submit">Update Account</button>
  </form>
</body>
</html>
