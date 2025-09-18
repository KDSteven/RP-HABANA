<?php
session_start();
include 'config/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? "");
    $password = trim($_POST['password'] ?? "");

    if (empty($username) || empty($password)) {
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

                // 🚫 Check if pending reset
                $stmt = $conn->prepare("SELECT 1 FROM password_resets WHERE user_id=? AND status='Pending' LIMIT 1");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $pendingRes = $stmt->get_result();
                $hasPendingReset = ($pendingRes && $pendingRes->num_rows > 0);
                $stmt->close();

                if ($hasPendingReset) {
                    $error = "Your account has a pending password reset. Please wait for Admin approval.";
                } elseif (password_verify($password, $user['password'])) {
                    // ✅ Login success
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['branch_id'] = $user['branch_id'] ?? null;

                    // 🔔 Insert login log WITH branch
                    $action = "Login successful";
                    $branchForLog = $user['branch_id'] ?? null;

                    $logStmt = $conn->prepare("
                        INSERT INTO logs (user_id, action, details, timestamp, branch_id)
                        VALUES (?, ?, '', NOW(), ?)
                    ");
                    $logStmt->bind_param("isi", $user['id'], $action, $branchForLog);
                    $logStmt->execute();
                    $logStmt->close();


                    // Force password change if required
                    if ((int)$user['must_change_password'] === 1) {
                        header("Location: change_password.php");
                        exit();
                    }

                    header("Location: dashboard.php");
                    exit;
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

    // ❌ Failed login attempt log (no IP address)
    if (!empty($username)) {
        $action = "Login failed for username: $username";
        $logStmt = $conn->prepare("
            INSERT INTO logs (user_id, action, details, timestamp, branch_id)
            VALUES (NULL, ?, '', NOW(), NULL)
        ");
        $logStmt->bind_param("s", $action);
        $logStmt->execute();
        $logStmt->close();
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Error</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-5">
    <div class="alert alert-danger">
      <strong>Error:</strong> <?= htmlspecialchars($error ?: "An error occurred.") ?>
    </div>
    <a href="index.html" class="btn btn-primary">Go Back</a>
  </div>
</body>
</html>
