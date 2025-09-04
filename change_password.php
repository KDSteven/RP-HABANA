<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($new) < 6) {
        $msg = "Password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $msg = "Passwords do not match.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=?, must_change_password=0 WHERE id=?");
        $stmt->bind_param("si", $hash, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        $msg = "Password updated. You can continue.";
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Change Password</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
  <h3>Change Your Password</h3>
  <?php if(!empty($msg)): ?>
    <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <form method="POST" style="max-width:400px;">
    <label class="form-label">New Password</label>
    <input type="password" class="form-control" name="new_password" required minlength="6">

    <label class="form-label mt-3">Confirm Password</label>
    <input type="password" class="form-control" name="confirm_password" required minlength="6">

    <button type="submit" class="btn btn-primary mt-3">Change Password</button>
  </form>
</body>
</html>
