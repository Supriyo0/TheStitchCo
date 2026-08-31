<?php
/**
 * AJAX Coupon Validation API Endpoint
 * The Stitch Co.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/order_functions.php';

$code = trim($_POST['code'] ?? '');
$cartData = get_cart_contents();

if ($cartData['subtotal'] <= 0) {
    echo json_encode(['valid' => false, 'message' => 'Your cart is empty.']);
    exit;
}

$validation = validate_coupon($code, $cartData['subtotal']);

if ($validation['valid']) {
    $_SESSION['applied_coupon'] = [
        'id' => $validation['coupon_id'],
        'code' => $validation['code'],
        'discount_amount' => $validation['discount_amount']
    ];
} else {
    unset($_SESSION['applied_coupon']);
}

echo json_encode($validation);
