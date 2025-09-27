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

// Small helpers
function fetch_product_name(mysqli $conn, int $pid): string {
  $name = '';
  if ($res = $conn->query("SELECT product_name FROM products WHERE product_id = ".(int)$pid." LIMIT 1")) {
    $name = (string)($res->fetch_assoc()['product_name'] ?? '');
  }
  return $name;
}

function fetch_critical_point(mysqli $conn, int $pid): int {
  $crit = 0;
  if ($stmt = $conn->prepare("SELECT COALESCE(critical_point,0) AS c FROM products WHERE product_id = ?")) {
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $rs = $stmt->get_result();
    $crit = (int)($rs->fetch_assoc()['c'] ?? 0);
    $stmt->close();
  }
  return $crit;
}

function fetch_pending_outgoing(mysqli $conn, int $branchId, int $pid): int {
  $pending = 0;
  if ($stmt = $conn->prepare("
      SELECT COALESCE(SUM(quantity),0) AS q
      FROM transfer_requests
      WHERE source_branch = ? AND product_id = ? AND status = 'pending'
    ")) {
    $stmt->bind_param("ii", $branchId, $pid);
    $stmt->execute();
    $rs = $stmt->get_result();
    $pending = (int)($rs->fetch_assoc()['q'] ?? 0);
    $stmt->close();
  }
  return $pending;
}

try {
  // Common data (for messages/logs)
  $productName = fetch_product_name($conn, $product_id);
  if ($productName === '') {
    echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
    exit;
  }

  if ($role === 'admin') {
    /* =========================
       ADMIN: perform transfer immediately (no approvals)
       ========================= */
    $conn->begin_transaction();

    // 1) Lock source inventory row and read stock
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
    $srcStock = (int)$res->fetch_assoc()['stock'];

    // 2) Read critical and pending-outgoing to compute transferable capacity
    $crit       = fetch_critical_point($conn, $product_id);
    $pendingOut = fetch_pending_outgoing($conn, $source_branch, $product_id);
    $availableToTransfer = $srcStock - $crit - $pendingOut;

    if ($availableToTransfer <= 0) {
      $conn->rollback();
      echo json_encode(['status' => 'error', 'message' => 'Item is at/under its critical level at the source branch.']);
      exit;
    }
    if ($quantity > $availableToTransfer) {
      $conn->rollback();
      echo json_encode([
        'status'  => 'error',
        'message' => "Only {$availableToTransfer} can be transferred without breaching critical level."
      ]);
      exit;
    }

    // 3) Decrease from source
    $stmt = $conn->prepare("
      UPDATE inventory SET stock = stock - ?
      WHERE product_id = ? AND branch_id = ?
    ");
    $stmt->bind_param("iii", $quantity, $product_id, $source_branch);
    $stmt->execute();
    if ($stmt->affected_rows <= 0) {
      $stmt->close();
      $conn->rollback();
      echo json_encode(['status' => 'error', 'message' => 'Failed to update source inventory.']);
      exit;
    }
    $stmt->close();

    // 4) Upsert to destination (lock if exists)
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
      $stmt->execute();
      $stmt->close();
    } else {
      $stmt = $conn->prepare("
        INSERT INTO inventory (product_id, branch_id, stock, archived)
        VALUES (?, ?, ?, 0)
      ");
      $stmt->bind_param("iii", $product_id, $destination_branch, $quantity);
      $stmt->execute();
      $stmt->close();
    }

    // 5) Record as approved (audit trail)
    $stmt = $conn->prepare("
      INSERT INTO transfer_requests
        (product_id, source_branch, destination_branch, quantity, status, requested_by, request_date, decided_by, decision_date)
      VALUES (?, ?, ?, ?, 'approved', ?, NOW(), ?, NOW())
    ");
    $stmt->bind_param("iiiiii", $product_id, $source_branch, $destination_branch, $quantity, $userId, $userId);
    $stmt->execute();
    $stmt->close();

    // 6) Log action
    logAction(
      $conn,
      "Stock Transfer",
      "Transferred {$quantity} {$productName} from Branch {$source_branch} to Branch {$destination_branch}",
      null,
      $destination_branch
    );

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Transfer completed.']);
    exit;

  } else {
    /* =========================
       NON-ADMIN: create pending approval (but block if it would breach critical)
       ========================= */

    // Lock source row to compute an accurate available-to-transfer
    $conn->begin_transaction();

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
    $srcStock = (int)$res->fetch_assoc()['stock'];

    $crit       = fetch_critical_point($conn, $product_id);
    $pendingOut = fetch_pending_outgoing($conn, $source_branch, $product_id);
    $availableToTransfer = $srcStock - $crit - $pendingOut;

    if ($availableToTransfer <= 0) {
      $conn->rollback();
      echo json_encode(['status' => 'error', 'message' => 'Item is at/under its critical level at the source branch.']);
      exit;
    }
    if ($quantity > $availableToTransfer) {
      $conn->rollback();
      echo json_encode([
        'status'  => 'error',
        'message' => "Only {$availableToTransfer} can be requested without breaching critical level."
      ]);
      exit;
    }

    // Okay to create a pending request
    $stmt = $conn->prepare("
      INSERT INTO transfer_requests
        (product_id, source_branch, destination_branch, quantity, status, requested_by, request_date)
      VALUES (?, ?, ?, ?, 'pending', ?, NOW())
    ");
    $stmt->bind_param("iiiii", $product_id, $source_branch, $destination_branch, $quantity, $userId);
    $stmt->execute();
    $stmt->close();

    // Log
    logAction(
      $conn,
      "Stock Transfer Request",
      "Requested transfer of {$quantity} {$productName} from Branch {$source_branch} to Branch {$destination_branch}"
    );

    $conn->commit();

    echo json_encode(['status' => 'success', 'message' => 'Transfer request submitted for approval.']);
    exit;
  }

} catch (Throwable $e) {
  // If a transaction is open, rollback
  if ($conn && $conn->errno === 0) {
    // We cannot reliably detect transaction state; try rollback safely
    @$conn->rollback();
  }
  // You can log $e->getMessage() to a server log if needed
  echo json_encode(['status' => 'error', 'message' => 'Something went wrong.']);
  exit;
}
