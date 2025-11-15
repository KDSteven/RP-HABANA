<?php
require __DIR__ . '/config/db.php';
header('Content-Type: application/json');

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';

/** Response helper */
function respond($ok, $message) {
    echo json_encode(['ok' => $ok, 'message' => $message]);
    exit;
}

$inTx = false;

try {

    /* =======================================================
       CREATE NEW CATEGORY
    ======================================================= */
    if ($action === 'create') {
        $name = trim($data['category_name'] ?? '');

        if ($name === '') {
            respond(false, 'Category name is required.');
        }

        // Prevent duplicates (case-insensitive)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE LOWER(category_name) = LOWER(?)");
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $stmt->bind_result($exists);
        $stmt->fetch();
        $stmt->close();

        if ($exists > 0) {
            respond(false, 'Category already exists.');
        }

        $stmt = $conn->prepare("INSERT INTO categories (category_name, active) VALUES (?, 1)");
        $stmt->bind_param('s', $name);
        $stmt->execute();

        respond(true, 'Category created successfully.');
    }

    /* =======================================================
   DEACTIVATE CATEGORY (Soft Archive)
   Prevent archiving if still used by products
======================================================= */
if ($action === 'deactivate') {
    $id = (int)($data['category_id'] ?? 0);
    if ($id === 0) respond(false, 'Invalid category ID.');

    // Fetch category safely
    $stmt = $conn->prepare("
        SELECT TRIM(category_name) 
        FROM categories 
        WHERE category_id = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($catName);
    $stmt->fetch();
    $stmt->close();

    if (!$catName || $catName === '') {
        respond(false, 'Category not found.');
    }

    // Count products using the category (NULL-safe check)
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM products 
        WHERE TRIM(category) = TRIM(?)
          AND (archived = 0 OR archived IS NULL)
    ");
    $stmt->bind_param('s', $catName);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();

    if ($cnt > 0) {
        respond(false, "Cannot archive: category is used by {$cnt} active product(s).");
    }

    // Safe to archive
    $stmt = $conn->prepare("UPDATE categories SET active = 0 WHERE category_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    respond(true, 'Category archived.');
}


    /* =======================================================
       HARD DELETE (RESTRICT)
       Products table stores string category
    ======================================================= */
    if ($action === 'restrict') {
        $id = (int)($data['category_id'] ?? 0);
        if ($id === 0) respond(false, 'Invalid category ID.');

        // Get category name
        $stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($catName);
        $stmt->fetch();
        $stmt->close();

        if (!$catName) {
            respond(false, 'Category not found.');
        }

        // Count products using this category (TRIM for consistency)
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM products 
            WHERE TRIM(category) = TRIM(?) 
              AND archived = 0
        ");
        $stmt->bind_param('s', $catName);
        $stmt->execute();
        $stmt->bind_result($cnt);
        $stmt->fetch();
        $stmt->close();

        if ($cnt > 0) {
            respond(false, "Cannot delete: category is used by {$cnt} product(s).");
        }

        // Safe delete
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        respond(true, 'Category deleted permanently.');
    }

    /* =======================================================
       REASSIGN CATEGORY
    ======================================================= */
    if ($action === 'reassign') {

        $id = (int)($data['category_id'] ?? 0);
        $to = (int)($data['reassign_to'] ?? 0);

        if ($id === 0 || $to === 0 || $id === $to) {
            respond(false, 'Invalid reassignment.');
        }

        // Get old category
        $stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($oldName);
        $stmt->fetch();
        $stmt->close();

        // Get new category
        $stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
        $stmt->bind_param('i', $to);
        $stmt->execute();
        $stmt->bind_result($newName);
        $stmt->fetch();
        $stmt->close();

        if (!$oldName || !$newName) {
            respond(false, 'Category not found.');
        }

        // Start transaction
        $conn->begin_transaction();
        $inTx = true;

        // Update products
        $stmt = $conn->prepare("UPDATE products SET category = ? WHERE TRIM(category) = TRIM(?)");
        $stmt->bind_param('ss', $newName, $oldName);
        $stmt->execute();
        $stmt->close();

        // Delete old category
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $inTx = false;

        respond(true, "Category reassigned from '{$oldName}' to '{$newName}'.");
    }


    /* =======================================================
       UNKNOWN ACTION
    ======================================================= */
    respond(false, 'Unknown action: ' . $action);

} catch (Throwable $e) {

    if ($inTx) {
        $conn->rollback();
    }

    respond(false, $e->getMessage());
}
