<?php
include 'config/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand_name = trim($_POST['brand_name']);
    if (!empty($brand_name)) {
        $stmt = $conn->prepare("INSERT INTO brands (brand_name) VALUES (?)");
        $stmt->bind_param("s", $brand_name);
        if ($stmt->execute()) {
            header("Location: inventory.php"); // Refresh page to update brand list
            exit();
        } else {
            echo "Error adding brand: " . $stmt->error;
        }
    }
}
?>
