<?php
/**
 * Standalone My Wishlist Page
 * The Stitch Co.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_functions.php';

require_login('login.php');

$db = get_db();
$currentUser = current_user();
$userId = (int)$currentUser['id'];

// Fetch Wishlist Items
$stmt = $db->prepare("
    SELECT p.*, w.id AS wishlist_id FROM wishlists w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ? AND p.is_active = 1
    ORDER BY w.id DESC
");
$stmt->execute([$userId]);
$wishlistItems = $stmt->fetchAll();

$orderCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE customer_id = $userId")->fetchColumn();
$addressCount = (int)$db->query("SELECT COUNT(*) FROM user_addresses WHERE user_id = $userId")->fetchColumn();

$pageTitle = 'My Wishlist | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem 5rem;">
    <!-- Top Breadcrumb & Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 900; color: #0F172A; text-transform: uppercase; margin: 0;">
                My Wishlist (<?= count($wishlistItems) ?>)
            </h1>
            <span style="font-size: 0.85rem; color: #64748B;">Your curated collection of saved heavyweight streetwear drops.</span>
        </div>
        <div>
            <a href="shop.php" class="hero-btn-primary" style="padding: 0.6rem 1.3rem; font-size: 0.85rem; text-decoration: none;">🛍️ Continue Shopping</a>
        </div>
    </div>

    <!-- Account Navigation Sub-Bar (iOS Glass Floating Dock) -->
    <div style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.7); border-radius: 16px; padding: 0.5rem; margin-bottom: 2.5rem; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.08); display: flex; gap: 0.5rem; overflow-x: auto; scrollbar-width: none;">
        <a href="dashboard.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">📊 Dashboard</a>
        <a href="orders.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">📦 My Orders (<?= $orderCount ?>)</a>
        <a href="wishlist.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: #0F172A; color: #FFFFFF; font-weight: 800; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">❤️ Wishlist (<?= count($wishlistItems) ?>)</a>
        <a href="addresses.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">📍 Saved Addresses (<?= $addressCount ?>)</a>
        <a href="profile.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">⚙️ Profile Settings</a>
    </div>

    <!-- Wishlist Content Grid -->
    <?php if (empty($wishlistItems)): ?>
        <div style="text-align: center; padding: 4.5rem 1rem; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border-radius: 20px; border: 1.5px solid rgba(255, 255, 255, 0.7); box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06);">
            <div style="font-size: 3.5rem; margin-bottom: 0.8rem;">❤️</div>
            <h3 style="font-size: 1.3rem; font-weight: 900; color: #0F172A; margin-bottom: 0.4rem;">Your Wishlist is Empty</h3>
            <p style="color: #64748B; font-size: 0.88rem; margin-bottom: 1.5rem;">Save your favorite oversized t-shirts and hoodies to grab them quickly!</p>
            <a href="shop.php" class="hero-btn-primary" style="font-size: 0.85rem; padding: 0.7rem 1.8rem; text-decoration: none;">Explore Streetwear Catalog</a>
        </div>
    <?php else: ?>
        <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1.5rem;">
            <?php foreach ($wishlistItems as $prod): 
                $images = json_decode($prod['images'] ?? '[]', true) ?: [];
                $thumb = $prod['featured_image'] ?: ($images[0] ?? 'assets/images/placeholder.jpg');
            ?>
                <div class="product-card" style="background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.75); border-radius: 18px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.06); transition: transform 0.2s ease;">
                    <div style="position: relative; aspect-ratio: 4/5; overflow: hidden;">
                        <a href="product.php?slug=<?= urlencode($prod['slug']) ?>">
                            <img src="<?= e($thumb) ?>" alt="<?= e($prod['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </a>
                        <button type="button" onclick="toggleWishlist(<?= $prod['id'] ?>, this)" class="wishlist-btn active" title="Remove from wishlist" style="position: absolute; top: 10px; right: 10px; background: rgba(255, 255, 255, 0.9); border: none; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #EF4444; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                        </button>
                    </div>
                    <div style="padding: 1.2rem; display: flex; flex-direction: column; flex: 1; justify-content: space-between;">
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 800; color: #64748B; text-transform: uppercase; margin-bottom: 0.2rem;"><?= e($prod['fit_type'] ?? 'Oversized') ?></div>
                            <h3 style="font-family: var(--font-heading); font-size: 1rem; font-weight: 900; color: #0F172A; margin-bottom: 0.5rem;">
                                <a href="product.php?slug=<?= urlencode($prod['slug']) ?>" style="text-decoration: none; color: inherit;"><?= e($prod['name']) ?></a>
                            </h3>
                            <div style="display: flex; align-items: baseline; gap: 0.5rem; margin-bottom: 1rem;">
                                <span style="font-size: 1.15rem; font-weight: 900; color: #0F172A;"><?= format_price($prod['price']) ?></span>
                                <?php if (!empty($prod['mrp']) && $prod['mrp'] > $prod['price']): ?>
                                    <span style="font-size: 0.82rem; color: #94A3B8; text-decoration: line-through;"><?= format_price($prod['mrp']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <a href="product.php?slug=<?= urlencode($prod['slug']) ?>" class="hero-btn-primary" style="width: 100%; text-align: center; text-decoration: none; font-size: 0.82rem; padding: 0.65rem 0.5rem; border-radius: 8px;">
                            SELECT SIZE & BUY ⚡
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
