<?php
session_start();
require 'config/db.php';

/* =========================
   Auth guard
========================= */
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}
$currentRole   = $_SESSION['role'] ?? '';
$currentBranch = $_SESSION['branch_id'] ?? null;

/* =========================
   Helpers
========================= */
function logAction($conn, $action, $details, $user_id = null, $branch_id = null) {
    if (!$user_id && isset($_SESSION['user_id']))   $user_id   = (int)$_SESSION['user_id'];
    if (!$branch_id && isset($_SESSION['branch_id'])) $branch_id = $_SESSION['branch_id']; // may be null
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, timestamp, branch_id) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("issi", $user_id, $action, $details, $branch_id);
    $stmt->execute();
    $stmt->close();
}

// Temporary password generator used by approvals flow
function generateTempPassword(mysqli $conn, int $userId): ?string {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($username);
    if (!$stmt->fetch()) { $stmt->close(); return null; }
    $stmt->close();
    $cleanUser = preg_replace('/\s+/', '', $username);
    $digits    = str_pad((string)random_int(0, 99), 2, "0", STR_PAD_LEFT);
    return "Temp:{$cleanUser}{$digits}";
}

/* =========================
   Fetch for forms
========================= */
$branches_for_create = $conn->query("SELECT * FROM branches WHERE archived = 0 ORDER BY branch_name ASC");
$branches_for_edit   = $conn->query("SELECT * FROM branches WHERE archived = 0 ORDER BY branch_name ASC");
$branches_create     = $branches_for_create ? $branches_for_create->fetch_all(MYSQLI_ASSOC) : [];
$branches_edit       = $branches_for_edit ? $branches_for_edit->fetch_all(MYSQLI_ASSOC)     : [];

/* =========================
   Actions
========================= */

// Archive user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_user_id'])) {
    $archiveId = (int) $_POST['archive_user_id'];

    // Optional: fetch some info for nicer logs/toast
    $uname = $fname = $urole = null; $uBranch = null;
    if ($stmt = $conn->prepare("SELECT username, name, role, branch_id FROM users WHERE id=? LIMIT 1")) {
        $stmt->bind_param("i", $archiveId);
        $stmt->execute();
        $stmt->bind_result($uname, $fname, $urole, $uBranch);
        $stmt->fetch();
        $stmt->close();
    }

    $stmt = $conn->prepare("UPDATE users SET archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $archiveId);
    $stmt->execute();
    $stmt->close();

    logAction($conn, "Archive Account",
        "Archived user: " . ($fname ? "{$fname} (username: {$uname}, role: {$urole})" : "user_id={$archiveId}"),
        null,
        $uBranch
    );

    $_SESSION['toast_msg']  = "Archived account: <b>" . htmlspecialchars($fname ?: $uname ?: ("#{$archiveId}"), ENT_QUOTES) . "</b>";
    $_SESSION['toast_type'] = 'danger';
    header("Location: accounts.php?archived=success");
    exit;
}

// Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $full_name = trim($_POST['name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? '';

    $branch_id = null;
    if (in_array($role, ['staff','stockman'], true)) {
        $branch_id = isset($_POST['branch_id']) && $_POST['branch_id'] !== '' ? (int)$_POST['branch_id'] : null;
    }

    if ($full_name === '' || $username === '' || $password === '' || $role === '') {
        $_SESSION['toast_msg']  = "Please fill out all required fields.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?create=invalid");
        exit;
    }
    if (in_array($role, ['staff','stockman'], true) && $branch_id === null) {
        $_SESSION['toast_msg']  = "Please select a branch for Staff/Stockman.";
        $_SESSION['toast_type'] = 'warning';
        header("Location: accounts.php?create=need_branch");
        exit;
    }

    // unique username
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        $_SESSION['toast_msg']  = "Username already exists.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?create=exists");
        exit;
    }
    $stmt->close();

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    if ($branch_id === null) {
        $stmt = $conn->prepare("INSERT INTO users (username, name, password, role, branch_id, archived) VALUES (?, ?, ?, ?, NULL, 0)");
        $stmt->bind_param("ssss", $username, $full_name, $hashedPassword, $role);
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, name, password, role, branch_id, archived) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("ssssi", $username, $full_name, $hashedPassword, $role, $branch_id);
    }
    $stmt->execute();
    $stmt->close();

    logAction($conn, "Create Account", "Created user: {$username} ({$full_name}), role: {$role}" . ($branch_id ? ", branch_id={$branch_id}" : ""), null, $branch_id);

    $_SESSION['toast_msg']  = "Account created: <b>{$full_name}</b> (<code>{$username}</code>)";
    $_SESSION['toast_type'] = 'success';
    header("Location: accounts.php?create=success");
    exit;
}

