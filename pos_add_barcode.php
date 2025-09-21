<?php
session_start();
include 'config/db.php';
include 'functions.php';

$branch_id = (int)($_SESSION['branch_id'] ?? 0);
if(empty($_POST['barcode'])) exit(json_encode(['success'=>false,'message'=>'No barcode supplied']));
$barcode = trim($_POST['barcode']);

// Fetch product
$stmt = $conn->prepare("
    SELECT p.product_id,p.product_name,p.price,p.markup_price,IFNULL(i.stock,0) AS stock 
    FROM products p
    JOIN inventory i ON p.product_id=i.product_id
    WHERE p.barcode=? AND i.branch_id=? LIMIT 1
");
$stmt->bind_param("si",$barcode,$branch_id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$prod) {
    echo json_encode(['success'=>false,'message'=>"No product found for barcode {$barcode}"]);
    exit;
}

if((int)$prod['stock'] <= 0){
    echo json_encode(['success'=>false,'message'=>"{$prod['product_name']} is out of stock."]);
    exit;
}

// Helper
function finalPrice($price,$markup){ return $price+($price*($markup/100)); }

// Check if already in cart
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if($item['type']==='product' && $item['product_id']==$prod['product_id']){
        // increment qty if stock allows
        if($item['qty'] < $prod['stock']){
            $item['qty']++;
            $found = true;
        } else {
            echo json_encode(['success'=>false,'message'=>"Cannot add more. Stock limit reached for {$prod['product_name']}"]);
            exit;
        }
        break;
    }
}
unset($item);

if(!$found){
    $itemData = [
        'type'=>'product',
        'product_id'=>$prod['product_id'],
        'qty'=>1,
        'price'=>finalPrice($prod['price'],$prod['markup_price']),
        'stock'=>$prod['stock']
    ];
    $_SESSION['cart'][] = $itemData;
}

// Return updated cart HTML
ob_start();
include 'pos_cart_partial.php';
$cartHtml = ob_get_clean();

echo json_encode(['success'=>true,'cart_html'=>$cartHtml]);
