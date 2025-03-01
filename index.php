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
            border-radius: 10px;
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
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" placeholder="Enter your username" required pattern="^(?=.*\d)(?=.*[a-zA-Z])[a-zA-Z\d]{6,}$">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" placeholder="Enter your password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">SIGN-IN</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
