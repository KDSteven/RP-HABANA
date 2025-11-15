<?php
session_start();
header('Content-Type: application/json');
require __DIR__.'/config/db.php';

if (($_SESSION['role'] ?? '') !== 'admin') { echo json_encode(['ok'=>false,'message'=>'Forbidden']); exit; }

$brand_id    = (int)($_POST['brand_id'] ?? 0);
$mode        = $_POST['mode'] ?? 'deactivate';
$reassign_to = (int)($_POST['reassign_to'] ?? 0);

if ($brand_id <= 0) { echo json_encode(['ok'=>false,'message'=>'Invalid brand']); exit; }

try {
  if ($mode === 'deactivate') {
    $stmt = $conn->prepare("UPDATE brands SET active=0 WHERE brand_id=?");
    $stmt->bind_param("i",$brand_id);
    $stmt->execute();
    echo json_encode(['ok'=>true,'message'=>'Brand deactivated.']); exit;
  }

  if ($mode === 'restrict') {
    // only if unused
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM products WHERE brand_id=?");
    $stmt->bind_param("i",$brand_id);
    $stmt->execute(); $c = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
    if ($c > 0) { echo json_encode(['ok'=>false,'message'=>'Brand is in use; cannot delete.']); exit; }

    $stmt = $conn->prepare("DELETE FROM brands WHERE brand_id=? LIMIT 1");
    $stmt->bind_param("i",$brand_id);
    $stmt->execute();
    echo json_encode(['ok'=>true,'message'=>'Brand deleted.']); exit;
  }

  if ($mode === 'reassign') {
    if ($reassign_to <= 0 || $reassign_to === $brand_id) {
      echo json_encode(['ok'=>false,'message'=>'Pick a different target brand.']); exit;
    }
    $conn->begin_transaction();
    $stmt = $conn->prepare("UPDATE products SET brand_id=? WHERE brand_id=?");
    $stmt->bind_param("ii",$reassign_to,$brand_id);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM brands WHERE brand_id=? LIMIT 1");
    $stmt->bind_param("i",$brand_id);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['ok'=>true,'message'=>'Products reassigned & brand deleted.']); exit;
  }

  echo json_encode(['ok'=>false,'message'=>'Unknown mode.']);
} catch (Throwable $e) {
  if ($conn->errno === 1451) { // FK constraint
    echo json_encode(['ok'=>false,'message'=>'Still referenced; try Reassign.']); 
  } else {
    echo json_encode(['ok'=>false,'message'=>'Error: '.$e->getMessage()]);
  }
}
