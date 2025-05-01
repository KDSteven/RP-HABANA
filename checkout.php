<?php
session_start();
include 'db.php';

if (isset($_POST['checkout'])) {
    foreach ($_SESSION['cart'] as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];

        // Get product price
        $product = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();
        $price = $product['price'];
        $total = $price * $quantity;

        // Save sale
        $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, total) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $product_id, $quantity, $total);
        $stmt->execute();

        // Update product stock
        $conn->query("UPDATE products SET quantity = quantity - $quantity WHERE id = $product_id");
    }

    $_SESSION['cart'] = []; // Empty cart
    echo "<p>Sale completed! <a href='index.php'>Back to POS</a></p>";
}
?>
