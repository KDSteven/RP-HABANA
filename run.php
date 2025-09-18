<?php
// scripts/backfill_log_branch_names.php
require __DIR__ . '/config/db.php';
require __DIR__ . '/functions.php';

set_time_limit(0);
$batchSize = 500;
$offset = 0;

while (true) {
    $res = $conn->query("
        SELECT log_id, details 
        FROM logs 
        ORDER BY log_id ASC 
        LIMIT $batchSize OFFSET $offset
    ");
    if (!$res || $res->num_rows === 0) break;

    while ($row = $res->fetch_assoc()) {
        $new = expandBranchTokensToNames($conn, $row['details'] ?? '');
        if ($new !== $row['details']) {
            $stmt = $conn->prepare("UPDATE logs SET details = ? WHERE log_id = ?");
            $stmt->bind_param("si", $new, $row['log_id']);
            $stmt->execute();
            $stmt->close();
        }
    }

    $offset += $batchSize;
}

echo "Backfill complete.\n";
