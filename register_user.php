<?php
session_start();
include 'config/db.php'; // Your DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Set branch_id only if the role is 'staff'
    $branch_id = ($role === 'staff' || $role ==='stockman') ? $_POST['branch_id'] : null;

    // Prepare the statement to insert the new user
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, branch_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $username, $password, $role, $branch_id);

    if ($stmt->execute()) {
        // Success - redirect or show success message
        header("Location: accounts.php?success=1");
    } else {
        // Error - handle the error
        echo "Error: " . $stmt->error;
    }
    
    $stmt->close();
    exit;
}
?>
