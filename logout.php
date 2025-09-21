<?php
session_start();
require 'config/db.php';

// Save details before destroying session
$user_id   = $_SESSION['user_id'] ?? null;
$branch_id = $_SESSION['branch_id'] ?? null;
$action    = "Logout";

// Destroy session
$_SESSION = [];
session_unset();
session_destroy();

// Log logout if user was logged in
if ($user_id) {
    $details = "User logged out.";
    $stmt = $conn->prepare("
        INSERT INTO logs (user_id, action, details, timestamp, branch_id)
        VALUES (?, ?, ?, NOW(), ?)
    ");
    $stmt->bind_param("issi", $user_id, $action, $details, $branch_id);
    $stmt->execute();
    $stmt->close();
}
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Signing out…</title>
  </head>
  <body>
    <script>
      try {
        // Clear sidebar submenu state
        Object.keys(localStorage).forEach(k => {
          if (k.startsWith('sidebar-sub-')) localStorage.removeItem(k);
        });
      } catch(e) {}
      // Redirect to login page
      window.location.replace('index.php'); // or index.html if that's your login
    </script>
    <noscript>
      <a href="index.php">Continue to login</a>
    </noscript>
  </body>
</html>
