<?php
session_start();
require 'config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$currentRole   = $_SESSION['role'] ?? '';
$currentBranch = $_SESSION['branch_id'] ?? null;

// ------------------- FETCH BRANCHES FOR FORM -------------------
$branches_for_create = $conn->query("SELECT * FROM branches WHERE archived = 0 ORDER BY branch_name ASC");
$branches_for_edit   = $conn->query("SELECT * FROM branches WHERE archived = 0 ORDER BY branch_name ASC");

// Convert to arrays to allow multiple loops
$branches_create = $branches_for_create ? $branches_for_create->fetch_all(MYSQLI_ASSOC) : [];
$branches_edit   = $branches_for_edit ? $branches_for_edit->fetch_all(MYSQLI_ASSOC) : [];

// ------------------- HANDLE ACTIONS -------------------
// Archive user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_user_id'])) {
    $archiveId = (int) $_POST['archive_user_id'];
    $stmt = $conn->prepare("UPDATE users SET archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $archiveId);
    $stmt->execute();
    header("Location: accounts.php?archived=success");
    exit;
}

// Create user FIXXXXXX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? '';
   $branch_id = (in_array($role, ['staff','stockman']) && isset($_POST['branch_id']))  ? (int)$_POST['branch_id'] : 0;


    if($username === '' || $password === '' || $role === '') {
        header("Location: accounts.php?create=invalid");
        exit;
    }

    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if($stmt->get_result()->num_rows > 0){
        header("Location: accounts.php?create=exists");
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, branch_id, archived) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("sssi", $username, $hashedPassword, $role, $branch_id);
    $stmt->execute();

    logAction($conn, "Create Account", "Created user: $username, role: $role, branch: $branch_id");
    header("Location: accounts.php?create=success");
    exit;
}

// ------------------- UPDATE USER -------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id       = (int) ($_POST['edit_user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    // Determine branch_id
    if (in_array($role, ['staff','stockman']) && !empty($_POST['branch_id'])) {
        $branch_id = (int) $_POST['branch_id'];
    } else {
        $branch_id = null; // Admin or role without branch
    }

    if ($id <= 0 || $username === '' || $role === '') {
        header("Location: accounts.php?updated=invalid");
        exit;
    }

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if (is_null($branch_id)) {
            $stmt = $conn->prepare("UPDATE users 
                                    SET username=?, password=?, role=?, branch_id=NULL 
                                    WHERE id=?");
            $stmt->bind_param("sssi", $username, $hashedPassword, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users 
                                    SET username=?, password=?, role=?, branch_id=? 
                                    WHERE id=?");
            $stmt->bind_param("sssii", $username, $hashedPassword, $role, $branch_id, $id);
        }
    } else {
        if (is_null($branch_id)) {
            $stmt = $conn->prepare("UPDATE users 
                                    SET username=?, role=?, branch_id=NULL 
                                    WHERE id=?");
            $stmt->bind_param("ssi", $username, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users 
                                    SET username=?, role=?, branch_id=? 
                                    WHERE id=?");
            $stmt->bind_param("ssii", $username, $role, $branch_id, $id);
        }
    }

    $stmt->execute();

    logAction($conn, "Update Account", "Updated user: $username, role: $role, branch_id: " . ($branch_id ?? 'NULL'));

    header("Location: accounts.php?updated=success");
    exit;
}

// Create branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_branch'])) {
    $branch_number = trim($_POST['branch_number'] ?? '');
    $branch_name = trim($_POST['branch_name'] ?? '');
    $branch_location = trim($_POST['branch_location'] ?? '');
    $branch_email = trim($_POST['branch_email'] ?? '');
    $branch_contact = trim($_POST['branch_contact'] ?? '');
    $branch_contact_number = trim($_POST['branch_contact_number'] ?? '');

    if ($branch_number === '' || $branch_name === '' || $branch_email === '') {
        header("Location: accounts.php?branch=invalid");
        exit;
    }

    // Insert branch (assuming branch_id is auto-increment)
    $stmt = $conn->prepare("INSERT INTO branches (branch_number, branch_name, branch_location, branch_email, branch_contact, branch_contact_number, archived) VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("isssss", $branch_number, $branch_name, $branch_location, $branch_email, $branch_contact, $branch_contact_number);
    $stmt->execute();
    header("Location: accounts.php?branch=created");
    exit;
}

// Archive branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_branch'])) {
    $branch_id = (int) ($_POST['branch_id'] ?? 0);
    if ($branch_id > 0) {
        $stmt = $conn->prepare("UPDATE branches SET archived = 1 WHERE branch_id = ?");
        $stmt->bind_param("i", $branch_id);
        $stmt->execute();
    }
    logAction($conn, "Archive Branch", "Archived branch ID: $branch_id");
    header("Location: accounts.php?branch=archived");
    exit;
}

// Fetch non-archived users
$usersQuery = "
    SELECT u.id, u.username, u.role, b.branch_name
    FROM users u
    LEFT JOIN branches b ON u.branch_id = b.branch_id
    WHERE u.archived = 0
    ORDER BY u.id DESC
";
$users = $conn->query($usersQuery);

// Pending notifications
$pending = 0;
if ($currentRole === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE LOWER(status) = 'pending'");
    $pending = $result ? (int)($result->fetch_assoc()['pending'] ?? 0) : 0;
}

// Log function
function logAction($conn, $action, $details, $user_id = null, $branch_id = null) {
    if (!$user_id && isset($_SESSION['user_id'])) $user_id = $_SESSION['user_id'];
    if (!$branch_id && isset($_SESSION['branch_id'])) $branch_id = $_SESSION['branch_id'];
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, timestamp, branch_id) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("issi", $user_id, $action, $details, $branch_id);
    $stmt->execute();
    $stmt->close();
}


