<?php
include 'config/db.php';

header('Content-Type: application/json');

// Initialize arrays
$months = [];
$sales = [];

$query = "
SELECT DATE_FORMAT(sale_date, '%b') AS month, 
       SUM(total) AS total 
FROM sales 
GROUP BY MONTH(sale_date) 
ORDER BY MONTH(sale_date)
";

$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $months[] = $row['month'];
        $sales[] = (float)$row['total'];
    }
}

// Return JSON only
echo json_encode(['months' => $months, 'sales' => $sales]);
exit;
