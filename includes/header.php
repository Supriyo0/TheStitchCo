<?php
/**
 * Header & Responsive Navbar Component
 * The Stitch Co.
 */

// Enable Gzip output compression if supported
if (!ob_get_level() && !headers_sent()) {
    if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
        ob_start('ob_gzhandler');
    }
}

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
    
    <!-- High-Performance Preconnects & Font Loading -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&family=Cinzel:wght@600;700;900&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/festive-themes.css?v=<?= time() ?>">
    <link rel="icon" href="assets/images/logo.jpg" type="image/jpeg">
    <script>
        window.activeTheme = '<?= e($activeTheme) ?>';
    </script>
</head>
<body data-theme="<?= e($activeTheme) ?>">

<?php if ($isMaintenanceActive && is_admin()): ?>
    <!-- Persistent Admin Maintenance Mode Warning Banner -->
    <div style="background: linear-gradient(90deg, #B91C1C 0%, #7F1D1D 100%); color: #FFFFFF; padding: 0.65rem 1rem; font-size: 0.82rem; font-weight: 800; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; z-index: 999999; position: relative; border-bottom: 2px solid #EF4444; box-shadow: 0 4px 15px rgba(185, 28, 28, 0.4);">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.1rem; animation: blink 1.2s infinite alternate;">&#9888;</span>
            <span><strong>STORE IS IN MAINTENANCE MODE:</strong> Public visitors cannot access the site. (Admin Live Preview)</span>
        </div>
        <div style="display: flex; align-items: center; gap: 0.6rem;">
            <a href="admin/settings.php" style="background: #FFFFFF; color: #B91C1C; padding: 0.3rem 0.8rem; border-radius: 4px; text-decoration: none; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.5px;">TURN OFF IN ADMIN</a>
        </div>
    </div>
<?php endif; ?>





<!-- Premium Typographic Brand Loader -->
<div id="brand-loader">
    <div class="loader-brand-box">
        <!-- Theme-aware SVG ring around logo -->
        <div class="loader-logo-ring">
            <svg class="loader-ring-svg" viewBox="0 0 110 110" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="55" cy="55" r="50" stroke-width="1" stroke="currentColor" stroke-dasharray="6 4" opacity="0.3"/>
                <circle cx="55" cy="55" r="44" stroke-width="0.5" stroke="currentColor" opacity="0.15"/>
            </svg>
            <img src="assets/images/logo.jpg" alt="The Stitch Co." class="loader-logo-img">
        </div>

        <div class="loader-brand-wordmark">THE STITCH CO.</div>
        <div class="loader-brand-sub">
            <?php
            $loaderSubs = [
                'durga_puja'  => 'SHUBHO SARODIYA &bull; PUJOR MAHOTSAV',
                'diwali'      => 'SHUBH DEEPAVALI &bull; FESTIVAL OF LIGHTS',
                'winter'      => 'WINTER COLLECTION &bull; FROST EDITION',
                'christmas'   => 'HOLIDAY SEASON &bull; YULETIDE DROPS',
                'freedom'     => 'CELEBRATING FREEDOM &bull; JAI HIND',
                'summer'      => 'SUMMER COLLECTION &bull; SOLAR DROPS',
                'default'     => 'WEAR YOUR VIBE',
            ];
            echo e($loaderSubs[$activeTheme] ?? 'WEAR YOUR VIBE');
            ?>
        </div>
        <div class="loader-progress-line">
            <div class="loader-progress-fill"></div>
        </div>
    </div>
</div>

<?php
$showAnnouncement = (int)get_setting('announcement_bar_enabled', 1);
$defaultAnnouncement = 'FREE SHIPPING ON ALL PREPAID ORDERS ABOVE &#8377;999 &nbsp;|&nbsp; USE CODE <strong>WELCOME10</strong> FOR 10% OFF';
$announcementText = trim(get_setting('announcement_bar_text', $defaultAnnouncement));

// If text is gibberish or empty, fallback to default
if (empty($announcementText) || strlen($announcementText) < 8 || strpos($announcementText, ' ') === false || strpos($announcementText, 'vgykh') !== false) {
    $announcementText = $defaultAnnouncement;
}

