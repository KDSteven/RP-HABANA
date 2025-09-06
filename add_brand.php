<?php
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle adding a new brand
    if (isset($_POST['brand_name'])) {
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

    // Handle adding a new category
      if (isset($_POST['category_name'])) {
        $category_name = trim($_POST['category_name']);
        if (!empty($category_name)) {
            $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $stmt->bind_param("s", $category_name);
            if ($stmt->execute()) {
                header("Location: inventory.php?category_added=1");
                exit();
            } else {
                echo "Error adding category: " . $stmt->error;
            }
        }
    }
}
?>