// --- Helper used for temp passwords (copied from approvals.php) ---
function generateTempPassword(mysqli $conn, int $userId): ?string {
    // 1. Fetch username
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) return null;

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($username);

    if (!$stmt->fetch()) {
        $stmt->close();
        return null; // user not found
    }
    $stmt->close();

    // 2. Clean username (remove spaces just in case)
    $cleanUser = preg_replace('/\s+/', '', $username);

    // 3. Add 2 random digits (always 2 characters, with leading zero if needed)
    $digits = str_pad((string)random_int(0, 99), 2, "0", STR_PAD_LEFT);

    // 4. Build the password
    return "Temp:{$cleanUser}{$digits}";
}

// ===== Handle Password Reset Approvals (Admin only) =====
if (isset($_POST['reset_action'], $_POST['reset_id']) && $currentRole === 'admin') {
    $reset_id     = (int) $_POST['reset_id'];
    $reset_action = $_POST['reset_action'];

    if ($reset_action === 'approve') {
        // Lookup user for this reset
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE id=? AND status='pending'");
        $stmt->bind_param("i", $reset_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if ($row) {
            $user_id      = (int) $row['user_id'];
            $tempPassword = generateTempPassword($conn, $user_id);
            $hashed       = password_hash($tempPassword, PASSWORD_DEFAULT);

            // Update user's password and force change on next login
            $stmt = $conn->prepare("UPDATE users SET password=?, must_change_password=1 WHERE id=?");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();
            $stmt->close();

            // Mark request as approved
            $stmt = $conn->prepare("UPDATE password_resets SET status='approved', decided_by=?, decided_at=NOW() WHERE id=?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $reset_id);
            $stmt->execute();
            $stmt->close();

            // ✅ Toast message + type
            $_SESSION['toast_msg']  = "Password reset approved. Temporary password for user #{$user_id}: <b>{$tempPassword}</b>";
            $_SESSION['toast_type'] = 'success';

            // (Optional) log
            logAction($conn, "Approve Password Reset", "Reset approved for user_id={$user_id}");
        } else {
            // ✅ Toast: not found / already processed
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


// Fetch pending password reset requests (admin only)
$resetRequests = null;
if ($currentRole === 'admin') {
    $resetRequests = $conn->query("
        SELECT pr.id AS reset_id, u.username, u.role, pr.requested_at
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.status = 'pending'
        ORDER BY pr.requested_at ASC
    ");
}

// compute $pendingResets count (put near the query)
$pendingResetsCount = 0;
if ($currentRole === 'admin') {
  $res = $conn->query("SELECT COUNT(*) AS c FROM password_resets WHERE status='pending'");
  $pendingResetsCount = $res ? (int)$res->fetch_assoc()['c'] : 0;
}

$pendingTransfers = 0;
if ($currentRole === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM transfer_requests WHERE status='pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pendingTransfers = (int)($row['pending'] ?? 0);
    }
}

$pendingStockIns = 0;
if ($currentRole === 'admin') {
    $result = $conn->query("SELECT COUNT(*) AS pending FROM stock_in_requests WHERE status='pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pendingStockIns = (int)($row['pending'] ?? 0);
    }
}

$pendingTotalInventory = $pendingTransfers + $pendingStockIns;

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
      /* minimal inline styles to avoid missing CSS during test */
      .card { background:#fff;padding:15px;border-radius:6px;margin-bottom:15px; }
      table { width:100%; border-collapse:collapse; }
      table th, table td { padding:8px; border:1px solid #ddd; text-align:left; }
      .notif-badge { background:red;color:#fff;border-radius:50%;padding:3px 7px;font-size:12px;margin-left:8px; }
    </style>
</head>
<body class="accounts-page">

<div class="sidebar">
    <h2>
        <?= htmlspecialchars(strtoupper($currentRole), ENT_QUOTES) ?>
        <span class="notif-wrapper">
            <i class="fas fa-bell" id="notifBell"></i>
            <span id="notifCount" <?= $pending > 0 ? '' : 'style="display:none;"' ?>><?= $pending ?></span>
        </span>
    </h2>

    <a href="dashboard.php" ><i class="fas fa-tv"></i> Dashboard</a>

    <!-- Admin Links -->
    <?php
    // put this once before the sidebar (top of file is fine)
    $self = strtolower(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
    $isArchive = substr($self, 0, 7) === 'archive'; // matches archive.php, archive_view.php, etc.
    $invOpen   = in_array($self, ['inventory.php','physical_inventory.php'], true);
    $toolsOpen = ($self === 'backup_admin.php' || $isArchive);
    ?>

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
  </div>
</div>

  <!-- Sales (normal link with active state) -->
  <a href="sales.php" class="<?= $self === 'sales.php' ? 'active' : '' ?>">
    <i class="fas fa-receipt"></i> Sales
  </a>


<a href="accounts.php" class="<?= $self === 'accounts.php' ? 'active' : '' ?>">
  <i class="fas fa-users"></i> Accounts
  <?php if ($pendingResetsCount > 0): ?>
    <span class="badge-pending"><?= $pendingResetsCount ?></span>
  <?php endif; ?>
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
                            <td><?= htmlspecialchars($user['username'], ENT_QUOTES) ?></td>
                            <td><?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES) ?></td>
                            <td>
  <?= in_array($user['role'], ['staff','stockman']) 
        ? htmlspecialchars($user['branch_name'], ENT_QUOTES) 
        : 'N/A' ?>
</td>

                            <td class="text-center">
    <div class="action-buttons">
        <!-- Edit User -->
        <button class="acc-btn btn btn-warning btn-sm"
            data-id="<?= (int)$user['id'] ?>"
            data-username="<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>"
            data-role="<?= htmlspecialchars($user['role'], ENT_QUOTES) ?>"
            data-branch="<?= htmlspecialchars($user['branch_id'] ?? '', ENT_QUOTES) ?>"
            onclick="openEditModal(this)">
            <i class="fas fa-edit"></i>
        </button>

        <!-- Archive User -->
      <form method="POST" class="archive-form-user">
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
                    <tr><td colspan="5" class="text-center">No users found.</td></tr>
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

        <!-- Step indicators -->
        <div class="step-indicator mb-3">
            <div class="step-dot step-1-dot active"></div>
            <div class="step-dot step-2-dot"></div>
        </div>

        <form method="POST" id="createUserForm">
            <!-- Step 1: Credentials -->
            <div class="step step-1 active mb-3">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter username" class="form-control mb-2" required>
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" class="form-control mb-2" required>
                <small class="text-muted">Password must be at least 6 characters</small>
                <button type="button" class="btn btn-primary mt-2" onclick="nextStep(2)">Next</button>
            </div>

            <!-- Step 2: Role & Branch -->
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

<!-- Toast container (Bootstrap 5) -->
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
        <button class="btn btn-success" onclick="openCreateModal()"><i class="fas fa-plus"></i> Create Branch</button>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Branch Name</th>
                    <th>Location</th>
                    <th>Email</th>
                    <th>Contact</th>
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
                            <td class="text-center">
                            <div class="action-buttons">
                                <!-- Edit User -->
                                <button class="acc-btn btn btn-warning btn-sm"
                                    data-id="<?= $branch['branch_id'] ?>"
                                    data-name="<?= htmlspecialchars($branch['branch_name'], ENT_QUOTES) ?>"
                                    data-location="<?= htmlspecialchars($branch['branch_location'], ENT_QUOTES) ?>"
                                    data-email="<?= htmlspecialchars($branch['branch_email'], ENT_QUOTES) ?>"
                                    data-contact="<?= htmlspecialchars($branch['branch_contact'], ENT_QUOTES) ?>"
                                    data-contact_number="<?= htmlspecialchars($branch['branch_contact_number'], ENT_QUOTES) ?>"
                                    onclick="openEditBranchModal(this)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <!-- Archive Branch -->
                                <form method="POST" class="archive-form-branch">
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
                    <?php endwhile;
                else: ?>
                    <tr><td colspan="5" class="text-center">No branches found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>



<!-- EDIT USER MODAL (template-styled) -->
<div class="modal" id="editModal" style="display:none;">
  <div class="modal-content border-0 shadow-lg">
    <div class="modal-header text-white" style="display:flex; align-items:center; justify-content:space-between;">
      <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Account</h5>
      <!-- Add explicit onclick -->
      <button type="button" class="btn-close" aria-label="Close" onclick="closeEditModal()"></button>
    </div>

    <div class="modal-body">
      <form method="POST" id="editUserForm">
        <input type="hidden" name="edit_user_id" id="editUserId">

        <label class="form-label mt-2">Username</label>
        <input type="text" class="form-control" name="username" id="editUsername" required>

        <label class="form-label mt-3">Password <small class="text-muted">(leave blank to keep current)</small></label>
        <input type="password" class="form-control" name="password" id="editPassword">

        <label class="form-label mt-3">Role</label>
        <select class="form-select" name="role" id="editRole" onchange="toggleEditBranch();">
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
      <button type="button" class="btn btn-outline-secondary" onclick="closeEditModal()">Cancel</button>
      <button type="submit" form="editUserForm" name="update_user" class="btn btn-primary">Save Changes</button>
    </div>
  </div>
</div>

<!-- EDIT BRANCH MODAL (template-styled) -->
<div class="modal" id="editBranchModal" style="display:none;">
  <div class="modal-content border-0 shadow-lg">
    <div class="modal-header text-white" style="display:flex; align-items:center; justify-content:space-between;">
      <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Edit Branch</h5>
      <!-- Keep explicit onclick -->
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
        <input type="email" class="form-control" name="branch_email" id="editBranchEmail" placeholder="Email" required>

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


<!-- Archive Confirmation Modal (template-style) -->
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



<!-- Bootstrap 5 JS (bundle includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>

<script>
(() => {
  const modal        = document.getElementById('archiveConfirmModal');
  const textEl       = document.getElementById('archiveConfirmText');
  const btnCancel    = document.getElementById('archiveCancelBtn');
  const btnConfirm   = document.getElementById('archiveConfirmBtn');
  let pendingForm    = null;

  // Open modal helper
  function openArchiveModal(label) {
    textEl.textContent = `You’re about to archive ${label}. This hides it but keeps history/logs. Continue?`;
    modal.style.display = 'flex';
  }
  // Close modal helper
  function closeArchiveModal() {
    modal.style.display = 'none';
  }

  // Catch all archive buttons
  document.querySelectorAll('.btn-archive-unique').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const form  = btn.closest('form');
      const type  = btn.dataset.archiveType || 'item';
      const name  = btn.dataset.archiveName || (type === 'account' ? 'this account' : 'this branch');

      pendingForm = form;
      openArchiveModal(name);
    });
  });

  // Confirm -> submit the stored form
  btnConfirm.addEventListener('click', () => {
    if (pendingForm) pendingForm.submit();
    pendingForm = null;
    closeArchiveModal();
  });

  // Cancel
  btnCancel.addEventListener('click', () => {
    pendingForm = null;
    closeArchiveModal();
  });

  // Click outside to close (optional)
  modal.addEventListener('click', (evt) => {
    if (evt.target === modal) closeArchiveModal();
  });
})();

(function () {
  const modal = document.getElementById('archiveConfirmModal');
  const textEl = document.getElementById('archiveConfirmText');
  const cancelBtn = document.getElementById('archiveCancelBtn');
  const confirmBtn = document.getElementById('archiveConfirmBtn');

  let pendingForm = null;

  // Utility to safely inject the label
  function escapeHtml(s) {
    return s.replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  // Example: wire up any archive buttons with data-archive-name
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-archive-name]');
    if (!btn) return;

    e.preventDefault();
    pendingForm = btn.closest('form');

    const label = btn.getAttribute('data-archive-name') || 'this item';
    // IMPORTANT: innerHTML so <strong> renders; escape only the dynamic label
    textEl.innerHTML = `Are you sure you want to archive <strong>${escapeHtml(label)}</strong>?`;

    modal.style.display = 'flex';
  });

  cancelBtn.addEventListener('click', () => {
    modal.style.display = 'none';
    pendingForm = null;
  });

  confirmBtn.addEventListener('click', () => {
    if (pendingForm) pendingForm.submit();
  });
})();

</script>

<script>
/* ---------------- User Modal ---------------- */
function openCreateUserModal(){ document.getElementById('createUserModal').classList.add('active'); }
function closeCreateUserModal(){ document.getElementById('createUserModal').classList.remove('active'); }
// ------- EDIT USER -------
function openEditModal(button){
  const modal = document.getElementById('editModal');
  modal.style.display = 'flex'; // show

  // populate fields
  document.getElementById('editUserId').value   = button.dataset.id;
  document.getElementById('editUsername').value = button.dataset.username;
  document.getElementById('editRole').value     = button.dataset.role;

  // preselect branch radio by ID
  const branchId = button.dataset.branch || '';
  document.querySelectorAll('#editBranchGroup input[name="branch_id"]')
    .forEach(r => r.checked = (r.value === branchId));

  toggleEditBranch(); // show/hide branch radios

  // backdrop click to close
  function onBg(e){ if (e.target === modal) { closeEditModal(); modal.removeEventListener('click', onBg); } }
  modal.addEventListener('click', onBg);

  // Esc to close
  function onEsc(ev){ if (ev.key === 'Escape') { closeEditModal(); document.removeEventListener('keydown', onEsc); } }
  document.addEventListener('keydown', onEsc);
}

function closeEditModal(){
  const modal = document.getElementById('editModal');
  modal.style.display = 'none'; // hide
}

// ------- EDIT BRANCH -------
function openEditBranchModal(button){
  const modal = document.getElementById('editBranchModal');
  modal.style.display = 'flex'; // show (was .classList.add('active') but no CSS was toggling it)

  // populate fields
  document.getElementById('editBranchId').value           = button.dataset.id;
  document.getElementById('editBranchName').value         = button.dataset.name;
  document.getElementById('editBranchLocation').value     = button.dataset.location;
  document.getElementById('editBranchEmail').value        = button.dataset.email;
  document.getElementById('editBranchContact').value      = button.dataset.contact;
  document.getElementById('editBranchContactNumber').value= button.dataset.contact_number;

  // backdrop click to close
  function onBg(e){ if (e.target === modal) { closeEditBranchModal(); modal.removeEventListener('click', onBg); } }
  modal.addEventListener('click', onBg);

  // Esc to close
  function onEsc(ev){ if (ev.key === 'Escape') { closeEditBranchModal(); document.removeEventListener('keydown', onEsc); } }
  document.addEventListener('keydown', onEsc);
}

function closeEditBranchModal(){
  const modal = document.getElementById('editBranchModal');
  modal.style.display = 'none'; // hide
}

// ------- Role → show/hide branch radios for User modal -------
function toggleEditBranch(){
  const role = document.getElementById('editRole').value;
  // show for staff & stockman, hide for admin
  document.getElementById('editBranchGroup').style.display = 
    (role === 'staff' || role === 'stockman') ? 'block' : 'none';
}

/* ---------------- Step Wizard ---------------- */
function nextStep(step){
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.querySelector('.step-' + step).classList.add('active');
    document.querySelectorAll('.step-dot').forEach((dot,i)=> dot.classList.toggle('active', i+1===step));
}
function prevStep(step){ nextStep(step); }

/* ---------------- Toggle Branch radios ---------------- */
function toggleCreateBranch(){
    const role = document.getElementById('createRoleSelect').value;
    document.getElementById('createBranchGroup').style.display = 
        (role === 'staff' || role === 'stockman') ? 'block' : 'none';
}

function toggleEditBranch(){
    document.getElementById('editBranchGroup').style.display = 
        document.getElementById('editRole').value === 'staff' ? 'block' : 'none';
}



</script>
<!-- CREATE BRANCH MODAL -->
<div class="modal" id="createModal" style="display:none;">
    <div class="modal-content">
      <div class="modal-header">Create Branch</div>
      <form method="POST">
      <input type="text" name="branch_number" placeholder="Branch Number" required pattern="\d+" title="Branch Number must be numeric">
      <input type="text" name="branch_name" placeholder="Branch Name" required pattern="^[A-Za-z0-9\s\-']+$" title="Branch name must only contain letters, numbers, spaces, hyphens, or apostrophes">
      <input type="text" name="branch_location" placeholder="Branch Location">
      <!-- <input type="email" name="branch_email" placeholder="Branch Email" required> -->
      <input type="text" name="branch_contact" placeholder="Branch Contact">
      <input type="text" name="branch_contact_number" placeholder="Branch Contact number">
        <div class="modal-footer">
          <button type="button" onclick="closeModal()">Cancel</button>
          <button type="submit" name="create_branch">Create Branch</button>
        </div>
      </form>
    </div>
</div>

<script>
/* Modal helpers */
function openCreateModal(){ document.getElementById('createModal').style.display='flex'; }
function openEditModal(button){
    document.getElementById('editModal').style.display = 'block';
    document.getElementById('editUserId').value = button.dataset.id;
    document.getElementById('editUsername').value = button.dataset.username;
    document.getElementById('editRole').value = button.dataset.role;
    // preselect branch radio if present
    var branchName = button.dataset.branch || '';
    var radios = document.getElementsByName('branch_id');
    for(var i=0;i<radios.length;i++){
        var label = radios[i].parentNode.textContent.trim();
        if(label === branchName){
            radios[i].checked = true;
            break;
        }
    }
    toggleEditBranch();
}
function closeModal(){
    document.getElementById('createModal').style.display='none';
    document.getElementById('editModal').style.display='none';
}
function toggleEditBranch(){
    const role = document.getElementById('editRole').value;
    document.getElementById('editBranchGroup').style.display = 
        (role === 'staff' || role === 'stockman') ? 'block' : 'none';
}

function toggleBranchDropdown(){
    const role = document.getElementById('roleSelect').value;
    document.getElementById('branchRadioGroup').style.display = (role === 'staff') ? 'block' : 'none';
}

/* simple validation placeholder for the create account form */
function validateForm(){
    return true;
}
</script>

<script>
(function(){
  const groups = document.querySelectorAll('.menu-group.has-sub');

  groups.forEach((g, idx) => {
    const btn = g.querySelector('.menu-toggle');
    const panel = g.querySelector('.submenu');
    if (!btn || !panel) return;

    // Optional: restore last state from localStorage
    const key = 'sidebar-sub-' + idx;
    const saved = localStorage.getItem(key);
    if (saved === 'open') {
      btn.setAttribute('aria-expanded', 'true');
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

<!-- Requires Bootstrap JS already on the page -->
<script>
(function () {
  // Expose a helper: showToast(message, type = 'info', options = {})
  // type: 'success' | 'danger' | 'info' | 'warning' | 'primary' | 'secondary' | 'dark'
  // options: { delay?: number, title?: string, iconHtml?: string }
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
    // Reset then apply the chosen bg
    toastHeader.classList.remove(...Object.values(TYPE_CLASS));
    toastHeader.classList.add(TYPE_CLASS[type] || TYPE_CLASS.info);

    // Toggle icon to match type (optional)
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

  window.showToast = function showToast(message, type = 'info', options = {}) {
    if (!toastEl || !toastBody || !toastHeader) return;

    // Content
    toastBody.innerHTML = message;
    setHeaderStyle(type);

    // Title (optional)
    if (options.title) {
      const titleEl = toastHeader.querySelector('strong.me-auto');
      if (titleEl) titleEl.textContent = options.title;
    }

    // Delay
    const delay = Number.isFinite(options.delay) ? options.delay : 3000;

    // Show
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
      { title: 'Password Reset' }
    );
  });
</script>
<?php
  unset($_SESSION['toast_msg'], $_SESSION['toast_type']);
endif; ?>



<script src="notifications.js"></script>
</body>
</html>
