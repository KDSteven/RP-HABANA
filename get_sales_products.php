<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'config/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff','admin'])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$sale_id = (int)($_GET['sale_id'] ?? 0);
if ($sale_id <= 0) {
    echo json_encode(["error" => "Invalid sale_id"]);
    exit;
}

/* --- Fetch Sale Header --- */
$stmt = $conn->prepare("
    SELECT total AS net_total, vat AS vat_amount, (total + vat) AS total_with_vat
    FROM sales
    WHERE sale_id = ?
");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();
$stmt->close();

$saleNet = (float)($sale['net_total'] ?? 0);
$saleVAT = (float)($sale['vat_amount'] ?? 0);

/* --- PRODUCTS --- */
$stmt = $conn->prepare("
    SELECT si.product_id, p.product_name, si.quantity, si.price
    FROM sales_items si
    JOIN products p ON si.product_id = p.product_id
    WHERE si.sale_id = ?
");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$res = $stmt->get_result();
$products = [];
while ($row = $res->fetch_assoc()) {
    $products[] = [
        'product_id'   => (int)$row['product_id'],
        'product_name' => $row['product_name'],
        'quantity'     => (int)$row['quantity'],
        'price'        => (float)$row['price']
    ];
}
$stmt->close();

/* --- SERVICES --- */
$stmt = $conn->prepare("
    SELECT ss.service_id, sv.service_name, ss.price
    FROM sales_services ss
    JOIN services sv ON ss.service_id = sv.service_id
    WHERE ss.sale_id = ?
");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$res = $stmt->get_result();
$services = [];
while ($row = $res->fetch_assoc()) {
    $services[] = [
        'service_id'   => (int)$row['service_id'],
        'service_name' => $row['service_name'],
        'quantity'     => 1,  // services are one each
        'price'        => (float)$row['price']
    ];
}
$stmt->close();

/* --- Final JSON --- */
header("Content-Type: application/json");
echo json_encode([
    'total' => round($saleNet, 2),
    'vat'   => round($saleVAT, 2),
    'total_with_vat' => round($saleNet + $saleVAT, 2),
    'products' => $products,
    'services' => $services
]);
