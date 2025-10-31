<?php
session_start();
require 'config/db.php';
require 'functions.php';

$user_id   = (int)($_SESSION['user_id'] ?? 0);
$branch_id = (int)($_SESSION['branch_id'] ?? 0);
if (!$user_id || !$branch_id) { header('Location: index.html'); exit; }

$active = get_active_shift($conn, $user_id, $branch_id);
if (!$active) {
  $_SESSION['toast'] = ['type'=>'danger','msg'=>'Open a shift first.'];
  header('Location: pos.php'); exit;
}

$shift_id = (int)$active['shift_id'];
$move_type = $_POST['move_type'] ?? '';
$amount    = (float)($_POST['amount'] ?? 0);
$reason    = trim($_POST['reason'] ?? '');

if (!in_array($move_type, ['pay_in','pay_out'], true) || $amount <= 0) {
  $_SESSION['toast'] = ['type'=>'danger','msg'=>'Invalid petty cash entry.'];
  header('Location: pos.php'); exit;
}

$stmt = $conn->prepare("INSERT INTO shift_cash_moves (shift_id, move_type, amount, reason) VALUES (?,?,?,?)");
$stmt->bind_param("isds", $active['shift_id'], $move_type, $amount, $reason);
$stmt->execute();
$stmt->close();

$_SESSION['toast'] = ['type'=>'success','msg'=>'Petty cash recorded.'];
header('Location: pos.php'); exit;
