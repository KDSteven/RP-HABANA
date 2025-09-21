<?php
session_start();
include 'config/db.php';
include 'functions.php';

// Ensure cart exists
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Helper: calculate final price
function finalPrice($price, $markup) {
    return $price + ($price * ($markup / 100));
}

// -------------------- PARSE INPUT --------------------
// Support both JSON (from fetch) and normal POST
$input = json_decode(file_get_contents('php://input'), true);
$post = $_POST + ($input ?? []);

$action = $post['action'] ?? '';
$response = ['success' => false, 'message' => '', 'cart_html' => '', 'totals' => []];

// -------------------- HANDLE ACTIONS --------------------
switch ($action) {

    case 'add_product':
        $pid = (int)($post['product_id'] ?? 0);
        $qty = max(1, (int)($post['qty'] ?? 1));
        if ($pid > 0) {
           $stmt = $conn->prepare("
    SELECT p.product_name, p.price, p.markup_price, IFNULL(i.stock,0) AS stock, 
           p.expiration_date, p.category
    FROM products p
    JOIN inventory i ON p.product_id=i.product_id
    WHERE p.product_id=? AND i.branch_id=? LIMIT 1
");

            $stmt->bind_param("ii", $pid, $_SESSION['branch_id']);
            $stmt->execute();
            $prod = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$prod || $prod['stock'] <= 0) {
                $response['message'] = "Product not available";
                break;
            }

            $added = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['type'] === 'product' && $item['product_id'] == $pid) {
                    $item['qty'] = min($item['qty'] + $qty, $prod['stock']);
                    $added = true;
                    break;
                }
            }
            unset($item);

            if (!$added) {
              $_SESSION['cart'][] = [
    'type'          => 'product',
    'product_id'    => $pid,
    'product_name'  => $prod['product_name'],
    'qty'           => $qty,
    'price'         => finalPrice($prod['price'], $prod['markup_price']),
    'stock'         => $prod['stock'],
    'expiration'    => $prod['expiration_date'], // 👈 use 'expiration' (match your HTML)
    'category'      => $prod['category']        // 👈 new
];

            }

            $response['success'] = true;
        }
        break;

    case 'add_service':
        $sid = (int)($post['service_id'] ?? 0);
        if ($sid > 0) {
            $stmt = $conn->prepare("SELECT service_name, price FROM services WHERE service_id=?");
            $stmt->bind_param("i",$sid);
            $stmt->execute();
            $srv = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($srv) {
                $_SESSION['cart'][] = [
                    'type'=>'service',
                    'service_id'=>$sid,
                    'name'=>$srv['service_name'],
                    'qty'=>1,
                    'price'=>(float)$srv['price']
                ];
                $response['success'] = true;
            }
        }
        break;

    case 'update_qty':
        $type = $post['item_type'] ?? '';
        $id   = $post['item_id'] ?? '';
        $qty  = max(0, (int)($post['qty'] ?? 1));

        foreach ($_SESSION['cart'] as $k => &$item) {
            if ($item['type'] === $type && ($item[$type.'_id'] ?? $item['product_id'] ?? '') == $id) {
                if ($qty === 0) unset($_SESSION['cart'][$k]);
                else $item['qty'] = ($type==='product') ? min($qty, (int)$item['stock']) : $qty;
                break;
            }
        }
        unset($item);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        $response['success'] = true;
        break;

    case 'remove_item':
        $type = $post['item_type'] ?? '';
        $id   = $post['item_id'] ?? '';
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], fn($i)=>!($i['type']===$type && ($i[$type.'_id']??$i['product_id']??'')==$id)));
        $response['success'] = true;
        break;

    case 'cancel_order':
        $_SESSION['cart'] = [];
        $response['success'] = true;
        break;

    default:
        $response['message'] = "Invalid action";
}

// ---------------- Build Cart HTML ----------------
ob_start();
include 'pos_cart_partial.php';
$response['cart_html'] = ob_get_clean();

// ---------------- Totals ----------------
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += ($item['price'] ?? 0) * (int)($item['qty'] ?? 0);
}
$vat = $subtotal * 0.12;
$grandTotal = $subtotal + $vat;

$response['totals'] = [
    'subtotal'=>number_format($subtotal,2),
    'vat'=>number_format($vat,2),
    'grand'=>number_format($grandTotal,2)
];

// ---------------- Return JSON ----------------
header('Content-Type: application/json');
echo json_encode($response);
exit;
