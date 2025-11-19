<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/functions.php'; // <-- IMPORTANT
header('Content-Type: application/json');

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';

try {

    /* ===================================================
       CREATE BRAND
    =================================================== */
    if ($action === 'create') {
        $name = trim($data['brand_name'] ?? '');
        if ($name === '') throw new Exception('Brand name required');

        $stmt = $conn->prepare("INSERT INTO brands (brand_name, active) VALUES (?, 1)");
        $stmt->bind_param('s', $name);
        $stmt->execute();

        // ⭐ LOG
        logAction($conn, "Create Brand", "Created brand: {$name}");

        echo json_encode([
            'ok' => true,
            'message' => "Brand '{$name}' created successfully."
        ]);
        exit;
    }

    /* ===================================================
       DEACTIVATE BRAND (ARCHIVE)
    =================================================== */
    if ($action === 'deactivate') {
        $id = (int)($data['brand_id'] ?? 0);
        if ($id === 0) throw new Exception('Invalid brand ID');

        $brand = $conn->query("SELECT brand_name FROM brands WHERE brand_id={$id}")
                      ->fetch_assoc()['brand_name'] ?? 'Unknown';

        $conn->query("UPDATE brands SET active = 0 WHERE brand_id = {$id}");

        logAction($conn, "Archive Brand", "Archived brand: {$brand}");

        echo json_encode([
            'ok' => true,
            'message' => "Brand '{$brand}' archived successfully."
        ]);
        exit;
    }

    /* ===================================================
       HARD DELETE BRAND (ONLY IF UNUSED)
    =================================================== */
    if ($action === 'restrict') {
        $id = (int)($data['brand_id'] ?? 0);
        if ($id === 0) throw new Exception('Invalid brand ID');

        $brand = $conn->query("SELECT brand_name FROM brands WHERE brand_id={$id}")
                      ->fetch_assoc()['brand_name'] ?? null;

        if (!$brand) throw new Exception('Brand not found');

        // Count usage
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM products WHERE brand_name = ?");
        $stmt->bind_param('s', $brand);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['c'];

        if ($count > 0)
            throw new Exception("Brand is used by {$count} product(s). Cannot delete.");

        $conn->query("DELETE FROM brands WHERE brand_id = {$id}");

        logAction($conn, "Delete Brand", "Deleted unused brand: {$brand}");

        echo json_encode([
            'ok' => true,
            'message' => "Brand '{$brand}' deleted permanently."
        ]);
        exit;
    }

    /* ===================================================
       REASSIGN BRAND (MERGE A → B)
    =================================================== */
    if ($action === 'reassign') {
        $idFrom = (int)($data['brand_id'] ?? 0);
        $idTo   = (int)($data['reassign_to'] ?? 0);

        if ($idFrom === 0 || $idTo === 0)
            throw new Exception('Invalid reassignment');

        if ($idFrom === $idTo)
            throw new Exception('Cannot reassign to the same brand');

        $fromName = $conn->query("SELECT brand_name FROM brands WHERE brand_id={$idFrom}")
                         ->fetch_assoc()['brand_name'] ?? null;

        $toName = $conn->query("SELECT brand_name FROM brands WHERE brand_id={$idTo}")
                       ->fetch_assoc()['brand_name'] ?? null;

        if (!$fromName || !$toName)
            throw new Exception('Brand not found');

        $stmt = $conn->prepare("UPDATE products SET brand_name = ? WHERE brand_name = ?");
        $stmt->bind_param('ss', $toName, $fromName);
        $stmt->execute();

        $conn->query("DELETE FROM brands WHERE brand_id = {$idFrom}");

        logAction(
            $conn,
            "Reassign Brand",
            "Reassigned brand: {$fromName} → {$toName}"
        );

        echo json_encode([
            'ok' => true,
            'message' => "Brand '{$fromName}' reassigned to '{$toName}'."
        ]);
        exit;
    }

    throw new Exception('Unknown action');

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ]);
}
