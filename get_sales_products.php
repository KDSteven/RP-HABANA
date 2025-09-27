<?php
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

$products = [];
$services = [];
$subtotal = 0.0;
$saleVAT  = 0.0;


// --- PRODUCTS ---
$stmt = $conn->prepare("
    SELECT si.product_id, p.product_name, si.quantity, si.price
    FROM sales_items si
    JOIN products p ON si.product_id = p.product_id
    WHERE si.sale_id = ?
");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$productsResult = $stmt->get_result();

$products = [];
$subtotal = 0;

while ($row = $productsResult->fetch_assoc()) {
    $qty   = (int)$row['quantity'];
    $price = (float)$row['price'];

    $lineTotal = $qty * $price;
    $subtotal += $lineTotal;

    $products[] = [
        'product_id'   => $row['product_id'],
        'product_name' => $row['product_name'],
        'quantity'     => $qty,
        'price'        => $price,
        'line_total'   => $lineTotal
    ];
}
$stmt->close();

// --- VAT FROM SALES ---
$stmt = $conn->prepare("SELECT vat FROM sales WHERE sale_id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$saleResult = $stmt->get_result();
$saleRow = $saleResult->fetch_assoc();
$stmt->close();

$saleVAT = isset($saleRow['vat']) ? (float)$saleRow['vat'] : 0.0;

// --- SERVICES ---
$stmt = $conn->prepare("
    SELECT ss.id AS sale_service_id, sv.service_id, sv.service_name, ss.price
    FROM sales_services ss
    JOIN services sv ON ss.service_id = sv.service_id
    WHERE ss.sale_id = ?
");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $qty   = (int)$row['quantity'];
    $price = (float)$row['price'];
    $lineTotal = $qty * $price;

    $subtotal += $lineTotal;

    $services[] = [
        "service_id"   => (int)$row['service_id'],
        "service_name" => $row['service_name'],
        "quantity"     => $qty,
        "price"        => $price,
        "line_total"   => $lineTotal
    ];
}
$stmt->close();

$total = $subtotal + $saleVAT;

header("Content-Type: application/json");
echo json_encode([
    "products" => $products,
    "services" => $services,
    "subtotal" => round($subtotal, 2),
    "vat"      => round($saleVAT, 2),
    "total"    => round($total, 2)
]);
