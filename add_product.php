<?php
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = $_POST['product_name'] ?? '';
    $category = $_POST['category'] ?? '';
    $price = $_POST['price'] ?? 0;
    $markupPrice = $_POST['markup_price'] ?? 0;
    $ceilingPoint = $_POST['ceiling_point'] ?? 0;
    $criticalPoint = $_POST['critical_point'] ?? 0;
    $stocks = $_POST['stocks'] ?? 0;
    $branchId = $_POST['branch_id'] ?? 0;

    // Insert into product table
    $stmt = $conn->prepare("INSERT INTO products (product_name, category, price, markup_price, ceiling_point, critical_point) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdddd", $productName, $category, $price, $markupPrice, $ceilingPoint, $criticalPoint);

    if ($stmt->execute()) {
        $productId = $stmt->insert_id;
        $stmt->close();

        // Insert into inventory table
        $stmt2 = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
        $stmt2->bind_param("iii", $productId, $branchId, $stocks);

        if ($stmt2->execute()) {
            header("Location: inventory.php?success=1");
            exit();
        } else {
            echo "Error adding to inventory: " . $stmt2->error;
        }
        $stmt2->close();
    } else {
        echo "Error adding product: " . $stmt->error;
    }
}
?>
