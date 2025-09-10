<?php
include 'config/db.php';

if (isset($_GET['barcode']) && isset($_GET['branch_id'])) {
    $barcode = $conn->real_escape_string($_GET['barcode']);
    $branch_id = (int)$_GET['branch_id'];

    $sql = "
        SELECT p.id, p.product_name, p.price, i.stock
        FROM products p
        LEFT JOIN inventory i ON p.id = i.product_id AND i.branch_id = ?
        WHERE p.barcode = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $branch_id, $barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode([
            "exists" => true,
            "product_id" => $row['id'],
            "product_name" => $row['product_name'],
            "price" => $row['price'],
            "stock" => $row['stock'] ?? 0 // default 0 if branch has no entry
        ]);
    } else {
        echo json_encode(["exists" => false]);
    }

    $stmt->close();
}
?>
