<?php

date_default_timezone_set('Asia/Manila');

$servername = "localhost";  
$username   = "root";         
$password   = "";            
$dbname     = "rp_habana";  

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// IMPORTANT: Fix MySQL timezone (Hostinger defaults to UTC)
$conn->query("SET time_zone = '+08:00'");

?>
