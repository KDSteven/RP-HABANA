<?php
session_start();
include 'config/db.php';
include 'functions.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

/* ========= Helpers ========= */
function finalPrice($price, $markup) {
    $p = (float)$price; $m = (float)$markup;
    return $p + ($p * ($m / 100));
}
function findCartIndex(string $type, $id): int {
    foreach ($_SESSION['cart'] as $idx => $item) {
        if (($item['type'] ?? '') !== $type) continue;
        $key = ($type === 'product') ? 'product_id' : 'service_id';
        if (($item[$key] ?? null) == $id) return $idx;
    }
    return -1;
}
function computeTotals(): array {
    $subtotal = 0.0;
    $totalVat = 0.0;

    foreach ($_SESSION['cart'] as $item) {
        $qty     = max(0, (int)($item['qty'] ?? 0));
        $price   = (float)($item['price'] ?? 0);
        $vatPerc = (float)($item['vat'] ?? 0);   // percent (e.g. 12)

        $lineSub = $price * $qty;
        $lineVat = $lineSub * ($vatPerc / 100.0);

        $subtotal += $lineSub;
        $totalVat += $lineVat;
    }

    $grand = $subtotal + $totalVat;

    return [
        'raw' => ['subtotal'=>$subtotal, 'vat'=>$totalVat, 'grand'=>$grand],
        'display' => [
            'subtotal' => number_format($subtotal, 2),
            'vat'      => number_format($totalVat, 2),
            'grand'    => number_format($grand, 2),
        ],
    ];
}

/* ========= Parse input (JSON or form) ========= */
$input   = json_decode(file_get_contents('php://input'), true);
$post    = array_merge($_POST, is_array($input) ? $input : []);
$action  = $post['action'] ?? '';

$response = ['success' => false, 'message' => '', 'cart_html' => '', 'totals' => []];

