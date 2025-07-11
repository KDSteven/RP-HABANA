<?php
session_start();
include 'db.php';

if (isset($_POST['checkout'])) {
    if (empty($_SESSION['cart'])) {
        echo "<p>Cart is empty. <a href='pos.php'>Back to POS</a></p>";
        exit;
    }

    $payment = isset($_POST['payment']) ? (float)$_POST['payment'] : 0;
    $total = 0;

    // Calculate total price first
    foreach ($_SESSION['cart'] as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];

        $product = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();
        if (!$product) continue;

        $price = $product['price'];
        $subtotal = $price * $quantity;
        $total += $subtotal;
    }

    // Payment validation
    if ($payment < $total) {
        echo "<p style='color:red;'>Insufficient payment. Total is ₱" . number_format($total, 2) . ", but you entered ₱" . number_format($payment, 2) . ".</p>";
        echo "<a href='pos.php'>Back to POS</a>";
        exit;
    }

    // Process transaction
    foreach ($_SESSION['cart'] as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];

        $product = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();
        $price = $product['price'];
        $subtotal = $price * $quantity;

        $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, total) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $product_id, $quantity, $subtotal);
        $stmt->execute();

        $conn->query("UPDATE products SET quantity = quantity - $quantity WHERE id = $product_id");
    }

    $change = $payment - $total;
    $_SESSION['cart'] = [];

    echo "<p>✅ Sale completed successfully.</p>";
    echo "<p><strong>Total:</strong> ₱" . number_format($total, 2) . "</p>";
    echo "<p><strong>Payment:</strong> ₱" . number_format($payment, 2) . "</p>";
    echo "<p><strong>Change:</strong> ₱" . number_format($change, 2) . "</p>";
    echo "<p><a href='pos.php'>Back to POS</a></p>";
}
?>