// Update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id        = (int)($_POST['edit_user_id'] ?? 0);
    $full_name = trim($_POST['name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? '';

    $branch_id = (in_array($role, ['staff','stockman'], true) && !empty($_POST['branch_id'])) ? (int)$_POST['branch_id'] : null;

    if ($id <= 0 || $full_name === '' || $username === '' || $role === '') {
        $_SESSION['toast_msg']  = "Update failed. Please complete required fields.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?updated=invalid");
        exit;
    }

    if ($password !== '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($branch_id === null) {
            $stmt = $conn->prepare("UPDATE users SET username=?, name=?, password=?, role=?, branch_id=NULL WHERE id=?");
            $stmt->bind_param("ssssi", $username, $full_name, $hashedPassword, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, name=?, password=?, role=?, branch_id=? WHERE id=?");
            $stmt->bind_param("ssssii", $username, $full_name, $hashedPassword, $role, $branch_id, $id);
        }
    } else {
        if ($branch_id === null) {
            $stmt = $conn->prepare("UPDATE users SET username=?, name=?, role=?, branch_id=NULL WHERE id=?");
            $stmt->bind_param("sssi", $username, $full_name, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, name=?, role=?, branch_id=? WHERE id=?");
            $stmt->bind_param("sssii", $username, $full_name, $role, $branch_id, $id);
        }
    }
    $stmt->execute();
    $stmt->close();

    logAction($conn, "Update Account", "Updated user: {$username} ({$full_name}), role: {$role}" . ($branch_id ? ", branch_id={$branch_id}" : ""), null, $branch_id);

    $_SESSION['toast_msg']  = "Account updated: <b>{$full_name}</b> (<code>{$username}</code>)";
    $_SESSION['toast_type'] = 'success';
    header("Location: accounts.php?updated=success");
    exit;
}

// Create branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_branch'])) {
    $branch_number        = trim($_POST['branch_number'] ?? '');
    $branch_name          = trim($_POST['branch_name'] ?? '');
    $branch_location      = trim($_POST['branch_location'] ?? '');
    $branch_email         = trim($_POST['branch_email'] ?? '');
    $branch_contact       = trim($_POST['branch_contact'] ?? '');
    $branch_contact_number= trim($_POST['branch_contact_number'] ?? '');

    if ($branch_number === '' || $branch_name === '' /* || $branch_email === '' */) {
        $_SESSION['toast_msg']  = "Please complete required branch fields.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?branch=invalid");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO branches (branch_number, branch_name, branch_location, branch_email, branch_contact, branch_contact_number, archived) VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("isssss", $branch_number, $branch_name, $branch_location, $branch_email, $branch_contact, $branch_contact_number);
    $stmt->execute();
    $newBranchId = $conn->insert_id;
    $stmt->close();

    logAction($conn, "Create Branch", "Created branch: {$branch_name}" . ($branch_email ? " ({$branch_email})" : ""), null, $newBranchId);

    $_SESSION['toast_msg']  = "Branch created: <b>" . htmlspecialchars($branch_name, ENT_QUOTES) . "</b>";
    $_SESSION['toast_type'] = 'success';
    header("Location: accounts.php?branch=created");
    exit;
}

// Update branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_branch'])) {
    $branch_id             = (int)($_POST['edit_branch_id'] ?? 0);
    $branch_name           = trim($_POST['branch_name'] ?? '');
    $branch_location       = trim($_POST['branch_location'] ?? '');
    $branch_email          = trim($_POST['branch_email'] ?? '');
    $branch_contact        = trim($_POST['branch_contact'] ?? '');
    $branch_contact_number = trim($_POST['branch_contact_number'] ?? '');

    if ($branch_id <= 0 || $branch_name === '' /* || $branch_email === '' */) {
        $_SESSION['toast_msg']  = "Branch update failed. Please complete required fields.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?branch=invalid");
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE branches
           SET branch_name = ?,
               branch_location = ?,
               branch_email = ?,
               branch_contact = ?,
               branch_contact_number = ?
         WHERE branch_id = ?
    ");
    $stmt->bind_param("sssssi", $branch_name, $branch_location, $branch_email, $branch_contact, $branch_contact_number, $branch_id);
    $stmt->execute();
    $stmt->close();

    logAction($conn, "Update Branch", "Updated branch_id={$branch_id} to '{$branch_name}'", null, $branch_id);

    $_SESSION['toast_msg']  = "Branch updated: <b>" . htmlspecialchars($branch_name, ENT_QUOTES) . "</b>";
    $_SESSION['toast_type'] = 'success';
    header("Location: accounts.php?branch=updated");
    exit;
}

// Archive branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_branch'])) {
    $branch_id = (int) ($_POST['branch_id'] ?? 0);
    if ($branch_id > 0) {
        $stmt = $conn->prepare("UPDATE branches SET archived = 1 WHERE branch_id = ?");
        $stmt->bind_param("i", $branch_id);
        $stmt->execute();
        $stmt->close();

        // Fetch name for nicer logs/toast
        $bn = null;
        if ($st2 = $conn->prepare("SELECT branch_name FROM branches WHERE branch_id=?")) {
            $st2->bind_param("i", $branch_id);
            $st2->execute();
            $st2->bind_result($bn);
            $st2->fetch();
            $st2->close();
        }

        logAction($conn, "Archive Branch", "Archived branch: " . ($bn ?: "branch_id={$branch_id}"), null, $branch_id);

        $_SESSION['toast_msg']  = "Branch archived: <b>" . htmlspecialchars($bn ?: "ID {$branch_id}", ENT_QUOTES) . "</b>";
        $_SESSION['toast_type'] = 'danger';
    }
    header("Location: accounts.php?branch=archived");
    exit;
}

