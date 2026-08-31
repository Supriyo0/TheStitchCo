<?php
/**
 * Order, Cart, Coupon & Wishlist Business Logic
 * The Stitch Co.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Get or Create active Cart ID for session or logged-in user
function get_or_create_cart_id(): int {
    $db = get_db();
    $userId = $_SESSION['user_id'] ?? null;
    $sessionId = session_id();

    if ($userId) {
        $stmt = $db->prepare("SELECT id FROM carts WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();
        if ($cart) {
            return (int)$cart['id'];
        }
        $stmt = $db->prepare("INSERT INTO carts (user_id, session_id) VALUES (?, ?)");
        $stmt->execute([$userId, $sessionId]);
        return (int)$db->lastInsertId();
    } else {
        $stmt = $db->prepare("SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL LIMIT 1");
        $stmt->execute([$sessionId]);
        $cart = $stmt->fetch();
        if ($cart) {
            return (int)$cart['id'];
        }
        $stmt = $db->prepare("INSERT INTO carts (session_id) VALUES (?)");
        $stmt->execute([$sessionId]);
        return (int)$db->lastInsertId();
    }
}

// Merge guest session cart into user cart upon login
function merge_session_cart_to_user(int $userId): void {
    $db = get_db();
    $sessionId = session_id();

    // Check for guest cart
    $stmt = $db->prepare("SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL LIMIT 1");
    $stmt->execute([$sessionId]);
    $guestCart = $stmt->fetch();

    if (!$guestCart) {
        return;
    }

    $guestCartId = (int)$guestCart['id'];
    $userCartId = get_or_create_cart_id();

    if ($guestCartId === $userCartId) {
        return;
    }

    // Move guest items into user cart
    $stmt = $db->prepare("SELECT * FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$guestCartId]);
    $guestItems = $stmt->fetchAll();

    foreach ($guestItems as $item) {
        $checkStmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND size = ? AND color = ?");
        $checkStmt->execute([$userCartId, $item['product_id'], $item['size'], $item['color']]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $updateStmt = $db->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?");
            $updateStmt->execute([$item['quantity'], $existing['id']]);
        } else {
            $insertStmt = $db->prepare("INSERT INTO cart_items (cart_id, product_id, size, color, quantity) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->execute([$userCartId, $item['product_id'], $item['size'], $item['color'], $item['quantity']]);
        }
    }

    // Delete guest cart
    $delStmt = $db->prepare("DELETE FROM carts WHERE id = ?");
    $delStmt->execute([$guestCartId]);
}

// Fetch Full Cart Contents with product details
function get_cart_contents(): array {
    $db = get_db();
    $cartId = get_or_create_cart_id();

    $stmt = $db->prepare("
        SELECT 
            ci.id AS cart_item_id,
            ci.quantity,
            ci.size,
            ci.color,
            p.id AS product_id,
            p.name,
            p.slug,
            p.sku,
            p.price,
            p.mrp,
            p.delivery_charge,
            p.discount_percent,
            p.stock,
            p.thumbnail,
            p.images_json,
            (ci.quantity * p.price) AS subtotal
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ? AND p.is_active = 1
        ORDER BY ci.id DESC
    ");
    $stmt->execute([$cartId]);
    $items = $stmt->fetchAll();

    $subtotal = 0.00;
    $totalCount = 0;
    $totalDeliveryCharge = 0.00;

    foreach ($items as &$item) {
        $images = json_decode($item['images_json'] ?? '[]', true);
        $item['primary_image'] = !empty($item['thumbnail']) ? $item['thumbnail'] : (!empty($images[0]) ? $images[0] : 'assets/images/placeholder.svg');
        $subtotal += (float)$item['subtotal'];
        $totalCount += (int)$item['quantity'];
        $totalDeliveryCharge += ((float)($item['delivery_charge'] ?? 0.00)) * (int)$item['quantity'];
    }

    return [
        'items' => $items,
        'count' => $totalCount,
        'subtotal' => $subtotal,
        'delivery_charge' => $totalDeliveryCharge
    ];
}

// Add item to cart with stock validation
function add_to_cart(int $productId, int $quantity = 1, string $size = 'M', string $color = 'Black'): array {
    $db = get_db();
    $cartId = get_or_create_cart_id();

    // Check product existence and active stock
    $stmt = $db->prepare("SELECT id, name, price, stock, is_active FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product || !$product['is_active']) {
        return ['success' => false, 'message' => 'Product is currently unavailable.'];
    }

    if ($product['stock'] < $quantity) {
        return ['success' => false, 'message' => 'Only ' . $product['stock'] . ' units available in stock.'];
    }

    // Check if already in cart
    $checkStmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND size = ? AND color = ?");
    $checkStmt->execute([$cartId, $productId, $size, $color]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        $newQty = $existing['quantity'] + $quantity;
        if ($newQty > $product['stock']) {
            return ['success' => false, 'message' => 'Cannot add more. You have maximum available stock in your cart.'];
        }
        $upStmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $upStmt->execute([$newQty, $existing['id']]);
    } else {
        $insStmt = $db->prepare("INSERT INTO cart_items (cart_id, product_id, size, color, quantity) VALUES (?, ?, ?, ?, ?)");
        $insStmt->execute([$cartId, $productId, $size, $color, $quantity]);
    }

    $cartData = get_cart_contents();
    return [
        'success' => true,
        'message' => 'Added to cart successfully!',
        'cart_count' => $cartData['count'],
        'cart_subtotal' => $cartData['subtotal']
    ];
}

// Update Cart item quantity
function update_cart_item(int $cartItemId, int $newQuantity): array {
    $db = get_db();
    $cartId = get_or_create_cart_id();

    if ($newQuantity <= 0) {
        $stmt = $db->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
        $stmt->execute([$cartItemId, $cartId]);
        $cartData = get_cart_contents();
        return ['success' => true, 'message' => 'Item removed from cart.', 'cart_count' => $cartData['count'], 'cart_subtotal' => $cartData['subtotal']];
    }

    $stmt = $db->prepare("
        SELECT ci.id, ci.quantity, p.stock 
        FROM cart_items ci 
        JOIN products p ON ci.product_id = p.id 
        WHERE ci.id = ? AND ci.cart_id = ?
    ");
    $stmt->execute([$cartItemId, $cartId]);
    $item = $stmt->fetch();

    if (!$item) {
        return ['success' => false, 'message' => 'Cart item not found.'];
    }

    if ($newQuantity > $item['stock']) {
        return ['success' => false, 'message' => 'Maximum available stock is ' . $item['stock'] . '.'];
    }

    $upStmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $upStmt->execute([$newQuantity, $cartItemId]);

    $cartData = get_cart_contents();
    return [
        'success' => true,
        'message' => 'Cart updated.',
        'cart_count' => $cartData['count'],
        'cart_subtotal' => $cartData['subtotal']
    ];
}

// Remove from cart
function remove_from_cart(int $cartItemId): array {
    $db = get_db();
    $cartId = get_or_create_cart_id();

    $stmt = $db->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
    $stmt->execute([$cartItemId, $cartId]);

    $cartData = get_cart_contents();
    return [
        'success' => true,
        'message' => 'Item removed.',
        'cart_count' => $cartData['count'],
        'cart_subtotal' => $cartData['subtotal']
    ];
}

// Clear cart
function clear_cart(): void {
    $db = get_db();
    $cartId = get_or_create_cart_id();
    $stmt = $db->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cartId]);
}

// Server-side Coupon Validator
function validate_coupon(string $couponCode, float $cartSubtotal): array {
    $couponCode = trim(strtoupper($couponCode));
    if (empty($couponCode)) {
        return ['valid' => false, 'message' => 'Please enter a coupon code.'];
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$couponCode]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        return ['valid' => false, 'message' => 'Invalid or expired coupon code.'];
    }

    $today = date('Y-m-d');
    if (!empty($coupon['start_date']) && $coupon['start_date'] > $today) {
        return ['valid' => false, 'message' => 'This coupon is not active yet.'];
    }
    if (!empty($coupon['expiry_date']) && $coupon['expiry_date'] < $today) {
        return ['valid' => false, 'message' => 'This coupon has expired.'];
    }

    if ($coupon['used_count'] >= $coupon['usage_limit']) {
        return ['valid' => false, 'message' => 'Coupon usage limit has been reached.'];
    }

    if ($cartSubtotal < (float)$coupon['min_cart_amount']) {
        return [
            'valid' => false,
            'message' => 'Minimum order amount for ' . $couponCode . ' is ' . format_price($coupon['min_cart_amount']) . '.'
        ];
    }

    // Calculate discount
    $discount = 0.00;
    if ($coupon['discount_type'] === 'percentage') {
        $discount = ($cartSubtotal * (float)$coupon['discount_value']) / 100;
        if (!empty($coupon['max_discount']) && $discount > (float)$coupon['max_discount']) {
            $discount = (float)$coupon['max_discount'];
        }
    } else {
        $discount = (float)$coupon['discount_value'];
    }

    if ($discount > $cartSubtotal) {
        $discount = $cartSubtotal;
    }

    return [
        'valid' => true,
        'coupon_id' => $coupon['id'],
        'code' => $coupon['code'],
        'description' => $coupon['description'],
        'discount_amount' => $discount,
        'message' => 'Coupon applied successfully! You saved ' . format_price($discount) . '.'
    ];
}

// Wishlist helpers
function get_wishlist_count(?int $userId): int {
    if (!$userId) return 0;
    $db = get_db();
    $stmt = $db->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function toggle_wishlist(int $userId, int $productId): array {
    $db = get_db();
    $stmt = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    $exists = $stmt->fetch();

    if ($exists) {
        $del = $db->prepare("DELETE FROM wishlists WHERE id = ?");
        $del->execute([$exists['id']]);
        return ['success' => true, 'action' => 'removed', 'message' => 'Removed from wishlist.'];
    } else {
        $ins = $db->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");
        $ins->execute([$userId, $productId]);
        return ['success' => true, 'action' => 'added', 'message' => 'Added to wishlist!'];
    }
}

// Log Order Status Timeline
function log_order_status_transition(int $orderId, ?string $previousStatus, string $newStatus, ?string $comment, string $changedBy = 'System'): void {
    try {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO order_status_history (order_id, previous_status, new_status, comment, changed_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$orderId, $previousStatus, $newStatus, $comment, $changedBy]);
    } catch (Exception $e) {
        error_log("Failed to log order status history: " . $e->getMessage());
    }
}
