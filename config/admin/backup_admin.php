<?php
// admin/backup_admin.php
// -------------------------------------------------------------
// SIMPLE, SAFE DB BACKUP & RESTORE (Admin only)
// -------------------------------------------------------------
define('MYSQL_BIN_DIR', 'C:\\xampp\\mysql\\bin\\');  // adjust if needed

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// after session_start();
$self = basename($_SERVER['SCRIPT_NAME']);   // e.g. "backup_admin.php"

// Safe defaults to avoid "undefined variable" warnings
$role    = isset($role) ? $role : ($_SESSION['role'] ?? '');
$pending = isset($pending) ? (int)$pending : (int)($_SESSION['pending_approvals'] ?? 0); // change key if you store it differently


// --- Admin check (adjust to your auth system) ---
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
if (!$is_admin) {
  http_response_code(403);
  exit('Forbidden');
}

require_once __DIR__ . '/../db.php';

// Use the same names defined in db.php
$db   = $dbname;
$host = $servername;
$user = $username;
$pass = $password;

date_default_timezone_set('Asia/Manila');

// --- Backups directory OUTSIDE web root ---
// On XAMPP:   C:\xampp\db_backups
// On Hosting: /home/username/db_backups
$BACKUP_DIR = rtrim(dirname($_SERVER['DOCUMENT_ROOT']), '\\/') . DIRECTORY_SEPARATOR . 'db_backups';
if (!is_dir($BACKUP_DIR)) {
    mkdir($BACKUP_DIR, 0775, true);
}
if (!is_dir($BACKUP_DIR)) {
    die('Cannot create backups directory at ' . htmlspecialchars($BACKUP_DIR));
}

// --- Secure download endpoint (?dl=filename.sql) ---
if ($is_admin && isset($_GET['dl'])) {
    $file = basename($_GET['dl']); // prevent path traversal
    $path = $BACKUP_DIR . DIRECTORY_SEPARATOR . $file;

    if (!is_file($path)) {
        http_response_code(404);
        exit('Backup not found.');
    }

    header('Content-Type: application/sql');
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="' . $file . '"');
    readfile($path);
    exit;
}

function is_windows() {
  return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}

function bin_path($bin) {
  if (defined('MYSQL_BIN_DIR') && MYSQL_BIN_DIR) {
    $p = rtrim(MYSQL_BIN_DIR, '\\/').DIRECTORY_SEPARATOR.$bin.(is_windows()?'.exe':'');
    return file_exists($p) ? $p : $bin;
  }
  return $bin;
}

function safe_filename($name) {
  return preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $name);
}

// -------------------------------------------------------------
// BACKUP (Download)
// -------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'backup') {
  global $db, $host, $user, $pass;

  $stamp = date('Ymd_His');
  $fname = safe_filename($db . "_backup_{$stamp}.sql");
  $fpath = $BACKUP_DIR . DIRECTORY_SEPARATOR . $fname;

  // Try mysqldump first
  $mysqldump = bin_path('mysqldump');
  $cmd = sprintf('"%s" --host=%s --user=%s --password=%s --routines --events --triggers --single-transaction --set-gtid-purged=OFF --default-character-set=utf8mb4 %s',
    $mysqldump,
    escapeshellarg($host),
    escapeshellarg($user),
    escapeshellarg($pass),
    escapeshellarg($db)
  );

  $ok = false;
  try {
    $descriptorspec = [
      1 => ['file', $fpath, 'w'],
      2 => ['pipe', 'w']
    ];
    $proc = proc_open($cmd, $descriptorspec, $pipes);
    if (is_resource($proc)) {
      $stderr = stream_get_contents($pipes[2]);
      fclose($pipes[2]);
      $status = proc_close($proc);
      $ok = ($status === 0);
      if (!$ok && filesize($fpath) === 0) @unlink($fpath);
    }
  } catch (Throwable $e) {}

  if (!$ok) {
    // Fallback: pure PHP export
    $conn = $GLOBALS['conn'];
    $conn->set_charset('utf8mb4');

    $sql_out  = "-- Simple dump for {$db} @ {$stamp}\n";
    $sql_out .= "SET FOREIGN_KEY_CHECKS=0;\n";

    $tables = [];
    $res = $conn->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    while ($row = $res->fetch_array()) {
      $tables[] = $row[0];
    }

    foreach ($tables as $table) {
      $create = $conn->query("SHOW CREATE TABLE `{$table}`")->fetch_assoc()['Create Table'] ?? '';
      $sql_out .= "DROP TABLE IF EXISTS `{$table}`;\n";
      $sql_out .= $create . ";\n\n";

      $data = $conn->query("SELECT * FROM `{$table}`");
      if ($data && $data->num_rows > 0) {
        while ($row = $data->fetch_assoc()) {
          $cols = array_map(fn($c) => "`".$conn->real_escape_string($c)."`", array_keys($row));
          $vals = array_map(function($v) use ($conn) {
            return is_null($v) ? "NULL" : "'".$conn->real_escape_string($v)."'";
          }, array_values($row));
          $sql_out .= "INSERT INTO `{$table}` (".implode(',', $cols).") VALUES (".implode(',', $vals).");\n";
        }
      }
      $sql_out .= "\n";
    }
    $sql_out .= "SET FOREIGN_KEY_CHECKS=1;\n";

    file_put_contents($fpath, $sql_out);
    $ok = file_exists($fpath) && filesize($fpath) > 0;
  }

