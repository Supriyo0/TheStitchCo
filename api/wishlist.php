<?php
/**
 * AJAX Wishlist API Endpoint
 * The Stitch Co.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/order_functions.php';

if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'redirect' => 'login.php',
        'message' => 'Please login to save items to your wishlist.'
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$productId = (int)($_POST['product_id'] ?? 0);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
    exit;
}

$result = toggle_wishlist($userId, $productId);
$result['wishlist_count'] = get_wishlist_count($userId);

echo json_encode($result);
