<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['role'];
$branch_id = $_SESSION['branch_id'] ?? null;

if ($role === 'staff') {
    $stmt = $conn->prepare("SELECT sale_id, sale_date, total FROM sales WHERE branch_id = ? ORDER BY sale_date DESC");
    $stmt->bind_param("i", $branch_id);
} else {
    $stmt = $conn->prepare("SELECT sale_id, sale_date, total FROM sales ORDER BY sale_date DESC");
}

$stmt->execute();
$result = $stmt->get_result();

$sales = [];
while ($row = $result->fetch_assoc()) {
    $sales[] = [
        'sale_id' => $row['sale_id'],
        'sale_date' => $row['sale_date'],
        'total' => floatval($row['total']),
    ];
}

header('Content-Type: application/json');
echo json_encode($sales);
?>
