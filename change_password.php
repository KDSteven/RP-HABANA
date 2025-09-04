<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: index.html");
  exit;
}

$msg = "";
$err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if (strlen($new) < 6) {
    $err = "Password must be at least 6 characters.";
  } elseif ($new !== $confirm) {
    $err = "Passwords do not match.";
  } else {
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password=?, must_change_password=0 WHERE id=?");
    $stmt->bind_param("si", $hash, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();

    // optional: show success then redirect
    header("Location: dashboard.php");
    exit;
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Change Password</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css">
  <style>
    body { background: #0f1216; min-height: 100vh; display:flex; align-items:center; justify-content:center; }
    .cp-card {
      width: 520px; max-width: 92vw;
      background: #141922; color: #e9eef5;
      border: 1px solid #1e2430; border-radius: 18px; overflow: hidden;
      box-shadow: 0 12px 28px rgba(0,0,0,.35), 0 2px 6px rgba(0,0,0,.25);
      animation: cardIn .2s ease-out;
    }
    .cp-header {
      background: linear-gradient(135deg,#ff9d2f 0%, #ff6a00 100%);
      color: #fff; padding: 14px 18px;
      display: flex; align-items: center; gap: 10px;
    }
    .cp-body { padding: 22px; }
    .cp-sub { color: #a7b0bb; font-size: .92rem; }
    .form-label { font-weight: 600; color: #dfe6ee; }
    .input-group-text { background: #11161f; border-color: #263042; color: #98a2ad; }
    .form-control {
      background: #0f141d; color: #e9eef5; border-color: #263042;
    }
    .form-control:focus {
      background: #0f141d; color: #fff;
      border-color: #ff9100; box-shadow: 0 0 0 .2rem rgba(255,145,0,.15);
    }
    .cp-btn {
      background: linear-gradient(135deg,#ff9d2f 0%, #ff6a00 100%);
      border: 0; font-weight: 700; color: #fff;
      border-radius: 12px; padding: .75rem 1rem;
    }
    .cp-btn:hover { box-shadow: 0 10px 22px rgba(255,106,0,.25); }
    .cp-note { color: #9aa6b2; font-size: .85rem; }
    .meter { height: 8px; border-radius: 8px; background: #212a38; overflow: hidden; }
    .meter-fill { height: 100%; width: 0%; background: #ff6a00; transition: width .2s ease; }
    .alert-soft {
      background: #17202b; border: 1px solid #2a3647; color: #e9eef5; border-radius: 12px;
    }
    @keyframes cardIn { from { transform: translateY(4px); opacity: 0;} to {transform:none; opacity:1;} }
  </style>
</head>
<body>
  <div class="cp-card">
    <div class="cp-header">
      <i class="fas fa-lock"></i>
      <div>
        <div class="fw-bold">Change Password</div>
        <div class="cp-sub">For your account security, create a strong password.</div>
      </div>
    </div>

    <div class="cp-body">
      <?php if(!empty($err)): ?>
        <div class="alert alert-danger alert-soft mb-3"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <label class="form-label">New Password</label>
        <div class="input-group mb-2">
          <span class="input-group-text"><i class="fas fa-key"></i></span>
          <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6" autocomplete="new-password">
          <button class="input-group-text" type="button" id="toggleNew"><i class="fas fa-eye-slash"></i></button>
        </div>
        <div class="meter mb-3" aria-hidden="true"><div class="meter-fill" id="meterFill"></div></div>

        <label class="form-label">Confirm Password</label>
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="fas fa-check"></i></span>
          <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6" autocomplete="new-password">
          <button class="input-group-text" type="button" id="toggleConfirm"><i class="fas fa-eye-slash"></i></button>
        </div>

        <div class="cp-note mb-3">
          Tip: Use at least 8+ chars with a mix of upper/lowercase, numbers, and symbols.
        </div>

        <button type="submit" class="btn cp-btn w-100">Update Password</button>
      </form>
    </div>
  </div>

  <script>
    // Show/Hide toggles
    const toggle = (inputId, btnId) => {
      const input = document.getElementById(inputId);
      const btn = document.getElementById(btnId);
      btn.addEventListener('click', () => {
        const isPw = input.type === 'password';
        input.type = isPw ? 'text' : 'password';
        btn.querySelector('i').className = isPw ? 'fas fa-eye' : 'fas fa-eye-slash';
      });
    };
    toggle('new_password', 'toggleNew');
    toggle('confirm_password', 'toggleConfirm');

    // Simple strength meter
    const npw = document.getElementById('new_password');
    const meterFill = document.getElementById('meterFill');
    npw.addEventListener('input', () => {
      const v = npw.value;
      let score = 0;
      if (v.length >= 8) score += 25;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score += 25;
      if (/\d/.test(v)) score += 25;
      if (/[^A-Za-z0-9]/.test(v)) score += 25;
      meterFill.style.width = Math.min(score, 100) + '%';
      meterFill.style.background = score >= 75 ? '#22c55e' : score >= 50 ? '#f59e0b' : '#ef4444';
    });
  </script>
</body>
</html>
