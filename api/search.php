<?php
/**
 * AJAX Live Search API
 * The Stitch Co.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$db = get_db();
$searchTerm = '%' . $query . '%';

$stmt = $db->prepare("
    SELECT id, name, slug, price, mrp, thumbnail, category, badge
    FROM products
    WHERE is_active = 1 AND (name LIKE ? OR category LIKE ? OR subcategory LIKE ? OR sku LIKE ?)
    LIMIT 6
");
$stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
$results = $stmt->fetchAll();

foreach ($results as &$row) {
    $row['price_formatted'] = format_price($row['price']);
    $row['mrp_formatted'] = format_price($row['mrp']);
    $row['url'] = 'product.php?id=' . $row['id'];
}

echo json_encode(['results' => $results]);
