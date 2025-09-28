<?php
// reject_stock_in.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/config/db.php';

if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); exit; }

$reqId    = (int)($_POST['id'] ?? 0);
$adminId  = (int)($_SESSION['user_id'] ?? 0);
$remarks  = trim($_POST['remarks'] ?? '');

$stmt = $conn->prepare("
  UPDATE stock_in_request
  SET status='rejected', decided_by=?, decision_date=NOW(), remarks=?
  WHERE id=? AND status='pending'
");
$stmt->bind_param("isi", $adminId, $remarks, $reqId);
$stmt->execute();
echo json_encode(['status'=>'ok']);