// Theme specific default greetings if not customized
if ($activeTheme === 'durga_puja' && (strpos($announcementText, 'WELCOME10') !== false || $announcementText === $defaultAnnouncement)) {
    $announcementText = 'SHUBHO SARODIYA &bull; PUJOR EXCLUSIVE STREETWEAR DROPS &nbsp;|&nbsp; USE CODE <strong>PUJO10</strong> FOR 10% OFF';
} elseif ($activeTheme === 'diwali' && (strpos($announcementText, 'WELCOME10') !== false || $announcementText === $defaultAnnouncement)) {
    $announcementText = 'HAPPY DIWALI &bull; ILLUMINATE YOUR STREETWEAR STYLE &nbsp;|&nbsp; USE CODE <strong>DIWALI200</strong> FOR &#8377;200 OFF';
} elseif ($activeTheme === 'freedom' && (strpos($announcementText, 'WELCOME10') !== false || $announcementText === $defaultAnnouncement)) {
    $announcementText = 'CELEBRATE FREEDOM &bull; PROUDLY CRAFTED IN INDIA &nbsp;|&nbsp; USE CODE <strong>INDIA78</strong>';
} elseif ($activeTheme === 'winter' && (strpos($announcementText, 'WELCOME10') !== false || $announcementText === $defaultAnnouncement)) {
    $announcementText = 'WINTER STREETWEAR DROPS &bull; STAY WARM &amp; STYLISH &nbsp;|&nbsp; USE CODE <strong>WINTER10</strong> FOR 10% OFF';
} elseif ($activeTheme === 'christmas' && (strpos($announcementText, 'WELCOME10') !== false || $announcementText === $defaultAnnouncement)) {
    $announcementText = 'MERRY CHRISTMAS &amp; HAPPY NEW YEAR &bull; HOLIDAY SALE &nbsp;|&nbsp; USE CODE <strong>NOEL15</strong> FOR 15% OFF';
} elseif ($activeTheme === 'summer' && (strpos($announcementText, 'WELCOME10') !== false || $announcementText === $defaultAnnouncement)) {
    $announcementText = 'SUMMER STREET DROPS &bull; 100% BREATHABLE COTTON &nbsp;|&nbsp; USE CODE <strong>SUMMER10</strong>';
}
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
                <!-- Theme-specific orbiting ring around avatar -->
                <div class="profile-orbit-ring-wrap">
                    <!-- Rotating SVG orbit ring -->
                    <svg class="profile-orbit-svg" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <?php if ($activeTheme === 'durga_puja'): ?>
                            <!-- Durga Puja: Rotating diya ring with flame dots -->
                            <circle cx="26" cy="26" r="23" stroke-dasharray="3 5.8" stroke-linecap="round"/>
                            <circle cx="26" cy="26" r="18" stroke-dasharray="1 4" opacity="0.4"/>
                        <?php elseif ($activeTheme === 'diwali'): ?>
                            <!-- Diwali: Jewel faceted orbit -->
                            <polygon points="26,3 49,26 26,49 3,26" stroke-dasharray="4 4"/>
                            <circle cx="26" cy="26" r="20" stroke-dasharray="2 6" opacity="0.5"/>
                        <?php elseif ($activeTheme === 'winter'): ?>
                            <!-- Winter: Hexagonal snowflake ring -->
                            <polygon points="26,3 46.8,14.5 46.8,37.5 26,49 5.2,37.5 5.2,14.5" stroke-dasharray="3 3"/>
                            <circle cx="26" cy="26" r="19" stroke-dasharray="1 5" opacity="0.4"/>
                        <?php elseif ($activeTheme === 'christmas'): ?>
                            <!-- Christmas: Star-octagon ring -->
                            <circle cx="26" cy="26" r="23" stroke-dasharray="5 3.5" stroke-linecap="round"/>
                            <circle cx="26" cy="26" r="17" stroke-dasharray="2 5" opacity="0.45"/>
                        <?php elseif ($activeTheme === 'freedom'): ?>
                            <!-- Freedom: Precise double circle (Ashoka) -->
                            <circle cx="26" cy="26" r="23" stroke-dasharray="24 2"/>
                            <circle cx="26" cy="26" r="18" stroke-dasharray="1 3" opacity="0.35"/>
                        <?php elseif ($activeTheme === 'summer'): ?>
                            <!-- Summer: Radiating starburst -->
                            <circle cx="26" cy="26" r="23" stroke-dasharray="2 4" stroke-linecap="round"/>
                            <circle cx="26" cy="26" r="17" stroke-dasharray="4 3" opacity="0.4"/>
                        <?php else: ?>
                            <!-- Default: Elegant thin dashed circle -->
                            <circle cx="26" cy="26" r="23" stroke-dasharray="4 3" opacity="0.5"/>
                        <?php endif; ?>
                    </svg>

                    <button type="button" class="nav-icon-btn profile-trigger-btn" id="profile-dropdown-btn" onclick="toggleProfileDropdown(event)" aria-label="Account Menu" aria-expanded="false">
                        <?php if ($currentUser): ?>
                            <?php if (!empty($currentUser['avatar'])): ?>
                                <img src="<?= e($currentUser['avatar']) ?>" alt="<?= e($currentUser['fullname']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <span class="profile-initials-avatar"><?= strtoupper(substr($currentUser['fullname'], 0, 1)) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="profile-guest-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </span>
                        <?php endif; ?>
                    </button>
                </div>

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
                                <div style="font-size: 0.65rem; font-weight: 800; color: #16A34A; margin-top: 2px;">&#10003; Verified Customer Account &rarr;</div>
                            </div>
                        </a>

                        <div class="glass-menu-divider"></div>

                        <!-- Section: Orders & Dashboard -->
                        <div class="glass-section-heading">MY ACCOUNT &amp; ORDERS</div>
                        <ul class="glass-dropdown-nav">
                            <li>
                                <a href="dashboard.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                    </span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">Profile Dashboard</span>
                                        <span class="glass-nav-sub">Overview, stats &amp; recent activity</span>
                                    </div>
                                    <span class="glass-nav-arrow">&rarr;</span>
                                </a>
                            </li>
                            <li>
                                <a href="orders.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M5 8a2 2 0 1 0-4 0v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8m-14 0V6a2 2 0 1 1 4 0v2"/></svg>
                                    </span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">My Orders</span>
                                        <span class="glass-nav-sub">Order history &amp; status</span>
                                    </div>
                                    <span class="glass-count-tag"><?= $userOrderCount ?></span>
                                </a>
                            </li>
                            <li>
                                <a href="wishlist.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                    </span>
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
                        <div class="glass-section-heading">SETTINGS &amp; PREFERENCES</div>
                        <ul class="glass-dropdown-nav">
                            <li>
                                <a href="addresses.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    </span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">Saved Addresses</span>
                                        <span class="glass-nav-sub">Doorstep delivery locations</span>
                                    </div>
                                    <span class="glass-count-tag"><?= $userAddressCount ?></span>
                                </a>
                            </li>
                            <li>
                                <a href="profile.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                                    </span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">Profile Settings</span>
                                        <span class="glass-nav-sub">Avatar, phone &amp; security</span>
                                    </div>
                                    <span class="glass-nav-arrow">&rarr;</span>
                                </a>
                            </li>
                            <li>
                                <a href="contact.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    </span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">Help &amp; Support</span>
                                        <span class="glass-nav-sub">WhatsApp &amp; customer care</span>
                                    </div>
                                    <span class="glass-nav-arrow">&rarr;</span>
                                </a>
                            </li>

                            <?php if (in_array($currentUser['role'] ?? '', ['admin', 'super_admin'])): ?>
                                <li>
                                    <a href="admin/index.php" class="glass-nav-item glass-admin-link">
                                        <span class="glass-nav-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                                        </span>
                                        <div class="glass-nav-text">
                                            <span class="glass-nav-title" style="color: #2563EB;">Admin Control Panel</span>
                                            <span class="glass-nav-sub">Manage catalog, orders &amp; site</span>
                                        </div>
                                        <span class="glass-nav-arrow" style="color: #2563EB;">&rarr;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <div class="glass-menu-divider"></div>

                        <div class="glass-logout-wrap">
                            <a href="logout.php" class="glass-logout-btn">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                <span>Log Out (<?= e($currentUser['fullname']) ?>)</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Guest Mode -->
                        <div class="glass-guest-box">
                            <div class="glass-guest-title">Welcome to The Stitch Co.</div>
                            <p class="glass-guest-desc">
                                Sign in to access your orders, track shipments &amp; get exclusive drop perks.
                            </p>
                            <div class="glass-guest-actions">
                                <a href="login.php" class="glass-btn-primary">Sign In</a>
                                <a href="login.php#register" class="glass-btn-secondary">Create Account</a>
                            </div>
                        </div>

                        <div class="glass-menu-divider"></div>

                        <div class="glass-section-heading">QUICK LINKS</div>
                        <ul class="glass-dropdown-nav">
                            <li>
                                <a href="shop.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                    </span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">Shop All Drops</span>
                                        <span class="glass-nav-sub">Explore streetwear catalog</span>
                                    </div>
                                    <span class="glass-nav-arrow">&rarr;</span>
                                </a>
                            </li>
                            <li>
                                <a href="categories.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                    </span>
                                    <div class="glass-nav-text">
                                        <span class="glass-nav-title">Browse Categories</span>
                                        <span class="glass-nav-sub">Oversized tees, hoodies &amp; more</span>
                                    </div>
                                    <span class="glass-nav-arrow">&rarr;</span>
                                </a>
                            </li>
                            <li>
                                <a href="contact.php" class="glass-nav-item">
                                    <span class="glass-nav-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    </span>
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
        <li>
            <a href="index.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>Home</span>
            </a>
        </li>
        <li>
            <a href="shop.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Shop All Catalog</span>
            </a>
        </li>
        <li>
            <a href="categories.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Browse Categories</span>
            </a>
        </li>

        <li style="padding: 0.8rem 1rem 0.3rem; font-size: 0.68rem; font-weight: 900; color: #64748B; text-transform: uppercase; letter-spacing: 1.5px; border-top: 1px solid rgba(255,255,255,0.08); margin-top: 0.5rem;">COLLECTIONS</li>
        
        <?php foreach ($drawerCategories as $dCat): ?>
            <li>
                <a href="shop.php?cat=<?= e($dCat['cat_key']) ?>">
                    <span style="opacity: 0.6; margin-right: 0.5rem;">&bull;</span>
                    <span><?= e($dCat['cat_name']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>

        <li style="padding: 0.8rem 1rem 0.3rem; font-size: 0.68rem; font-weight: 900; color: #94A3B8; text-transform: uppercase; letter-spacing: 1.5px; border-top: 1px solid rgba(255,255,255,0.12); margin-top: 0.5rem;">ACCOUNT &amp; SERVICES</li>
        <?php if ($currentUser): ?>
            <li>
                <a href="dashboard.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span>Account Dashboard</span>
                </a>
            </li>
            <li>
                <a href="orders.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><path d="M5 8h14M5 8a2 2 0 1 0-4 0v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8m-14 0V6a2 2 0 1 1 4 0v2"/></svg>
                    <span>My Orders (<?= $userOrderCount ?>)</span>
                </a>
            </li>
            <li>
                <a href="wishlist.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <span>Saved Wishlist</span>
                </a>
            </li>
            <li>
                <a href="addresses.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span>Saved Addresses (<?= $userAddressCount ?>)</span>
                </a>
            </li>
            <li>
                <a href="profile.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    <span>Profile Settings</span>
                </a>
            </li>
            <?php if (in_array($currentUser['role'], ['admin', 'super_admin'])): ?>
                <li>
                    <a href="admin/index.php" style="color: #60A5FA !important; font-weight: 900;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#60A5FA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        <span>Admin Panel</span>
                    </a>
                </li>
            <?php endif; ?>
            <li>
                <a href="logout.php" style="color: #F87171 !important;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F87171" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>Sign Out (<?= e($currentUser['fullname']) ?>)</span>
                </a>
            </li>
        <?php else: ?>
            <li>
                <a href="login.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    <span>Login / Register</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</div>
