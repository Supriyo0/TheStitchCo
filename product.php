<?php
/**
 * Product Details Page (PDP)
 * The Stitch Co.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_functions.php';

$db = get_db();
$productId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: shop.php");
    exit;
}

$images = json_decode($product['images_json'] ?? '[]', true);
if (empty($images)) {
    $images = [$product['thumbnail']];
}

$sizes = json_decode($product['sizes_json'] ?? '["S","M","L","XL","XXL"]', true);
$colors = json_decode($product['colors_json'] ?? '[]', true);

// Fetch Related Products
$relStmt = $db->prepare("SELECT * FROM products WHERE category = ? AND id != ? AND is_active = 1 LIMIT 4");
$relStmt->execute([$product['category'], $productId]);
$related = $relStmt->fetchAll();

$pageTitle = e($product['name']) . ' | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 1.5rem;">
    <!-- Breadcrumb -->
    <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1.5rem;">
        <a href="index.php">Home</a> &nbsp;/&nbsp; 
        <a href="shop.php?cat=<?= e($product['category']) ?>"><?= ucfirst(e($product['category'])) ?></a> &nbsp;/&nbsp; 
        <strong><?= e($product['name']) ?></strong>
    </div>

    <!-- Product Layout Grid -->
    <div class="pdp-wrap">
        <!-- Left: Image Gallery -->
        <div class="pdp-gallery">
            <div class="pdp-thumbnails">
                <?php foreach ($images as $index => $imgUrl): ?>
                    <div class="thumb-item <?= $index === 0 ? 'active' : '' ?>" data-img-src="<?= e($imgUrl) ?>">
                        <img src="<?= e($imgUrl) ?>" alt="Thumbnail">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="pdp-main-image">
                <img id="pdp-main-img" src="<?= e($images[0]) ?>" alt="<?= e($product['name']) ?>">
            </div>
        </div>

        <!-- Right: Details, Variant Selector, Actions -->
        <div class="pdp-details">
            <span class="pdp-brand"><?= STORE_NAME ?> • STREETWEAR</span>
            <h1 class="pdp-title"><?= e($product['name']) ?></h1>

            <div class="pdp-rating-row">
                <div class="rating-badge">★ <?= number_format($product['rating'], 1) ?></div>
                <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 700;">(<?= $product['review_count'] ?> Verified Customer Reviews)</span>
            </div>

            <div class="pdp-price-row">
                <span class="pdp-price-current"><?= format_price_no_decimals($product['price']) ?></span>
                <?php if ($product['mrp'] > $product['price']): ?>
                    <span class="pdp-price-mrp"><?= format_price_no_decimals($product['mrp']) ?></span>
                    <span class="pdp-discount-badge"><?= round((($product['mrp'] - $product['price']) / $product['mrp']) * 100) ?>% OFF</span>
                <?php endif; ?>
                <span style="font-size: 0.78rem; color: var(--text-muted); margin-left: auto; font-weight: 600;">Inclusive of all taxes</span>
            </div>

            <!-- Size Selector -->
            <div class="variant-block">
                <div class="variant-title">
                    <span>Select Size</span>
                    <span style="color: var(--secondary-light); font-size: 0.8rem; font-weight: 800; cursor: pointer;">Size Guide 📏</span>
                </div>
                <div class="size-pills" id="size-selector-group">
                    <?php foreach ($sizes as $idx => $s): ?>
                        <label>
                            <input type="radio" name="pdp_size" value="<?= e($s) ?>" class="size-pill-radio" <?= $idx === 1 ? 'checked' : '' ?>>
                            <span class="size-pill-label"><?= e($s) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Color Options -->
            <?php if (!empty($colors)): ?>
                <div class="variant-block">
                    <div class="variant-title">
                        <span>Color</span>
                    </div>
                    <div style="display: flex; gap: 0.6rem;">
                        <?php foreach ($colors as $idx => $c): ?>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 0.45rem; padding: 0.4rem 0.85rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 0.82rem; font-weight: 800; background: var(--surface);">
                                <input type="radio" name="pdp_color" value="<?= e($c['name']) ?>" <?= $idx === 0 ? 'checked' : '' ?> style="accent-color: var(--secondary-light);">
                                <span style="display: inline-block; width: 14px; height: 14px; border-radius: 50%; background: <?= e($c['hex']) ?>; border: 1px solid rgba(0,0,0,0.2);"></span>
                                <span><?= e($c['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Stock & Delivery Info -->
            <div style="margin-bottom: 1.4rem; font-size: 0.85rem; font-weight: 800; display: flex; gap: 1rem; flex-wrap: wrap;">
                <?php if ($product['stock'] > 10): ?>
                    <span style="color: #16A34A; background: #ECFDF5; padding: 0.3rem 0.75rem; border-radius: 6px; border: 1px solid #A7F3D0;">● In Stock (<?= $product['stock'] ?> units left)</span>
                <?php elseif ($product['stock'] > 0): ?>
                    <span style="color: #EA580C; background: #FFF7ED; padding: 0.3rem 0.75rem; border-radius: 6px; border: 1px solid #FED7AA;">● Low Stock! Only <?= $product['stock'] ?> items remaining</span>
                <?php else: ?>
                    <span style="color: #DC2626; background: #FEF2F2; padding: 0.3rem 0.75rem; border-radius: 6px; border: 1px solid #FECACA;">● Out of Stock</span>
                <?php endif; ?>

                <span style="color: <?= ($product['delivery_charge'] ?? 0) > 0 ? '#2563EB' : '#16A34A' ?>; background: var(--surface-alt); padding: 0.3rem 0.75rem; border-radius: 6px; border: 1px solid var(--border);">
                    🚚 <?= ($product['delivery_charge'] ?? 0) > 0 ? 'Delivery: ' . format_price($product['delivery_charge']) : 'FREE Express Delivery' ?>
                </span>
            </div>

            <!-- Action Buttons matching Image 3 -->
            <div class="pdp-actions" style="display: flex; flex-direction: column; gap: 0.75rem; margin: 1.5rem 0 1.2rem;">
                <button class="add-to-cart-btn" style="background: #000000; color: #FFFFFF; padding: 0.95rem; font-size: 0.92rem; font-weight: 900; letter-spacing: 0.8px; border-radius: 4px; border: none; cursor: pointer; text-transform: uppercase;" onclick="handlePdpAddToCart(<?= $product['id'] ?>)">
                    ADD TO CART
                </button>
                <button class="btn-buy-now" style="background: #FFFFFF; color: #000000; border: 1.5px solid #000000; padding: 0.9rem; font-size: 0.92rem; font-weight: 900; letter-spacing: 0.8px; border-radius: 4px; cursor: pointer; text-transform: uppercase;" onclick="handlePdpBuyNow(<?= $product['id'] ?>)">
                    BUY NOW
                </button>
            </div>

            <!-- Add to Wishlist Link -->
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <button class="wishlist-toggle-btn-text" onclick="window.addToWishlist(<?= $product['id'] ?>)" style="background: none; border: none; font-size: 0.85rem; font-weight: 800; color: #0F172A; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    ADD TO WISHLIST
                </button>
            </div>

            <!-- PDP Trust Badges (Matching Image 3 Blueprint) -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; padding: 1rem; background: #FAFAFA; border: 1px solid #E2E8F0; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                    <div>
                        <strong style="display: block; color: #000;">Free Shipping</strong>
                        <span style="color: #64748B;">On orders above ₹999</span>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    <div>
                        <strong style="display: block; color: #000;">Easy Returns</strong>
                        <span style="color: #64748B;">7 days return policy</span>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <div>
                        <strong style="display: block; color: #000;">Secure Payment</strong>
                        <span style="color: #64748B;">100% secure payment</span>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path></svg>
                    <div>
                        <strong style="display: block; color: #000;">Support 24/7</strong>
                        <span style="color: #64748B;">We're here to help</span>
                    </div>
                </div>
            </div>

            <!-- Fabric & Specifications Box (HUD) -->
            <div style="background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-md); padding: 1.4rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm);">
                <h4 style="font-family: var(--font-heading); font-size: 0.92rem; font-weight: 900; text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.8px; color: var(--primary);">
                    ⚙️ Fabric & Engineering Specs
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; font-size: 0.82rem;">
                    <div style="background: var(--surface-alt); padding: 0.65rem 0.8rem; border-radius: 6px; border: 1px solid var(--border);">
                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Fabric Weave</span>
                        <strong style="color: var(--text);"><?= e($product['fabric']) ?></strong>
                    </div>
                    <div style="background: var(--surface-alt); padding: 0.65rem 0.8rem; border-radius: 6px; border: 1px solid var(--border);">
                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Streetwear Fit</span>
                        <strong style="color: var(--text);">Drop Shoulder / Boxy Cut</strong>
                    </div>
                    <div style="background: var(--surface-alt); padding: 0.65rem 0.8rem; border-radius: 6px; border: 1px solid var(--border);">
                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Print Technology</span>
                        <strong style="color: var(--text);">High-Density Screen Print</strong>
                    </div>
                    <div style="background: var(--surface-alt); padding: 0.65rem 0.8rem; border-radius: 6px; border: 1px solid var(--border);">
                        <span style="display: block; font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Wash Care</span>
                        <strong style="color: var(--text);">Cold Wash / Inside Out</strong>
                    </div>
                </div>
            </div>

            <!-- Product Description -->
            <div style="background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-md); padding: 1.4rem; margin-bottom: 1.5rem;">
                <h4 style="font-family: var(--font-heading); font-size: 0.92rem; font-weight: 900; text-transform: uppercase; margin-bottom: 0.6rem; letter-spacing: 0.8px;">
                    Drop Overview
                </h4>
                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.7;">
                    <?= nl2br(e($product['description'])) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related)): ?>
        <div style="margin-top: 3rem;">
            <div class="section-header">
                <h2 class="section-title">Similar Streetwear Drops</h2>
            </div>
            <div class="products-grid">
                <?php foreach ($related as $rel): ?>
                    <div class="product-card">
                        <div class="product-media">
                            <a href="product.php?id=<?= $rel['id'] ?>">
                                <img src="<?= e($rel['thumbnail']) ?>" alt="<?= e($rel['name']) ?>">
                            </a>
                        </div>
                        <div class="product-info">
                            <a href="product.php?id=<?= $rel['id'] ?>" class="product-name"><?= e($rel['name']) ?></a>
                            <div class="product-pricing">
                                <span class="price-current"><?= format_price_no_decimals($rel['price']) ?></span>
                            </div>
                            <button class="add-to-cart-btn" onclick="addToCart(<?= $rel['id'] ?>, 1, 'M', 'Black')">Add to Cart</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function getSelectedVariant() {
    const sizeRadio = document.querySelector('input[name="pdp_size"]:checked');
    const colorRadio = document.querySelector('input[name="pdp_color"]:checked');
    return {
        size: sizeRadio ? sizeRadio.value : 'M',
        color: colorRadio ? colorRadio.value : 'Black'
    };
}

function handlePdpAddToCart(productId) {
    const v = getSelectedVariant();
    window.addToCart(productId, 1, v.size, v.color);
}

function handlePdpBuyNow(productId) {
    const v = getSelectedVariant();
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', 1);
    formData.append('size', v.size);
    formData.append('color', v.color);

    fetch('api/cart.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'checkout.php';
            } else if (data.redirect) {
                showToast(data.message || 'Please log in to purchase items.', 'error');
                setTimeout(() => {
                    window.location.href = data.redirect + '?redirect=checkout.php';
                }, 800);
            } else {
                showToast(data.message || 'Error processing purchase', 'error');
            }
        });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
