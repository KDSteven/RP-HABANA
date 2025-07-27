<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'stockman') {
    header("Location: dashboard.php");
    exit;
}

$branch_id = $_SESSION['branch_id'] ?? 0;

// Handle transfer request submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_transfer'])) {
    $product_id = (int)$_POST['product_id'];
    $source_branch = (int)$_POST['source_branch'];
    $destination_branch = (int)$_POST['destination_branch'];
    $quantity = (int)$_POST['quantity'];
    $requested_by = $_SESSION['user_id'];

    if ($product_id && $source_branch && $destination_branch && $quantity > 0 && $source_branch !== $destination_branch) {
        $stmt = $conn->prepare("
            INSERT INTO transfer_requests (product_id, source_branch, destination_branch, quantity, requested_by) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiii", $product_id, $source_branch, $destination_branch, $quantity, $requested_by);

        if ($stmt->execute()) {
            $message = "<div class='alert success'>Transfer request submitted successfully!</div>";
        } else {
            $message = "<div class='alert error'>Error submitting transfer request.</div>";
        }
    } else {
        $message = "<div class='alert error'>Invalid data. Please check your input.</div>";
    }
}

// Fetch branches for dropdowns
$branches = $conn->query("SELECT * FROM branches");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stock Transfer</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
body { display:flex; height:100vh; background:#f5f5f5; }
.sidebar { width:220px; background:#f7931e; color:white; padding:20px; }
.sidebar h2 { text-align:center; margin-bottom:20px; }
.sidebar a { display:block; padding:12px; color:white; text-decoration:none; margin-bottom:10px; border-radius:5px; }
.sidebar a:hover { background:#e67e00; }
.content { flex:1; padding:30px; background:#fff; border-radius:8px; margin:20px; box-shadow:0 2px 10px rgba(0,0,0,0.1);}
h2 { margin-bottom:20px; }
form label { display:block; margin:10px 0 5px; font-weight:bold; }
form select, form input[type="number"], button {
    width:100%; padding:10px; margin-bottom:15px; border:1px solid #ccc; border-radius:5px; font-size:1rem;
}
button { background:#f7931e; color:#fff; border:none; cursor:pointer; }
button:hover { background:#e67e00; }
.alert { padding:10px; border-radius:5px; margin-bottom:15px; }
.success { background:#28a745; color:#fff; }
.error { background:#dc3545; color:#fff; }
</style>
</head>
<body>
<div class="sidebar">
    <h2><?= strtoupper($_SESSION['role']) ?></h2>
    <a href="dashboard.php"><i class="fas fa-tv"></i> Dashboard</a>
    <a href="inventory.php?branch=<?= $branch_id ?>"><i class="fas fa-box"></i> Inventory</a>
    <a href="transfer.php" class="active"><i class="fas fa-exchange-alt"></i> Transfer</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <h2>Request Stock Transfer</h2>
    <?= $message ?>

    <form method="POST">
        <label for="source_branch">Source Branch</label>
        <select name="source_branch" id="source_branch" required>
            <option value="">Select Branch</option>
            <?php while ($branch = $branches->fetch_assoc()): ?>
                <option value="<?= $branch['branch_id'] ?>"><?= htmlspecialchars($branch['branch_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label for="product_id">Product</label>
        <select name="product_id" id="product_id" required disabled>
            <option value="">Select a branch first</option>
        </select>

        <label for="destination_branch">Destination Branch</label>
        <select name="destination_branch" required>
            <?php
            $branches->data_seek(0);
            while ($branch = $branches->fetch_assoc()): ?>
                <option value="<?= $branch['branch_id'] ?>"><?= htmlspecialchars($branch['branch_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label for="quantity">Quantity</label>
        <input type="number" name="quantity" min="1" required>

        <button type="submit" name="request_transfer">Submit Request</button>
    </form>
</div>
<script src="notifications.js"></script>
<script>
document.getElementById('source_branch').addEventListener('change', function() {
    const branchId = this.value;
    const productDropdown = document.getElementById('product_id');

    if (!branchId) {
        productDropdown.innerHTML = '<option value="">Select a branch first</option>';
        productDropdown.disabled = true;
        return;
    }

    fetch('get_products_by_branch.php?branch_id=' + branchId)
        .then(response => response.json())
        .then(data => {
            productDropdown.disabled = false;
            productDropdown.innerHTML = '';

            if (data.length === 0) {
                productDropdown.innerHTML = '<option value="">No products available</option>';
                return;
            }

            data.forEach(product => {
                const option = document.createElement('option');
                option.value = product.product_id;
                option.textContent = `${product.product_name} (Stock: ${product.stock})`;
                productDropdown.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            productDropdown.disabled = true;
        });
});
</script>
</body>
</html>
