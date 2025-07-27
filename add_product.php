<?php
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = trim($_POST['product_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $markupPrice = floatval($_POST['markup_price'] ?? 0);
    $ceilingPoint = intval($_POST['ceiling_point'] ?? 0);
    $criticalPoint = intval($_POST['critical_point'] ?? 0);
    $stocks = intval($_POST['stocks'] ?? 0);
    $branchId = intval($_POST['branch_id'] ?? 0);

    // ✅ Validation Rules
    if ($criticalPoint > $ceilingPoint) {
        echo "<script>alert('Critical Point cannot be greater than Ceiling Point.');history.back();</script>";
        exit;
    }

    if ($stocks > $ceilingPoint) {
        echo "<script>alert('Stocks cannot be greater than Ceiling Point.');history.back();</script>";
        exit;
    }

    if ($stocks < 0 || $price < 0) {
        echo "<script>alert('Invalid values for stock or price.');history.back();</script>";
        exit;
    }

    // ✅ Insert into products table
    $stmt = $conn->prepare("INSERT INTO products (brand, product_name, category, price, markup_price, ceiling_point, critical_point) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssddii", $brand, $product_name, $category, $price, $markup_price, $ceiling_point, $critical_point);


    if ($stmt->execute()) {
        $productId = $stmt->insert_id;
        $stmt->close();

        // ✅ Insert into inventory table
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
