<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// session_start();
// include 'config/db.php';

// if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff','admin'])) {
//     http_response_code(403);
//     echo json_encode(["error" => "Unauthorized"]);
//     exit;
// }

// $sale_id = (int)($_GET['sale_id'] ?? 0);
// if ($sale_id <= 0) {
//     echo json_encode(["error" => "Invalid sale_id"]);
//     exit;
// }

// /* --- Fetch Sale Header --- */
// $stmt = $conn->prepare("
//     SELECT total AS net_total, vat AS vat_amount, (total + vat) AS total_with_vat
//     FROM sales
//     WHERE sale_id = ?
// ");
// $stmt->bind_param("i", $sale_id);
// $stmt->execute();
// $sale = $stmt->get_result()->fetch_assoc();
// $stmt->close();

// $saleNet = (float)($sale['net_total'] ?? 0);
// $saleVAT = (float)($sale['vat_amount'] ?? 0);

// /* --- PRODUCTS --- */
// $stmt = $conn->prepare("
//     SELECT si.product_id, p.product_name, si.quantity, si.price
//     FROM sales_items si
//     JOIN products p ON si.product_id = p.product_id
//     WHERE si.sale_id = ?
// ");
// $stmt->bind_param("i", $sale_id);
// $stmt->execute();
// $res = $stmt->get_result();
// $products = [];
// while ($row = $res->fetch_assoc()) {
//     $products[] = [
//         'product_id'   => (int)$row['product_id'],
//         'product_name' => $row['product_name'],
//         'quantity'     => (int)$row['quantity'],
//         'price'        => (float)$row['price']
//     ];
// }
// $stmt->close();

// /* --- SERVICES --- */
// $stmt = $conn->prepare("
//     SELECT ss.service_id, sv.service_name, ss.price
//     FROM sales_services ss
//     JOIN services sv ON ss.service_id = sv.service_id
//     WHERE ss.sale_id = ?
// ");
// $stmt->bind_param("i", $sale_id);
// $stmt->execute();
// $res = $stmt->get_result();
// $services = [];
// while ($row = $res->fetch_assoc()) {
//     $services[] = [
//         'service_id'   => (int)$row['service_id'],
//         'service_name' => $row['service_name'],
//         'quantity'     => 1,  // services are one each
//         'price'        => (float)$row['price']
//     ];
// }
// $stmt->close();

// /* --- Final JSON --- */
// header("Content-Type: application/json");
// echo json_encode([
//     'total' => round($saleNet, 2),
//     'vat'   => round($saleVAT, 2),
//     'total_with_vat' => round($saleNet + $saleVAT, 2),
//     'products' => $products,
//     'services' => $services
// ]);
// get_sales_products.php
// get_sales_products.php  — returns JSON for refund modal (PRODUCTS ONLY)

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');           // show errors in dev
header('Content-Type: application/json'); // always JSON

// Convert any fatal/uncaught error to JSON 500 so fetch() shows the message
set_exception_handler(function($e){
  http_response_code(500);
  echo json_encode(['error' => 'Server error', 'details' => $e->getMessage()]);
});
set_error_handler(function($no,$str,$file,$line){
  throw new ErrorException($str, 0, $no, $file, $line);
});

session_start();
require __DIR__ . '/config/db.php';

// Basic auth check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff','admin'], true)) {
  http_response_code(403);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

// Input
$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;
if ($sale_id <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid sale_id']);
  exit;
}

/* --- Fetch sale header (for VAT %) --- */
$hdr = $conn->prepare("
  SELECT total AS net_total, vat AS vat_amount
  FROM sales
  WHERE sale_id = ?
");
if (!$hdr) { throw new Exception('Prepare hdr failed: '.$conn->error); }
$hdr->bind_param("i", $sale_id);
$hdr->execute();
$sale = $hdr->get_result()->fetch_assoc();
$hdr->close();

if (!$sale) {
  http_response_code(404);
  echo json_encode(['error' => 'Sale not found']);
  exit;
}

$saleNet = (float)$sale['net_total'];
$saleVAT = (float)$sale['vat_amount'];

/* --- PRODUCTS with remaining refundable qty --- */
$ps = $conn->prepare("
  SELECT
    si.product_id,
    p.product_name,
    si.price,
    si.quantity AS sold_qty,
    COALESCE(SUM(sri.qty), 0) AS refunded_qty,
    GREATEST(si.quantity - COALESCE(SUM(sri.qty), 0), 0) AS remaining_qty
  FROM sales_items si
  JOIN products p ON p.product_id = si.product_id
  LEFT JOIN sales_refund_items sri
         ON sri.sale_id    = si.sale_id
        AND sri.product_id = si.product_id
  WHERE si.sale_id = ?
  GROUP BY si.product_id, p.product_name, si.price, si.quantity
  ORDER BY p.product_name ASC
");
if (!$ps) { throw new Exception('Prepare products failed: '.$conn->error); }
$ps->bind_param("i", $sale_id);
$ps->execute();
$prodRows = $ps->get_result()->fetch_all(MYSQLI_ASSOC);
$ps->close();

// Build JSON
$products = array_map(function($r){
  return [
    'product_id'    => (int)$r['product_id'],
    'product_name'  => (string)$r['product_name'],
    'price'         => (float)$r['price'],
    'sold_qty'      => (int)$r['sold_qty'],
    'refunded_qty'  => (int)$r['refunded_qty'],
    'remaining_qty' => (int)$r['remaining_qty'],
  ];
}, $prodRows);

// Respond (services intentionally empty)
echo json_encode([
  'total'          => round($saleNet, 2),
  'vat'            => round($saleVAT, 2),
  'total_with_vat' => round($saleNet + $saleVAT, 2),
  'products'       => $products,
  'services'       => []   // not used
]);
