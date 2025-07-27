<?php
session_start();
include 'config/db.php';

$role = $_SESSION['role'] ?? '';
$branch_id = $_SESSION['branch_id'] ?? 0;

// Query based on role
if ($role === 'admin') {
    $query = "
        SELECT p.product_name, i.stock, p.critical_point
        FROM inventory i
        INNER JOIN products p ON i.product_id = p.product_id
        WHERE i.stock <= p.critical_point
        ORDER BY i.stock ASC
    ";
} else {
    $query = "
        SELECT p.product_name, i.stock, p.critical_point
        FROM inventory i
        INNER JOIN products p ON i.product_id = p.product_id
        WHERE i.branch_id = $branch_id AND i.stock <= p.critical_point
        ORDER BY i.stock ASC
    ";
}

$result = $conn->query($query);

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'product_name' => $row['product_name'],
        'stock' => $row['stock'],
        'critical_point' => $row['critical_point']
    ];
}

echo json_encode($notifications);
?>
