<?php
include 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $branch_id = $_POST['branch_id'];

    $sql = "DELETE FROM branches WHERE id = '$branch_id'";

    if ($conn->query($sql) === TRUE) {
        echo "Branch deleted successfully!";
    } else {
        echo "Error: " . $conn->error;
    }

    $conn->close();
}
?>