/* =========================
   Password reset approvals
========================= */
if (isset($_POST['reset_action'], $_POST['reset_id']) && $currentRole === 'admin') {
    $reset_id     = (int) $_POST['reset_id'];
    $reset_action = $_POST['reset_action'];

    if ($reset_action === 'approve') {
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE id=? AND status='pending'");
        $stmt->bind_param("i", $reset_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if ($row) {
            $user_id      = (int) $row['user_id'];
            $tempPassword = generateTempPassword($conn, $user_id);
            if ($tempPassword === null) {
                $_SESSION['toast_msg']  = "Could not generate temporary password.";
                $_SESSION['toast_type'] = 'danger';
                header("Location: accounts.php");
                exit;
            }
            $hashed = password_hash($tempPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password=?, must_change_password=1 WHERE id=?");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE password_resets SET status='approved', decided_by=?, decided_at=NOW() WHERE id=?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $reset_id);
            $stmt->execute();
            $stmt->close();

            $_SESSION['toast_msg']  = "Password reset approved. Temporary password for user #{$user_id}: <b>{$tempPassword}</b>";
            $_SESSION['toast_type'] = 'success';
            logAction($conn, "Approve Password Reset", "Reset approved for user_id={$user_id}");
        } else {
            $_SESSION['toast_msg']  = "Reset request not found or already processed.";
            $_SESSION['toast_type'] = 'warning';
        }
    } elseif ($reset_action === 'reject') {
        $stmt = $conn->prepare("UPDATE password_resets SET status='rejected', decided_by=?, decided_at=NOW() WHERE id=? AND status='pending'");
        $stmt->bind_param("ii", $_SESSION['user_id'], $reset_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['toast_msg']  = "Password reset request rejected.";
        $_SESSION['toast_type'] = 'danger';
        logAction($conn, "Reject Password Reset", "Reset rejected for reset_id={$reset_id}");
    }

    header("Location: accounts.php");
    exit;
}

/* =========================
   Queries for page render
========================= */

// Users (non-archived)
$usersQuery = "
    SELECT u.id, u.username, u.name, u.role, u.branch_id, b.branch_name
    FROM users u
    LEFT JOIN branches b ON u.branch_id = b.branch_id
    WHERE u.archived = 0
    ORDER BY u.id DESC
";
$users = $conn->query($usersQuery);

// Pending numbers for badges
$pendingTransfers = $pendingStockIns = $pendingTotalInventory = 0;
if ($currentRole === 'admin') {
    if ($res = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='pending'")) {
        $pendingTransfers = (int)($res->fetch_assoc()['pending'] ?? 0);
    }
    if ($res = $conn->query("SELECT COUNT(*) AS pending FROM stock_in_requests WHERE status='pending'")) {
        $pendingStockIns = (int)($res->fetch_assoc()['pending'] ?? 0);
    }
}
$pendingTotalInventory = $pendingTransfers + $pendingStockIns;

// Pending password reset requests & count (admin only)
$resetRequests = null;
$pendingResetsCount = 0;
if ($currentRole === 'admin') {
    $resetRequests = $conn->query("
        SELECT pr.id AS reset_id, u.username, u.role, pr.requested_at
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.status = 'pending'
        ORDER BY pr.requested_at ASC
    ");
    if ($res = $conn->query("SELECT COUNT(*) AS c FROM password_resets WHERE status='pending'")) {
        $pendingResetsCount = (int)$res->fetch_assoc()['c'];
    }
}

// Pending bell (legacy)
$pending = 0;
if ($currentRole === 'admin') {
    if ($res = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE LOWER(status) = 'pending'")) {
        $pending = (int)($res->fetch_assoc()['pending'] ?? 0);
    }
}

// Fetch current user's full name
$currentName = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($fetchedName);
    if ($stmt->fetch()) {
        $currentName = $fetchedName;
    }
    $stmt->close();
}


/* =========================
   View helpers
========================= */
$self = strtolower(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
$isArchive = substr($self, 0, 7) === 'archive';
$invOpen   = in_array($self, ['inventory.php','physical_inventory.php'], true);
$toolsOpen = ($self === 'backup_admin.php' || $isArchive);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <?php $pageTitle = 'Acounts & Branches'; ?>
  <title><?= htmlspecialchars("RP Habana — $pageTitle") ?></title>
  <link rel="icon" href="img/R.P.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/notifications.css">
  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/accounts.css?v2">
  <audio id="notifSound" src="notif.mp3" preload="auto"></audio>
  <style>
    .card { background:#fff;padding:15px;border-radius:6px;margin-bottom:15px; }
    table { width:100%; border-collapse:collapse; }
    table th, table td { padding:8px; border:1px solid #ddd; text-align:left; }
    .notif-badge { background:red;color:#fff;border-radius:50%;padding:3px 7px;font-size:12px;margin-left:8px; }
  </style>
</head>
<body class="accounts-page">

<div class="sidebar">
  <h2 class="user-heading">
    <span class="role"><?= htmlspecialchars(strtoupper($currentRole), ENT_QUOTES) ?></span>
    <?php if ($currentName !== ''): ?>
      <span class="name"> (<?= htmlspecialchars($currentName, ENT_QUOTES) ?>)</span>
    <?php endif; ?>
    <span class="notif-wrapper">
      <i class="fas fa-bell" id="notifBell"></i>
      <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>><?= (int)$pending ?></span>
    </span>
  </h2>



  <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>

  <?php if ($currentRole === 'admin'): ?>
  <div class="menu-group has-sub">
    <button class="menu-toggle" type="button" aria-expanded="<?= $invOpen ? 'true' : 'false' ?>">
      <span><i class="fas fa-box"></i> Inventory
        <?php if ($pendingTotalInventory > 0): ?>
          <span class="badge-pending"><?= $pendingTotalInventory ?></span>
        <?php endif; ?>
      </span>
      <i class="fas fa-chevron-right caret"></i>
    </button>
    <div class="submenu" <?= $invOpen ? '' : 'hidden' ?>>
      <a href="inventory.php#pending-requests" class="<?= $self === 'inventory.php' ? 'active' : '' ?>">
        <i class="fas fa-list"></i> Inventory List
        <?php if ($pendingTotalInventory > 0): ?>
          <span class="badge-pending"><?= $pendingTotalInventory ?></span>
        <?php endif; ?>
      </a>
      <a href="physical_inventory.php" class="<?= $self === 'physical_inventory.php' ? 'active' : '' ?>">
        <i class="fas fa-warehouse"></i> Physical Inventory
      </a>
      <a href="barcode-print.php<?php 
        $b = (int)($_SESSION['current_branch_id'] ?? 0);
        echo $b ? ('?branch='.$b) : '';?>" class="<?= $self === 'barcode-print.php' ? 'active' : '' ?>">
        <i class="fas fa-barcode"></i> Barcode Labels
      </a>
    </div>
  </div>
    <!-- Current page -->
    <a href="services.php" class="<?= $self === 'services.php' ? 'active' : '' ?>">
      <i class="fa fa-wrench" aria-hidden="true"></i> Services
    </a>
  <a href="sales.php" class="<?= $self === 'sales.php' ? 'active' : '' ?>">
    <i class="fas fa-receipt"></i> Sales
  </a>

  <a href="accounts.php" class="<?= $self === 'accounts.php' ? 'active' : '' ?>">
    <i class="fas fa-users"></i> Accounts & Branches
    <?php if ($pendingResetsCount > 0): ?>
      <span class="badge-pending"><?= $pendingResetsCount ?></span>
    <?php endif; ?>
  </a>

  <div class="menu-group has-sub">
    <button class="menu-toggle" type="button" aria-expanded="<?= $toolsOpen ? 'true' : 'false' ?>">
      <span><i class="fas fa-screwdriver-wrench me-2"></i> Data Tools</span>
      <i class="fas fa-chevron-right caret"></i>
    </button>
    <div class="submenu" <?= $toolsOpen ? '' : 'hidden' ?>>
      <a href="/config/admin/backup_admin.php" class="<?= $self === 'backup_admin.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-database"></i> Backup & Restore
      </a>
      <a href="archive.php" class="<?= $isArchive ? 'active' : '' ?>">
        <i class="fas fa-archive"></i> Archive
      </a>
    </div>
  </div>

  <a href="logs.php" class="<?= $self === 'logs.php' ? 'active' : '' ?>">
    <i class="fas fa-file-alt"></i> Logs
  </a>
  <?php endif; ?>

  <?php if ($currentRole === 'stockman'): ?>
    <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
    <a href="physical_inventory.php" class="active"><i class="fas fa-warehouse"></i> Physical Inventory</a>
  <?php endif; ?>

  <?php if ($currentRole === 'staff'): ?>
    <a href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
    <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
  <?php endif; ?>

  <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">

  <!-- EXISTING ACCOUNTS -->
  <div class="card shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0">Existing Accounts</h2>
      <button class="btn btn-primary" onclick="openCreateUserModal()"><i class="fas fa-plus"></i> Create Account</button>
    </div>

    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Username</th>
            <th>Role</th>
            <th>Branch</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($users && $users->num_rows): ?>
            <?php while ($user = $users->fetch_assoc()): ?>
              <tr>
                <td><?= (int)$user['id'] ?></td>
                <td><?= htmlspecialchars($user['name'], ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($user['username'], ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES) ?></td>
                <td>
                  <?= in_array($user['role'], ['staff','stockman'], true)
                        ? htmlspecialchars($user['branch_name'] ?? '—', ENT_QUOTES)
                        : 'N/A' ?>
                </td>
                <td class="text-center">
                  <div class="action-buttons">
                    <!-- Edit User -->
                    <button class="acc-btn btn btn-warning btn-sm"
                        data-id="<?= (int)$user['id'] ?>"
                        data-full_name="<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>"
                        data-username="<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>"
                        data-role="<?= htmlspecialchars($user['role'], ENT_QUOTES) ?>"
                        data-branch_id="<?= htmlspecialchars((string)($user['branch_id'] ?? ''), ENT_QUOTES) ?>"
                        onclick="openEditUserModal(this)">
                      <i class="fas fa-edit"></i>
                    </button>

                    <!-- Archive User -->
                    <form method="POST" class="archive-form-user d-inline">
                      <input type="hidden" name="archive_user_id" value="<?= (int)$user['id'] ?>">
                      <button type="button"
                              class="btn-archive-unique btn-sm"
                              data-archive-type="account"
                              data-archive-name="<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>">
                        <i class="fas fa-archive"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center">No users found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- CREATE USER MODAL -->
  <div id="createUserModal" class="modal">
    <div class="modal-content p-4">
      <span class="close" onclick="closeCreateUserModal()">&times;</span>
      <h2 class="mb-3">Create Account</h2>

      <div class="step-indicator mb-3">
        <div class="step-dot step-1-dot active"></div>
        <div class="step-dot step-2-dot"></div>
      </div>

      <form method="POST" id="createUserForm">
        <div class="step step-1 active mb-3">
          <label>Name</label>
          <input type="text" name="name" placeholder="Enter name" class="form-control mb-2" required>

          <label>Username</label>
          <input type="text" name="username" placeholder="Enter username" class="form-control mb-2" required>

          <label>Password</label>
          <input type="password" name="password" placeholder="Enter password" class="form-control mb-2" required>
          <small class="text-muted">Password must be at least 6 characters</small><br>

          <button type="button" class="btn btn-primary mt-2" onclick="nextStep(2)">Next</button>
        </div>

        <div class="step step-2 mb-3">
          <label>Select Role</label>
          <select name="role" id="createRoleSelect" class="form-select mb-2" onchange="toggleCreateBranch();">
            <option value="admin">Admin</option>
            <option value="staff">Staff</option>
            <option value="stockman">Stockman</option>
          </select>

          <div id="createBranchGroup" class="mb-2" style="display:none;">
            <p><strong>Select Branch:</strong></p>
            <?php foreach ($branches_create as $branch): ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="branch_id" value="<?= $branch['branch_id'] ?>" id="branch<?= $branch['branch_id'] ?>">
                <label class="form-check-label" for="branch<?= $branch['branch_id'] ?>">
                  <?= htmlspecialchars($branch['branch_name']) ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-secondary" onclick="prevStep(1)">Back</button>
            <button type="submit" name="create_user" class="btn btn-success">Create Account</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- PENDING PASSWORD RESET REQUESTS (Admin only) -->
  <?php if ($currentRole === 'admin'): ?>
  <div class="card shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0"><i class="fas fa-key me-2 text-primary"></i>Pending Password Reset Requests</h2>
    </div>

    <?php if ($resetRequests && $resetRequests->num_rows > 0): ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th>Username</th>
              <th>Role</th>
              <th>Requested At</th>
              <th style="width:260px">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($r = $resetRequests->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($r['username'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($r['role'], ENT_QUOTES) ?></td>
              <td><?= date('Y-m-d H:i', strtotime($r['requested_at'])) ?></td>
              <td>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="reset_id" value="<?= (int)$r['reset_id'] ?>">
                  <button type="submit" name="reset_action" value="approve" class="btn btn-sm btn-success">
                    <i class="fas fa-check"></i> Approve
                  </button>
                </form>
                <form method="POST" class="d-inline ms-1">
                  <input type="hidden" name="reset_id" value="<?= (int)$r['reset_id'] ?>">
                  <button type="submit" name="reset_action" value="reject" class="btn btn-sm btn-danger">
                    <i class="fas fa-times"></i> Reject
                  </button>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="mb-0">No pending password reset requests.</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Toast container -->
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100">
    <div id="appToast" class="toast border-0 shadow-lg" role="alert" aria-live="polite" aria-atomic="true">
      <div id="appToastHeader" class="toast-header bg-primary text-white">
        <i class="fas fa-info-circle me-2"></i>
        <strong class="me-auto">System Notice</strong>
        <small class="text-white-50">just now</small>
        <button type="button" class="btn-close btn-close-white ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="appToastBody">Action completed.</div>
    </div>
  </div>

  <!-- BRANCH MANAGEMENT -->
  <div class="card shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0">Manage Branches</h2>
      <button class="btn btn-success" onclick="openCreateBranchModal()"><i class="fas fa-plus"></i> Create Branch</button>
    </div>

    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
          <tr>
            <th>Branch Name</th>
            <th>Location</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Contact Number</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $branch_query = $conn->query("SELECT * FROM branches WHERE archived = 0 ORDER BY branch_name ASC");
          if ($branch_query && $branch_query->num_rows):
            while ($branch = $branch_query->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($branch['branch_name'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($branch['branch_location'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($branch['branch_email'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($branch['branch_contact'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($branch['branch_contact_number'], ENT_QUOTES) ?></td>
              <td class="text-center">
                <div class="action-buttons">
                  <button class="acc-btn btn btn-warning btn-sm"
                      data-id="<?= (int)$branch['branch_id'] ?>"
                      data-name="<?= htmlspecialchars($branch['branch_name'], ENT_QUOTES) ?>"
                      data-location="<?= htmlspecialchars($branch['branch_location'], ENT_QUOTES) ?>"
                      data-email="<?= htmlspecialchars($branch['branch_email'], ENT_QUOTES) ?>"
                      data-contact="<?= htmlspecialchars($branch['branch_contact'], ENT_QUOTES) ?>"
                      data-contact_number="<?= htmlspecialchars($branch['branch_contact_number'], ENT_QUOTES) ?>"
                      onclick="openEditBranchModal(this)">
                    <i class="fas fa-edit"></i>
                  </button>

                  <form method="POST" class="archive-form-branch d-inline">
                    <input type="hidden" name="branch_id" value="<?= (int)$branch['branch_id'] ?>">
                    <input type="hidden" name="archive_branch" value="1">
                    <button type="button"
                            class="btn-archive-unique btn-sm"
                            data-archive-type="branch"
                            data-archive-name="<?= htmlspecialchars($branch['branch_name'], ENT_QUOTES) ?>">
                      <i class="fas fa-archive"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="6" class="text-center">No branches found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- EDIT USER MODAL -->
  <div class="modal" id="editModal" style="display:none;">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-white" style="display:flex; align-items:center; justify-content:space-between;">
        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Account</h5>
        <button type="button" class="btn-close" aria-label="Close" onclick="closeEditUserModal()"></button>
      </div>

      <div class="modal-body">
        <form method="POST" id="editUserForm">
          <input type="hidden" name="edit_user_id" id="editUserId">

          <label class="form-label mt-2">Name</label>
          <input type="text" class="form-control" name="name" id="editName" required>

          <label class="form-label mt-2">Username</label>
          <input type="text" class="form-control" name="username" id="editUsername" required>

          <label class="form-label mt-3">Password <small class="text-muted">(leave blank to keep current)</small></label>
          <input type="password" class="form-control" name="password" id="editPassword">

          <label class="form-label mt-3">Role</label>
          <select class="form-select" name="role" id="editRole" onchange="reflectEditBranchVisibility();">
            <option value="admin">Admin</option>
            <option value="staff">Staff</option>
            <option value="stockman">Stockman</option>
          </select>

          <div id="editBranchGroup" style="display:none; margin-top:12px;">
            <p class="mb-2 fw-semibold">Select Branch:</p>
            <?php foreach($branches_edit as $branch): ?>
              <label class="d-block">
                <input type="radio" name="branch_id" value="<?= $branch['branch_id'] ?>">
                <?= htmlspecialchars($branch['branch_name']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </form>
      </div>

      <div class="modal-footer" style="display:flex; gap:8px; justify-content:flex-end;">
        <button type="button" class="btn btn-outline-secondary" onclick="closeEditUserModal()">Cancel</button>
        <button type="submit" form="editUserForm" name="update_user" class="btn btn-primary">Save Changes</button>
      </div>
    </div>
  </div>

  <!-- EDIT BRANCH MODAL -->
  <div class="modal" id="editBranchModal" style="display:none;">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-white" style="display:flex; align-items:center; justify-content:space-between;">
        <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Edit Branch</h5>
        <button type="button" class="btn btn-sm btn-light" onclick="closeEditBranchModal()">✕</button>
      </div>

      <div class="modal-body">
        <form method="POST" id="editBranchForm">
          <input type="hidden" name="edit_branch_id" id="editBranchId">

          <label class="form-label mt-2">Branch Name</label>
          <input type="text" class="form-control" name="branch_name" id="editBranchName" placeholder="Branch Name" required>

          <label class="form-label mt-3">Location</label>
          <input type="text" class="form-control" name="branch_location" id="editBranchLocation" placeholder="Location">

          <label class="form-label mt-3">Email</label>
          <input type="email" class="form-control" name="branch_email" id="editBranchEmail" placeholder="Email">

          <label class="form-label mt-3">Contact Person</label>
          <input type="text" class="form-control" name="branch_contact" id="editBranchContact" placeholder="Contact Person">

          <label class="form-label mt-3">Contact Number</label>
          <input type="text" class="form-control" name="branch_contact_number" id="editBranchContactNumber" placeholder="Contact Number">
        </form>
      </div>

      <div class="modal-footer" style="display:flex; gap:8px; justify-content:flex-end;">
        <button type="button" class="btn btn-outline-secondary" onclick="closeEditBranchModal()">Cancel</button>
        <button type="submit" form="editBranchForm" name="update_branch" class="btn btn-success">Save Changes</button>
      </div>
    </div>
  </div>

  <!-- Archive Confirmation Modal -->
  <div class="modal" id="archiveConfirmModal" style="display:none;">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger text-white">Confirm Archive</div>
      <div class="modal-body">
        <p id="archiveConfirmText">Are you sure you want to archive <strong>this item?</strong></p>
      </div>
      <div class="modal-footer" style="display:flex; gap:8px; justify-content:flex-end;">
        <button type="button" class="btn btn-outline-secondary" id="archiveCancelBtn">Cancel</button>
        <button type="button" class="btn btn-danger" id="archiveConfirmBtn">Yes, Archive</button>
      </div>
    </div>
  </div>

</div> <!-- /.content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<script>
/* ===== Toast helper ===== */
(function () {
  const toastEl     = document.getElementById('appToast');
  const toastBody   = document.getElementById('appToastBody');
  const toastHeader = document.getElementById('appToastHeader');

  const TYPE_CLASS = {
    success: 'bg-success',
    danger:  'bg-danger',
    info:    'bg-info',
    warning: 'bg-warning',
    primary: 'bg-primary',
    secondary: 'bg-secondary',
    dark:    'bg-dark'
  };

  function setHeaderStyle(type) {
    toastHeader.classList.remove(...Object.values(TYPE_CLASS));
    toastHeader.classList.add(TYPE_CLASS[type] || TYPE_CLASS.info);
    const icon = toastHeader.querySelector('i');
    if (icon) {
      icon.className = 'me-2 ' + ({
        success: 'fas fa-check-circle',
        danger:  'fas fa-times-circle',
        warning: 'fas fa-exclamation-triangle',
        info:    'fas fa-info-circle',
        primary: 'fas fa-bell',
        secondary: 'fas fa-bell',
        dark:    'fas fa-bell'
      }[type] || 'fas fa-info-circle');
    }
  }

  window.showToast = function (message, type = 'info', options = {}) {
    if (!toastEl || !toastBody || !toastHeader) return;
    toastBody.innerHTML = message;
    setHeaderStyle(type);
    if (options.title) {
      const titleEl = toastHeader.querySelector('strong.me-auto');
      if (titleEl) titleEl.textContent = options.title;
    }
    const delay = Number.isFinite(options.delay) ? options.delay : 3000;
    const toast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay, autohide: true });
    toast.show();
  };
})();
</script>

<?php if (!empty($_SESSION['toast_msg'])): ?>
<script>
  window.addEventListener('DOMContentLoaded', function () {
    showToast(
      <?= json_encode($_SESSION['toast_msg']) ?>,
      <?= json_encode($_SESSION['toast_type'] ?? 'info') ?>,
      { title: 'System Notice' }
    );
  });
</script>
<?php
  unset($_SESSION['toast_msg'], $_SESSION['toast_type']);
endif; ?>

<script>
/* ===== Archive confirm modal ===== */
(() => {
  const modal      = document.getElementById('archiveConfirmModal');
  const textEl     = document.getElementById('archiveConfirmText');
  const btnCancel  = document.getElementById('archiveCancelBtn');
  const btnConfirm = document.getElementById('archiveConfirmBtn');
  let pendingForm  = null;

  function openArchiveModal(label) {
    textEl.textContent = `You’re about to archive ${label}. This hides it but keeps history/logs. Continue?`;
    modal.style.display = 'flex';
  }
  function closeArchiveModal() { modal.style.display = 'none'; }

  document.querySelectorAll('.btn-archive-unique').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      pendingForm = btn.closest('form');
      const type  = btn.dataset.archiveType || 'item';
      const name  = btn.dataset.archiveName || (type === 'account' ? 'this account' : 'this branch');
      openArchiveModal(name);
    });
  });

  btnConfirm.addEventListener('click', () => {
    if (pendingForm) pendingForm.submit();
    pendingForm = null;
    closeArchiveModal();
  });
  btnCancel.addEventListener('click', () => { pendingForm = null; closeArchiveModal(); });
  modal.addEventListener('click', (evt) => { if (evt.target === modal) closeArchiveModal(); });
})();

/* ===== Safer label injection (second approach when clicking by data-archive-name anywhere) ===== */
(function () {
  const modal = document.getElementById('archiveConfirmModal');
  const textEl = document.getElementById('archiveConfirmText');
  const cancelBtn = document.getElementById('archiveCancelBtn');
  const confirmBtn = document.getElementById('archiveConfirmBtn');
  let pendingForm = null;

  function escapeHtml(s) {
    return s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;', "'":'&#39;'}[c]));
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-archive-name]');
    if (!btn) return;
    if (!btn.classList.contains('btn-archive-unique')) return; // avoid double-bind
    e.preventDefault();
    pendingForm = btn.closest('form');
    const label = btn.getAttribute('data-archive-name') || 'this item';
    textEl.innerHTML = `Are you sure you want to archive <strong>${escapeHtml(label)}</strong>?`;
    modal.style.display = 'flex';
  });

  cancelBtn.addEventListener('click', () => { modal.style.display = 'none'; pendingForm = null; });
  confirmBtn.addEventListener('click', () => { if (pendingForm) pendingForm.submit(); });
})();

/* ===== User modal helpers (unique names; no duplicates) ===== */
function openCreateUserModal(){ document.getElementById('createUserModal').classList.add('active'); }
function closeCreateUserModal(){ document.getElementById('createUserModal').classList.remove('active'); }

function openEditUserModal(button){
  const modal = document.getElementById('editModal');
  modal.style.display = 'flex';
  document.getElementById('editUserId').value   = button.dataset.id || '';
  document.getElementById('editName').value     = button.dataset.full_name || '';
  document.getElementById('editUsername').value = button.dataset.username || '';
  document.getElementById('editRole').value     = button.dataset.role || 'admin';

  const branchId = button.dataset.branch_id || '';
  document.querySelectorAll('#editBranchGroup input[name="branch_id"]').forEach(r => {
    r.checked = (r.value === branchId);
  });

  reflectEditBranchVisibility();

  function onBg(e){ if (e.target === modal) { closeEditUserModal(); modal.removeEventListener('click', onBg); } }
  modal.addEventListener('click', onBg);
  function onEsc(ev){ if (ev.key === 'Escape') { closeEditUserModal(); document.removeEventListener('keydown', onEsc); } }
  document.addEventListener('keydown', onEsc);
}
function closeEditUserModal(){ document.getElementById('editModal').style.display = 'none'; }

function reflectEditBranchVisibility(){
  const role = document.getElementById('editRole').value;
  document.getElementById('editBranchGroup').style.display =
    (role === 'staff' || role === 'stockman') ? 'block' : 'none';
}

/* ===== Branch modals ===== */
function openCreateBranchModal(){ document.getElementById('createModal').style.display='flex'; }
function closeCreateBranchModal(){ document.getElementById('createModal').style.display='none'; }

function openEditBranchModal(button){
  const modal = document.getElementById('editBranchModal');
  modal.style.display = 'flex';
  document.getElementById('editBranchId').value            = button.dataset.id || '';
  document.getElementById('editBranchName').value          = button.dataset.name || '';
  document.getElementById('editBranchLocation').value      = button.dataset.location || '';
  document.getElementById('editBranchEmail').value         = button.dataset.email || '';
  document.getElementById('editBranchContact').value       = button.dataset.contact || '';
  document.getElementById('editBranchContactNumber').value = button.dataset.contact_number || '';
}
function closeEditBranchModal(){ document.getElementById('editBranchModal').style.display='none'; }

/* ===== Multi-step wizard ===== */
function nextStep(step){
  document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
  document.querySelector('.step-' + step)?.classList.add('active');
  document.querySelectorAll('.step-dot').forEach((dot,i)=> dot.classList.toggle('active', i+1===step));
}
function prevStep(step){ nextStep(step); }

/* ===== Create modal (legacy form) ===== */
function closeModal(){
  closeCreateBranchModal();
  closeEditUserModal();
}

/* ===== Role toggle in create modal ===== */
function toggleCreateBranch(){
  const role = document.getElementById('createRoleSelect').value;
  document.getElementById('createBranchGroup').style.display =
    (role === 'staff' || role === 'stockman') ? 'block' : 'none';
}

/* ===== Sidebar submenu state ===== */
(function(){
  const groups = document.querySelectorAll('.menu-group.has-sub');
  groups.forEach((g, idx) => {
    const btn = g.querySelector('.menu-toggle');
    const panel = g.querySelector('.submenu');
    if (!btn || !panel) return;
    const key = 'sidebar-sub-' + idx;
    if (localStorage.getItem(key) === 'open') {
      btn.setAttribute('aria-expanded','true');
      panel.hidden = false;
    }
    btn.addEventListener('click', () => {
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!expanded));
      panel.hidden = expanded;
      localStorage.setItem(key, expanded ? 'closed' : 'open');
    });
  });
})();
</script>

<!-- Create Branch Modal (legacy structure retained) -->
<div class="modal" id="createModal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">Create Branch</div>
    <form method="POST">
      <input type="text" name="branch_number" placeholder="Branch Number" required pattern="\d+" title="Branch Number must be numeric">
      <input type="text" name="branch_name" placeholder="Branch Name" required pattern="^[A-Za-z0-9\s\-']+$" title="Branch name must only contain letters, numbers, spaces, hyphens, or apostrophes">
      <input type="text" name="branch_location" placeholder="Branch Location">
      <!-- <input type="email" name="branch_email" placeholder="Branch Email"> -->
      <input type="text" name="branch_contact" placeholder="Branch Contact">
      <input type="text" name="branch_contact_number" placeholder="Branch Contact number">
      <div class="modal-footer">
        <button type="button" onclick="closeCreateBranchModal()">Cancel</button>
        <button type="submit" name="create_branch">Create Branch</button>
      </div>
    </form>
  </div>
</div>

<script src="notifications.js"></script>
</body>
</html>
