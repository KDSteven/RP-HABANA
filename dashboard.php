<?php
session_start();
include 'config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$location_id = $_SESSION['location_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            display: flex;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            background:#f39200;
            padding-top: 20px;
            position: fixed;
            color: white;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
        }
        .sidebar a:hover {
            color: #f39200;;
            background: white;
        }
        .content {
            margin-left: 260px;
            padding: 20px;
            width: 100%;
        }
        .section {
            margin-top: 50px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h4 class="text-center">Admin Panel</h4>
    <a href="#dashboard">📊 Dashboard</a>
    <a href="#inventory">📦 Inventory</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="sidebar">
    <h4 class="text-center">Admin Panel</h4>
    <a href="#dashboard">📊 Dashboard</a>
    <a href="#inventory">📦 Inventory</a>
    <a href="index.html">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="content">
    <!-- Dashboard Section -->
    <section id="dashboard">
        <h2>Welcome, <?php echo $username; ?>!</h2>
        <h4>Your Role: <?php echo ucfirst($role); ?></h4>
    </section>

    <!-- Inventory Section -->
    <section id="inventory" class="section">
        <h3>Inventory</h3>

        <?php if ($role === 'admin'): ?>
            <!-- Admin sees all branches -->
            <?php for ($i = 1; $i <= 7; $i++): ?>
                <div class="accordion" id="inventoryAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?= $i ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $i ?>" aria-expanded="false" aria-controls="collapse<?= $i ?>">
                                Branch <?= $i ?>
                            </button>
                        </h2>
                        <div id="collapse<?= $i ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $i ?>" data-bs-parent="#inventoryAccordion">
                            <div class="accordion-body">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT product_name, category, price, branch_{$i}_stock AS stock FROM inventory";
                                        $result = $conn->query($sql);
                                        while ($row = $result->fetch_assoc()):
                                        ?>
                                            <tr>
                                                <td><?= $row['product_name'] ?></td>
                                                <td><?= $row['category'] ?></td>
                                                <td><?= number_format($row['price'], 2) ?></td>
                                                <td><?= $row['stock'] ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>

        <?php else: ?>
            <!-- Staff sees only their assigned branch -->
            <div class="accordion" id="inventoryAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $location_id ?>">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $location_id ?>" aria-expanded="true" aria-controls="collapse<?= $location_id ?>">
                            Branch <?= $location_id ?>
                        </button>
                    </h2>
                    <div id="collapse<?= $location_id ?>" class="accordion-collapse collapse show" aria-labelledby="heading<?= $location_id ?>" data-bs-parent="#inventoryAccordion">
                        <div class="accordion-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT product_name, category, price, branch_{$location_id}_stock AS stock FROM inventory";
                                    $result = $conn->query($sql);
                                    while ($row = $result->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?= $row['product_name'] ?></td>
                                            <td><?= $row['category'] ?></td>
                                            <td><?= number_format($row['price'], 2) ?></td>
                                            <td><?= $row['stock'] ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </section>
</div>

</body>
</html>

<?php $conn->close(); ?>