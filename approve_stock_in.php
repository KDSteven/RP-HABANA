<?php
// approve_stock_in.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions.php';

if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); exit; }

$reqId   = (int)($_POST['id'] ?? 0);
$adminId = (int)($_SESSION['user_id'] ?? 0);

$conn->begin_transaction();
try {
  // Lock row
  $stmt = $conn->prepare("
    SELECT id, product_id, branch_id, quantity, expiry_date, status
    FROM stock_in_request
    WHERE id=? FOR UPDATE
  ");
  $stmt->bind_param("i", $reqId);
  $stmt->execute();
  $req = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if (!$req || $req['status'] !== 'pending') throw new Exception("Invalid or already processed.");

  // Update totals
  $stmt = $conn->prepare("SELECT inventory_id, stock FROM inventory WHERE product_id=? AND branch_id=? LIMIT 1");
  $stmt->bind_param("ii", $req['product_id'], $req['branch_id']);
  $stmt->execute();
  $stmt->bind_result($inventory_id, $current_stock);
  $hasRow = $stmt->fetch();
  $stmt->close();

  if ($hasRow) {
    $newStock = (int)$current_stock + (int)$req['quantity'];
    $stmt = $conn->prepare("UPDATE inventory SET stock=? WHERE inventory_id=?");
    $stmt->bind_param("ii", $newStock, $inventory_id);
    $stmt->execute(); $stmt->close();
  } else {
    $stmt = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $req['product_id'], $req['branch_id'], $req['quantity']);
    $stmt->execute(); $stmt->close();
  }

  // Lots if expiry present
  if (!empty($req['expiry_date'])) {
    $stmt = $conn->prepare("
      INSERT INTO inventory_lots (product_id, branch_id, expiry_date, qty)
      VALUES (?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)
    ");
    $stmt->bind_param("iisi", $req['product_id'], $req['branch_id'], $req['expiry_date'], $req['quantity']);
    $stmt->execute(); $stmt->close();
  }

  // Mark approved
  $stmt = $conn->prepare("UPDATE stock_in_request SET status='approved', decided_by=?, decision_date=NOW() WHERE id=? AND status='pending'");
  $stmt->bind_param("ii", $adminId, $reqId);
  $stmt->execute(); $stmt->close();

  logAction($conn, "Approve Stock-In",
    "Approved +{$req['quantity']} (prod {$req['product_id']})".(!empty($req['expiry_date'])?" (Expiry: {$req['expiry_date']})":""),
    null, (int)$req['branch_id']
  );

  $conn->commit();
  echo json_encode(['status'=>'ok']);
} catch (Throwable $e) {
  $conn->rollback();
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
