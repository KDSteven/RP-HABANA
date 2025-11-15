<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require __DIR__ . '/config/db.php';
require __DIR__ . '/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: archive.php?err=forbidden'); exit;
}

$category_id = (int)($_POST['category_id'] ?? 0);
if ($category_id <= 0) { header('Location: archive.php?err=invalid'); exit; }

$stmt = $conn->prepare("UPDATE categories SET active = 1 WHERE category_id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$stmt->close();

logAction($conn, "Restore Category", "Restored category_id={$category_id}");

header('Location: archive.php?category_restored=1');
exit;
