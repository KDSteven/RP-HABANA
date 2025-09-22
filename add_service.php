<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

// Logging function
function logAction($conn, $action, $details, $user_id = null, $branch_id = null) {
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    if (!$branch_id && isset($_SESSION['branch_id'])) {
        $branch_id = $_SESSION['branch_id'];
    }

    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, timestamp, branch_id) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("issi", $user_id, $action, $details, $branch_id);
    $stmt->execute();
    $stmt->close();
}

// Wrapper function for service logging
function logServiceAdd($conn, $serviceName, $serviceId, $branchId) {
    logAction($conn, "Add Service", "Added service '$serviceName' (ID: $serviceId) to branch ID $branchId");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = trim($_POST['service_name'] ?? '');
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $description = trim($_POST['description'] ?? '');
    $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;

    // Validation
    if ($service_name === '' || $branch_id <= 0) {
        $_SESSION['stock_message'] = "❌ Missing required fields.";
        header("Location: inventory.php?stock=error");
        exit;
    }
    if ($price < 0) {
        $_SESSION['stock_message'] = "❌ Invalid price value.";
        header("Location: inventory.php?stock=error");
        exit;
    }

    // Insert into services
    $stmt = $conn->prepare("INSERT INTO services (branch_id, service_name, price, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $branch_id, $service_name, $price, $description);

    if ($stmt->execute()) {
        $serviceId = $conn->insert_id; // get inserted service ID
        $stmt->close();

        // Logging
        logServiceAdd($conn, $service_name, $serviceId, $branch_id);

        // Session-based success message
        $_SESSION['stock_message'] = "✅ Service '$service_name' added successfully (Branch ID: $branch_id)";
        header('Location: services.php?as=added');
        exit;
    } else {
        $_SESSION['stock_message'] = "❌ Error adding service: " . $stmt->error;
        $stmt->close();
        header('Location: services.php?as=error');
        exit;
    }

}
?>
