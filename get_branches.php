<?php
session_start();
include 'config/db.php';

header('Content-Type: application/json');

// Optional: restrict to logged-in users
if (!isset($_SESSION['user_id'])) {
  echo json_encode([]); exit;
}

$out = [];
$res = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name");
while ($row = $res->fetch_assoc()) {
  $out[] = $row;
}
echo json_encode($out);
