<?php
require 'config/db.php';
header('Content-Type: application/json');

$mode = $_GET['mode'] ?? ''; // brand | category
$id   = intval($_GET['id'] ?? 0);

if (!$id || ($mode !== 'brand' && $mode !== 'category')) {
    echo json_encode(["ok" => false, "count" => 0]);
    exit;
}

if ($mode === 'brand') {

    // get brand name
    $q = $conn->prepare("SELECT brand_name FROM brands WHERE brand_id = ?");
    $q->bind_param("i", $id);
    $q->execute();
    $q->bind_result($name);
    $q->fetch();
    $q->close();

    if (!$name) {
        echo json_encode(["ok" => false, "count" => 0]);
        exit;
    }

    // count products using this brand
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE brand_name = ?");
    $stmt->bind_param("s", $name);

} else {

    // get category name
    $q = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
    $q->bind_param("i", $id);
    $q->execute();
    $q->bind_result($name);
    $q->fetch();
    $q->close();

    if (!$name) {
        echo json_encode(["ok" => false, "count" => 0]);
        exit;
    }

    // count products using this category
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category = ?");
    $stmt->bind_param("s", $name);
}

$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

echo json_encode(["ok" => true, "count" => $count]);
