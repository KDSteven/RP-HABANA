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

// ✅ Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $full_name    = trim($_POST['name'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';
    $role         = $_POST['role'] ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');

    // Normalize phone number: remove spaces, dashes, parentheses
    $phone_number = preg_replace('/[\s\-\(\)]/', '', $phone_number);

    // Validate required fields
    if ($full_name === '' || $username === '' || $password === '' || $role === '') {
        $_SESSION['toast_msg']  = "Please fill out all required fields.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?create=invalid");
        exit;
    }

    // Validate phone number format (Philippine numbers only)
    if (!preg_match('/^(?:\+639\d{9}|09\d{9})$/', $phone_number)) {
        $_SESSION['toast_msg']  = "Invalid phone number format. Use 09123456789 or +639123456789.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?create=invalid_phone");
        exit;
    }

    // Validate branch for staff/stockman
    $branch_id = null;
    if (in_array($role, ['staff','stockman'], true)) {
        $branch_id = isset($_POST['branch_id']) && $_POST['branch_id'] !== '' ? (int)$_POST['branch_id'] : null;
        if ($branch_id === null) {
            $_SESSION['toast_msg']  = "Please select a branch for Staff/Stockman.";
            $_SESSION['toast_type'] = 'warning';
            header("Location: accounts.php?create=need_branch");
            exit;
        }
    }

    // Confirm password check
    if ($password === '' || $confirm === '' || $password !== $confirm) {
        $_SESSION['toast_msg']  = "Passwords do not match.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?create=pw_mismatch");
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check for duplicate username
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $_SESSION['toast_msg']  = "Username already exists. Choose another.";
        $_SESSION['toast_type'] = 'warning';
        $check->close();
        header("Location: accounts.php?create=duplicate");
        exit;
    }
    $check->close();

    // ✅ Insert new user (includes phone number)
    if ($branch_id === null) {
        $stmt = $conn->prepare("INSERT INTO users (name, username, password, role, branch_id, phone_number) VALUES (?, ?, ?, ?, NULL, ?)");
        $stmt->bind_param("sssss", $full_name, $username, $hashedPassword, $role, $phone_number);
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, username, password, role, branch_id, phone_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssis", $full_name, $username, $hashedPassword, $role, $branch_id, $phone_number);
    }

    $stmt->execute();
    $stmt->close();

    // ✅ Log + toast
    logAction(
        $conn,
        "Create Account",
        "Created new user: {$username} ({$full_name}), role: {$role}, phone: {$phone_number}" . ($branch_id ? ", branch_id={$branch_id}" : ""),
        null,
        $branch_id
    );

    $_SESSION['toast_msg']  = "New account created: <b>{$full_name}</b> (<code>{$username}</code>)";
    $_SESSION['toast_type'] = 'success';
    header("Location: accounts.php?create=success");
    exit;
}


