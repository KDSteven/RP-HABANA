<?php

include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $product_name = $_POST['product_name'];
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);

    // Optional: update markup, ceiling, and critical points if needed
    $markup_price = floatval($_POST['markup_price'] ?? 0);
    $ceiling_point = intval($_POST['ceiling_point'] ?? 0);
    $critical_point = intval($_POST['critical_point'] ?? 0);

    $branch_id = isset($_GET['branch']) ? intval($_GET['branch']) : 1;

    // First: update the `products` table
    $product_sql = "UPDATE products SET 
                        product_name = ?, 
                        category = ?, 
                        price = ?, 
                        markup_price = ?, 
                        ceiling_point = ?, 
                        critical_point = ? 
                    WHERE product_id = ?";
    $stmt1 = $conn->prepare($product_sql);
    $stmt1->bind_param('ssdiiii', $product_name, $category, $price, $markup_price, $ceiling_point, $critical_point, $product_id);
    $stmt1->execute();

    // Second: update the stock in `inventory` table for the selected branch
    // Fix branch_id usage: fallback to 1 if not set
    
    
    $inventory_sql = "UPDATE inventory SET stock = ? WHERE product_id = ? AND branch_id = ?";
    $stmt2 = $conn->prepare($inventory_sql);
    $stmt2->bind_param('iii', $stock, $product_id, $branch_id);
    $stmt2->execute();

    if ($stmt1->affected_rows >= 0 || $stmt2->affected_rows >= 0) {
        echo "<script>alert('Product updated successfully!'); window.location.href = 'inventory.php?branch=$branch_id';</script>";
    } else {
        echo "Error updating product: " . $conn->error;
    }
}
?>
