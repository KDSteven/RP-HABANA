<?php

$branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
if (!$branch_id) {
    die("❌ Invalid branch ID.");
}

include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $product_name = $_POST['product_name'];
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $markup_price = floatval($_POST['markup_price']);
    $retail_price = floatval($_POST['retail_price']);
    $ceiling_point = intval($_POST['ceiling_point']);
    $critical_point = intval($_POST['critical_point']);
    $stock = intval($_POST['stock']);
    $vat = floatval($_POST['vat']);
    $expiration_date = $_POST['expiration_date'] ?: null;
    $branchId = intval($_POST['branch_id']);

    if ($stock > $ceiling_point) {
        echo "<script>alert('Stock cannot exceed Ceiling Point!'); window.history.back();</script>";
        exit();
    }

    // Update products table
    $product_sql = "UPDATE products SET 
                        product_name = ?, 
                        category = ?, 
                        price = ?, 
                        markup_price = ?, 
                        retail_price = ?, 
                        ceiling_point = ?, 
                        critical_point = ?, 
                        vat = ?, 
                        expiration_date = ?
                    WHERE product_id = ?";
    $stmt1 = $conn->prepare($product_sql);
    $stmt1->bind_param(
        'ssdddddisi',
        $product_name,
        $category,
        $price,
        $markup_price,
        $retail_price,
        $ceiling_point,
        $critical_point,
        $vat,
        $expiration_date,
        $product_id
    );
    $stmt1->execute();

    // ✅ Insert or update inventory stock
   echo "DEBUG: branch_id = $branch_id, product_id = $product_id, stock = $stock";


    $updateStockSQL = "
    INSERT INTO inventory (product_id, branch_id, stock)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE stock = VALUES(stock)";
$stmt2 = $conn->prepare($updateStockSQL);
$stmt2->bind_param("iii", $product_id, $branch_id, $stock);
$stmt2->execute();


    if ($stmt1->affected_rows >= 0 || $stmt2->affected_rows >= 0) {
        echo "<script>alert('Product updated successfully!'); window.location.href = 'inventory.php?branch=$branch_id';</script>";
    } else {
        echo "Error updating product or stock: " . $conn->error;
    }
}
?>
