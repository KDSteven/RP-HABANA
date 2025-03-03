<?php
session_start();
include 'config/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ✅ Retrieve username and password from form
    $username = $_POST['username'] ?? "";
    $password = $_POST['password'] ?? "";

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        // ✅ Prepare and execute SQL query securely
        $sql = "SELECT user_id, username, password, role, location_id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
        
            // ✅ Debugging: Check Entered Password vs. Stored Hash
            echo "Entered Password: " . $password . "<br>";
            echo "Stored Hash: " . $user['password'] . "<br>";
        
            // ✅ Verify password
            if (password_verify($password, $user['password'])) {
                echo "✅ Password matches!<br>";
        
                // ✅ Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['location_id'] = $user['location_id'];
        
                // ✅ Redirect based on role
                header("Location: dashboard.php");
                exit();
            } else {
                echo "❌ Invalid password.";
                exit();
            }
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
            <strong>Error:</strong> <?php echo !empty($error) ? $error : "An error occurred."; ?>
        </div>
        <a href="index.html" class="btn btn-primary">Go Back</a>
    </div>
</body>
</html>
