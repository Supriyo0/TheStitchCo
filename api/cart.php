<?php
/**
 * AJAX Cart API Endpoint
 * The Stitch Co.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/order_functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (in_array($action, ['add', 'update', 'remove'])) {
    if (!is_logged_in()) {
        echo json_encode([
            'success' => false,
            'redirect' => 'login.php',
            'message' => 'Please log in to add items to your cart or wishlist.'
        ]);
        exit;
    }
}

if ($action === 'add') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $size = trim($_POST['size'] ?? 'M');
    $color = trim($_POST['color'] ?? 'Black');

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product identifier.']);
        exit;
    }

    $result = add_to_cart($productId, $quantity, $size, $color);
    echo json_encode($result);
    exit;
}

if ($action === 'update') {
    $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($cartItemId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item.']);
        exit;
    }

    $result = update_cart_item($cartItemId, $quantity);
    echo json_encode($result);
    exit;
}

if ($action === 'remove') {
    $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
    if ($cartItemId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item.']);
        exit;
    }

    $result = remove_from_cart($cartItemId);
    echo json_encode($result);
    exit;
}

if ($action === 'get') {
    $cartData = get_cart_contents();
    echo json_encode([
        'success' => true,
        'count' => $cartData['count'],
        'subtotal' => $cartData['subtotal'],
        'subtotal_formatted' => format_price($cartData['subtotal']),
        'items' => $cartData['items']
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid cart action.']);
