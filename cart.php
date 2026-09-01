<?php
/**
 * Shopping Cart Page
 * The Stitch Co.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_functions.php';

$cartData = get_cart_contents();
$appliedCoupon = $_SESSION['applied_coupon'] ?? null;

$subtotal = $cartData['subtotal'];
$productDeliveryCharge = $cartData['delivery_charge'] ?? 0.00;
$discountAmount = 0.00;

if ($appliedCoupon && $subtotal > 0) {
    // Re-validate coupon server-side
    $val = validate_coupon($appliedCoupon['code'], $subtotal);
    if ($val['valid']) {
        $discountAmount = $val['discount_amount'];
        $_SESSION['applied_coupon']['discount_amount'] = $discountAmount;
    } else {
        unset($_SESSION['applied_coupon']);
        $appliedCoupon = null;
    }
}

$shippingFee = ($productDeliveryCharge > 0) ? $productDeliveryCharge : (($subtotal >= (float)get_setting('free_shipping_threshold', 999)) ? 0.00 : (float)get_setting('standard_shipping_fee', 0));
$grandTotal = max(0, $subtotal - $discountAmount + $shippingFee);

$pageTitle = 'My Cart (' . $cartData['count'] . ') | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container cart-page-wrap">
    <h1 class="cart-page-title">
        MY SHOPPING CART (<?= $cartData['count'] ?>)
    </h1>

    <?php if (!is_logged_in()): ?>
        <div class="cart-empty-state">
            <div style="font-size: 3.5rem; margin-bottom: 1rem;">🔒</div>
            <h2 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 0.5rem;">Please Log In to View Your Cart</h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">You need to be logged in to add products to your cart, save items, or checkout.</p>
            <div style="display: flex; gap: 0.8rem; justify-content: center; flex-wrap: wrap;">
                <a href="login.php?redirect=cart.php" class="hero-btn-primary" style="padding: 0.85rem 2rem; font-size: 0.95rem;">LOG IN NOW →</a>
                <a href="shop.php" class="hero-btn-secondary" style="padding: 0.85rem 1.8rem; font-size: 0.95rem; color: var(--text); border: 1.5px solid var(--border);">BROWSE STORE</a>
            </div>
        </div>
    <?php elseif (empty($cartData['items'])): ?>
        <div class="cart-empty-state">
            <div style="font-size: 3.5rem; margin-bottom: 1rem;">🛍️</div>
            <h2 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 0.5rem;">Your Cart is Empty</h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Looks like you haven't added anything to your cart yet.</p>
            <a href="shop.php" class="hero-btn-primary" style="padding: 0.85rem 2rem; font-size: 0.95rem;">START SHOPPING →</a>
        </div>
    <?php else: ?>
        <div class="cart-layout-grid">
            <!-- Left: Cart Items List -->
            <div class="cart-items-column">
                <!-- Free Shipping Progress Bar -->
                <?php $threshold = (float)get_setting('free_shipping_threshold', 999); ?>
                <div class="cart-free-shipping-card">
                    <?php if ($subtotal >= $threshold): ?>
                        <div style="color: #16A34A; font-weight: 800; font-size: 0.88rem;">🎉 Yay! You have qualified for FREE Shipping!</div>
                    <?php else: ?>
                        <div style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.5rem;">
                            Add <strong><?= format_price($threshold - $subtotal) ?></strong> more to get <strong>FREE SHIPPING</strong>!
                        </div>
                        <div style="height: 6px; background: #E2E8F0; border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: <?= min(100, round(($subtotal / $threshold) * 100)) ?>%; background: var(--secondary-light);"></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="cart-items-list">
                    <?php foreach ($cartData['items'] as $item): ?>
                        <div class="cart-item-card">
                            <div class="cart-item-img-wrap">
                                <img src="<?= e($item['primary_image']) ?>" alt="<?= e($item['name']) ?>" class="cart-item-img">
                            </div>
                            <div class="cart-item-info">
                                <div class="cart-item-top-row">
                                    <a href="product.php?id=<?= $item['product_id'] ?>" class="cart-item-name"><?= e($item['name']) ?></a>
                                    <button type="button" onclick="updateQty(<?= $item['cart_item_id'] ?>, 0)" class="cart-item-remove-btn" title="Remove Item" aria-label="Remove Item">✕</button>
                                </div>
                                <div class="cart-item-attributes">
                                    <span>Size: <strong><?= e($item['size']) ?></strong></span>
                                    <span>Color: <strong><?= e($item['color']) ?></strong></span>
                                    <span>Delivery: <strong style="color: <?= ($item['delivery_charge'] ?? 0) > 0 ? '#2563EB' : '#16A34A' ?>;"><?= ($item['delivery_charge'] ?? 0) > 0 ? format_price($item['delivery_charge']) : 'FREE' ?></strong></span>
                                </div>
                                <div class="cart-item-bottom-row">
                                    <div class="cart-item-price-tag">
                                        <?= format_price($item['price']) ?>
                                    </div>
                                    <!-- Quantity Controls -->
                                    <div class="cart-qty-pill">
                                        <button type="button" onclick="updateQty(<?= $item['cart_item_id'] ?>, <?= $item['quantity'] - 1 ?>)" aria-label="Decrease quantity">−</button>
                                        <span class="cart-qty-number"><?= $item['quantity'] ?></span>
                                        <button type="button" onclick="updateQty(<?= $item['cart_item_id'] ?>, <?= $item['quantity'] + 1 ?>)" aria-label="Increase quantity">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right: Summary & Checkout CTA -->
            <div class="cart-summary-column">
                <div class="cart-summary-card">
                    <h3 class="cart-summary-heading">Order Summary</h3>

                    <!-- Coupon Input Form -->
                    <div class="cart-coupon-wrap">
                        <input type="text" id="coupon-code-input" placeholder="Coupon Code (e.g. WELCOME10)" value="<?= e($appliedCoupon['code'] ?? '') ?>" class="cart-coupon-field">
                        <button type="button" onclick="applyCoupon()" class="cart-coupon-apply-btn">APPLY</button>
                    </div>

                    <div class="cart-summary-breakdown">
                        <div class="cart-summary-row">
                            <span style="color: var(--text-muted);">Cart Subtotal</span>
                            <span style="font-weight: 700;"><?= format_price($subtotal) ?></span>
                        </div>
                        <?php if ($discountAmount > 0): ?>
                            <div class="cart-summary-row" style="color: #16A34A;">
                                <span>Coupon Discount (<?= e($appliedCoupon['code']) ?>)</span>
                                <span style="font-weight: 800;">- <?= format_price($discountAmount) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="cart-summary-row">
                            <span style="color: var(--text-muted);">Estimated Shipping</span>
                            <span style="font-weight: 700; color: <?= $shippingFee == 0 ? '#16A34A' : 'inherit' ?>;"><?= $shippingFee == 0 ? 'FREE' : format_price($shippingFee) ?></span>
                        </div>
                    </div>

                    <div class="cart-summary-total-row">
                        <span>Total Amount</span>
                        <span style="color: var(--primary);"><?= format_price($grandTotal) ?></span>
                    </div>

                    <a href="checkout.php" class="btn-fintech-pill" style="width: 100%; justify-content: center; padding: 0.5rem 1.4rem !important;">
                        <span class="btn-icon-badge badge-green">🔒</span>
                        <span>PROCEED TO SECURE CHECKOUT</span>
                    </a>

                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="shop.php" style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted);">← Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
@media (min-width: 992px) {
    .cart-layout-grid {
        grid-template-columns: 1.7fr 1fr !important;
    }
}
</style>

<script>
function updateQty(itemId, newQty) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('cart_item_id', itemId);
    formData.append('quantity', newQty);

    fetch('api/cart.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showToast(data.message || 'Error updating quantity', 'error');
            }
        });
}

function applyCoupon() {
    const code = document.getElementById('coupon-code-input').value.trim();
    if (!code) {
        showToast('Please enter a coupon code', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('code', code);

    fetch('api/coupon.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.valid) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 600);
            } else {
                showToast(data.message || 'Invalid coupon', 'error');
            }
        });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