// ✅ Update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id           = (int)($_POST['edit_user_id'] ?? 0);
    $full_name    = trim($_POST['name'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';
    $role         = $_POST['role'] ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');

    // Normalize phone number (remove spaces/dashes)
    $phone_number = preg_replace('/[\s\-\(\)]/', '', $phone_number);

    $branch_id = (in_array($role, ['staff','stockman'], true) && !empty($_POST['branch_id']))
        ? (int)$_POST['branch_id'] : null;

    // ✅ Validate required fields
    if ($id <= 0 || $full_name === '' || $username === '' || $role === '') {
        $_SESSION['toast_msg']  = "Update failed. Please complete required fields.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?updated=invalid");
        exit;
    }

    // ✅ Validate phone number (PH format)
    if (!preg_match('/^(?:\+639\d{9}|09\d{9})$/', $phone_number)) {
        $_SESSION['toast_msg']  = "Invalid phone number format. Use 09123456789 or +639123456789.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?updated=invalid_phone");
        exit;
    }

    // ✅ Prepare update query
    if ($password !== '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($branch_id === null) {
            $stmt = $conn->prepare("UPDATE users 
                                    SET username=?, name=?, password=?, role=?, branch_id=NULL, phone_number=? 
                                    WHERE id=?");
            $stmt->bind_param("sssssi", $username, $full_name, $hashedPassword, $role, $phone_number, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users 
                                    SET username=?, name=?, password=?, role=?, branch_id=?, phone_number=? 
                                    WHERE id=?");
            $stmt->bind_param("ssssisi", $username, $full_name, $hashedPassword, $role, $branch_id, $phone_number, $id);
        }
    } else {
        if ($branch_id === null) {
            $stmt = $conn->prepare("UPDATE users 
                                    SET username=?, name=?, role=?, branch_id=NULL, phone_number=? 
                                    WHERE id=?");
            $stmt->bind_param("ssssi", $username, $full_name, $role, $phone_number, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users 
                                    SET username=?, name=?, role=?, branch_id=?, phone_number=? 
                                    WHERE id=?");
            $stmt->bind_param("sssisi", $username, $full_name, $role, $branch_id, $phone_number, $id);
        }
    }

    $stmt->execute();
    $stmt->close();

    // ✅ Log and toast message
    logAction(
        $conn,
        "Update Account",
        "Updated user: {$username} ({$full_name}), role: {$role}, phone: {$phone_number}" . ($branch_id ? ", branch_id={$branch_id}" : ""),
        null,
        $branch_id
    );

    $_SESSION['toast_msg']  = "Account updated: <b>{$full_name}</b> (<code>{$username}</code>)";
    $_SESSION['toast_type'] = 'success';
    header("Location: accounts.php?updated=success");
    exit;
}

// Create branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_branch'])) {
    // UI-only: you can still read it for confirmation/logging, but we won't store it.
    $branch_number         = trim($_POST['branch_number'] ?? ''); 
    $branch_name           = trim($_POST['branch_name'] ?? '');
    $branch_location       = trim($_POST['branch_location'] ?? '');
    $branch_email          = trim($_POST['branch_email'] ?? '');
    $branch_contact        = trim($_POST['branch_contact'] ?? '');
    $branch_contact_number = trim($_POST['branch_contact_number'] ?? '');

    // Require the fields that actually exist in the DB
    if ($branch_name === '' || $branch_location === '' || $branch_email === '' || 
        $branch_contact === '' || $branch_contact_number === '') {
        $_SESSION['toast_msg']  = "Please complete required branch fields.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?branch=invalid");
        exit;
    }

    // Server-side validation (matches your front-end patterns)
    if (!preg_match("/^(?=.*[A-Za-z])[A-Za-z0-9\\s\\-']{2,60}$/", $branch_name)) {
        $_SESSION['toast_msg']  = "Branch name is invalid.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?branch=invalid_name");
        exit;
    }
    if (!preg_match("/^[A-Za-z0-9\\s.,\\-\'\\/()#]{1,120}$/", $branch_location)) {
        $_SESSION['toast_msg']  = "Location is invalid.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?branch=invalid_loc");
        exit;
    }
    if (!filter_var($branch_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['toast_msg']  = "Email is invalid.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?branch=invalid_email");
        exit;
    }
    if (!preg_match("/^[A-Za-z\\s'-]{2,50}$/", $branch_contact)) {
        $_SESSION['toast_msg']  = "Contact person is invalid.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?branch=invalid_contact");
        exit;
    }
    if (!preg_match("/^[0-9+\\s\\-()]{7,20}$/", $branch_contact_number)) {
        $_SESSION['toast_msg']  = "Contact number is invalid.";
        $_SESSION['toast_type'] = 'danger';
        header("Location: accounts.php?branch=invalid_phone");
        exit;
    }

    // INSERT — no branch_number column here
    $stmt = $conn->prepare("
        INSERT INTO branches (branch_name, branch_location, branch_email, branch_contact, branch_contact_number, archived)
        VALUES (?, ?, ?, ?, ?, 0)
    ");
    $stmt->bind_param("sssss", $branch_name, $branch_location, $branch_email, $branch_contact, $branch_contact_number);
    $stmt->execute();
    $newBranchId = $conn->insert_id;
    $stmt->close();

    // You can mention the UI number in logs/toast if provided
    $niceName = $branch_name . ($branch_number !== '' ? " (#{$branch_number})" : "");
    logAction($conn, "Create Branch", "Created branch: {$niceName}" . ($branch_email ? " ({$branch_email})" : ""), null, $newBranchId);

    $_SESSION['toast_msg']  = "Branch created: <b>" . htmlspecialchars($niceName, ENT_QUOTES) . "</b>";
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
   Queries for page render
========================= */

// Users (non-archived)
$usersQuery = "
    SELECT 
        u.id, 
        u.username, 
        u.name, 
        u.phone_number,
        u.role, 
        u.branch_id, 
        b.branch_name
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
    #pwChecklist .pw-item { opacity: 0.7; }
    #pwChecklist .ok { opacity: 1; font-weight: 600; }
  .input-group-text {
      cursor: pointer;
    }
    .input-group-text a {
      text-decoration: none;
      color: inherit;
    }
    .input-group-text a:hover {
      color: #000; /* darken on hover */
    }

  </style>
</head>
<body class="accounts-page">

<!-- Sidebar -->
<div class="sidebar" id="mainSidebar">
  <!-- Toggle button always visible on the rail -->
  <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="false">
    <i class="fas fa-bars" aria-hidden="true"></i>
  </button>

  <!-- Wrap existing sidebar content so we can hide/show it cleanly -->
  <div class="sidebar-content">
    <h2 class="user-heading">
      <span class="role"><?= htmlspecialchars(strtoupper($currentRole), ENT_QUOTES) ?></span>
      <?php if ($currentName !== ''): ?>
        <span class="name">(<?= htmlspecialchars($currentName, ENT_QUOTES) ?>)</span>
      <?php endif; ?>
      <span class="notif-wrapper">
        <i class="fas fa-bell" id="notifBell"></i>
        <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>><?= (int)$pending ?></span>
      </span>
    </h2>

        <!-- Common -->
    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>

    <?php
// put this once before the sidebar (top of file is fine)
$self = strtolower(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
$isArchive = substr($self, 0, 7) === 'archive'; // matches archive.php, archive_view.php, etc.
$invOpen   = in_array($self, ['inventory.php','physical_inventory.php'], true);
$toolsOpen = ($self === 'backup_admin.php' || $isArchive);
?>

<!-- Admin Links -->
<?php if ($currentRole === 'admin'): ?>

  <!-- Inventory group (unchanged) -->
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
    <a href="inventory.php#pending-requests" class="<?= $self === 'inventory.php#pending-requests' ? 'active' : '' ?>">
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

    <a href="services.php" class="<?= $self === 'services.php' ? 'active' : '' ?>">
      <i class="fa fa-wrench" aria-hidden="true"></i> Services
    </a>

  <!-- Sales (normal link with active state) -->
  <a href="sales.php" class="<?= $self === 'sales.php' ? 'active' : '' ?>">
    <i class="fas fa-receipt"></i> Sales
  </a>


<a href="accounts.php" class="<?= $self === 'accounts.php' ? 'active' : '' ?>">
  <i class="fas fa-users"></i> Accounts & Branches
</a>

  <!-- NEW: Backup & Restore group with Archive inside -->
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



   <!-- Stockman Links -->
  <?php if ($currentRole === 'stockman'): ?>
    <div class="menu-group has-sub">
      <button class="menu-toggle" type="button" aria-expanded="<?= $invOpen ? 'true' : 'false' ?>">
        <span><i class="fas fa-box"></i> Inventory</span>
        <i class="fas fa-chevron-right caret"></i>
      </button>
      <div class="submenu" <?= $invOpen ? '' : 'hidden' ?>>
        <a href="inventory.php" class="<?= $self === 'inventory.php' ? 'active' : '' ?>">
          <i class="fas fa-list"></i> Inventory List
        </a>
        <a href="physical_inventory.php" class="<?= $self === 'physical_inventory.php' ? 'active' : '' ?>">
          <i class="fas fa-warehouse"></i> Physical Inventory
        </a>
        <!-- Stockman can access Barcode Labels; server forces their branch -->
        <a href="barcode-print.php" class="<?= $self === 'barcode-print.php' ? 'active' : '' ?>">
          <i class="fas fa-barcode"></i> Barcode Labels
        </a>
      </div>
    </div>
  <?php endif; ?>
    <!-- Staff Links -->
    <?php if ($currentRole === 'staff'): ?>
        <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
        <a href="history.php"><i class="fas fa-history"></i> Sales History</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
  </div>
</div>

<div class="content">

 <!-- EXISTING ACCOUNTS -->
<div class="card shadow-sm mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Existing Accounts</h2>
    <button class="btn btn-primary" onclick="openCreateUserModal()">
      <i class="fas fa-plus"></i> Create Account
    </button>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Username</th>
          <th>Phone Number</th> <!-- NEW COLUMN -->
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

              <!-- NEW PHONE NUMBER COLUMN -->
              <td><?= htmlspecialchars($user['phone_number'] ?? '—', ENT_QUOTES) ?></td>

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
                      data-phone-number="<?= htmlspecialchars($user['phone_number'] ?? '', ENT_QUOTES) ?>"
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
          <tr><td colspan="7" class="text-center">No users found.</td></tr>
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

    <form method="POST" id="createUserForm" novalidate>
      <div class="step step-1 active mb-3">
        <!-- NAME (unchanged rules) -->
          <label>Name</label>
          <input type="text" name="name" placeholder="Enter name" class="form-control mb-2"
                pattern="^[A-Za-z\s'-]{2,50}$"
                title="Name should be 2–50 characters and may contain letters, spaces, hyphens, or apostrophes"
                required>

          <!-- USERNAME + availability helper -->
          <label>Username</label>
          <input type="text" id="username" name="username" placeholder="Enter username" class="form-control mb-1"
                pattern="^[A-Za-z0-9._]{4,20}$"
                title="Username should be 4–20 characters and may contain letters, numbers, dots, or underscores"
                required>
          <div><small id="usernameHelp" class="form-text"></small></div>

          <!-- Phone Number -->
            <div class="mb-3">
              <label class="form-label">Phone Number</label>
              <input 
                type="text" 
                name="phone_number" 
                class="form-control" 
                placeholder="+63 912 345 6789 or 09123456789"
                pattern="^(?:\+639\d{9}|09\d{9})$"
                title="Please enter a valid Philippine mobile number (e.g., 09123456789 or +639123456789)" 
                required>
            </div>

          <!-- PASSWORD -->
          <label class="mt-2">Password</label>
          <div class="input-group mb-2">
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
            <button type="button" class="input-group-text bg-white btn-toggle-pw" data-target="#password" aria-label="Show/Hide password">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>

          <!-- CONFIRM PASSWORD -->
          <label class="mt-2">Confirm Password</label>
          <div class="input-group mb-1">
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
            <button type="button" class="input-group-text bg-white btn-toggle-pw" data-target="#confirm_password" aria-label="Show/Hide confirm password">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>



<small id="confirmHelp" class="form-text text-danger d-none">Passwords do not match.</small>

        <!-- Live feedback checklist -->
        <div id="pwChecklist" class="small mb-2">
          <div class="pw-item" data-test="lower">• Lowercase letter</div>
          <div class="pw-item" data-test="upper">• Uppercase letter</div>
          <div class="pw-item" data-test="number">• Number</div>
          <div class="pw-item" data-test="special">• Special (@$!%*?&)</div>
          <div class="pw-item" data-test="len">• At least 8 characters</div>
        </div>

        <!-- Strength bar -->
        <div class="mb-1">
          <div class="progress" style="height: 8px;">
            <div id="pwStrengthBar" class="progress-bar" role="progressbar" style="width:0%"></div>
          </div>
          <small id="pwStrengthText" class="text-muted">Strength: —</small>
        </div>

        <small class="text-muted d-block">Password must be at least 8 characters and include uppercase, lowercase, number, and special character</small>

        <!-- Next button: now disabled until step-1 is valid -->
        <button type="button" id="nextStepBtn" class="btn btn-primary mt-3" onclick="nextFromStep1()" disabled>Next</button>
      </div>

      <div class="step step-2 mb-3">
        <!-- ROLE -->
        <label>Select Role</label>
        <select name="role" id="createRoleSelect" class="form-select mb-2" onchange="toggleCreateBranch();">
          <option value="admin">Admin</option>
          <option value="staff">Staff</option>
          <option value="stockman">Stockman</option>
        </select>

        <!-- BRANCH -->
        <div id="createBranchGroup" class="mb-2" style="display:none;">
          <p><strong>Select Branch:</strong></p>
          <?php foreach ($branches_create as $branch): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="branch_id" value="<?= $branch['branch_id'] ?>" id="branch<?= $branch['branch_id'] ?>" required>
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
      <button class="btn btn-success"
        data-bs-toggle="modal"
        data-bs-target="#createBranchModal">
        <i class="fas fa-plus"></i> Create Branch
      </button>
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
      <form method="POST" id="editUserForm" novalidate>
        <input type="hidden" name="edit_user_id" id="editUserId">

        <label class="form-label mt-2">Name</label>
        <input
          type="text"
          class="form-control"
          name="name"
          id="editName"
          required
          pattern="^[A-Za-z\s'-]{2,50}$"
          title="2–50 characters; letters, spaces, hyphens, apostrophes only.">

        <label class="form-label mt-2">Username</label>
        <input
          type="text"
          class="form-control"
          name="username"
          id="editUsername"
          required
          pattern="^(?=.*[A-Za-z])[A-Za-z0-9._-]{4,20}$"
          title="4–20 chars, at least one letter. Allowed: letters, numbers, dot, underscore, hyphen.">
        <small id="editUsernameHelp" class="form-text"></small>

            <div class="mb-3">
              <label class="form-label">Phone Number</label>
              <input 
                type="text" 
                name="phone_number" 
                id="editPhone_number"
                class="form-control" 
                placeholder="+63 912 345 6789 or 09123456789"
                pattern="^(?:\+639\d{9}|09\d{9})$"
                title="Please enter a valid Philippine mobile number (e.g., 09123456789 or +639123456789)" 
                required>
            </div>

        <label class="form-label mt-3">New Password
          <small class="text-muted">(leave blank to keep current)</small>
        </label>
        <div class="input-group">
          <input type="password" class="form-control" name="password" id="editPassword" placeholder="New password (optional)">
          <span class="input-group-text bg-white" id="editTogglePwBtn" role="button"><i class="fa-solid fa-eye"></i></span>
        </div>
        <div class="input-group mt-2">
          <input type="password" class="form-control" id="editPassword2" placeholder="Confirm new password">
          <span class="input-group-text bg-white" id="editTogglePw2Btn" role="button"><i class="fa-solid fa-eye"></i></span>
        </div>
        <small id="editPwHelp" class="form-text text-muted">If you change the password, it must include upper/lowercase, a number, a special (@$!%*?&), and be at least 8 characters.</small><br>
        <small id="editConfirmHelp" class="form-text text-danger d-none">Passwords do not match.</small><br>

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
              <input class="edit-branch-radio" type="radio" name="branch_id" value="<?= $branch['branch_id'] ?>">
              <?= htmlspecialchars($branch['branch_name']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </form>
    </div>
    <div class="modal-footer" style="display:flex; gap:8px; justify-content:flex-end;">
      <button type="button" class="btn btn-outline-secondary" onclick="closeEditUserModal()">Cancel</button>
      <button type="submit" id="editSaveBtn" form="editUserForm" name="update_user" class="btn btn-primary" disabled>Save Changes</button>
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
      <form method="POST" id="editBranchForm" novalidate>
        <input type="hidden" name="edit_branch_id" id="editBranchId">

        <!-- Branch Name (required; must include a letter; allow letters/numbers/space/-/') -->
        <label class="form-label mt-2" for="editBranchName">Branch Name</label>
        <input
          type="text"
          class="form-control"
          name="branch_name"
          id="editBranchName"
          placeholder="Branch Name"
          required
          maxlength="60"
          pattern="^(?=.*[A-Za-z])[A-Za-z0-9\s\-']{2,60}$"
          title="2–60 chars. Must include at least one letter. Allowed: letters, numbers, spaces, hyphens, apostrophes.">

        <!-- Location (required; allow letters/numbers/spaces . , - ' / ( ) #) -->
        <label class="form-label mt-3" for="editBranchLocation">Location</label>
        <input
          type="text"
          class="form-control"
          name="branch_location"
          id="editBranchLocation"
          placeholder="Street / City"
          required
          maxlength="120"
          pattern="^[A-Za-z0-9\s.,\-'/()#]{1,120}$"
          title="1–120 chars. Letters, numbers, spaces, . , - ' / ( ) # allowed.">
          
          <!-- Email -->
          <input
            type="email"
            class="form-control"
            name="branch_email"
            id="editBranchEmail"
            placeholder="Email"
            required
            maxlength="120"
            pattern="^(?=.*[A-Za-z])[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$"
          >

        <!-- Contact Person (required; letters/spaces/-/') -->
        <label class="form-label mt-3" for="editBranchContact">Contact Person</label>
        <input
          type="text"
          class="form-control"
          name="branch_contact"
          id="editBranchContact"
          placeholder="Contact Person"
          required
          maxlength="50"
          pattern="^[A-Za-z\s'-]{2,50}$"
          title="Letters, spaces, hyphens, apostrophes; 2–50 characters.">

        <!-- Contact Number (required; digits + + - space ( ) ) -->
        <label class="form-label mt-3" for="editBranchContactNumber">Contact Number</label>
        <input
          type="tel"
          class="form-control"
          name="branch_contact_number"
          id="editBranchContactNumber"
          placeholder="Contact Number"
          required
          minlength="7"
          maxlength="20"
          pattern="^(?:\+?63|0)9\d{2}[\s-]?\d{3}[\s-]?\d{4}$"
          title="Philippine mobile number. Examples: 0917 123 4567, 09171234567, +63 917 123 4567, +639171234567">
      </form>
    </div>

    <div class="modal-footer" style="display:flex; gap:8px; justify-content:flex-end;">
      <button type="button" class="btn btn-outline-secondary" onclick="closeEditBranchModal()">Cancel</button>
      <button type="submit" id="editBranchSaveBtn" form="editBranchForm" name="update_branch" class="btn btn-success" disabled>
        Save Changes
      </button>
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
/* =========================
   TOAST HELPER
========================= */
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
    if (!toastHeader) return;
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
<?php unset($_SESSION['toast_msg'], $_SESSION['toast_type']); endif; ?>

<script>
/* =========================
   ARCHIVE CONFIRM (single, safe)
========================= */
(function () {
  const modal      = document.getElementById('archiveConfirmModal');
  const textEl     = document.getElementById('archiveConfirmText');
  const cancelBtn  = document.getElementById('archiveCancelBtn');
  const confirmBtn = document.getElementById('archiveConfirmBtn');

  let pendingForm = null;

  function escapeHtml(s) {
    return s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;', "'":'&#39;'}[c]));
  }

  // Open via click on any .btn-archive-unique
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-archive-unique');
    if (!btn) return;

    e.preventDefault();
    pendingForm = btn.closest('form');

    const label = btn.getAttribute('data-archive-name') || 'this item';
    if (textEl) textEl.innerHTML = `Are you sure you want to archive <strong>${escapeHtml(label)}</strong>?`;

    if (modal) modal.style.display = 'flex';
  });

  // Confirm / Cancel
  confirmBtn && confirmBtn.addEventListener('click', () => {
    if (pendingForm) pendingForm.submit();
    pendingForm = null;
    if (modal) modal.style.display = 'none';
  });

  cancelBtn && cancelBtn.addEventListener('click', () => {
    pendingForm = null;
    if (modal) modal.style.display = 'none';
  });

  // Click outside closes
  modal && modal.addEventListener('click', (evt) => {
    if (evt.target === modal) {
      pendingForm = null;
      modal.style.display = 'none';
    }
  });
})();
</script>

<script>
/* =========================
   MODAL HELPERS
========================= */
function openCreateUserModal(){ document.getElementById('createUserModal')?.classList.add('active'); }
function closeCreateUserModal(){ document.getElementById('createUserModal')?.classList.remove('active'); }

// function openEditUserModal(button){
//   console.log('phone from data:', button.getAttribute('data-phoneNumber'));
//   const modal = document.getElementById('editModal');
//   if (!modal) return;
//   modal.style.display = 'flex';

//   document.getElementById('editUserId').value   = button.dataset.id || '';
//   document.getElementById('editName').value     = button.dataset.full_name || '';
//   document.getElementById('editUsername').value = button.dataset.username || '';
//   document.getElementById('editPhone_number').value  = button.getAttribute('data-phoneNumber') || '';
//   document.getElementById('editRole').value     = button.dataset.role || 'admin';

//   const branchId = button.dataset.branch_id || '';
//   document.querySelectorAll('#editBranchGroup input[name="branch_id"]').forEach(r => {
//     r.checked = (r.value === branchId);
//   });

//   reflectEditBranchVisibility();

//   function onBg(e){ if (e.target === modal) { closeEditUserModal(); modal.removeEventListener('click', onBg); } }
//   modal.addEventListener('click', onBg);

//   function onEsc(ev){ if (ev.key === 'Escape') { closeEditUserModal(); document.removeEventListener('keydown', onEsc); } }
//   document.addEventListener('keydown', onEsc);
// }
function closeEditUserModal(){ const m = document.getElementById('editModal'); if (m) m.style.display = 'none'; }

function reflectEditBranchVisibility(){
  const role = document.getElementById('editRole')?.value;
  const grp  = document.getElementById('editBranchGroup');
  if (grp) grp.style.display = (role === 'staff' || role === 'stockman') ? 'block' : 'none';
}

/* Branch modals */
function openCreateBranchModal(){ const m = document.getElementById('createModal'); if (m) m.style.display = 'flex'; }
function closeCreateBranchModal(){ const m = document.getElementById('createModal'); if (m) m.style.display = 'none'; }

function openEditBranchModal(button){
  const modal = document.getElementById('editBranchModal');
  if (!modal) return;
  modal.style.display = 'flex';
  document.getElementById('editBranchId').value            = button.dataset.id || '';
  document.getElementById('editBranchName').value          = button.dataset.name || '';
  document.getElementById('editBranchLocation').value      = button.dataset.location || '';
  document.getElementById('editBranchEmail').value         = button.dataset.email || '';
  document.getElementById('editBranchContact').value       = button.dataset.contact || '';
  document.getElementById('editBranchContactNumber').value = button.dataset.contact_number || '';
}
function closeEditBranchModal(){ const m = document.getElementById('editBranchModal'); if (m) m.style.display = 'none'; }

/* Multi-step wizard + close all */
function nextStep(step){
  document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
  document.querySelector('.step-' + step)?.classList.add('active');
  document.querySelectorAll('.step-dot').forEach((dot,i)=> dot.classList.toggle('active', i+1===step));
}
function prevStep(step){ nextStep(step); }
function closeModal(){ closeCreateBranchModal(); closeEditUserModal(); }

/* Role toggle in create modal */
function toggleCreateBranch(){
  const role = document.getElementById('createRoleSelect')?.value;
  const grp  = document.getElementById('createBranchGroup');
  if (grp) grp.style.display = (role === 'staff' || role === 'stockman') ? 'block' : 'none';
}

/* Sidebar submenu persist */
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
<script>
(() => {
  const form   = document.getElementById('editUserForm');
  if (!form) return;

  const idEl   = document.getElementById('editUserId');
  const nameEl = document.getElementById('editName');
  const userEl = document.getElementById('editUsername');
  const roleEl = document.getElementById('editRole');
  const pwEl   = document.getElementById('editPassword');
  const pw2El  = document.getElementById('editPassword2');
  const saveBtn= document.getElementById('editSaveBtn');

  const unHelp = document.getElementById('editUsernameHelp');
  const matchHelp = document.getElementById('editConfirmHelp');

  let originalUsername = '';

  const RE = {
    name: /^[A-Za-z\s'-]{2,50}$/,
    user: /^(?=.*[A-Za-z])[A-Za-z0-9._-]{4,20}$/,
  };

  function pwStrongOK() {
    const v = pwEl.value || '';
    if (!v) return true; // empty = keep existing password
    return (/[a-z]/.test(v) && /[A-Z]/.test(v) && /\d/.test(v) && /[@$!%*?&]/.test(v) && v.length >= 8);
  }
  function pwMatchOK() {
    if (!pwEl.value && !pw2El.value) { matchHelp.classList.add('d-none'); return true; }
    const ok = pwEl.value === pw2El.value;
    matchHelp.classList.toggle('d-none', ok);
    return ok;
  }
  function nameOK() { return RE.name.test((nameEl.value || '').trim()); }
  function userSyntaxOK() { return RE.user.test((userEl.value || '').trim()); }

  function branchOK() {
    const role = roleEl.value;
    if (role !== 'staff' && role !== 'stockman') return true;
    return !!form.querySelector('#editBranchGroup input[name="branch_id"]:checked');
  }

  // username availability (supports exclude_id)
  let usernameAvailable = true;
  let debounceT;
  function debounce(fn, ms=350){ clearTimeout(debounceT); debounceT = setTimeout(fn, ms); }

  async function checkUsername() {
    const u = (userEl.value || '').trim();
    if (!userSyntaxOK()) {
      usernameAvailable = false;
      setUnHelp('warn', '4–20 chars, at least one letter. Allowed: letters, numbers, dot, underscore, hyphen.');
      updateSaveEnabled();
      return;
    }
    if (u === originalUsername) {
      usernameAvailable = true;
      setUnHelp('ok', 'Username unchanged.');
      updateSaveEnabled();
      return;
    }
    setUnHelp('checking', 'Checking availability…');
    try {
      const res = await fetch('check_username.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ username: u, exclude_id: Number(idEl.value || 0) })
      });
      const data = await res.json();
      if (data && data.ok !== false && data.available === true) {
        usernameAvailable = true;
        setUnHelp('ok', 'Username is available.');
      } else {
        usernameAvailable = false;
        setUnHelp('warn', 'Username is already taken.');
      }
    } catch {
      // If checker fails, allow save only when username unchanged
      usernameAvailable = (u === originalUsername);
      setUnHelp(usernameAvailable ? 'ok' : 'error', usernameAvailable ? 'Username unchanged.' : 'Could not verify username right now.');
    }
    updateSaveEnabled();
  }

  function setUnHelp(state, msg) {
    unHelp.className = 'form-text';
    if (state === 'checking') unHelp.classList.add('text-muted');
    if (state === 'ok')       unHelp.classList.add('text-success');
    if (state === 'warn' || state === 'error') unHelp.classList.add('text-danger');
    unHelp.textContent = msg || '';
  }

  function updateSaveEnabled() {
    const ok =
      nameOK() &&
      userSyntaxOK() &&
      usernameAvailable &&
      pwStrongOK() &&
      pwMatchOK() &&
      branchOK();

    saveBtn.disabled = !ok;
  }

  // eye toggles
  function bindToggle(btnId, inputEl) {
    const btn = document.getElementById(btnId);
    if (!btn || !inputEl) return;
    const icon = btn.querySelector('i');
    btn.addEventListener('click', () => {
      const show = inputEl.type === 'password';
      inputEl.type = show ? 'text' : 'password';
      if (icon) {
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
      }
      inputEl.focus({preventScroll:true});
    });
  }
  bindToggle('editTogglePwBtn',  pwEl);
  bindToggle('editTogglePw2Btn', pw2El);

  // reflect branch visibility + "required" dynamically
  window.reflectEditBranchVisibility = function () {
    const show = roleEl.value === 'staff' || roleEl.value === 'stockman';
    const group = document.getElementById('editBranchGroup');
    group.style.display = show ? 'block' : 'none';
    form.querySelectorAll('#editBranchGroup input[name="branch_id"]').forEach(r => {
      r.required = show;
    });
    updateSaveEnabled();
  };

  // OPEN modal (replace your existing function with this)
  window.openEditUserModal = function (button) {
    const modal = document.getElementById('editModal');
    modal.style.display = 'flex';

  // Populate fields
  document.getElementById('editUserId').value   = button.dataset.id || '';
  document.getElementById('editName').value     = button.dataset.full_name || '';
  document.getElementById('editUsername').value = button.dataset.username || '';
  document.getElementById('editPhone_number').value = button.dataset.phoneNumber || '';
  document.getElementById('editRole').value     = button.dataset.role || 'admin';

    // set branch radios
    const branchId = button.dataset.branch_id || '';
    form.querySelectorAll('#editBranchGroup input[name="branch_id"]').forEach(r => {
      r.checked = (r.value === branchId);
    });

    // reset password fields
    pwEl.value = '';
    pw2El.value = '';
    matchHelp.classList.add('d-none');
    setUnHelp('idle', '');

    reflectEditBranchVisibility();
    updateSaveEnabled();

    // close on backdrop / ESC
    function onBg(e){ if (e.target === modal) { closeEditUserModal(); modal.removeEventListener('click', onBg); } }
    modal.addEventListener('click', onBg);
    function onEsc(ev){ if (ev.key === 'Escape') { closeEditUserModal(); document.removeEventListener('keydown', onEsc); } }
    document.addEventListener('keydown', onEsc);
  };

  // keep form reactive
  [nameEl, roleEl, pwEl, pw2El].forEach(el => {
    el.addEventListener('input', updateSaveEnabled);
    el.addEventListener('change', updateSaveEnabled);
  });
  userEl.addEventListener('input', () => debounce(checkUsername, 350));
})();
</script>

<script>
/* =========================
   USERNAME AVAILABILITY + STEP-1 GUARD
   (prevents "Next" until all fields OK)
========================= */
(function() {
  const $name        = document.querySelector('input[name="name"]');
  const $u           = document.getElementById('username');
  const $help        = document.getElementById('usernameHelp');
  const $next        = document.getElementById('nextStepBtn');
  const $pw          = document.getElementById('password');
  const $cpw         = document.getElementById('confirm_password');
  const $confirmHelp = document.getElementById('confirmHelp');

  let usernameAvailable = false;
  let t; const debounce = (fn, ms=350)=>{ clearTimeout(t); t=setTimeout(fn, ms); };

  function mark(state, msg) {
    if (!$help) return;
    $help.className = 'form-text';
    if (state === 'checking') $help.classList.add('text-muted');
    if (state === 'ok')       $help.classList.add('text-success');
    if (state === 'warn' || state === 'error') $help.classList.add('text-danger');
    $help.textContent = msg || '';
  }

  function localNameOK() {
    return /^[A-Za-z\s'-]{2,50}$/.test(($name?.value || '').trim());
  }
  function localUserSyntaxOK() {
    return /^(?=.*[A-Za-z])[A-Za-z0-9._-]{4,20}$/.test($u?.value || '');
  }
  function pwChecksOK() {
    const v = $pw?.value || '';
    return (/[a-z]/.test(v) && /[A-Z]/.test(v) && /\d/.test(v) && /[@$!%*?&]/.test(v) && v.length >= 8);
  }
  function pwMatchOK() {
    const ok = ($pw?.value || '') === ($cpw?.value || '');
    if ($confirmHelp) $confirmHelp.classList.toggle('d-none', ok || !($cpw?.value));
    return ok && ($cpw?.value || '').length > 0;
  }

  function updateNext() {
    const ok = localNameOK() && localUserSyntaxOK() && usernameAvailable && pwChecksOK() && pwMatchOK();
    if ($next) $next.disabled = !ok;
  }

  async function checkUsername(v) {
    if (!$u) return;
    if (!v) { mark('idle',''); usernameAvailable = false; updateNext(); return; }
    if (!localUserSyntaxOK()) {
      mark('warn','Username must be 4–20 chars, include at least one letter. Allowed: letters, numbers, dot, underscore, hyphen.');
      usernameAvailable = false; updateNext(); return;
    }
    mark('checking','Checking availability…');
    try {
      const res = await fetch('check_username.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({username: v})
      });
      const data = await res.json();
      if (!data.ok) throw 0;
      usernameAvailable = !!data.available;
      mark(usernameAvailable ? 'ok' : 'warn', usernameAvailable ? 'Username is available.' : 'Username is already taken.');
    } catch {
      usernameAvailable = false;
      mark('error','Could not check username right now.');
    }
    updateNext();
  }

  // Bind inputs
  $u     && $u.addEventListener('input', () => debounce(() => checkUsername($u.value), 350));
  $name  && $name.addEventListener('input', updateNext);
  $pw    && $pw.addEventListener('input', updateNext);
  $cpw   && $cpw.addEventListener('input', updateNext);

  // Step-1 guard (used by "Next" button)
  window.nextFromStep1 = function () {
    updateNext();
    if ($next && !$next.disabled) nextStep(2);
  };

  // Init (autofill)
  if ($u && $u.value) checkUsername($u.value); else updateNext();
})();
</script>

<script>
/* =========================
   SHOW/HIDE PASSWORD (one listener for both fields)
   Supports:
   - <button class="btn-toggle-pw" data-target="#password">…</button>
   - legacy IDs: #togglePwBtn and #togglePwBtn2
========================= */
document.addEventListener('click', function (e) {
  const btn = e.target.closest('.btn-toggle-pw, #togglePwBtn, #togglePwBtn2');
  if (!btn) return;

  // Prefer data-target if present; else find input in the same .input-group
  let input = null;
  const sel = btn.getAttribute('data-target');
  if (sel) {
    input = document.querySelector(sel);
  } else {
    const group = btn.closest('.input-group');
    input = group ? group.querySelector('input.form-control') : null;
  }
  if (!input) return;

  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';

  const icon = btn.querySelector('i');
  if (icon) {
    icon.classList.toggle('fa-eye', !show);
    icon.classList.toggle('fa-eye-slash', show);
  }
  input.focus({ preventScroll: true });
});
</script>

<script>
/* =========================
   PASSWORD STRENGTH METER
========================= */
(function () {
  const pwd = document.getElementById('password');
  const checklist = document.getElementById('pwChecklist');
  if (!pwd || !checklist) return;

  const items = {
    lower:   checklist.querySelector('[data-test="lower"]'),
    upper:   checklist.querySelector('[data-test="upper"]'),
    number:  checklist.querySelector('[data-test="number"]'),
    special: checklist.querySelector('[data-test="special"]'),
    len:     checklist.querySelector('[data-test="len"]')
  };
  const bar   = document.getElementById('pwStrengthBar');
  const label = document.getElementById('pwStrengthText');

  function evaluate(value) {
    const tests = {
      lower: /[a-z]/.test(value),
      upper: /[A-Z]/.test(value),
      number: /\d/.test(value),
      special: /[@$!%*?&]/.test(value),
      len: value.length >= 8
    };

    // Checklist UI
    Object.entries(tests).forEach(([k, ok]) => {
      if (!items[k]) return;
      items[k].classList.toggle('ok', ok);
      items[k].textContent = (ok ? '✓ ' : '• ') + items[k].textContent.replace(/^✓ |^• /, '');
    });

    // Score 0–5
    const score  = Object.values(tests).filter(Boolean).length;
    const widths = [0, 20, 40, 60, 80, 100];
    const texts  = ['—', 'Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];

    if (bar) {
      bar.style.width = widths[score] + '%';
      bar.classList.remove('bg-danger','bg-warning','bg-info','bg-success');
      if (score <= 2) bar.classList.add('bg-danger');
      else if (score === 3) bar.classList.add('bg-warning');
      else if (score === 4) bar.classList.add('bg-info');
      else if (score === 5) bar.classList.add('bg-success');
    }
    if (label) label.textContent = 'Strength: ' + texts[score];
  }

  pwd.addEventListener('input', (e) => evaluate(e.target.value));
  evaluate(pwd.value || ''); // init
})();
</script>

<!-- ======================= CREATE BRANCH MODAL ======================= -->
<div class="modal fade" id="createBranchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content fp-card">
      <div class="modal-header fp-header">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-warehouse"></i>
          <h5 class="modal-title mb-0">Create Branch</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="createBranchForm" method="POST" action="accounts.php" autocomplete="off" novalidate>
        <input type="hidden" name="create_branch" value="1">

        <!-- Branch Number (required) -->
        <div class="mb-3 px-3">
          <label class="form-label fw-semibold" for="cb_number">Branch Number</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
            <input
              type="text"
              class="form-control"
              id="cb_number"
              name="branch_number"
              inputmode="numeric"
              placeholder="e.g. 101"
              required
              pattern="^\d{1,6}$"
              title="Numeric only, 1–6 digits">
          </div>
          <div class="form-text">Numbers only, up to 6 digits.</div>
        </div>

        <!-- Branch Name (required) -->
        <div class="mb-3 px-3">
          <label class="form-label fw-semibold" for="cb_name">Branch Name</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-tag"></i></span>
            <input
              type="text"
              class="form-control"
              id="cb_name"
              name="branch_name"
              placeholder="e.g. Habana Main"
              required
              maxlength="60"
              pattern="^(?=.*[A-Za-z])[A-Za-z0-9\s\-']{2,60}$"
              title="2–60 chars. Must include at least one letter. Allowed: letters, numbers, spaces, hyphens, apostrophes.">
          </div>
        </div>

        <!-- Location (required) -->
        <div class="mb-3 px-3">
          <label class="form-label fw-semibold" for="cb_location">Location</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
            <input
              type="text"
              class="form-control"
              id="cb_location"
              name="branch_location"
              placeholder="Street / City"
              required
              maxlength="120"
              pattern="^[A-Za-z0-9\s.,\-'/()#]{1,120}$"
              title="1–120 chars. Letters, numbers, spaces, . , - ' / ( ) # allowed.">
          </div>
        </div>

        <!-- Email (required) -->
        <div class="mb-3 px-3">
          <label class="form-label fw-semibold" for="cb_email">Email</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            <input
              type="email"
              class="form-control"
              id="cb_email"
              name="branch_email"
              placeholder="name@example.com"
              required
              maxlength="120">
          </div>
        </div>

        <!-- Contact Person (required) -->
        <div class="mb-3 px-3">
          <label class="form-label fw-semibold" for="cb_contact">Contact Person</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input
              type="text"
              class="form-control"
              id="cb_contact"
              name="branch_contact"
              placeholder="e.g. Juan Dela Cruz"
              required
              maxlength="50"
              pattern="^[A-Za-z\s'-]{2,50}$"
              title="Letters, spaces, hyphens, apostrophes; 2–50 characters.">
          </div>
        </div>

        <!-- Contact Number (required) -->
        <div class="mb-3 px-3">
          <label class="form-label fw-semibold" for="cb_contact_number">Contact Number</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-phone"></i></span>
            <input
              type="tel"
              class="form-control"
              id="cb_contact_number"
              name="branch_contact_number"
              placeholder="e.g. +63 912 345 6789"
              required
              minlength="7"
              maxlength="20"
              pattern="^(?:\+63|0)9\d{9}$"
              title="Philippine mobile: 09XXXXXXXXX or +639XXXXXXXXX">
          </div>
        </div>

        <!-- Inline confirmation -->
        <div id="cb_confirmSection" class="d-none mx-auto text-center mt-1 px-3" style="max-width: 420px;">
          <p id="cb_confirmMessage" class="mb-2"></p>
          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary btn-sm" id="cb_cancelConfirm">Cancel</button>
            <button type="submit" class="btn btn-success btn-sm">Yes, Create Branch</button>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer px-3 pb-3">
          <button type="button" id="cb_openConfirm" class="btn btn-success w-100 py-3" disabled>
            <span class="btn-label">Create</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Make the header title white -->
<style>
  .fp-header .modal-title { color:#fff !important; }
</style>

<script>
(() => {
  const form      = document.getElementById('createBranchForm');
  if (!form) return;

  const btnOpen   = document.getElementById('cb_openConfirm');
  const confirmEl = document.getElementById('cb_confirmSection');
  const msgEl     = document.getElementById('cb_confirmMessage');
  const cancelBtn = document.getElementById('cb_cancelConfirm');

  const fNumber  = document.getElementById('cb_number');
  const fName    = document.getElementById('cb_name');
  const fLoc     = document.getElementById('cb_location');
  const fEmail   = document.getElementById('cb_email');
  const fContact = document.getElementById('cb_contact');
  const fPhone   = document.getElementById('cb_contact_number');

  // Name cannot be numbers-only
  fName.addEventListener('input', () => {
    const v = fName.value.trim();
    fName.setCustomValidity(/^\d+$/.test(v) ? 'Branch name cannot be numbers only — include letters.' : '');
    fName.value = fName.value.replace(/\s{2,}/g, ' ');
    updateCreateEnabled();
  });

  // Enable/disable Create based on validity
  function updateCreateEnabled() {
    btnOpen.disabled = !form.checkValidity();
    confirmEl.classList.add('d-none');
  }

  // Trim on blur; watch inputs for validity changes
  [fNumber, fName, fLoc, fEmail, fContact, fPhone].forEach(el => {
    el.addEventListener('blur',  () => { el.value = el.value.trim(); });
    el.addEventListener('input', updateCreateEnabled);
    el.addEventListener('change', updateCreateEnabled);
  });
  updateCreateEnabled();

  // Show confirm summary
  btnOpen.addEventListener('click', () => {
    if (!form.checkValidity()) { form.classList.add('was-validated'); updateCreateEnabled(); return; }
    const num  = fNumber.value.trim();
    const name = fName.value.trim();
    const loc  = fLoc.value.trim();
    msgEl.innerHTML =
      `Create branch <strong>${name}</strong>` +
      (num ? ` (No. <strong>${num}</strong>)` : '') +
      (loc ? ` at <strong>${loc}</strong>?` : '?');
    confirmEl.classList.remove('d-none');
  });

  cancelBtn.addEventListener('click', () => confirmEl.classList.add('d-none'));

  // Final guard
  form.addEventListener('submit', (e) => {
    if (!form.checkValidity()) {
      e.preventDefault();
      form.classList.add('was-validated');
      updateCreateEnabled();
    }
  });

  // Focus when opens; reset on close
  const modal = document.getElementById('createBranchModal');
  modal.addEventListener('shown.bs.modal', () => fNumber.focus());
  modal.addEventListener('hidden.bs.modal', () => {
    form.reset();
    form.classList.remove('was-validated');
    confirmEl.classList.add('d-none');
    updateCreateEnabled();
  });
})();
</script>

<script>
(() => {
  const form   = document.getElementById('editBranchForm');
  if (!form) return;

  const saveBtn = document.getElementById('editBranchSaveBtn');

  const fName   = document.getElementById('editBranchName');
  const fLoc    = document.getElementById('editBranchLocation');
  const fEmail  = document.getElementById('editBranchEmail');
  const fPerson = document.getElementById('editBranchContact');
  const fPhone  = document.getElementById('editBranchContactNumber');

  // Disallow numbers-only name, give a clear message
  function validateName() {
    const v = (fName.value || '').trim();
    if (/^\d+$/.test(v)) {
      fName.setCustomValidity('Branch name cannot be numbers only — include letters.');
    } else {
      fName.setCustomValidity('');
    }
  }

  function normalizeSpaces(el) {
    el.value = el.value.replace(/\s{2,}/g, ' ').trim();
  }

  function updateState() {
    validateName();
    saveBtn.disabled = !form.checkValidity();
  }

  // Bind inputs: trim on blur, re-check on input/change
  [fName, fLoc, fEmail, fPerson, fPhone].forEach(el => {
    el.addEventListener('input', updateState);
    el.addEventListener('change', updateState);
    el.addEventListener('blur', () => { normalizeSpaces(el); updateState(); });
  });

  // Initial pass
  updateState();

  // Replace your existing openEditBranchModal with this (keeps your dataset fills, adds validation refresh)
  window.openEditBranchModal = function(button){
    const modal = document.getElementById('editBranchModal');
    modal.style.display = 'flex';

    // Fill values
    document.getElementById('editBranchId').value        = button.dataset.id || '';
    fName.value   = button.dataset.name || '';
    fLoc.value    = button.dataset.location || '';
    fEmail.value  = button.dataset.email || '';
    fPerson.value = button.dataset.contact || '';
    fPhone.value  = button.dataset.contact_number || '';

    // Refresh validity/UI
    updateState();

    // Close on backdrop / ESC
    function onBg(e){ if (e.target === modal) { closeEditBranchModal(); modal.removeEventListener('click', onBg); } }
    modal.addEventListener('click', onBg);
    function onEsc(ev){ if (ev.key === 'Escape') { closeEditBranchModal(); document.removeEventListener('keydown', onEsc); } }
    document.addEventListener('keydown', onEsc);
  };

  // Final guard on submit
  form.addEventListener('submit', (e) => {
    validateName();
    if (!form.checkValidity()) {
      e.preventDefault();
      // optional: add Bootstrap .was-validated visuals if you want
      form.classList.add('was-validated');
    }
  });
})();
</script>


<script src="sidebar.js"></script>
<script src="notifications.js"></script>
</body>
</html>
