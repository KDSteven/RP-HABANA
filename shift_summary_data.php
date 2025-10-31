<?php

ini_set('display_errors', '0');      // don't print warnings to output
ini_set('log_errors', '1');          // log them instead (php_error.log)
header('Content-Type: application/json; charset=utf-8');
if (function_exists('ob_get_level')) while (ob_get_level()) ob_end_clean(); // drop any prior output buffers
// shift_summary_data.php
session_start();
require 'config/db.php';
require 'functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'Unauthorized']);
  exit;
}

$user_id   = (int)$_SESSION['user_id'];
$role      = $_SESSION['role'];
$branch_id = (int)($_SESSION['branch_id'] ?? 0);

try {
// --- Resolve which shift to show
$shift_id = isset($_GET['shift_id']) ? (int)$_GET['shift_id'] : 0;

if ($shift_id <= 0) {
  // 1) try active shift
  $active = get_active_shift($conn, $user_id, $branch_id);
  if ($active) {
    $shift_id = (int)$active['shift_id'];
  } else {
    // 2) fallback to most recent shift for this user & branch (or any if admin)
    if ($role === 'admin') {
      $stmt = $conn->prepare("
        SELECT shift_id
        FROM shifts
        ORDER BY start_time DESC
        LIMIT 1
      ");
    } else {
      $stmt = $conn->prepare("
        SELECT shift_id
        FROM shifts
        WHERE user_id = ? AND branch_id = ?
        ORDER BY start_time DESC
        LIMIT 1
      ");
      $stmt->bind_param("ii", $user_id, $branch_id);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
      $shift_id = (int)$row['shift_id'];
    } else {
      echo json_encode(['ok'=>true,'active'=>false,'msg'=>'No shifts found for this user/branch']);
      exit;
    }
  }
}

  // ---- Shift basics
$stmt = $conn->prepare("
  SELECT s.shift_id, s.user_id, u.name AS cashier_name, s.branch_id,
         s.start_time, s.end_time, s.opening_cash, s.closing_cash,
         s.expected_cash, s.cash_difference, s.status
  FROM shifts s
  LEFT JOIN users u ON u.id = s.user_id
  WHERE s.shift_id = ?
");
  $stmt->bind_param("i", $shift_id);
  $stmt->execute();
  $shift = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$shift) {
    echo json_encode(['ok'=>false,'error'=>'Shift not found']);
    exit;
  }

// ---- Sales aggregates (exclude canceled/void)
$stmt = $conn->prepare("
  SELECT
    COUNT(*) AS sale_count,
    COALESCE(SUM(total),0)                    AS gross_total_ex_vat,
    COALESCE(SUM(discount),0)                 AS discount_total,
    COALESCE(SUM(vat),0)                      AS vat_total,
    COALESCE(SUM(payment),0)                  AS payment_total,
    COALESCE(SUM(change_given),0)             AS change_total,
    COALESCE(SUM(payment - change_given),0)   AS net_cash_to_drawer
  FROM sales
  WHERE shift_id = ? AND LOWER(COALESCE(status,'')) NOT IN ('canceled','void')
");
$stmt->bind_param("i", $shift_id);
$stmt->execute();
$salesAgg = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

// ---- Refunds (money out of drawer)
// ---- Refunds (money out of drawer for this shift)
// Join via sales so we don't rely on a shift_id column in sales_refunds
$stmt = $conn->prepare("
  SELECT COUNT(*) AS refund_count,
         COALESCE(SUM(sr.refund_total),0) AS refund_total
  FROM sales_refunds sr
  JOIN sales s ON s.sale_id = sr.sale_id
  WHERE s.shift_id = ?
");
$stmt->bind_param("i", $shift_id);
$stmt->execute();
$refundAgg = $stmt->get_result()->fetch_assoc() ?: ['refund_count'=>0,'refund_total'=>0.0];
$stmt->close();

// ---- Petty cash moves
$stmt = $conn->prepare("
  SELECT
    COALESCE(SUM(CASE WHEN move_type='pay_in'  THEN amount END),0) AS pay_in_total,
    COALESCE(SUM(CASE WHEN move_type='pay_out' THEN amount END),0) AS pay_out_total
  FROM shift_cash_moves
  WHERE shift_id = ?
");
$stmt->bind_param("i", $shift_id);
$stmt->execute();
$movesAgg = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

// ---- Pay-in / Pay-out list (details)
$stmt = $conn->prepare("
  SELECT
    id AS move_id,
    move_type,
    amount,
    reason,
    created_at
  FROM shift_cash_moves
  WHERE shift_id = ?
  ORDER BY created_at DESC, id DESC
");
$stmt->bind_param("i", $shift_id);
$stmt->execute();
$movesList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
$stmt->close();

$opening  = (float)($shift['opening_cash'] ?? 0.0);
$netCash  = (float)($salesAgg['net_cash_to_drawer'] ?? 0.0);
$payIn    = (float)($movesAgg['pay_in_total'] ?? 0.0);
$payOut   = (float)($movesAgg['pay_out_total'] ?? 0.0);
$refTot   = (float)($refundAgg['refund_total'] ?? 0.0);

// Expected cash in drawer:
$expected = $opening + $netCash + $payIn - $payOut - $refTot;



echo json_encode([
  'ok' => true,
  'active' => true,
  'shift' => $shift,
  'agg' => [
    'sales'    => $salesAgg,
    'refunds'  => $refundAgg,
    'moves'    => $movesAgg,
    'expected' => $expected
  ],
  'lists' => [
    'moves'   => $movesList,  // <-- FIXED (was just [])
    'sales'   => [],
    'refunds' => []
  ]
]);


} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
