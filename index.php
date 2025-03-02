<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R.P Habana - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
        }
        .left-section {
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .left-section h1 {
            color: #f39200;
            font-weight: bold;
            font-size: 5em;
        }
        .left-section p {
            font-size: 2em;
        }
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
        hr{
            height: 3px;
            background-color: #fff;
            color: #fff;
        }
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
                    <form id="loginForm">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" id="username" class="form-control" placeholder="Enter your username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" id="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">SIGN-IN</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="validationModal" tabindex="-1" aria-labelledby="validationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="validationModalLabel">Logging Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalMessage">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById("loginForm").addEventListener("submit", function(event) {
            var username = document.getElementById("username").value;
            var password = document.getElementById("password").value;
            var usernamePattern = /^(?=.*\d)(?=.*[a-zA-Z])[a-zA-Z\d]{6,}$/;

            if (!usernamePattern.test(username)) {
                showModal("Username must be at least 6 characters long and contain both letters and numbers.");
                event.preventDefault();
                return;
            }

            if (password.trim() === "") {
                showModal("Password cannot be empty.");
                event.preventDefault();
                return;
            }
        });

        function showModal(message) {
            document.getElementById("modalMessage").innerText = message;
            var validationModal = new bootstrap.Modal(document.getElementById("validationModal"));
            validationModal.show();
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
