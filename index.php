<?php
session_start();
include 'config/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? "";
    $password = $_POST['password'] ?? "";

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        // ✅ Added must_change_password to query
        $sql = "SELECT id, username, password, role, branch_id, must_change_password FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    // Login success – set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['branch_id'] = $user['branch_id']; // Only meaningful for staff

                    // ✅ Step 5: check if user must change password
                    if ((int)$user['must_change_password'] === 1) {
                        header("Location: change_password.php");
                        exit();
                    }

                    // Normal redirect
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }

            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
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
