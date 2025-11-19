<?php
include 'config/db.php';
require_once 'functions.php'; // IMPORTANT for logAction()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ------------------------------
       ADD BRAND
    ------------------------------ */
    if (isset($_POST['brand_name'])) {
        $brand_name = trim($_POST['brand_name']);

        if (!empty($brand_name)) {
            $stmt = $conn->prepare("INSERT INTO brands (brand_name) VALUES (?)");
            $stmt->bind_param("s", $brand_name);

            if ($stmt->execute()) {

                // ✅ LOG ACTION
                logAction(
                    $conn,
                    "Create Brand",
                    "Created brand: {$brand_name}",
                    $_SESSION['user_id'] ?? null,
                    $_SESSION['branch_id'] ?? null
                );

                header("Location: inventory.php?brand_added=1");
                exit();

            } else {
                echo "Error adding brand: " . $stmt->error;
            }
        }
    }

    /* ------------------------------
       ADD CATEGORY
    ------------------------------ */
    if (isset($_POST['category_name'])) {
        $category_name = trim($_POST['category_name']);

        if (!empty($category_name)) {
            $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $stmt->bind_param("s", $category_name);

            if ($stmt->execute()) {

                // ✅ LOG ACTION
                logAction(
                    $conn,
                    "Create Category",
                    "Created category: {$category_name}",
                    $_SESSION['user_id'] ?? null,
                    $_SESSION['branch_id'] ?? null
                );

                header("Location: inventory.php?category_added=1");
                exit();

            } else {
                echo "Error adding category: " . $stmt->error;
            }
        }
    }
}
?>