/* ========= Actions ========= */
switch ($action) {

    case 'add_product': {
        $pid = (int)($post['product_id'] ?? 0);
        $qty = (int)($post['qty'] ?? 1);
        if ($qty < 1) $qty = 1;

        if ($pid <= 0 || empty($_SESSION['branch_id'])) {
            $response['message'] = 'Invalid product or branch.';
            break;
        }

        $stmt = $conn->prepare("
            SELECT p.product_name, p.price, p.markup_price, IFNULL(i.stock,0) AS stock,
                   p.expiration_date, p.category, p.vat
            FROM products p
            JOIN inventory i ON p.product_id = i.product_id
            WHERE p.product_id = ? AND i.branch_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $pid, $_SESSION['branch_id']);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$prod) { $response['message'] = 'Product not found.'; break; }

        $stock      = (int)$prod['stock'];
        $vatPercent = (float)($prod['vat'] ?? 0);  // store as percent

        if ($stock <= 0) { $response['message'] = 'Product out of stock.'; break; }

        $idx = findCartIndex('product', $pid);
        if ($idx >= 0) {
            $currentQty = (int)$_SESSION['cart'][$idx]['qty'];
            if ($currentQty >= $stock) {
                $response['message'] = '"' . $prod['product_name'] . '" has only ' . $stock . ' in stock.';
                break;
            }
            $newQty = min($currentQty + $qty, $stock);
            $_SESSION['cart'][$idx]['qty']   = $newQty;
            $_SESSION['cart'][$idx]['stock'] = $stock;       // keep session stock in sync
            $_SESSION['cart'][$idx]['vat']   = $vatPercent;  // ensure VAT present
        } else {
            $_SESSION['cart'][] = [
                'type'         => 'product',
                'product_id'   => $pid,
                'product_name' => $prod['product_name'],
                'qty'          => min($qty, $stock),
                'price'        => finalPrice($prod['price'], $prod['markup_price']),
                'vat'          => $vatPercent,                // percent
                'stock'        => $stock,
                'expiration'   => $prod['expiration_date'],
                'category'     => $prod['category'],
            ];
        }

        $response['success'] = true;
        break;
    }

    case 'add_service': {
        $sid = (int)($post['service_id'] ?? 0);
        $qty = (int)($post['qty'] ?? 1);
        if ($qty < 1) $qty = 1;

        if ($sid <= 0) { $response['message'] = 'Invalid service.'; break; }

        // If you don't have VAT on services, keep vat NULL or 0.
        // Adjust the SELECT to include vat if present in your schema.
        $stmt = $conn->prepare("SELECT service_name, price, COALESCE(vat,0) AS vat FROM services WHERE service_id=? LIMIT 1");
        $stmt->bind_param("i", $sid);
        $stmt->execute();
        $srv = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$srv) { $response['message'] = 'Service not found.'; break; }

        $idx = findCartIndex('service', $sid);
        if ($idx >= 0) {
            $_SESSION['cart'][$idx]['qty'] = (int)$_SESSION['cart'][$idx]['qty'] + $qty;
            $_SESSION['cart'][$idx]['vat'] = (float)($srv['vat'] ?? 0); // ensure VAT present
        } else {
            $_SESSION['cart'][] = [
                'type'       => 'service',
                'service_id' => $sid,
                'name'       => $srv['service_name'],
                'qty'        => $qty,
                'price'      => (float)$srv['price'],
                'vat'        => (float)($srv['vat'] ?? 0),  // percent
            ];
        }

        $response['success'] = true;
        break;
    }

    case 'update_qty': {
        // qty is DELTA (+1 / -1)
        $type  = $post['item_type'] ?? '';
        $id    = $post['item_id'] ?? '';
        $delta = (int)($post['qty'] ?? 0); // can be negative

        if (!$type || $id === '') { $response['message'] = 'Invalid item.'; break; }

        $idx = findCartIndex($type, $id);
        if ($idx < 0) { $response['message'] = 'Item not found in cart.'; break; }

        $curQty = (int)($_SESSION['cart'][$idx]['qty'] ?? 0);
        $reqQty = $curQty + $delta;
        $newQty = $reqQty;

        if ($type === 'product') {
            $pid = (int)$id;

            // Re-fetch live stock for this branch to avoid stale session stock
            $stmt = $conn->prepare("SELECT IFNULL(stock,0) AS stock FROM inventory WHERE branch_id=? AND product_id=? LIMIT 1");
            $stmt->bind_param("ii", $_SESSION['branch_id'], $pid);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $liveStock = (int)($row['stock'] ?? 0);
            $_SESSION['cart'][$idx]['stock'] = $liveStock; // keep session in sync

            // Clamp requested quantity to live stock, but not below 0
            $newQty = max(0, min($reqQty, $liveStock));

            // Optionally inform client if we clamped
            if ($newQty !== $reqQty) {
                $response['message'] = "Not enough stock. Adjusted quantity to {$newQty}.";
            }
        } else {
            $newQty = max(0, $reqQty);
        }

        if ($newQty <= 0) {
            array_splice($_SESSION['cart'], $idx, 1);
        } else {
            $_SESSION['cart'][$idx]['qty'] = $newQty;
        }

        $response['success'] = true;
        break;
    }

    case 'remove_item': {
        $type = $post['item_type'] ?? '';
        $id   = $post['item_id'] ?? '';
        $idx  = findCartIndex($type, $id);
        if ($idx >= 0) {
            array_splice($_SESSION['cart'], $idx, 1);
            $response['success'] = true;
        } else {
            $response['message'] = 'Item not found.';
        }
        break;
    }

    case 'cancel_order': {
        $_SESSION['cart'] = [];
        $response['success'] = true;
        break;
    }

    default:
        $response['message'] = 'Invalid action';
}

/* ========= Rebuild cart HTML ========= */
ob_start();
include 'pos_cart_partial.php'; // reads from $_SESSION['cart']
$response['cart_html'] = ob_get_clean();

/* ========= Totals ========= */
$response['totals'] = computeTotals();

/* ========= Return JSON ========= */
header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode($response);
exit;
