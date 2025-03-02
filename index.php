<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R.P Habana - Inventory System</title>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { height: 100vh; }
        .left-section {
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .left-section h1 { color: #f39200; font-weight: bold; font-size: 5em; }
        .left-section p { font-size: 2em; }
        .right-section {
            background: url('img/bg.jpg') center/cover no-repeat;
            position: relative;
        }
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 153, 0, 0.7);
        }
        .login-box {
            background: rgba(0, 0, 0, 0.7);
            padding: 40px;
            width: 550px;
            border-radius: 10px;
            color: #fff;
        }
        hr { height: 3px; background-color: #fff; color: #fff; }
        #valButton, #verButton { background-color: #f39200; }

    </style>
</head>
<body>
    <div class="container-fluid h-100">
        <div class="row h-100">
            <div class="col-md-6 left-section">
                <div>
                    <h1>R.P HABANA</h1>
                    <p>Inventory and Sales Management System</p>
                </div>
            </div>
            <div class="col-md-6 right-section d-flex align-items-center justify-content-center">
                <div class="overlay"></div>
                <div class="login-box position-relative">
                    <h4 class="text-start">SIGN-IN</h4>
                    <hr>
                    <form id="loginForm">
                        <div class="mb-3">
                            <i class="fas fa-user"></i>
                            <label class="form-label">Username</label>
                            <input type="text" id="username" class="form-control" placeholder="Enter your username" required>
                        </div>
                        <div class="mb-3">
                            <i class="fas fa-lock"></i>
                            <label class="form-label">Password</label>
                            <input type="password" id="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" name="signin-Btn">SIGN-IN</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- validation modal -->
    <div class="modal fade" id="validationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Logging Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalMessage"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="valButton" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 2FA modal -->
    <div class="modal fade" id="otpModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enter OTP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>An OTP has been sent to your email. Please enter it below:</p>
                    <input type="text" id="otpInput" class="form-control" placeholder="Enter OTP" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="verButton" name="verify-btn" onclick="verifyOTP()">Verify</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById("loginForm").addEventListener("submit", function(event) {
            event.preventDefault();
            var username = document.getElementById("username").value;
            var password = document.getElementById("password").value;
            var usernamePattern = /^(?=.*\d)(?=.*[a-zA-Z])[a-zA-Z\d]{6,}$/;

            if (!usernamePattern.test(username)) {
                showModal("Username must be at least 6 characters long and contain both letters and numbers.");
                return;
            }

            if (password.trim() === "") {
                showModal("Password cannot be empty.");
                return;
            }
            
            var otpModal = new bootstrap.Modal(document.getElementById("otpModal"));
            otpModal.show();
        });

        function showModal(message) {
            document.getElementById("modalMessage").innerText = message;
            var validationModal = new bootstrap.Modal(document.getElementById("validationModal"));
            validationModal.show();
        }

        function verifyOTP() {
            var otp = document.getElementById("otpInput").value;
            if (otp === "123456") { // Placeholder for real OTP validation
                alert("OTP verified. Logging in...");
                window.location.href = "dashboard.html";
            } else {
                alert("Invalid OTP. Please try again.");
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
