<?php
/**
 * Standalone Profile Dashboard Page
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

// Stats
$orderCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE customer_id = $userId")->fetchColumn();
$wishlistCount = (int)$db->query("SELECT COUNT(*) FROM wishlists WHERE user_id = $userId")->fetchColumn();
$addressCount = (int)$db->query("SELECT COUNT(*) FROM user_addresses WHERE user_id = $userId")->fetchColumn();

// Recent 3 Orders
$stmt = $db->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC LIMIT 3");
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll();

$pageTitle = 'Profile Dashboard | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem 5rem;">
    <!-- Top Breadcrumb & Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 900; color: #0F172A; text-transform: uppercase; margin: 0;">
                Profile Dashboard
            </h1>
            <span style="font-size: 0.85rem; color: #64748B;">Welcome back, <strong><?= e($currentUser['fullname']) ?></strong>! Manage your account and orders.</span>
        </div>
        <div style="display: flex; gap: 0.8rem; flex-wrap: wrap;">
            <a href="orders.php" class="btn-fintech-pill">
                <span class="btn-icon-badge badge-blue">📦</span>
                <span>View Orders</span>
            </a>
            <a href="shop.php" class="hero-btn-secondary">
                <span class="btn-icon-badge badge-amber">🛍️</span>
                <span>Explore Catalog</span>
            </a>
        </div>
    </div>

    <!-- Account Navigation Sub-Bar (Groww Style Pill Subdock) -->
    <div class="account-subdock">
        <a href="dashboard.php" style="padding: 0.6rem 1.1rem; border-radius: 9999px; background: #0F172A; color: #FFFFFF; font-weight: 800; font-size: 0.85rem; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.4rem;">
            <span>📊</span> <span>Dashboard</span>
        </a>
        <a href="orders.php" style="padding: 0.6rem 1.1rem; border-radius: 9999px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.4rem;">
            <span>📦</span> <span>My Orders (<?= $orderCount ?>)</span>
        </a>
        <a href="wishlist.php" style="padding: 0.6rem 1.1rem; border-radius: 9999px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.4rem;">
            <span>❤️</span> <span>Wishlist (<?= $wishlistCount ?>)</span>
        </a>
        <a href="addresses.php" style="padding: 0.6rem 1.1rem; border-radius: 9999px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.4rem;">
            <span>📍</span> <span>Saved Addresses (<?= $addressCount ?>)</span>
        </a>
        <a href="profile.php" style="padding: 0.6rem 1.1rem; border-radius: 9999px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.4rem;">
            <span>⚙️</span> <span>Profile Settings</span>
        </a>
    </div>

    <!-- Quick Stats Cards (iOS Frosted Glass) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.2rem; margin-bottom: 2.5rem;">
        
        <!-- Total Orders Card -->
        <a href="orders.php" style="text-decoration: none; color: inherit; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.7); border-radius: 18px; padding: 1.5rem; box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06); transition: transform 0.2s ease;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
                <span style="font-size: 0.85rem; font-weight: 800; color: #64748B; text-transform: uppercase;">Total Orders</span>
                <span style="font-size: 1.5rem;">📦</span>
            </div>
            <div style="font-family: var(--font-heading); font-size: 2.2rem; font-weight: 900; color: #0F172A;"><?= $orderCount ?></div>
            <span style="font-size: 0.75rem; color: #2563EB; font-weight: 700; display: block; margin-top: 0.4rem;">View all order tracking &rarr;</span>
        </a>

        <!-- Wishlist Card -->
        <a href="wishlist.php" style="text-decoration: none; color: inherit; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.7); border-radius: 18px; padding: 1.5rem; box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06); transition: transform 0.2s ease;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
                <span style="font-size: 0.85rem; font-weight: 800; color: #64748B; text-transform: uppercase;">Saved Wishlist</span>
                <span style="font-size: 1.5rem;">❤️</span>
            </div>
            <div style="font-family: var(--font-heading); font-size: 2.2rem; font-weight: 900; color: #EF4444;"><?= $wishlistCount ?></div>
            <span style="font-size: 0.75rem; color: #EF4444; font-weight: 700; display: block; margin-top: 0.4rem;">View saved street pieces &rarr;</span>
        </a>

        <!-- Addresses Card -->
        <a href="addresses.php" style="text-decoration: none; color: inherit; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.7); border-radius: 18px; padding: 1.5rem; box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06); transition: transform 0.2s ease;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
                <span style="font-size: 0.85rem; font-weight: 800; color: #64748B; text-transform: uppercase;">Delivery Addresses</span>
                <span style="font-size: 1.5rem;">📍</span>
            </div>
            <div style="font-family: var(--font-heading); font-size: 2.2rem; font-weight: 900; color: #10B981;"><?= $addressCount ?></div>
            <span style="font-size: 0.75rem; color: #10B981; font-weight: 700; display: block; margin-top: 0.4rem;">Manage delivery locations &rarr;</span>
        </a>

        <!-- Profile Card -->
        <a href="profile.php" style="text-decoration: none; color: inherit; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.7); border-radius: 18px; padding: 1.5rem; box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06); transition: transform 0.2s ease;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
                <span style="font-size: 0.85rem; font-weight: 800; color: #64748B; text-transform: uppercase;">Account Security</span>
                <span style="font-size: 1.5rem;">⚙️</span>
            </div>
            <div style="font-family: var(--font-heading); font-size: 1.3rem; font-weight: 900; color: #0F172A; margin-top: 0.6rem;">Active & Secure</div>
            <span style="font-size: 0.75rem; color: #64748B; font-weight: 700; display: block; margin-top: 0.4rem;">Edit profile & password &rarr;</span>
        </a>
    </div>

    <!-- Recent Orders Section -->
    <div style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.7); border-radius: 20px; padding: 1.8rem; box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.4rem;">
            <h3 style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 900; text-transform: uppercase; margin: 0;">
                Recent Orders Preview
            </h3>
            <a href="orders.php" style="font-size: 0.82rem; font-weight: 800; color: #2563EB; text-decoration: none;">View All Orders (<?= $orderCount ?>) &rarr;</a>
        </div>

        <?php if (empty($recentOrders)): ?>
            <div style="text-align: center; padding: 3rem 1rem;">
                <div style="font-size: 2.5rem; margin-bottom: 0.6rem;">📦</div>
                <h4 style="font-size: 1.05rem; font-weight: 800; margin-bottom: 0.4rem;">No Orders Placed Yet</h4>
                <p style="font-size: 0.84rem; color: #64748B; margin-bottom: 1.2rem;">When you order streetwear drops, they will appear here with live tracking.</p>
                <a href="shop.php" class="hero-btn-primary" style="font-size: 0.84rem; padding: 0.6rem 1.4rem; text-decoration: none;">Start Shopping</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($recentOrders as $ord): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 1rem 1.2rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <div style="font-weight: 900; font-size: 0.95rem; color: #0F172A;">
                                #<?= e($ord['order_number']) ?>
                            </div>
                            <div style="font-size: 0.78rem; color: #64748B; margin-top: 2px;">
                                <?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?> • <strong><?= format_price($ord['total_price']) ?></strong> (<?= e($ord['payment_method']) ?>)
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.8rem;">
                            <span class="status-pill status-<?= strtolower(str_replace(' ', '', $ord['status'])) ?>">
                                <?= e($ord['status']) ?>
                            </span>
                            <a href="orders.php" style="padding: 0.4rem 0.8rem; background: #0F172A; color: #FFFFFF; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-decoration: none;">
                                Order Details &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
