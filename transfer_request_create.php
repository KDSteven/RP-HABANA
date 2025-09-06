<?php
session_start();
header('Content-Type: application/json');
include 'config/db.php';

function out($status,$message){ echo json_encode(['status'=>$status,'message'=>$message]); exit; }

if (!isset($_SESSION['user_id'], $_SESSION['role'])) out('error','Not authorized.');
// Allow stockman (and optionally admin)
if ($_SESSION['role'] !== 'stockman' && $_SESSION['role'] !== 'admin') out('error','Access denied.');

$product_id        = (int)($_POST['product_id'] ?? 0);
$source_branch     = (int)($_POST['source_branch'] ?? 0);
$destination_branch= (int)($_POST['destination_branch'] ?? 0);
$quantity          = (int)($_POST['quantity'] ?? 0);
$requested_by      = (int)$_SESSION['user_id'];

if (!$product_id || !$source_branch || !$destination_branch || $source_branch === $destination_branch || $quantity < 1) {
  out('error','Invalid data. Please check your input.');
}

// OPTIONAL: validate stock at source (avoid negative)
// $stmt = $conn->prepare("SELECT stock FROM inventory WHERE product_id=? AND branch_id=?");
// $stmt->bind_param("ii",$product_id,$source_branch);
// $stmt->execute();
// $res = $stmt->get_result();
// $row = $res->fetch_assoc();
// if (!$row || (int)$row['stock'] < $quantity) out('error','Insufficient stock at source.');

$stmt = $conn->prepare("
  INSERT INTO transfer_requests (product_id, source_branch, destination_branch, quantity, requested_by)
  VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("iiiii", $product_id, $source_branch, $destination_branch, $quantity, $requested_by);
if ($stmt->execute()) {
  out('success','Transfer request submitted successfully!');
}
out('error','Error submitting transfer request.');
