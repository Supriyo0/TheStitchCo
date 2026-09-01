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

// Check Maintenance Mode
check_maintenance_mode();

$cartData = get_cart_contents();
$wishlistCount = get_wishlist_count($_SESSION['user_id'] ?? null);
$currentUser = current_user();
$pageTitle = $pageTitle ?? STORE_NAME . ' | ' . STORE_TAGLINE;
$isMaintenanceActive = (int)get_setting('maintenance_mode', '0') === 1;
$activeTheme = get_setting('active_theme', 'default');
$themeParticlesEnabled = (int)get_setting('theme_particles_enabled', '1');
$themeSoundEnabled = (int)get_setting('theme_sound_enabled', '1');

$userOrderCount = 0;
$userAddressCount = 0;
if ($currentUser) {
    try {
        $dbHdr = get_db();
        $userOrderCount = (int)$dbHdr->query("SELECT COUNT(*) FROM orders WHERE customer_id = " . (int)$currentUser['id'])->fetchColumn();
        $userAddressCount = (int)$dbHdr->query("SELECT COUNT(*) FROM user_addresses WHERE user_id = " . (int)$currentUser['id'])->fetchColumn();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="Premium heavyweight oversized graphic streetwear designed to elevate your style. 240 GSM Bio-Wash Combed Cotton.">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/festive-themes.css?v=<?= time() ?>">
    <link rel="icon" href="assets/images/logo.jpg" type="image/jpeg">
    <script>
        window.themeSoundMasterEnabled = <?= $themeSoundEnabled ? 'true' : 'false' ?>;
        window.activeTheme = '<?= e($activeTheme) ?>';
    </script>
</head>
<body data-theme="<?= e($activeTheme) ?>">

<?php if ($isMaintenanceActive && is_admin()): ?>
    <!-- Persistent Admin Maintenance Mode Warning Banner -->
    <div style="background: linear-gradient(90deg, #B91C1C 0%, #7F1D1D 100%); color: #FFFFFF; padding: 0.65rem 1rem; font-size: 0.82rem; font-weight: 800; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; z-index: 999999; position: relative; border-bottom: 2px solid #EF4444; box-shadow: 0 4px 15px rgba(185, 28, 28, 0.4);">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.1rem; animation: blink 1.2s infinite alternate;">⚠️</span>
            <span><strong>STORE IS IN MAINTENANCE MODE:</strong> Public visitors cannot access the site. (Admin Live Preview)</span>
        </div>
        <div style="display: flex; align-items: center; gap: 0.6rem;">
            <a href="admin/settings.php" style="background: #FFFFFF; color: #B91C1C; padding: 0.3rem 0.8rem; border-radius: 4px; text-decoration: none; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.5px;">⚡ TURN OFF IN ADMIN</a>
        </div>
    </div>
<?php endif; ?>

<?php
// Map theme to its decoration content
$themeTopDecoContent = '';
if ($activeTheme === 'durga_puja') {
    $themeTopDecoContent = '🌸 🪔 🌼 🌸 🪔 🌼 🌸 🪔 🌼 🌸 🪔 🌼 🌸 🪔 🌼 🌸 🪔 🌼 🌸 🪔 🌼 🌸 🪔 🌼 🌸';
} elseif ($activeTheme === 'diwali') {
    $themeTopDecoContent = '🪔 ✨ 🎇 ✨ 🪔 ✨ 🎇 ✨ 🪔 ✨ 🎇 ✨ 🪔 ✨ 🎇 ✨ 🪔 ✨ 🎇 ✨ 🪔 ✨ 🎇';
} elseif ($activeTheme === 'winter') {
    $themeTopDecoContent = '❄️ ❅ ❄️ ❅ ❄️ ❅ ❄️ ❅ ❄️ ❅ ❄️ ❅ ❄️ ❅ ❄️ ❅ ❄️ ❅ ❄️ ❅ ❄️ ❅ ❄️';
} elseif ($activeTheme === 'christmas') {
    $themeTopDecoContent = '🎄 🔔 ❄️ ⭐ 🎄 🔔 ❄️ ⭐ 🎄 🔔 ❄️ ⭐ 🎄 🔔 ❄️ ⭐ 🎄 🔔 ❄️ ⭐ 🎄';
} elseif ($activeTheme === 'summer') {
    $themeTopDecoContent = '☀️ 🌊 🌴 ☀️ 🌊 🌴 ☀️ 🌊 🌴 ☀️ 🌊 🌴 ☀️ 🌊 🌴 ☀️ 🌊 🌴 ☀️ 🌊 🌴';
}
// Freedom theme uses pure CSS tricolor stripe — no text content needed
?>

<?php if ($activeTheme !== 'default' && !empty($activeTheme)): ?>
<div class="theme-top-decoration" role="presentation" aria-hidden="true">
    <?= $themeTopDecoContent ?>
</div>
<?php endif; ?>



<!-- Stage 1 & 2 Brand Loader with Theme-Specific Festive Animations -->
<div id="brand-loader" class="loader-theme-<?= e($activeTheme) ?>">
    <div class="loader-brand-box">
        <?php if ($activeTheme === 'durga_puja'): ?>
            <!-- Durga Puja Festive Ornaments: Kash Phool, Dhak & Animated Pradip Flame -->
            <div class="loader-festive-motif motif-durga-puja">
                <span class="motif-kash-left" title="Kash Phool">🌾</span>
                <div class="motif-pradip-wrap">
                    <span class="pradip-flame">🔥</span>
                    <span class="pradip-base">🪔</span>
                </div>
                <span class="motif-dhak-center" title="Dhak Rhythm">🥁</span>
                <span class="motif-kash-right" title="Kash Phool">🌾</span>
            </div>
        <?php elseif ($activeTheme === 'diwali'): ?>
            <!-- Diwali Sparkling Pradips & Starbursts -->
            <div class="loader-festive-motif motif-diwali">
                <span class="diwali-sparkle">✨</span>
                <span class="motif-pradip-glow">🪔</span>
                <span class="diwali-firework">🎆</span>
                <span class="motif-pradip-glow">🪔</span>
                <span class="diwali-sparkle">✨</span>
            </div>
        <?php elseif ($activeTheme === 'winter'): ?>
            <!-- Winter Blizzard & Icicles -->
            <div class="loader-festive-motif motif-winter">
                <span class="winter-flake">❄️</span>
                <span class="winter-frost">🧊</span>
                <span class="winter-flake">❄️</span>
            </div>
        <?php elseif ($activeTheme === 'christmas'): ?>
            <!-- Christmas Santa Hat & Noel Bell -->
            <div class="loader-festive-motif motif-christmas">
                <span class="noel-bell">🔔</span>
                <span class="noel-santa">🎅</span>
                <span class="noel-tree">🎄</span>
            </div>
        <?php elseif ($activeTheme === 'freedom'): ?>
            <!-- Tiranga Ribbon -->
            <div class="loader-festive-motif motif-freedom">
                <span class="tiranga-flag">🇮🇳</span>
                <span class="tiranga-chakra">☸️</span>
                <span class="tiranga-flag">🇮🇳</span>
            </div>
        <?php elseif ($activeTheme === 'summer'): ?>
            <!-- Solar Flare -->
            <div class="loader-festive-motif motif-summer">
                <span class="summer-sun">☀️</span>
                <span class="summer-wave">🌊</span>
                <span class="summer-sun">☀️</span>
            </div>
        <?php endif; ?>

        <img src="assets/images/logo.jpg" alt="The Stitch Co." style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; margin: 0 auto 0.8rem; box-shadow: 0 6px 20px rgba(0,0,0,0.2); border: 2px solid rgba(255,255,255,0.8);">

        <h1>THE <span>STITCH</span> CO.</h1>

        <?php if ($activeTheme === 'durga_puja'): ?>
            <p class="loader-theme-subtitle durga-gold-glow">🌸 SHUBHO SARODIYA • শারদোৎসব 🌸</p>
        <?php elseif ($activeTheme === 'diwali'): ?>
            <p class="loader-theme-subtitle diwali-gold-glow">✨ SHUBH DEEPAVALI • FESTIVAL OF LIGHTS ✨</p>
        <?php elseif ($activeTheme === 'winter'): ?>
            <p class="loader-theme-subtitle winter-cyan-glow">❄️ WINTER STREETWEAR DROPS • FROST EDITION ❄️</p>
        <?php elseif ($activeTheme === 'christmas'): ?>
            <p class="loader-theme-subtitle christmas-red-glow">🎄 MERRY CHRISTMAS & HAPPY NEW YEAR 🎄</p>
        <?php elseif ($activeTheme === 'freedom'): ?>
            <p class="loader-theme-subtitle freedom-tricolor-glow">🇮🇳 CELEBRATE FREEDOM • PROUDLY INDIAN 🇮🇳</p>
        <?php elseif ($activeTheme === 'summer'): ?>
            <p class="loader-theme-subtitle summer-amber-glow">☀️ SUMMER STREET DROPS • BREATHABLE COTTON ☀️</p>
        <?php else: ?>
            <p style="color: #94A3B8; font-size: 0.82rem; font-weight: 700; letter-spacing: 2px; margin-top: 0.3rem;">WEAR YOUR VIBE</p>
        <?php endif; ?>

        <div class="loader-spinner"></div>
    </div>
</div>

<?php
$showAnnouncement = (int)get_setting('announcement_bar_enabled', 1);
$defaultAnnouncement = 'FREE SHIPPING ON ALL PREPAID ORDERS ABOVE ₹999 🚚 &nbsp;|&nbsp; USE CODE <strong>WELCOME10</strong> FOR 10% OFF';
$announcementText = trim(get_setting('announcement_bar_text', $defaultAnnouncement));

// If text is gibberish or empty, fallback to default
if (empty($announcementText) || strlen($announcementText) < 8 || strpos($announcementText, ' ') === false || strpos($announcementText, 'vgykh') !== false) {
    $announcementText = $defaultAnnouncement;
}

// Theme specific default greetings if not customized
if ($activeTheme === 'durga_puja' && (strpos($announcementText, 'WELCOME10') !== false || $announcementText === $defaultAnnouncement)) {
    $announcementText = 'SHUBHO SARODIYA! 🌸 PUJOR EXCLUSIVE STREETWEAR DROPS &nbsp;|&nbsp; USE CODE <strong>PUJO10</strong> FOR 10% OFF';
} elseif ($activeTheme === 'diwali' && (strpos($announcementText, 'WELCOME10') !== false || $announcementText === $defaultAnnouncement)) {
    $announcementText = 'HAPPY DIWALI 🪔 ILLUMINATE YOUR STREETWEAR STYLE &nbsp;|&nbsp; USE CODE <strong>DIWALI200</strong> FOR ₹200 OFF';
} elseif ($activeTheme === 'freedom' && (strpos($announcementText, 'WELCOME10') !== false || $announcementText === $defaultAnnouncement)) {
    $announcementText = 'CELEBRATE FREEDOM 🇮🇳 PROUDLY CRAFTED IN INDIA &nbsp;|&nbsp; USE CODE <strong>INDIA78</strong>';
} elseif ($activeTheme === 'winter' && (strpos($announcementText, 'WELCOME10') !== false || $announcementText === $defaultAnnouncement)) {
    $announcementText = 'WINTER STREETWEAR DROPS ❄️ STAY WARM & STYLISH &nbsp;|&nbsp; USE CODE <strong>WINTER10</strong> FOR 10% OFF';
} elseif ($activeTheme === 'christmas' && (strpos($announcementText, 'WELCOME10') !== false || $announcementText === $defaultAnnouncement)) {
    $announcementText = 'MERRY CHRISTMAS & HAPPY NEW YEAR 🎄 HOLIDAY SALE &nbsp;|&nbsp; USE CODE <strong>NOEL15</strong> FOR 15% OFF';
} elseif ($activeTheme === 'summer' && (strpos($announcementText, 'WELCOME10') !== false || $announcementText === $defaultAnnouncement)) {
    $announcementText = 'SUMMER STREET DROPS ☀️ 100% BREATHABLE COTTON &nbsp;|&nbsp; USE CODE <strong>SUMMER10</strong>';
}
?>
<?php if (in_array($activeTheme, ['durga_puja', 'diwali', 'christmas'])): ?>
    <!-- Sparkling Festive Fairy Lights Garland -->
    <div class="fairy-lights-garland" aria-hidden="true">
        <?php for ($i = 0; $i < 18; $i++): ?>
            <span class="fairy-light-bulb"></span>
        <?php endfor; ?>
    </div>
<?php endif; ?>

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
        <div style="display: flex; align-items: center; gap: 0.65rem; min-width: 0;">
            <button type="button" class="mobile-menu-toggle" id="mobile-menu-toggle" onclick="window.openMobileDrawer()" aria-label="Open navigation menu">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>
            <a href="index.php" class="nav-brand">
                <img src="assets/images/logo.jpg" alt="The Stitch Co. Logo" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; box-shadow: 0 2px 8px rgba(0,0,0,0.12); flex-shrink: 0;">
                <div class="nav-brand-text-box">
                    <span class="nav-brand-title">THE STITCH CO.</span>
                    <span class="nav-brand-sub">WEAR YOUR VIBE</span>
                </div>
            </a>
        </div>

        <!-- Center: Primary Desktop Links -->
        <ul class="nav-links">
            <li><a href="index.php" class="<?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : '' ?>">Home</a></li>
            <li><a href="shop.php" class="<?= (basename($_SERVER['PHP_SELF']) === 'shop.php' && empty($_GET['category'])) ? 'active' : '' ?>">All Drops</a></li>
            <li><a href="categories.php" class="<?= (basename($_SERVER['PHP_SELF']) === 'categories.php') ? 'active' : '' ?>">Categories</a></li>
            <li><a href="shop.php?sort=newest">New Arrivals</a></li>
            <li><a href="track-order.php" class="<?= (basename($_SERVER['PHP_SELF']) === 'track-order.php') ? 'active' : '' ?>">Track Order</a></li>
        </ul>

        <!-- Right: Desktop Instant Search Box -->
        <div class="header-search">
            <form action="shop.php" method="GET" class="header-search-form">
                <input type="text" name="q" placeholder="Search for products..." value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
                <button type="submit" class="header-search-submit" aria-label="Search">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
            </form>
        </div>

        <!-- Right: Actions (Wishlist, Cart, Profile Dropdown) -->
        <div class="nav-actions">
            <!-- Wishlist -->
            <a href="wishlist.php" class="nav-icon-btn" title="Wishlist" aria-label="Wishlist">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                <span class="badge-count wishlist-badge-count" style="display: <?= $wishlistCount > 0 ? 'flex' : 'none' ?>;"><?= $wishlistCount ?></span>
            </a>

            <!-- Cart -->
            <a href="cart.php" class="nav-icon-btn" title="Cart" aria-label="Shopping Cart">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                <span class="badge-count cart-badge-count" style="display: <?= $cartData['count'] > 0 ? 'flex' : 'none' ?>;"><?= $cartData['count'] ?></span>
            </a>

            <!-- Profile / Account Liquid Glass Dropdown -->
            <div class="profile-dropdown-wrapper" id="profile-dropdown-wrapper">
                <button type="button" class="nav-icon-btn profile-trigger-btn" id="profile-dropdown-btn" onclick="toggleProfileDropdown(event)" aria-label="Account Menu" aria-expanded="false" style="width: 38px; height: 38px; border-radius: 50%; padding: 0; overflow: hidden; border: none; background: transparent; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                    <?php if ($currentUser): ?>
                        <?php if (!empty($currentUser['avatar'])): ?>
                            <img src="<?= e($currentUser['avatar']) ?>" alt="<?= e($currentUser['fullname']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <span style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: #111827; color: #FFFFFF; font-size: 0.85rem; font-weight: 800; border-radius: 50%;"><?= strtoupper(substr($currentUser['fullname'], 0, 1)) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; border-radius: 50%; background: rgba(0,0,0,0.05);">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                    <?php endif; ?>
                </button>

                <!-- iOS Liquid Glass Dropdown Panel -->
                <div class="ios-liquid-glass-dropdown" id="profile-glass-menu">
                    <?php if ($currentUser): ?>
                        <!-- User Card Link -->
                        <a href="dashboard.php" class="glass-dropdown-user-box" style="text-decoration: none; color: inherit; transition: opacity 0.2s;" title="Open Profile Dashboard">
                            <div class="glass-user-avatar">
                                <?php if (!empty($currentUser['avatar'])): ?>
                                    <img src="<?= e($currentUser['avatar']) ?>" alt="<?= e($currentUser['fullname']) ?>">
                                <?php else: ?>
                                    <span><?= strtoupper(substr($currentUser['fullname'], 0, 1)) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="glass-user-info">
                                <div class="glass-user-name"><?= e($currentUser['fullname']) ?></div>
                                <div class="glass-user-email"><?= e($currentUser['email']) ?></div>
                                <div style="font-size: 0.65rem; font-weight: 800; color: #16A34A; margin-top: 2px;">⚡ Verified Customer Account &rarr;</div>
                            </div>
                        </a>

                        <div class="glass-menu-divider"></div>

                        <!-- Section: Orders & Dashboard -->
                        <div class="glass-section-heading">MY ACCOUNT & ORDERS</div>
                        <ul class="glass-dropdown-nav">
                            <li>
                                <a href="dashboard.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">📊</span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">Profile Dashboard</span>
                                        <span class="glass-nav-sub">Overview, stats & recent activity</span>
                                    </div>
                                    <span class="glass-nav-arrow">&rarr;</span>
                                </a>
                            </li>
                            <li>
                                <a href="orders.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">📦</span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">My Orders</span>
                                        <span class="glass-nav-sub">Order history & status</span>
                                    </div>
                                    <span class="glass-count-tag"><?= $userOrderCount ?></span>
                                </a>
                            </li>
                            <li>
                                <a href="wishlist.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">❤️</span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">My Wishlist</span>
                                        <span class="glass-nav-sub">Saved street drops</span>
                                    </div>
                                    <span class="glass-count-tag" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;"><?= $wishlistCount ?></span>
                                </a>
                            </li>
                        </ul>

                        <div class="glass-menu-divider"></div>

                        <!-- Section: Settings & Addresses -->
                        <div class="glass-section-heading">SETTINGS & PREFERENCES</div>
                        <ul class="glass-dropdown-nav">
                            <li>
                                <a href="addresses.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">📍</span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">Saved Addresses</span>
                                        <span class="glass-nav-sub">Doorstep delivery locations</span>
                                    </div>
                                    <span class="glass-count-tag"><?= $userAddressCount ?></span>
                                </a>
                            </li>
                            <li>
                                <a href="profile.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">⚙️</span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">Profile Settings</span>
                                        <span class="glass-nav-sub">Avatar, phone & security</span>
                                    </div>
                                    <span class="glass-nav-arrow">&rarr;</span>
                                </a>
                            </li>
                            <li>
                                <a href="contact.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">💬</span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">Help & Support</span>
                                        <span class="glass-nav-sub">WhatsApp & customer care</span>
                                    </div>
                                    <span class="glass-nav-arrow">&rarr;</span>
                                </a>
                            </li>

                            <?php if (in_array($currentUser['role'] ?? '', ['admin', 'super_admin'])): ?>
                                <li>
                                    <a href="admin/index.php" class="glass-nav-item glass-admin-link">
                                        <span class="glass-nav-icon">⚡</span>
                                        <div class="glass-nav-text">
                                            <span class="glass-nav-title" style="color: #2563EB;">Admin Control Panel</span>
                                            <span class="glass-nav-sub">Manage catalog, orders & site</span>
                                        </div>
                                        <span class="glass-nav-arrow" style="color: #2563EB;">&rarr;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <div class="glass-menu-divider"></div>

                        <div class="glass-logout-wrap">
                            <a href="logout.php" class="glass-logout-btn">
                                <span>🚪</span>
                                <span>Log Out (<?= e($currentUser['fullname']) ?>)</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Guest Mode -->
                        <div class="glass-guest-box">
                            <div class="glass-guest-title">
                                Welcome to The Stitch Co. 👋
                            </div>
                            <p class="glass-guest-desc">
                                Sign in to access your orders, track shipments & get exclusive drop perks.
                            </p>
                            <div class="glass-guest-actions">
                                <a href="login.php" class="glass-btn-primary">🔑 Sign In</a>
                                <a href="login.php#register" class="glass-btn-secondary">✨ Create Account</a>
                            </div>
                        </div>

                        <div class="glass-menu-divider"></div>

                        <div class="glass-section-heading">QUICK LINKS</div>
                        <ul class="glass-dropdown-nav">
                            <li>
                                <a href="shop.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">🛍️</span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">Shop All Drops</span>
                                        <span class="glass-nav-sub">Explore streetwear catalog</span>
                                    </div>
                                    <span class="glass-nav-arrow">&rarr;</span>
                                </a>
                            </li>
                            <li>
                                <a href="categories.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">✨</span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">Browse Categories</span>
                                        <span class="glass-nav-sub">Oversized tees, hoodies & more</span>
                                    </div>
                                    <span class="glass-nav-arrow">&rarr;</span>
                                </a>
                            </li>
                            <li>
                                <a href="contact.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">💬</span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">Customer Support</span>
                                        <span class="glass-nav-sub">Get help on WhatsApp</span>
                                    </div>
                                    <span class="glass-nav-arrow">&rarr;</span>
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Search Row -->
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
            <img src="assets/images/logo.jpg" alt="Logo" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <span style="font-family: var(--font-heading); font-weight: 900; font-size: 1.1rem; letter-spacing: 0.5px;">THE STITCH CO.</span>
        </div>
        <button class="drawer-close-btn" id="drawer-close-btn">&times;</button>
    </div>

    <!-- Mobile Search Form -->
    <form action="shop.php" method="GET" class="drawer-search-form" style="margin-bottom: 1.2rem;">
        <input type="text" name="q" placeholder="Search products..." style="width: 100%; padding: 0.65rem 1rem; border-radius: var(--radius-sm); border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.08); color: #fff;" autocomplete="off">
    </form>

    <?php
    // Fetch live categories from database
    $drawerCategories = [];
    try {
        $db = get_db();
        $drawerCategories = $db->query("SELECT cat_key, cat_name, icon FROM categories ORDER BY display_order ASC, id ASC")->fetchAll();
    } catch (Exception $e) {}
    ?>

    <ul class="drawer-menu">
        <li><a href="index.php">🏠 Home</a></li>
        <li><a href="shop.php">🛍️ Shop All Catalog</a></li>
        <li><a href="categories.php">✨ Browse Categories</a></li>

        <li style="padding: 0.8rem 1rem 0.3rem; font-size: 0.68rem; font-weight: 900; color: #64748B; text-transform: uppercase; letter-spacing: 1.5px; border-top: 1px solid rgba(255,255,255,0.08); margin-top: 0.5rem;">COLLECTIONS</li>
        
        <?php foreach ($drawerCategories as $dCat): 
            $dIcon = '👕';
            $k = $dCat['cat_key'];
            if ($k === 'oversized') $dIcon = '🔥';
            elseif ($k === 'polo') $dIcon = '👔';
            elseif ($k === 'hoodies') $dIcon = '🧥';
            elseif ($k === 'acid_wash') $dIcon = '⚡';
            elseif ($k === 'bottoms') $dIcon = '👖';
            elseif ($k === 'new_arrivals') $dIcon = '✨';
            elseif ($k === 'tshirts') $dIcon = '✨';
        ?>
            <li>
                <a href="shop.php?cat=<?= e($dCat['cat_key']) ?>">
                    <span><?= $dIcon ?></span>
                    <span><?= e($dCat['cat_name']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>

        <li style="padding: 0.8rem 1rem 0.3rem; font-size: 0.68rem; font-weight: 900; color: #94A3B8; text-transform: uppercase; letter-spacing: 1.5px; border-top: 1px solid rgba(255,255,255,0.12); margin-top: 0.5rem;">ACCOUNT & SERVICES</li>
        <?php if ($currentUser): ?>
            <li><a href="dashboard.php">📊 Account Dashboard</a></li>
            <li><a href="orders.php">📦 My Orders (<?= $userOrderCount ?>)</a></li>
            <li><a href="wishlist.php">❤️ Saved Wishlist</a></li>
            <li><a href="addresses.php">📍 Saved Addresses (<?= $userAddressCount ?>)</a></li>
            <li><a href="profile.php">⚙️ Profile Settings</a></li>
            <?php if (in_array($currentUser['role'], ['admin', 'super_admin'])): ?>
                <li><a href="admin/index.php" style="color: #60A5FA !important; font-weight: 900;">⚡ Admin Panel</a></li>
            <?php endif; ?>
            <li><a href="logout.php" style="color: #F87171 !important;">🚪 Sign Out (<?= e($currentUser['fullname']) ?>)</a></li>
        <?php else: ?>
            <li><a href="login.php">🔑 Login / Register</a></li>
        <?php endif; ?>
    </ul>
</div>
