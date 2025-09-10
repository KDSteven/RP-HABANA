<?php
session_start();
require 'config/db.php';
include 'functions.php'; // if logAction is there

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = (int) $_POST['service_id'];
    $service_name = trim($_POST['service_name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $branch_id = (int) $_POST['branch_id'];

    if ($service_id > 0 && $service_name !== '') {
        $stmt = $conn->prepare("UPDATE services SET service_name = ?, price = ?, description = ? WHERE service_id = ?");
        $stmt->bind_param("sdsi", $service_name, $price, $description, $service_id);

        if ($stmt->execute()) {
            // Log action
            logAction($conn, "Edit Service", "Edited service: $service_name (ID: $service_id)", null, $branch_id);

            $_SESSION['stock_message'] = "✅ Service updated successfully.";
            header("Location: inventory.php?edit=success");
            exit;
        } else {
            $_SESSION['stock_message'] = "❌ Failed to update service.";
            header("Location: inventory.php?edit=error");
            exit;
        }
    }
}
?>
