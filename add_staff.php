<?php
session_start();
include 'db_connect.php';

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied! Only admins can add staff.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $location_id = $_POST['location_id'];  // Assign to a branch

    $sql = "INSERT INTO users (username, password, full_name, email, role, location_id) 
            VALUES (?, ?, ?, ?, 'staff', ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $username, $password, $full_name, $email, $location_id);
    
    if ($stmt->execute()) {
        echo "Staff member added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
