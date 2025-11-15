<?php
session_start();
header('Content-Type: application/json');
require __DIR__.'/config/db.php';

if (($_SESSION['role'] ?? '') !== 'admin') { echo json_encode(['ok'=>false,'message'=>'Forbidden']); exit; }

$category_id = (int)($_POST['category_id'] ?? 0);
$mode        = $_POST['mode'] ?? 'deactivate';
$reassign_to = (int)($_POST['reassign_to'] ?? 0);

if ($category_id <= 0) { echo json_encode(['ok'=>false,'message'=>'Invalid category']); exit; }

try {
  if ($mode === 'deactivate') {
    $stmt = $conn->prepare("UPDATE categories SET active=0 WHERE category_id=?");
    $stmt->bind_param("i",$category_id);
    $stmt->execute();
    echo json_encode(['ok'=>true,'message'=>'Category deactivated.']); exit;
  }

  if ($mode === 'restrict') {
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM products WHERE category_id=?");
    $stmt->bind_param("i",$category_id);
    $stmt->execute(); $c = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
    if ($c > 0) { echo json_encode(['ok'=>false,'message'=>'Category is in use; cannot delete.']); exit; }

    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id=? LIMIT 1");
    $stmt->bind_param("i",$category_id);
    $stmt->execute();
    echo json_encode(['ok'=>true,'message'=>'Category deleted.']); exit;
  }

  if ($mode === 'reassign') {
    if ($reassign_to <= 0 || $reassign_to === $category_id) {
      echo json_encode(['ok'=>false,'message'=>'Pick a different target category.']); exit;
    }
    $conn->begin_transaction();
    $stmt = $conn->prepare("UPDATE products SET category_id=? WHERE category_id=?");
    $stmt->bind_param("ii",$reassign_to,$category_id);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id=? LIMIT 1");
    $stmt->bind_param("i",$category_id);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['ok'=>true,'message'=>'Products reassigned & category deleted.']); exit;
  }

  echo json_encode(['ok'=>false,'message'=>'Unknown mode.']);
} catch (Throwable $e) {
  if ($conn->errno === 1451) {
    echo json_encode(['ok'=>false,'message'=>'Still referenced; try Reassign.']); 
  } else {
    echo json_encode(['ok'=>false,'message'=>'Error: '.$e->getMessage()]);
  }
}
