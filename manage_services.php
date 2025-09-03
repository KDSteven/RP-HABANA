<?php
session_start();
require 'config/db.php';

// Get branch ID from query string
$branch_id = $_GET['branch'] ?? null;

if (!$branch_id) {
    die("No branch selected.");
}

// Fetch branch details
$stmt = $conn->prepare("SELECT branch_name, branch_location FROM branches WHERE branch_id = ?");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$branch = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch services for this branch
$stmt = $conn->prepare("SELECT * FROM services WHERE branch_id = ?");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$services = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Services - <?= htmlspecialchars($branch['branch_name']) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h1>Manage Services for <?= htmlspecialchars($branch['branch_name']) ?> (<?= htmlspecialchars($branch['branch_location']) ?>)</h1>

<!-- Add Service Button -->
<a href="add_service.php?branch=<?= $branch_id ?>" class="btn btn-primary">Add Service</a>

<!-- Service Table -->
<table border="1">
    <tr>
        <th>Service Name</th>
        <th>Price</th>
        <th>Actions</th>
    </tr>
    <?php while ($service = $services->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($service['service_name']) ?></td>
        <td><?= htmlspecialchars($service['price']) ?></td>
        <td>
            <a href="edit_service.php?id=<?= $service['service_id'] ?>">Edit</a> | 
            <a href="delete_service.php?id=<?= $service['service_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
