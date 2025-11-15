<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require __DIR__ . '/config/db.php';
require __DIR__ . '/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: archive.php?err=forbidden'); exit;
}

$brand_id = (int)($_POST['brand_id'] ?? 0);
if ($brand_id <= 0) { header('Location: archive.php?err=invalid'); exit; }

$stmt = $conn->prepare("UPDATE brands SET active = 1 WHERE brand_id = ?");
$stmt->bind_param("i", $brand_id);
$stmt->execute();
$stmt->close();

logAction($conn, "Restore Brand", "Restored brand_id={$brand_id}");

header('Location: archive.php?brand_restored=1');
exit;
