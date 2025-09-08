<?php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $action = "Logout";
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $ip);
    $stmt->execute();
}

session_destroy();
header("Location: index.html");
exit;
