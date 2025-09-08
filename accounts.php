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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Accounts</title>
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

    <?php if ($currentRole === 'admin'): ?>
        <a href="inventory.php"><i class="fas fa-box"></i> Inventory</a>
        <a href="physical_inventory.php"><i class="fas fa-warehouse"></i> Physical Inventory</a>
        <a href="sales.php"><i class="fas fa-receipt"></i> Sales</a>
        <a href="approvals.php"><i class="fas fa-check-circle"></i> Approvals
            <?php if ($pending > 0): ?>
                <span class="notif-badge"><?= $pending ?></span>
            <?php endif; ?>
        </a>
        <a href="accounts.php" class="active"><i class="fas fa-users"></i> Accounts</a>
        <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>
        <a href="logs.php"><i class="fas fa-file-alt"></i> Logs</a>
        <a href="/config/admin/backup_admin.php"><i class="fa-solid fa-database"></i> Backup and Restore</a>
    <?php endif; ?>

    <?php if ($currentRole === 'stockman'): ?>
        <a href="transfer.php"><i class="fas fa-exchange-alt"></i> Transfer</a>
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
        <button class="btn btn-warning btn-sm"
            data-id="<?= (int)$user['id'] ?>"
            data-username="<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>"
            data-role="<?= htmlspecialchars($user['role'], ENT_QUOTES) ?>"
            data-branch="<?= htmlspecialchars($user['branch_id'] ?? '', ENT_QUOTES) ?>"
            onclick="openEditModal(this)">
            <i class="fas fa-edit"></i>
        </button>

        <!-- Archive User -->
        <form method="POST" onsubmit="return confirm('Archive this account?');">
            <input type="hidden" name="archive_user_id" value="<?= (int)$user['id'] ?>">
            <button type="submit" class="btn-archive-unique btn-sm">
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
                                <button class="btn btn-warning btn-sm"
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
                                <form method="POST" onsubmit="return confirm('Archive this branch?');">
                                    <input type="hidden" name="branch_id" value="<?= (int)$branch['branch_id'] ?>">
                                    <button type="submit" class="btn-archive-unique btn-sm"><i class="fas fa-archive"></i></button>
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



<!-- EDIT USER MODAL -->
<div id="editModal" class="edit-modal">
  <div class="edit-modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>Edit Account</h2>
    <form method="POST">
      <input type="hidden" name="edit_user_id" id="editUserId">
      <label>Username</label>
      <input type="text" name="username" id="editUsername" required>
      <label>Password (leave blank to keep current)</label>
      <input type="password" name="password" id="editPassword">
      <label>Role</label>
      <select name="role" id="editRole" onchange="toggleEditBranch();">
        <option value="admin">Admin</option>
        <option value="staff">Staff</option>
        <option value="stockman">Stockman</option>
      </select>

      <div id="editBranchGroup" style="display:none; margin-top:10px;">
        <p>Select Branch:</p>
        <?php foreach($branches_edit as $branch): ?>
          <label>
            <input type="radio" name="branch_id" value="<?= $branch['branch_id'] ?>">
            <?= htmlspecialchars($branch['branch_name']) ?>
          </label><br>
        <?php endforeach; ?>
      </div>

      <button type="submit" name="update_user">Save Changes</button>
    </form>
  </div>
</div>
<!-- EDIT BRANCH MODAL -->
<div id="editBranchModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeEditBranchModal()">&times;</span>
    <h2>Edit Branch</h2>
    <form method="POST" id="editBranchForm">
      <input type="hidden" name="edit_branch_id" id="editBranchId">
      <input type="text" name="branch_name" id="editBranchName" placeholder="Branch Name" required>
      <input type="text" name="branch_location" id="editBranchLocation" placeholder="Location">
      <input type="email" name="branch_email" id="editBranchEmail" placeholder="Email" required>
      <input type="text" name="branch_contact" id="editBranchContact" placeholder="Contact Person">
      <input type="text" name="branch_contact_number" id="editBranchContactNumber" placeholder="Contact Number">
      <button type="submit" name="update_branch">Save Changes</button>
    </form>
  </div>
</div>
<script>
/* ---------------- User Modal ---------------- */
function openCreateUserModal(){ document.getElementById('createUserModal').classList.add('active'); }
function closeCreateUserModal(){ document.getElementById('createUserModal').classList.remove('active'); }
function openEditModal(button){
    document.getElementById('editModal').style.display = 'block';
    document.getElementById('editUserId').value = button.dataset.id;
    document.getElementById('editUsername').value = button.dataset.username;
    document.getElementById('editRole').value = button.dataset.role;

    // preselect branch radio if present (match by ID, not label text)
    let branchId = button.dataset.branch || '';
    document.querySelectorAll('#editBranchGroup input[name="branch_id"]').forEach(radio => {
        radio.checked = (radio.value === branchId);
    });

    toggleEditBranch();
}

function closeEditModal(){ document.getElementById('editModal').classList.remove('active'); }

/* ---------------- Branch Modal ---------------- */
function openEditBranchModal(button){
    const modal = document.getElementById('editBranchModal');
    modal.classList.add('active');
    document.getElementById('editBranchId').value = button.dataset.id;
    document.getElementById('editBranchName').value = button.dataset.name;
    document.getElementById('editBranchLocation').value = button.dataset.location;
    document.getElementById('editBranchEmail').value = button.dataset.email;
    document.getElementById('editBranchContact').value = button.dataset.contact;
    document.getElementById('editBranchContactNumber').value = button.dataset.contact_number;
}
function closeEditBranchModal(){ document.getElementById('editBranchModal').classList.remove('active'); }

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

<script src="notifications.js"></script>
</body>
</html>
