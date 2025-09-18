<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
  echo json_encode(['status' => 'error', 'message' => 'Not authorized']);
  exit;
}

require_once 'config/db.php';
require_once 'functions.php';

$userId = (int)$_SESSION['user_id'];
$role   = $_SESSION['role'];

// Expecting form fields from the modal
$product_id         = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$source_branch      = isset($_POST['source_branch']) ? (int)$_POST['source_branch'] : 0;
$destination_branch = isset($_POST['destination_branch']) ? (int)$_POST['destination_branch'] : 0;
$quantity           = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

if ($product_id <= 0 || $source_branch <= 0 || $destination_branch <= 0 || $quantity <= 0) {
  echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
  exit;
}
if ($source_branch === $destination_branch) {
  echo json_encode(['status' => 'error', 'message' => 'Source and destination must be different.']);
  exit;
}

try {
  if ($role === 'admin') {
    // --- ADMIN: perform transfer immediately (no approvals) ---
    $conn->begin_transaction();

    // Check available stock at source (and lock row)
    $stmt = $conn->prepare("
      SELECT stock FROM inventory
      WHERE product_id = ? AND branch_id = ? FOR UPDATE
    ");
    $stmt->bind_param("ii", $product_id, $source_branch);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    if (!$res || $res->num_rows === 0) {
      $conn->rollback();
      echo json_encode(['status' => 'error', 'message' => 'No inventory found at source branch.']);
      exit;
    }
    $row = $res->fetch_assoc();
    $srcStock = (int)$row['stock'];
    if ($srcStock < $quantity) {
      $conn->rollback();
      echo json_encode(['status' => 'error', 'message' => 'Insufficient stock at source branch.']);
      exit;
    }

    // Decrease from source
    $stmt = $conn->prepare("
      UPDATE inventory SET stock = stock - ?
      WHERE product_id = ? AND branch_id = ?
    ");
    $stmt->bind_param("iii", $quantity, $product_id, $source_branch);
    $stmt->execute();
    $stmt->close();

    // Upsert to destination
    $stmt = $conn->prepare("
      SELECT stock FROM inventory
      WHERE product_id = ? AND branch_id = ? FOR UPDATE
    ");
    $stmt->bind_param("ii", $product_id, $destination_branch);
    $stmt->execute();
    $dstRes = $stmt->get_result();
    $stmt->close();

    if ($dstRes && $dstRes->num_rows > 0) {
      $stmt = $conn->prepare("
        UPDATE inventory SET stock = stock + ?
        WHERE product_id = ? AND branch_id = ?
      ");
      $stmt->bind_param("iii", $quantity, $product_id, $destination_branch);
    } else {
      $stmt = $conn->prepare("
        INSERT INTO inventory (product_id, branch_id, stock, archived)
        VALUES (?, ?, ?, 0)
      ");
      $stmt->bind_param("iii", $product_id, $destination_branch, $quantity);
    }
    $stmt->execute();
    $stmt->close();

    // Record the transfer as approved directly (for history/audit)
    $stmt = $conn->prepare("
      INSERT INTO transfer_requests
        (product_id, source_branch, destination_branch, quantity, status, requested_by, request_date, decided_by, decision_date)
      VALUES (?, ?, ?, ?, 'approved', ?, NOW(), ?, NOW())
    ");
    $stmt->bind_param("iiiiii", $product_id, $source_branch, $destination_branch, $quantity, $userId, $userId);
    $stmt->execute();
    $stmt->close();

    // Log action
    $p = $conn->query("SELECT product_name FROM products WHERE product_id = {$product_id}")->fetch_assoc();
    logAction(
      $conn,
      "Stock Transfer",
      "Transferred {$quantity} {$p['product_name']} from Branch {$source_branch} to Branch {$destination_branch}",
      null,
      $destination_branch
    );

    $conn->commit();

    echo json_encode(['status' => 'success', 'message' => 'Transfer completed.']);
    exit;

  } else {
    // --- STOCKMAN (or non-admin): create pending approval ---
    $stmt = $conn->prepare("
      INSERT INTO transfer_requests
        (product_id, source_branch, destination_branch, quantity, status, requested_by, request_date)
      VALUES (?, ?, ?, ?, 'pending', ?, NOW())
    ");
    $stmt->bind_param("iiiii", $product_id, $source_branch, $destination_branch, $quantity, $userId);
    $stmt->execute();
    $stmt->close();

    $p = $conn->query("SELECT product_name FROM products WHERE product_id = {$product_id}")->fetch_assoc();
    logAction(
      $conn,
      "Stock Transfer Request",
      "Requested transfer of {$quantity} {$p['product_name']} from Branch {$source_branch} to Branch {$destination_branch}"
    );

    echo json_encode(['status' => 'success', 'message' => 'Transfer request submitted for approval.']);
    exit;
  }
} catch (Throwable $e) {
  if ($conn->errno) { $conn->rollback(); }
  echo json_encode(['status' => 'error', 'message' => 'Something went wrong.']);
  exit;
}
