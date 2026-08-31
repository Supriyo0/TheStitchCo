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

if (strlen($query) < 1) {
    echo json_encode([
        'query' => '',
        'categories' => [],
        'products' => [],
        'total' => 0
    ]);
    exit;
}

$db = get_db();
$searchTerm = '%' . $query . '%';

// 1. Match Categories
$catStmt = $db->prepare("
    SELECT cat_key, cat_name, icon
    FROM categories
    WHERE cat_name LIKE ? OR cat_key LIKE ?
    LIMIT 3
");
$catStmt->execute([$searchTerm, $searchTerm]);
$matchedCategories = $catStmt->fetchAll();

// 2. Match Products
$prodStmt = $db->prepare("
    SELECT id, name, slug, price, mrp, thumbnail, category, badge, stock
    FROM products
    WHERE is_active = 1 AND (name LIKE ? OR category LIKE ? OR subcategory LIKE ? OR sku LIKE ? OR description LIKE ?)
    ORDER BY is_best_seller DESC, is_new_arrival DESC, id DESC
    LIMIT 6
");
$prodStmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
$products = $prodStmt->fetchAll();

foreach ($products as &$row) {
    $row['price_formatted'] = format_price_no_decimals($row['price']);
    $row['mrp_formatted'] = format_price_no_decimals($row['mrp']);
    $row['discount_percent'] = ($row['mrp'] > $row['price']) ? round((($row['mrp'] - $row['price']) / $row['mrp']) * 100) : 0;
    $row['thumbnail_url'] = get_media_url($row['thumbnail'] ?? '');
    $row['url'] = 'product.php?id=' . $row['id'];
}

// 3. Get total count
$countStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM products 
    WHERE is_active = 1 AND (name LIKE ? OR category LIKE ? OR subcategory LIKE ? OR sku LIKE ? OR description LIKE ?)
");
$countStmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
$totalMatches = (int)$countStmt->fetchColumn();

echo json_encode([
    'query' => $query,
    'categories' => $matchedCategories,
    'products' => $products,
    'total' => $totalMatches
]);