if (!$ok) {
    $_SESSION['flash_level'] = 'danger';
    $_SESSION['flash_msg']   = 'Backup failed.';
    header("Location: $self"); exit;
}

// Success: redirect back so the table refreshes
$_SESSION['flash_level'] = 'success';
$_SESSION['flash_msg']   = 'Backup created successfully!';
// pass the created filename so the page can auto-click the download link
header("Location: $self?created=" . rawurlencode($fname));
exit;
}

// -------------------------------------------------------------
// RESTORE (Upload .sql)
// -------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'restore') {
  if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_level'] = 'warning';
    $_SESSION['flash_msg']   = 'Please choose a valid .sql file to restore.';
    header("Location: $self"); exit;
  }

  $tmp  = $_FILES['sql_file']['tmp_name'];
  $name = safe_filename($_FILES['sql_file']['name']);
  $dest = $BACKUP_DIR . DIRECTORY_SEPARATOR . 'upload_' . time() . '_' . $name;
  @move_uploaded_file($tmp, $dest);

  if (!file_exists($dest)) {
    $_SESSION['flash_level'] = 'danger';
    $_SESSION['flash_msg']   = 'Upload failed.';
    header("Location: $self"); exit;
  }

  global $db, $host, $user, $pass;

  // Prefer mysql client
  $mysql = bin_path('mysql');
  $cmd = sprintf('"%s" --host=%s --user=%s --password=%s --binary-mode=1 %s',
    $mysql,
    escapeshellarg($host),
    escapeshellarg($user),
    escapeshellarg($pass),
    escapeshellarg($db)
  );

  $ok = false;
  try {
    $descriptorspec = [
      0 => ['file', $dest, 'r'],
      1 => ['pipe', 'w'],
      2 => ['pipe', 'w']
    ];
    $proc = proc_open($cmd, $descriptorspec, $pipes);
    if (is_resource($proc)) {
      fclose($pipes[1]); fclose($pipes[2]);
      $status = proc_close($proc);
      $ok = ($status === 0);
    }
  } catch (Throwable $e) {}

  if (!$ok) {
    // Fallback: PHP importer
    set_time_limit(0);
    $conn = $GLOBALS['conn'];
    $conn->set_charset('utf8mb4');

    $sql = file_get_contents($dest);
    $sql = preg_replace('/DEFINER=`[^`]+`@`[^`]+`/i', 'DEFINER=CURRENT_USER', $sql);

    $statements = [];
    $buffer = '';
    $inString = false; $stringChar = '';
    $len = strlen($sql);
    for ($i=0; $i<$len; $i++) {
      $ch = $sql[$i];
      $buffer .= $ch;
      if ($inString) {
        if ($ch === $stringChar && $sql[$i-1] !== '\\') {
          $inString = false;
        }
      } else {
        if ($ch === '\'' || $ch === '"') {
          $inString = true; $stringChar = $ch;
        } elseif ($ch === ';') {
          $statements[] = trim($buffer);
          $buffer = '';
        }
      }
    }
    $buffer = trim($buffer);
    if ($buffer !== '') $statements[] = $buffer;

    $conn->begin_transaction();
    try {
      foreach ($statements as $stmt) {
        if ($stmt === '' || stripos($stmt, 'delimiter ') === 0) continue;
        $conn->query($stmt);
      }
      $conn->commit();
      $ok = true;
    } catch (Throwable $e) {
      $conn->rollback();
      $ok = false;
    }
  }

  $_SESSION['flash_level'] = $ok ? 'success' : 'danger';
  $_SESSION['flash_msg']   = $ok ? 'Database successfully restored.' : 'Restore failed.';
  header("Location: $self"); exit;
}

