<?php
session_start();
include 'config/db.php';

$toast = null;

// 🔽 Pull toast from session (set by change_password.php)
if (!empty($_SESSION['toast_msg'])) {
  $toast = [
    'type' => $_SESSION['toast_type'] ?? 'info',
    'msg'  => $_SESSION['toast_msg'],
  ];
  unset($_SESSION['toast_msg'], $_SESSION['toast_type']);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? "");
    $password = trim($_POST['password'] ?? "");

    if ($username === "" || $password === "") {
        $error = "Username and password are required.";
    } else {
        $sql = "SELECT id, username, password, role, branch_id, must_change_password 
                FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $stmt->close();

                // Check if pending reset (note the status 'Pending' per your earlier code)
                $stmt = $conn->prepare("SELECT 1 FROM password_resets WHERE user_id=? AND status='Pending' LIMIT 1");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $pendingRes = $stmt->get_result();
                $hasPendingReset = ($pendingRes && $pendingRes->num_rows > 0);
                $stmt->close();

                if ($hasPendingReset) {
                    $error = "Your account has a pending password reset. Please wait for Admin approval.";
                } elseif (password_verify($password, $user['password'])) {
                    // Login success
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['role']      = $user['role'];
                    $_SESSION['branch_id'] = $user['branch_id'] ?? null;

                    // Insert login log WITH branch
                    $action       = "Login successful";
                    $branchForLog = $user['branch_id'] ?? null;

                    $logStmt = $conn->prepare("
                        INSERT INTO logs (user_id, action, details, timestamp, branch_id)
                        VALUES (?, ?, '', NOW(), ?)
                    ");
                    if ($logStmt) {
                        $logStmt->bind_param("isi", $user['id'], $action, $branchForLog);
                        $logStmt->execute();
                        $logStmt->close();
                    }

                    // Force password change if required
                    if ((int)$user['must_change_password'] === 1) {
                        header("Location: change_password.php");
                        $conn->close();
                        exit();
                    }

                    // Success -> go to dashboard
                    header("Location: dashboard.php");
                    $conn->close();
                    exit();
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Database error: " . $conn->error;
        }
    }

    // Failed login attempt log (no IP)
    if (!empty($username)) {
        $action = "Login failed for username: $username";
        $logStmt = $conn->prepare("
            INSERT INTO logs (user_id, action, details, timestamp, branch_id)
            VALUES (NULL, ?, '', NOW(), NULL)
        ");
        if ($logStmt) {
            $logStmt->bind_param("s", $action);
            $logStmt->execute();
            $logStmt->close();
        }
    }

    // show toast on the page
    if (!empty($error)) {
        $toast = ['type' => 'danger', 'msg' => $error];
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>R.P Habana - Inventory and Sales Management System</title>
  <link rel="icon" href="img/R.P.png">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { height: 100vh; }
    .left-section {
      display: flex; align-items: center; justify-content: center; text-align: center; height: 100vh;
      background:
        linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.2)),
        url('img/index.jpg') center/cover no-repeat;
    }
    .left-section h1 { color: #f39200; font-weight: bold; font-size: 5em; }
    .left-section p { font-size: 2em; color: #ffffff;}
    .right-section { background: url('img/bg.jpg') center/cover no-repeat; position: relative; }
    .overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255, 153, 0, 0.7); }
    .login-box { background: rgba(0, 0, 0, 0.7); padding: 40px; width: 550px; border-radius: 10px; color: #fff; }
    hr { height: 3px; background-color: #fff; color: #fff; }

    /* Card + inputs */
    .fp-card { border: 0; border-radius: 16px; overflow: hidden;
      box-shadow: 0 8px 24px rgba(0,0,0,.18), 0 2px 6px rgba(0,0,0,.08); animation: fp-pop .24s ease-out; }
    .fp-header { background: linear-gradient(135deg, #ff9d2f 0%, #ff6a00 100%); color: #fff; border: 0; padding: 14px 16px; }
    .fp-header i { font-size: 1.1rem; opacity: .95; }
    .fp-input-icon { background: #fff; border: 1px solid #e6e6e6; border-right: 0; color: #9aa0a6; }
    .fp-input { border-left: 0; border: 1px solid #e6e6e6; padding-top: .6rem; padding-bottom: .6rem; }
    .fp-input:focus { border-color: #ff9100; box-shadow: 0 0 0 .2rem rgba(255,145,0,.15); }

    .fp-btn { background: linear-gradient(135deg, #ff9d2f 0%, #ff6a00 100%); border: 0; color: #fff; font-weight: 600;
      padding: .7rem 1rem; border-radius: 12px; transition: transform .06s ease-in-out, box-shadow .2s ease; }
    .fp-btn:hover { box-shadow: 0 8px 18px rgba(255,106,0,.25); }
    .fp-btn:active { transform: translateY(1px); }

    .fp-footnote { font-size: .82rem; color: #7a7f85; }
    .fp-alert { border: 1px solid transparent; border-radius: 12px; padding: .6rem .75rem; font-size: .92rem; }
    .fp-alert-success { background: #f3fff6; border-color: #b6f0c0; color: #1e7f38; }
    .fp-alert-warning { background: #fff9f0; border-color: #ffe2b9; color: #8a5a00; }
    .fp-alert-error { background: #fff5f5; border-color: #ffc9c9; color: #8a1c1c; }

    @keyframes fp-pop { from { transform: translateY(4px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    .link-ghost { color: #ffffffcc; font-weight: 600; text-decoration: none; border: 0; background: transparent; }
    .link-ghost:hover, .link-ghost:focus { color: #fff; outline: none; }

    .underline-slide { position: relative; }
    .underline-slide::after { content: ""; position: absolute; left: 0; right: 0; bottom: -2px; margin-inline: auto;
      width: 0%; height: 2px; background: linear-gradient(135deg, #ff9d2f 0%, #ff6a00 100%); transition: width .18s ease-in-out; }
    .underline-slide:hover::after, .underline-slide:focus::after { width: 100%; }

    .login-box small.text-muted { color: #cfd3d6 !important; }

    .strength-weak { color: #ef4444; font-weight: 600; }
    .strength-normal { color: #f59e0b; font-weight: 600; }
    .strength-strong { color: #22c55e; font-weight: 600; }
  </style>
</head>
<body>
  <div class="container-fluid h-100">
    <div class="row h-100">
      <!-- Left Section -->
      <div class="col-md-6 left-section">
        <div>
          <h1>R.P HABANA</h1>
          <p>Inventory and Sales Management System</p>
        </div>
      </div>

      <!-- Right Section -->
      <div class="col-md-6 right-section d-flex align-items-center justify-content-center">
        <div class="overlay"></div>
        <div class="login-box position-relative">
          <h4 class="text-start">SIGN-IN</h4>
          <hr>
          <form id="loginForm" action="index.php" method="POST" novalidate>
            <div class="mb-3">
              <i class="fas fa-user"></i>
              <label class="form-label">Username</label>
              <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required>
            </div>
            <div class="mb-3">
              <i class="fas fa-lock"></i>
              <label class="form-label">Password</label>
              <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">SIGN-IN</button>

            <div class="d-flex justify-content-between align-items-center mt-3">
              <small class="text-muted">Trouble signing in?</small>

              <button type="button"
                      class="btn btn-link p-0 link-ghost underline-slide d-inline-flex align-items-center gap-2"
                      data-bs-toggle="modal"
                      data-bs-target="#forgotAccountModal"
                      aria-label="Open password recovery">
                <i class="fas fa-key" aria-hidden="true"></i>
                <span>Forgot password</span>
              </button>

              <button type="button"
                      class="btn btn-link p-0 link-ghost underline-slide d-inline-flex align-items-center gap-2"
                      data-bs-toggle="modal"
                      data-bs-target="#adminRecoveryModal">
                <i class="fas fa-shield-alt"></i>
                <span>Admin recovery</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

<!-- Step 1: Ask for Username -->
<div class="modal fade" id="forgotAccountModal" tabindex="-1" aria-labelledby="forgotUsernameLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content fp-card">
      <div class="modal-header fp-header">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-user-lock"></i>
          <h5 class="modal-title mb-0" id="forgotUsernameLabel">Password Recovery</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4">
        <form id="forgotUsernameForm" novalidate>
          <div class="mb-3">
            <label for="recoveryUsername" class="form-label fw-semibold">Enter Your Username</label>
            <div class="input-group input-group-lg">
              <span class="input-group-text fp-input-icon"><i class="fas fa-user"></i></span>
              <input type="text" id="recoveryUsername" name="username" class="form-control fp-input"
                     placeholder="e.g., johndoe" required>
              <div class="invalid-feedback ps-2">Please enter your username.</div>
            </div>
          </div>

          <div id="usernameStatus" class="text-center small text-muted mt-2">
            Enter your username to verify your registered number.
          </div>

          <button type="submit" class="btn fp-btn w-100 mt-3" id="verifyUsernameBtn">
            <span class="btn-label">Next</span>
            <span class="btn-spinner spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Step 2: Send OTP -->
<div class="modal fade" id="forgotOtpModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="forgotOtpLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content fp-card">
      <div class="modal-header fp-header">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-key"></i>
          <h5 class="modal-title mb-0" id="forgotOtpLabel">Verify Your Number</h5>
        </div>
      </div>

      <div class="modal-body p-4">
        <form id="sendOtpForm" novalidate>
          <div class="mb-3 text-center">
            <p class="fw-semibold">Your registered number:</p>
            <h4 id="maskedPhoneDisplay" class="text-primary fw-bold mb-3">09XXXXX6871</h4>
            <input type="hidden" id="actualPhoneHidden" name="phone_number">
          </div>

          <div id="otpMessage" class="mt-2 text-center small text-muted">
            We’ll send a 6-digit OTP to this number.
          </div>

          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-outline-secondary" id="otpBackBtn" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" form="sendOtpForm" class="btn fp-btn" id="sendOtpBtn">
              <span class="btn-label">Send OTP</span>
              <span class="btn-spinner spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
            </button>
          </div>

          <!-- Resend OTP button and timer -->
            <div class="text-center mt-3">
              <button type="button" class="btn btn-link p-0 link-ghost underline-slide small" id="resendOtpBtn" disabled>
                Resend OTP (<span id="resendCountdown">60</span>s)
              </button>
            </div>

          <div class="fp-footnote text-center mt-3 small text-secondary">
            All user levels can recover passwords via OTP.
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Step 3: Verify OTP -->
<div class="modal fade" id="verifyOtpModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="verifyOtpLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content fp-card">
      <div class="modal-header fp-header">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-shield-alt"></i>
          <h5 class="modal-title mb-0" id="verifyOtpLabel">Enter One-Time Password</h5>
        </div>
      </div>

      <div class="modal-body p-4">
        <form id="verifyOtpForm" novalidate>
          <div class="text-center mb-3">
            <p class="fw-semibold">A 6-digit OTP has been sent to your registered number.</p>
          </div>

          <div class="d-flex justify-content-center gap-2 mb-3">
            <!-- make OTP inputs numeric-friendly -->
            <input type="tel" inputmode="numeric" pattern="\d*" class="form-control text-center otp-input" maxlength="1" required>
            <input type="tel" inputmode="numeric" pattern="\d*" class="form-control text-center otp-input" maxlength="1" required>
            <input type="tel" inputmode="numeric" pattern="\d*" class="form-control text-center otp-input" maxlength="1" required>
            <input type="tel" inputmode="numeric" pattern="\d*" class="form-control text-center otp-input" maxlength="1" required>
            <input type="tel" inputmode="numeric" pattern="\d*" class="form-control text-center otp-input" maxlength="1" required>
            <input type="tel" inputmode="numeric" pattern="\d*" class="form-control text-center otp-input" maxlength="1" required>
          </div>

          <input type="hidden" id="verifyPhoneHidden" name="phone_number">

          <div id="otpVerifyMessage" class="mt-2 text-center small text-muted">
            Please enter the OTP to proceed with password reset.
          </div>

          <!-- In #verifyOtpModal footer -->
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-outline-secondary" id="verifyCancelBtn" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" form="verifyOtpForm" class="btn fp-btn" id="verifyOtpBtn">
              <span class="btn-label">Verify OTP</span>
              <span class="btn-spinner spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

  <!-- Admin Recovery Modal -->
  <div class="modal fade" id="adminRecoveryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
      <div class="modal-content fp-card">
        <div class="modal-header fp-header">
          <div class="d-flex align-items-center gap-2">
            <i class="fas fa-shield-alt"></i>
            <h5 class="modal-title mb-0">Admin Recovery</h5>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <form id="adminRecoverForm" novalidate>
            <div class="mb-3">
              <label class="form-label fw-semibold">Admin Username</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text fp-input-icon"><i class="fas fa-user-shield"></i></span>
                <input type="text" name="username" class="form-control fp-input" required placeholder="e.g. admin01">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Master Recovery Code</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text fp-input-icon"><i class="fas fa-key"></i></span>
                <input type="password" name="recovery_code" class="form-control fp-input" required placeholder="Enter master code">
                <button class="input-group-text" type="button" id="toggleAdminCode"><i class="fas fa-eye-slash"></i></button>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">New Password</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text fp-input-icon"><i class="fas fa-lock"></i></span>
                <input type="password" name="new_password" id="admin_new_pw" class="form-control fp-input" required minlength="8" placeholder="New password">
                <button class="input-group-text" type="button" id="toggleAdminNew"><i class="fas fa-eye-slash"></i></button>
              </div>
              <div id="adminStrengthText" class="small mt-2 text-muted">Password strength</div>
            </div>

            <div class="mb-2">
              <label class="form-label fw-semibold">Confirm Password</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text fp-input-icon"><i class="fas fa-check"></i></span>
                <input type="password" name="confirm_password" id="admin_confirm_pw" class="form-control fp-input" required minlength="8" placeholder="Confirm password">
                <button class="input-group-text" type="button" id="toggleAdminConfirm"><i class="fas fa-eye-slash"></i></button>
              </div>
              <div id="adminConfirmText" class="small mt-2 text-muted"></div>
            </div>

            <div id="adminRecoverMessage" class="mt-3"></div>

            <button type="submit" class="btn fp-btn w-100 mt-2" id="adminRecoverBtn">
              <span class="btn-label">Reset Password</span>
              <span class="btn-spinner spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
            </button>

            <div class="fp-footnote text-center mt-3">
              This action is restricted to system administrators with the master recovery code.
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast container -->
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100">
    <div id="appToast" class="toast border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header bg-primary text-white">
        <i class="fas fa-info-circle me-2"></i>
        <strong class="me-auto">System Notice</strong>
        <small>just now</small>
        <button type="button" class="btn-close btn-close-white ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="appToastBody">Action completed.</div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Login front-end validation to avoid empty submits
    document.getElementById("loginForm").addEventListener("submit", function(event) {
      var username = document.getElementById("username").value.trim();
      var password = document.getElementById("password").value.trim();

      if (!username || !password) {
        event.preventDefault();
        showToast("Username and password are required.", "danger");
        return;
      }
    });

    // Toast helper
    function showToast(message, type = 'info') {
      const toastEl = document.getElementById('appToast');
      const toastHead = toastEl.querySelector('.toast-header');
      const toastBody = document.getElementById('appToastBody');
      const map = { success:'bg-success', danger:'bg-danger', info:'bg-info', warning:'bg-warning' };
      toastHead.className = 'toast-header text-white ' + (map[type] || 'bg-info');
      toastBody.textContent = message;
      new bootstrap.Toast(toastEl).show();
    }

    // === Forgot password (Stockman/Staff) AJAX ===
    const fpForm = document.getElementById('forgotPasswordForm');
    const fpBtn = document.getElementById('forgotSubmitBtn');
    const fpSpinner = fpBtn?.querySelector('.btn-spinner');
    const fpLabel = fpBtn?.querySelector('.btn-label');
    const fpMsg = document.getElementById('forgotPasswordMessage');

    if (fpForm) {
      fpForm.addEventListener('submit', function(e) {
        e.preventDefault();
        fpSpinner?.classList.remove('d-none');
        fpBtn.disabled = true;
        if (fpLabel) fpLabel.textContent = 'Submitting...';
        fpMsg.innerHTML = '';

        fetch('password_reset.php', { method: 'POST', body: new FormData(fpForm) })
          .then(res => res.json())
          .then(data => {
            const cls =
              data.status === 'success' ? 'fp-alert fp-alert-success' :
              data.status === 'warning' ? 'fp-alert fp-alert-warning' :
              'fp-alert fp-alert-error';
            fpMsg.innerHTML = `<div class="${cls}">${data.message}</div>`;
            if (data.status === 'success') fpForm.reset();
          })
          .catch(() => {
            fpMsg.innerHTML = `<div class="fp-alert fp-alert-error">Something went wrong. Please try again.</div>`;
          })
          .finally(() => {
            fpSpinner?.classList.add('d-none');
            if (fpLabel) fpLabel.textContent = 'Submit Request';
            fpBtn.disabled = false;
          });
      });
    }

    // === Admin Recovery JS ===
    (function(){
      const form   = document.getElementById('adminRecoverForm');
      const msg    = document.getElementById('adminRecoverMessage');
      const btn    = document.getElementById('adminRecoverBtn');
      const spin   = btn?.querySelector('.btn-spinner');
      const label  = btn?.querySelector('.btn-label');

      // Show/hide toggles
      const toggle = (inputEl, btnEl) => {
        btnEl?.addEventListener('click', () => {
          const isPw = inputEl.type === 'password';
          inputEl.type = isPw ? 'text' : 'password';
          btnEl.querySelector('i').className = isPw ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
      };
      toggle(document.querySelector('input[name="recovery_code"]'), document.getElementById('toggleAdminCode'));
      toggle(document.getElementById('admin_new_pw'), document.getElementById('toggleAdminNew'));
      toggle(document.getElementById('admin_confirm_pw'), document.getElementById('toggleAdminConfirm'));

      // Password strength
      const newPw = document.getElementById('admin_new_pw');
      const strengthText = document.getElementById('adminStrengthText');
      const scoreStrength = (val) => {
        let s = 0; if (val.length >= 8) s++;
        if (/[a-z]/.test(val) && /[A-Z]/.test(val)) s++;
        if (/\d/.test(val)) s++;
        if (/[^A-Za-z0-9]/.test(val)) s++;
        return s;
      };
      const setStrengthLabel = (val) => {
        if (!strengthText) return;
        const score = scoreStrength(val);
        strengthText.classList.remove('strength-weak','strength-normal','strength-strong','text-muted');
        if (score <= 1) { strengthText.textContent = 'Strength: Weak'; strengthText.classList.add('strength-weak'); }
        else if (score === 2) { strengthText.textContent = 'Strength: Normal'; strengthText.classList.add('strength-normal'); }
        else { strengthText.textContent = 'Strength: Strong'; strengthText.classList.add('strength-strong'); }
      };
      newPw?.addEventListener('input', () => setStrengthLabel(newPw.value || ''));

      // Confirm match
      const confirmPw = document.getElementById('admin_confirm_pw');
      const confirmText = document.getElementById('adminConfirmText');
      const setConfirmLabel = () => {
        if (!confirmText) return;
        confirmText.classList.remove('text-success','text-danger','text-muted');
        if (!confirmPw.value) { confirmText.textContent = ''; return; }
        if (confirmPw.value === newPw.value) {
          confirmText.textContent = 'Passwords match'; confirmText.classList.add('text-success');
        } else {
          confirmText.textContent = 'Passwords do not match'; confirmText.classList.add('text-danger');
        }
      };
      newPw?.addEventListener('input', setConfirmLabel);
      confirmPw?.addEventListener('input', setConfirmLabel);

      // Submit
      form?.addEventListener('submit', function(e){
        e.preventDefault();
        msg.innerHTML = '';
        spin?.classList.remove('d-none');
        if (btn) btn.disabled = true;
        if (label) label.textContent = 'Processing...';

        fetch('admin_recover.php', { method: 'POST', body: new FormData(form) })
          .then(r => r.json())
          .then(d => {
            const cls = d.status === 'success' ? 'fp-alert fp-alert-success'
                      : d.status === 'warning' ? 'fp-alert fp-alert-warning'
                      : 'fp-alert fp-alert-error';
            msg.innerHTML = `<div class="${cls}">${d.message}</div>`;
            if (d.status === 'success') {
              form.reset(); setStrengthLabel(''); setConfirmLabel();
              setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('adminRecoveryModal'));
                modal?.hide();
              }, 1400);
            }
          })
          .catch(() => {
            msg.innerHTML = `<div class="fp-alert fp-alert-error">Something went wrong. Please try again.</div>`;
          })
          .finally(() => {
            spin?.classList.add('d-none');
            if (btn) btn.disabled = false;
            if (label) label.textContent = 'Reset Password';
          });
      });
    })();

    // === Server-provided toast (PHP) ===
    <?php if (!empty($toast)): ?>
      document.addEventListener('DOMContentLoaded', function () {
        showToast(<?= json_encode($toast['msg']) ?>, <?= json_encode($toast['type']) ?>);
      });
    <?php endif; ?>
  </script>
  <!-- Password Reset -->
   <script>
    // Mask function
function maskPhone(phone) {
  const clean = phone.replace(/\D/g, '');
  return clean.replace(/(\d{2})\d{5}(\d{4})/, '$1XXXXX$2');
}

// Step 1: Fetch number by username
document.getElementById('forgotUsernameForm').addEventListener('submit', async e => {
  e.preventDefault();
  const username = document.getElementById('recoveryUsername').value.trim();
  const btn = document.getElementById('verifyUsernameBtn');
  const spinner = btn.querySelector('.btn-spinner');
  const status = document.getElementById('usernameStatus');

  btn.disabled = true;
  spinner.classList.remove('d-none');
  status.textContent = 'Checking username...';

  const res = await fetch('fetch_phone.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'username=' + encodeURIComponent(username)
  });

  const data = await res.json();
  btn.disabled = false;
  spinner.classList.add('d-none');

  if (data.success) {
    // Show next modal with masked phone
    const masked = maskPhone(data.phone_number);
    document.getElementById('maskedPhoneDisplay').textContent = masked;
    document.getElementById('actualPhoneHidden').value = data.phone_number;

    const modal1 = bootstrap.Modal.getInstance(document.getElementById('forgotAccountModal'));
    modal1.hide();
    new bootstrap.Modal(document.getElementById('forgotOtpModal')).show();
  } else {
    status.textContent = data.message || 'Username not found.';
    status.classList.add('text-danger');
  }
});

// === Step 2: Send OTP with 60s cooldown ===
const sendOtpForm = document.getElementById('sendOtpForm');
const sendOtpBtn = document.getElementById('sendOtpBtn');
const resendOtpBtn = document.getElementById('resendOtpBtn');
const resendCountdown = document.getElementById('resendCountdown');
const otpMessage = document.getElementById('otpMessage');
const otpSpinner = sendOtpBtn.querySelector('.btn-spinner');
const verifyPhoneHidden = document.getElementById('verifyPhoneHidden'); // for step 3 modal

let otpCooldownTimer = null;
let cooldownSeconds = 60;

function startCooldown() {
  resendOtpBtn.disabled = true;
  resendCountdown.textContent = cooldownSeconds;
  otpCooldownTimer = setInterval(() => {
    cooldownSeconds--;
    resendCountdown.textContent = cooldownSeconds;
    if (cooldownSeconds <= 0) {
      clearInterval(otpCooldownTimer);
      resendOtpBtn.disabled = false;
      cooldownSeconds = 60;
    }
  }, 1000);
}

async function sendOtp(phone) {
  sendOtpBtn.disabled = true;
  otpSpinner.classList.remove('d-none');
  otpMessage.textContent = 'Sending OTP...';
  otpMessage.classList.remove('text-danger','text-success');

  try {
    const res = await fetch('send_otp.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'phone_number=' + encodeURIComponent(phone)
    });
    const data = await res.json();

    if (data.success) {
      otpMessage.textContent = 'OTP sent successfully!';
      otpMessage.classList.add('text-success');
      startCooldown();

      // --- SHOW VERIFY MODAL ---
      const modal2 = bootstrap.Modal.getInstance(document.getElementById('forgotOtpModal'));
      modal2.hide();
      document.getElementById('verifyPhoneHidden').value = phone;
      new bootstrap.Modal(document.getElementById('verifyOtpModal')).show();
    } else {
      otpMessage.textContent = data.message || 'Failed to send OTP.';
      otpMessage.classList.add('text-danger');
    }
  } catch (err) {
    otpMessage.textContent = 'Error sending OTP.';
    otpMessage.classList.add('text-danger');
  } finally {
    sendOtpBtn.disabled = false;
    otpSpinner.classList.add('d-none');
  }
}

// Main Send OTP
sendOtpForm.addEventListener('submit', e => {
  e.preventDefault();
  const phone = document.getElementById('actualPhoneHidden').value.trim();
  sendOtp(phone);
});

// Resend OTP
resendOtpBtn.addEventListener('click', () => {
  const phone = document.getElementById('actualPhoneHidden').value.trim();
  if (!resendOtpBtn.disabled) sendOtp(phone);
});


// Step 3: Verify OTP
document.getElementById('verifyOtpForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = document.getElementById('verifyOtpBtn');
  const spinner = btn.querySelector('.btn-spinner');
  const message = document.getElementById('otpVerifyMessage');

  const otpInputs = document.querySelectorAll('.otp-input');
  const otpCode = Array.from(otpInputs).map(i => i.value).join('');
  const phone = document.getElementById('verifyPhoneHidden').value.trim();

  if (otpCode.length !== 6) {
    message.textContent = 'Please enter all 6 digits.';
    message.classList.add('text-danger');
    return;
  }

  btn.disabled = true;
  spinner.classList.remove('d-none');
  message.textContent = 'Verifying OTP...';
  message.classList.remove('text-danger');

  try {
    const res = await fetch('verify_otp.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'phone_number=' + encodeURIComponent(phone) + '&otp=' + encodeURIComponent(otpCode)
    });
    const data = await res.json();

    if (data.success) {
      message.textContent = 'OTP verified! Redirecting...';
      message.classList.add('text-success');

      // Let the server decide where to go
      setTimeout(() => {
        window.location.href = data.redirect || 'change_password.php';
      }, 800);
    } else {
      message.textContent = data.message || 'Invalid or expired OTP.';
      message.classList.add('text-danger');
    }
  } catch (err) {
    message.textContent = 'Server error. Please try again.';
    message.classList.add('text-danger');
  } finally {
    btn.disabled = false;
    spinner.classList.add('d-none');
  }
});

// === UX: Auto-focus for OTP input boxes ===
document.addEventListener('DOMContentLoaded', () => {
  const otpInputs = document.querySelectorAll('.otp-input');

  otpInputs.forEach((input, index) => {
    input.addEventListener('input', e => {
      const val = e.target.value;
      if (val.length === 1 && index < otpInputs.length - 1) {
        otpInputs[index + 1].focus();
      }
    });

    input.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !e.target.value && index > 0) {
        otpInputs[index - 1].focus();
      }
    });

    input.addEventListener('paste', e => {
      e.preventDefault();
      const paste = (e.clipboardData || window.clipboardData).getData('text');
      if (/^\d{6}$/.test(paste)) {
        otpInputs.forEach((inp, i) => (inp.value = paste[i] || ''));
        otpInputs[otpInputs.length - 1].focus();
      }
    });
  });
});

// When verify modal shows, focus first box
document.getElementById('verifyOtpModal')
  ?.addEventListener('shown.bs.modal', () => {
    const first = document.querySelector('#verifyOtpModal .otp-input');
    first?.focus();
  });

// Only allow digits in OTP boxes
document.querySelectorAll('#verifyOtpModal .otp-input').forEach(inp => {
  inp.addEventListener('input', e => e.target.value = e.target.value.replace(/\D/g, ''));
});

document.getElementById('verifyCancelBtn')?.addEventListener('click', () => {
  document.querySelectorAll('#verifyOtpModal .otp-input').forEach(i => i.value = '');
  const m = document.getElementById('otpVerifyMessage');
  m.textContent = 'Please enter the OTP to proceed with password reset.';
  m.classList.remove('text-danger','text-success');
});

  </script>
</body>
</html>
