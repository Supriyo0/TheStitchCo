<?php
/**
 * Header & Responsive Navbar Component
 * The Stitch Co.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/order_functions.php';

$cartData = get_cart_contents();
$wishlistCount = get_wishlist_count($_SESSION['user_id'] ?? null);
$currentUser = current_user();
$pageTitle = $pageTitle ?? STORE_NAME . ' | ' . STORE_TAGLINE;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="Premium heavyweight oversized graphic streetwear designed to elevate your style. 240 GSM Bio-Wash Combed Cotton.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/images/logo.jpg" type="image/jpeg">
</head>
<body>

<!-- Stage 1 & 2 Brand Loader -->
<div id="brand-loader">
    <div class="loader-brand-box">
        <img src="assets/images/logo.jpg" alt="The Stitch Co." style="width: 70px; height: 70px; border-radius: 12px; margin: 0 auto 1rem; border: 1.5px solid rgba(255,255,255,0.2);">
        <h1>THE <span>STITCH</span> CO.</h1>
        <p style="color: #94A3B8; font-size: 0.82rem; font-weight: 700; letter-spacing: 2px; margin-top: 0.3rem;">WEAR YOUR VIBE</p>
        <div class="loader-spinner"></div>
    </div>
</div>

<?php
$showAnnouncement = (int)get_setting('announcement_bar_enabled', 1);
$announcementText = get_setting('announcement_bar_text', 'FREE SHIPPING ON PREPAID ORDERS ABOVE ₹999 🚚 &nbsp;|&nbsp; USE CODE <strong>WELCOME10</strong> FOR 10% OFF');
?>
<?php if ($showAnnouncement && !empty($announcementText)): ?>
    <!-- Top Announcement Banner -->
    <div class="top-announcement">
        <?= $announcementText ?>
    </div>
<?php endif; ?>

<!-- Main Sticky Header Navbar -->
<header class="main-header">
    <div class="container navbar">
        <!-- Left: Mobile Toggle & Brand Logo -->
        <div style="display: flex; align-items: center; gap: 0.85rem;">
            <button class="mobile-toggle" id="mobile-menu-toggle" aria-label="Open menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>
            <a href="index.php" class="nav-brand">
                <div class="nav-brand-text-box">
                    <span class="nav-brand-title">STITCH</span>
                    <span class="nav-brand-sub">WEAR YOUR VIBE</span>
                </div>
            </a>
        </div>

        <!-- Center: Nav Links -->
        <ul class="nav-links">
            <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">HOME</a></li>
            <li><a href="categories.php" class="<?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">CATEGORIES</a></li>
            <li><a href="shop.php?cat=new_arrivals" class="<?= ($_GET['cat'] ?? '') === 'new_arrivals' ? 'active' : '' ?>">NEW ARRIVALS</a></li>
            <li><a href="shop.php?sort=popular" class="<?= ($_GET['sort'] ?? '') === 'popular' && empty($_GET['cat']) ? 'active' : '' ?>">BEST SELLERS</a></li>
            <li><a href="shop.php?cat=oversized" class="<?= ($_GET['cat'] ?? '') === 'oversized' ? 'active' : '' ?>">OVERSIZED</a></li>
        </ul>

        <!-- Search Bar (Desktop) -->
        <div class="header-search">
            <form action="shop.php" method="GET" class="header-search-form">
                <input type="text" name="q" placeholder="Search for products..." value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
                <button type="submit" class="header-search-submit" aria-label="Search">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
            </form>
        </div>

        <!-- Right: Actions (Wishlist, Cart, User) -->
        <div class="nav-actions">
            <!-- Wishlist -->
            <a href="account.php?tab=wishlist" class="nav-icon-btn" title="Wishlist" aria-label="Wishlist">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                <span class="badge-count wishlist-badge-count" style="display: <?= $wishlistCount > 0 ? 'flex' : 'none' ?>;"><?= $wishlistCount ?></span>
            </a>

            <!-- Cart -->
            <a href="cart.php" class="nav-icon-btn" title="Cart" aria-label="Shopping Cart">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                <span class="badge-count cart-badge-count" style="display: <?= $cartData['count'] > 0 ? 'flex' : 'none' ?>;"><?= $cartData['count'] ?></span>
            </a>

            <!-- User / Account -->
            <?php if ($currentUser): ?>
                <a href="account.php" class="nav-icon-btn" title="My Account" style="background: var(--surface-alt); padding: 0; overflow: hidden; border: 1.5px solid #000;">
                    <?php if (!empty($currentUser['avatar'])): ?>
                        <img src="<?= e($currentUser['avatar']) ?>" alt="<?= e($currentUser['fullname']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <span style="font-size: 0.95rem; font-weight: 800; color: #000;"><?= strtoupper(substr($currentUser['fullname'], 0, 1)) ?></span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="nav-icon-btn" title="Login / Register">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile Search Row (Matching Image 2 Blueprint) -->
    <div class="container mobile-search-row">
        <form action="shop.php" method="GET" class="mobile-search-form">
            <input type="text" name="q" placeholder="Search for products..." value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
            <button type="submit" class="mobile-search-submit" aria-label="Search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </button>
        </form>
    </div>
</header>

<!-- Mobile Slide Drawer -->
<div class="mobile-drawer-overlay" id="mobile-drawer-overlay"></div>
<div class="mobile-drawer" id="mobile-drawer">
    <div class="drawer-header">
        <div style="display: flex; align-items: center; gap: 0.6rem;">
            <img src="assets/images/logo.jpg" alt="Logo" style="width: 32px; height: 32px; border-radius: 6px;">
            <span style="font-family: var(--font-heading); font-weight: 900; font-size: 1.1rem; letter-spacing: 0.5px;">THE STITCH CO.</span>
        </div>
        <button class="drawer-close-btn" id="drawer-close-btn">&times;</button>
    </div>

    <!-- Mobile Search Form -->
    <form action="shop.php" method="GET" style="margin-bottom: 1.5rem;">
        <input type="text" name="q" placeholder="Search products..." style="width: 100%; padding: 0.65rem 1rem; border-radius: var(--radius-sm); border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.08); color: #fff;">
    </form>

    <ul class="drawer-menu">
        <li><a href="index.php">🏠 Home</a></li>
        <li><a href="shop.php">👕 Shop All Catalog</a></li>
        <li><a href="shop.php?cat=oversized">🔥 Oversized T-Shirts</a></li>
        <li><a href="shop.php?cat=tshirts">✨ Graphic T-Shirts</a></li>
        <li><a href="shop.php?cat=polo">👔 Structured Polos</a></li>
        <li><a href="shop.php?cat=hoodies">🧥 Heavyweight Hoodies</a></li>
        <li><a href="track-order.php">📦 Track My Order</a></li>
        <?php if ($currentUser): ?>
            <li><a href="account.php">👤 My Account (<?= e($currentUser['fullname']) ?>)</a></li>
            <?php if (in_array($currentUser['role'], ['admin', 'super_admin'])): ?>
                <li><a href="admin/index.php" style="color: #60A5FA;">⚡ Admin Dashboard</a></li>
            <?php endif; ?>
            <li><a href="logout.php" style="color: #EF4444;">🚪 Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">🔑 Login / Register</a></li>
        <?php endif; ?>
    </ul>
</div>