// -------------------------------------------------------------
// Simple UI below
// -------------------------------------------------------------
function flash() {
  if (isset($_SESSION['flash_msg'])) {
    $lvl = $_SESSION['flash_level'] ?? 'info';
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_level']);

    $map = [
      'success' => ['bg-success', 'fa-check-circle'],
      'danger'  => ['bg-danger',  'fa-times-circle'],
      'warning' => ['bg-warning text-dark', 'fa-exclamation-triangle'],
      'info'    => ['bg-primary', 'fa-info-circle']
    ];
    [$color, $icon] = $map[$lvl] ?? $map['info'];

    echo "<script>
      document.addEventListener('DOMContentLoaded', function() {
        var toastEl = document.getElementById('appToast');
        var header  = document.getElementById('appToastHeader');
        var body    = document.getElementById('appToastBody');
        var icon    = document.getElementById('appToastIcon');

        header.className = 'toast-header text-white $color';
        icon.className   = 'fas $icon me-2';
        body.textContent = ".json_encode($msg).";

        var bsToast = new bootstrap.Toast(toastEl);
        bsToast.show();
      });
    </script>";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<?php $pageTitle = 'Backup & Restore'; ?>
<title><?= htmlspecialchars("RP Habana — $pageTitle") ?></title>
<title><?= strtoupper($role) ?> Dashboard</title>
<link rel="icon" href="../../img/R.P.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="../../css/dashboard.css">
<link rel="stylesheet" href="../../css/notifications.css">
<link rel="stylesheet" href="../../css/sidebar.css">
<audio id="notifSound" src="../../notif.mp3"></audio>

<style>
/* --- Backup Page Styling --- */
.backup-wrap{ margin:20px auto; width: calc(100% - 220px); max-width:none;padding:20px}
.backup-header{box-shadow:0 4px 12px rgba(0,0,0,.57); padding: 30px; border-radius: 12px; display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.backup-header h1{font-size:26px;font-weight:700;margin:0;color:#333}
.meta .pill{padding:6px 12px;border-radius:999px;font-size:13px;color:#333}

.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-bottom:20px}
.panel{background:#fff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.57);padding:20px}
.panel h3{margin:0 0 10px;font-size:18px;font-weight:600}
.panel p{font-size:14px;color:#555}

.btn{border:none;border-radius:8px;padding:10px 14px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
.btn-primary{background:#007bff;color:#fff}
.btn-success{background:#16a34a;color:#fff}
.btn-primary:hover,.btn-success:hover{opacity:.9}

.input{width:100%;border:1px solid #ddd;border-radius:8px;padding:10px}

.divider{height:1px;background:#eee;margin:20px 0}

/* Table styling like reference */
.table-container{background:#fff;border-radius:5px;box-shadow:0 4px 12px rgba(0, 0, 0, 0.57);overflow:hidden}
.table thead th{background:linear-gradient(90deg,#f7931e,#ffb347);color:#fff;text-align:left;font-size:13px;padding:10px}
.table tbody tr:nth-child(even){background:#f9fafb}
.table tbody td{padding:8px 10px;vertical-align:middle;font-size:14px}
.td-actions a{margin-left:6px}

/* Action button style */
.btn-download{background:#f7931e;color:#fff;padding:6px 10px;font-size:13px;border-radius:6px;text-decoration:none;   transition: background 0.6s ease-in-out, 
              transform 0.4s ease-in-out, 
              box-shadow 0.6s ease-in-out, 
              color 0.3s ease-in-out;}
.btn-download:hover{    
    background: linear-gradient(90deg, #e67e00, #ffa500);
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.25);
    color: black;
  }


</style>
</head>
<body class="dashboard-page">

<!-- Sidebar -->
<div class="sidebar">
  <h2>
    <?= strtoupper($role) ?>
    <span class="notif-wrapper">
        <i class="fas fa-bell" id="notifBell"></i>
        <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>>0</span>
    </span>
  </h2>

    <!-- Common -->
    <a href="../../dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>

  <!-- Links -->
  <?php if ($role === 'admin'): ?>
    <a href="../../inventory.php"><i class="fas fa-box"></i> Inventory</a>
    <a href="../../Physical_inventory.php" class=""><i class="fas fa-warehouse"></i> Physical Inventory</a>
    <a href="../../sales.php"><i class="fas fa-receipt"></i> Sales</a>
    <a href="../../approvals.php"><i class="fas fa-check-circle"></i> Approvals
        <?php if ($pending > 0): ?>
            <span style="background:red;color:white;border-radius:50%;padding:3px 7px;font-size:12px;">
                <?= $pending ?>
            </span>
        <?php endif; ?>
    </a>
    <a href="../../accounts.php"><i class="fas fa-users"></i> Accounts</a>
    <a href="../../archive.php"><i class="fas fa-archive"></i> Archive</a>
    <a href="../../logs.php"><i class="fas fa-file-alt"></i> Logs</a>
    <a href="/config/admin/backup_admin.php" class="active"><i class="fa-solid fa-database"></i> Backup and Restore</a>
  <?php endif; ?>

  <?php if ($role === 'stockman'): ?>
    <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
  <?php endif; ?>

  <?php if ($role === 'staff'): ?>
    <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
    <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
  <?php endif; ?>

  <a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- Backup & Restore Content -->
<div class="backup-wrap">
  <div class="backup-header">
    <h1>Database Backup & Restore</h1>
    <div class="meta">
      <span class="pill"><strong>DB:</strong> <?= htmlspecialchars($db) ?></span>
    </div>
  </div>

  <?php if (function_exists('flash')) { flash(); } ?>

  <div class="grid">
    <!-- Backup -->
    <div class="panel">
      <h3>Download Backup (.sql)</h3>
      <p>Creates a full SQL dump (schema + data) and downloads it to your device.</p>
      <form method="post">
        <input type="hidden" name="action" value="backup">
        <button class="btn btn-primary" type="submit">
          <i class="fa-solid fa-download"></i> Download Backup
        </button>
      </form>
    </div>

    <!-- Restore -->
    <div class="panel">
      <h3>Restore from File (.sql)</h3>
      <p>Uploads a <code>.sql</code> file and restores it into <strong><?= htmlspecialchars($db) ?></strong>.</p>
        <form id="restoreForm" method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="restore">
          <input class="input" type="file" name="sql_file" accept=".sql" required>
          <div style="height:10px"></div>
          <button class="btn btn-success" type="submit">
            <i class="fa-solid fa-rotate-left"></i> Restore Database
          </button>
        </form>
    </div>
  </div>

  <div class="divider"></div>

    <!-- Stored backups -->
  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th>Filename</th>
          <th>Created</th>
          <th>Size</th>
          <th class="td-actions">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
           $files = glob($BACKUP_DIR . DIRECTORY_SEPARATOR . '*.sql');
            if ($files) {
                usort($files, function($a, $b) {
                    return filemtime($b) <=> filemtime($a); // sort newest first
                });
            }

  if (!$files || count($files) === 0) {
    echo '<tr><td colspan="4" class="text-muted">No backups found yet.</td></tr>';
  } else {
    // --- Pagination ---
    $per_page     = 10;
    $total_files  = count($files);
    $total_pages  = max(1, (int)ceil($total_files / $per_page));

    // Read requested page then clamp to [1, $total_pages]
    $page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1)             $page = 1;
    if ($page > $total_pages)  $page = $total_pages;

    $offset      = ($page - 1) * $per_page;
    $paged_files = array_slice($files, $offset, $per_page);

    foreach ($paged_files as $f) {
      $base    = basename($f);
      $size    = number_format(filesize($f)/1024, 1).' KB';
      $created = date('Y-m-d H:i', filemtime($f));
      echo '<tr>
        <td style="font-weight:600">'.htmlspecialchars($base).'</td>
        <td>'.$created.'</td>
        <td>'.$size.'</td>
        <td class="td-actions">
          <a class="btn-download" href="?dl='.rawurlencode($base).'" ' .
             'data-filename="'.htmlspecialchars($base, ENT_QUOTES).'">' .
            '<i class="fa-solid fa-download"></i> Download' .
          '</a>
        </td>
      </tr>';
    }

    // Pagination UI
    if ($total_pages > 1) {
      echo '<tr><td colspan="4" style="padding:12px 10px">';
      echo '<div style="text-align:center">';

      // Prev
      if ($page > 1) {
        echo '<a href="?page='.($page-1).'" style="margin:0 6px; padding:6px 12px; border-radius:6px; background:#f1f5f9; color:#333; text-decoration:none;">&laquo; Prev</a>';
      }

      // (optional) compact page numbers around current page
      $start = max(1, $page - 2);
      $end   = min($total_pages, $page + 2);
      for ($i = $start; $i <= $end; $i++) {
        $active = $i === $page
          ? 'background:#f7931e;color:#fff;font-weight:bold;'
          : 'background:#f1f5f9;color:#333;';
        echo '<a href="?page='.$i.'" style="margin:0 4px; padding:6px 12px; border-radius:6px; text-decoration:none; '.$active.'">'.$i.'</a>';
      }

      // Next
      if ($page < $total_pages) {
        echo '<a href="?page='.($page+1).'" style="margin:0 6px; padding:6px 12px; border-radius:6px; background:#f1f5f9; color:#333; text-decoration:none;">Next &raquo;</a>';
      }

      echo '</div>';
      echo '</td></tr>';
    }
  }
?>
    </tbody>
    </table>

<?php if (isset($_GET['created'])): ?>
  <!-- Hidden iframe to trigger the download without leaving the page -->
  <iframe id="dlFrame" style="display:none"></iframe>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // small delay so the toast can render first if needed
      setTimeout(() => {
        const file = <?= json_encode(basename($_GET['created'])) ?>;
        document.getElementById('dlFrame').src = '?dl=' + encodeURIComponent(file);
      }, 300);
    });
  </script> 
<?php endif; ?>

<!-- Restore confirmation modal -->
<div class="modal fade" id="restoreConfirmModal" tabindex="-1" aria-labelledby="restoreConfirmLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="restoreConfirmLabel">
          <i class="fa-solid fa-triangle-exclamation me-2"></i> Confirm Restore
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        This will <strong>OVERWRITE</strong> the current database. Continue?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="button" class="btn btn-danger" id="confirmRestoreBtn">
          <i class="fa-solid fa-rotate-left me-1"></i> Yes, Restore
        </button>
      </div>
    </div>
  </div>
</div>

    <!-- Toast container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100">
  <div id="appToast" class="toast border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
    <div id="appToastHeader" class="toast-header text-white">
      <i id="appToastIcon" class="fas fa-info-circle me-2"></i>
      <strong class="me-auto">System Notice</strong>
      <small id="appToastTime">just now</small>
      <button type="button" class="btn-close btn-close-white ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="appToastBody">
      Action completed.
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../notifications.js"></script>
<script>
  (function () {
    const form = document.getElementById('restoreForm');
    if (!form) return;

    const modalEl = document.getElementById('restoreConfirmModal');
    const confirmBtn = document.getElementById('confirmRestoreBtn');
    let bsModal = null;
    let allowSubmit = false;   // gate to avoid infinite loop

    form.addEventListener('submit', function (e) {
      if (allowSubmit) return; // already confirmed, let it go

      e.preventDefault();

      // Require file chosen before confirming
      const fileInput = form.querySelector('input[name="sql_file"]');
      if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        // optional: show a toast instead
        fileInput?.focus();
        return;
      }

      // Show modal
      bsModal = bsModal || new bootstrap.Modal(modalEl);
      bsModal.show();
    });

    confirmBtn.addEventListener('click', function () {
      allowSubmit = true;
      bsModal?.hide();
      // submit after modal hides (prevents focus glitch)
      setTimeout(() => form.submit(), 50);
    });
  })();
</script>

</body>
</html>
