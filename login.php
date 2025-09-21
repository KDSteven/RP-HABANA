<?php
session_start();
include 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? "");
    $password = trim($_POST['password'] ?? "");

    if ($username === "" || $password === "") {
        $_SESSION['toast_error'] = "Username and password are required.";
        header("Location: index.php");
        exit;
    }

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

            // check pending reset
            $stmt = $conn->prepare("SELECT 1 FROM password_resets WHERE user_id=? AND status='pending' LIMIT 1");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $hasPendingReset = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($hasPendingReset) {
                $_SESSION['toast_error'] = "Your account has a pending password reset. Please wait for Admin approval.";
                header("Location: index.php");
                exit;
            }

            if (password_verify($password, $user['password'])) {
                // success
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['branch_id'] = $user['branch_id'] ?? null;

                // log login
                $action = "Login successful";
                $branchForLog = $user['branch_id'] ?? null;
                $logStmt = $conn->prepare("
                    INSERT INTO logs (user_id, action, details, timestamp, branch_id)
                    VALUES (?, ?, '', NOW(), ?)
                ");
                $logStmt->bind_param("isi", $user['id'], $action, $branchForLog);
                $logStmt->execute();
                $logStmt->close();

                if ((int)$user['must_change_password'] === 1) {
                    header("Location: change_password.php");
                    exit;
                }
                header("Location: dashboard.php");
                exit;
            } else {
                $_SESSION['toast_error'] = "Invalid username or password.";
                header("Location: index.php");
                exit;
            }
        } else {
            $_SESSION['toast_error'] = "Invalid username or password.";
            header("Location: index.php");
            exit;
        }
    } else {
        $_SESSION['toast_error'] = "Database error: " . $conn->error;
        header("Location: index.php");
        exit;
    }
}

// fallback
header("Location: index.php");
exit;
