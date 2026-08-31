<?php
/**
 * Storefront Homepage
 * The Stitch Co. — A Fashion Brand by MJ Company
 * Complete Responsive Mobile & Desktop Streetwear Experience
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_functions.php';

$db = get_db();

// 1. Fetch All Active Hero Banners for 2-3s Auto-Slider
$heroStmt = $db->query("SELECT * FROM hero_banners WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
$heroBanners = $heroStmt->fetchAll();
if (empty($heroBanners)) {
    $heroBanners = [[
        'title' => 'OVERSIZED. PREMIUM. YOU.',
        'subtitle' => 'Crafted to stand out with 240 GSM Bio-Wash Cotton',
        'tag' => 'NEW DROP SUMMER \'26',
        'button_text' => 'SHOP NOW',
        'button_url' => 'shop.php?cat=oversized',
        'image' => 'assets/images/banners/hero_oversized.svg'
    ]];
}

// 2. Fetch Active Categories
$catStmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC");
$categories = $catStmt->fetchAll();

// 3. Fetch Featured Drops for the Banner Sliding Rail
$featuredDropsStmt = $db->query("SELECT id, name, price, mrp, thumbnail, category FROM products WHERE is_active = 1 ORDER BY is_best_seller DESC, id DESC LIMIT 6");
$featuredDrops = $featuredDropsStmt->fetchAll();

// 4. Fetch Best Sellers Products (2-Column Mobile Grid)
$bestSellersStmt = $db->query("SELECT * FROM products WHERE is_active = 1 AND is_best_seller = 1 ORDER BY id DESC LIMIT 6");
$bestSellers = $bestSellersStmt->fetchAll();

// 5. Fetch New Arrivals Drops
$newArrivalsStmt = $db->query("SELECT * FROM products WHERE is_active = 1 AND is_new_arrival = 1 ORDER BY id DESC LIMIT 6");
$newArrivals = $newArrivalsStmt->fetchAll();

$pageTitle = STORE_NAME . ' | ' . STORE_TAGLINE . ' - Premium Streetwear';
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Instant Responsive Hero Styles: Side-by-Side Horizontal Row on Mobile matching PC */
@media (max-width: 991px) {
  .hero-slide-item {
    min-height: 250px !important;
    height: auto !important;
    display: flex !important;
    align-items: center !important;
  }
  .hero-slide-grid {
    flex-direction: row !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 1.2rem 0.85rem !important;
    gap: 0.6rem !important;
    text-align: left !important;
  }
  .hero-slide-content {
    flex: 1 1 54% !important;
    max-width: 55% !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: flex-start !important;
    text-align: left !important;
    padding-right: 0.2rem !important;
  }
  .hero-slide-tag {
    font-size: 0.62rem !important;
    letter-spacing: 1px !important;
    margin-bottom: 0.25rem !important;
  }
  .hero-slide-title {
    font-family: var(--font-heading);
    font-size: clamp(1.1rem, 4.2vw, 1.45rem) !important;
    line-height: 1.12 !important;
    margin-bottom: 0.35rem !important;
    word-break: break-word !important;
  }
  .hero-slide-subtitle {
    font-size: 0.68rem !important;
    line-height: 1.25 !important;
    margin-bottom: 0.75rem !important;
    display: -webkit-box !important;
    -webkit-line-clamp: 2 !important;
    -webkit-box-orient: vertical !important;
    overflow: hidden !important;
  }
  .hero-slide-actions {
    display: flex !important;
    justify-content: flex-start !important;
  }
  .hero-btn-white {
    padding: 0.45rem 0.85rem !important;
    font-size: 0.72rem !important;
    letter-spacing: 0.4px !important;
    border-radius: 5px !important;
  }
  .carousel-dots-wrap {
    justify-content: flex-start !important;
    margin-top: 0.65rem !important;
    gap: 0.35rem !important;
  }
  .carousel-dot {
    width: 6px !important;
    height: 6px !important;
  }
  .carousel-dot.active {
    width: 16px !important;
  }
  .hero-slide-right-card {
    flex: 0 0 45% !important;
    max-width: 45% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
  }
  .hero-3d-showcase-container {
    width: 170px !important;
    height: 200px !important;
    overflow: visible !important;
  }
  .hero-3d-stack-card {
    width: 130px !important;
    height: 185px !important;
    padding: 0.5rem !important;
    border-radius: 12px !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.6) !important;
  }
  .hero-3d-title {
    font-size: 0.66rem !important;
    margin-top: 0.1rem !important;
  }
  .hero-3d-cat-tag {
    font-size: 0.55rem !important;
  }
  .hero-3d-icon-badge {
    width: 12px !important;
    height: 12px !important;
    font-size: 0.5rem !important;
  }
  .hero-3d-img-box {
    height: 85px !important;
    border-radius: 6px !important;
    margin: 0.25rem 0 !important;
  }
  .hero-3d-footer {
    padding-top: 0.25rem !important;
  }
  .hero-3d-price {
    font-size: 0.78rem !important;
  }
  .hero-3d-btn {
    font-size: 0.55rem !important;
    padding: 0.2rem 0.45rem !important;
  }
  .hero-3d-stack-card.pos-center {
    z-index: 10 !important;
    transform: translateX(0) scale(1) translateZ(0) !important;
    opacity: 1 !important;
  }
  .hero-3d-stack-card.pos-left {
    z-index: 5 !important;
    transform: translateX(-35px) scale(0.9) rotateY(10deg) !important;
    opacity: 0.82 !important;
    filter: none !important;
  }
  .hero-3d-stack-card.pos-right {
    z-index: 5 !important;
    transform: translateX(35px) scale(0.9) rotateY(-10deg) !important;
    opacity: 0.82 !important;
    filter: none !important;
  }
}
</style>

<!-- 1. Hero Auto-Sliding Horizontal Carousel (Matching Blueprint 1 & 2) -->
<div class="hero-carousel-wrap">
    <div class="container hero-carousel-container" id="hero-carousel">
        
        <!-- Track containing banner slides -->
        <div class="hero-carousel-track" id="hero-carousel-track" style="width: <?= count($heroBanners) * 100 ?>%;">
            <?php foreach ($heroBanners as $idx => $b): 
                $bImgSrc = get_media_url($b['image'] ?? '');
                
                // Fetch 3 selected showcase products
                $bProdIds = !empty($b['featured_products_json']) ? json_decode($b['featured_products_json'], true) : [];
                $bProducts = [];
                if (!empty($bProdIds) && is_array($bProdIds)) {
                    $inClause = implode(',', array_map('intval', $bProdIds));
                    if (!empty($inClause)) {
                        $bProducts = $db->query("SELECT id, name, price, mrp, category, thumbnail, badge FROM products WHERE id IN ($inClause) AND is_active = 1")->fetchAll();
                    }
                }
                if (empty($bProducts)) {
                    $bProducts = $db->query("SELECT id, name, price, mrp, category, thumbnail, badge FROM products WHERE is_active = 1 ORDER BY is_best_seller DESC, is_hero DESC, id DESC LIMIT 3")->fetchAll();
                }
            ?>
                <div class="hero-slide-item" style="width: <?= 100 / count($heroBanners) ?>%;">
                    
                    <!-- Background Dark Overlay with Uploaded Banner Image -->
                    <div class="hero-slide-bg" style="background-image: linear-gradient(90deg, rgba(10,10,10,0.92) 0%, rgba(10,10,10,0.65) 50%, rgba(10,10,10,0.85) 100%), url('<?= e($bImgSrc) ?>'); background-size: cover; background-position: center; opacity: 1;"></div>
                    
                    <div class="hero-slide-grid">
                        <!-- Slide Left Content -->
                        <div class="hero-slide-content">
                            <span class="hero-slide-tag">
                                <?= e($b['tag'] ?? 'NEW ARRIVALS') ?>
                            </span>
                            
                            <h1 class="hero-slide-title">
                                <?= e($b['title']) ?>
                            </h1>
                            
                            <p class="hero-slide-subtitle">
                                <?= e($b['subtitle'] ?? 'Premium Quality | 180 GSM | 100% Cotton') ?>
                            </p>
                            
                            <!-- CTA Action Button -->
                            <div class="hero-slide-actions">
                                <a href="<?= e($b['button_url'] ?? 'shop.php?cat=oversized') ?>" class="hero-btn-white">
                                    <?= e($b['button_text'] ?? 'SHOP NOW') ?> &rarr;
                                </a>
                            </div>

                            <!-- Carousel Indicators / Dots (Bottom Left) -->
                            <div class="carousel-dots-wrap">
                                <?php for ($i = 0; $i < count($heroBanners); $i++): ?>
                                    <button type="button" class="carousel-dot <?= $i === 0 ? 'active' : '' ?>" onclick="goToHeroSlide(<?= $i ?>)" aria-label="Slide <?= $i + 1 ?>"></button>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Slide Right: 3D Product Showcase Stack (Center + Left/Right Peeking) -->
                        <div class="hero-slide-right-card">
                            <div class="hero-3d-showcase-container" id="hero-3d-stack-<?= $idx ?>">
                                <?php foreach ($bProducts as $pIdx => $prod): 
                                    $pThumb = get_media_url($prod['thumbnail'] ?? '');
                                    $posClass = ($pIdx === 0) ? 'pos-center' : (($pIdx === 1) ? 'pos-right' : (($pIdx === 2) ? 'pos-left' : 'pos-hidden'));
                                ?>
                                    <a href="product.php?id=<?= $prod['id'] ?>" class="hero-3d-stack-card <?= $posClass ?>" data-index="<?= $pIdx ?>" onclick="handle3DCardClick(event, this, <?= $idx ?>)">
                                        <!-- Card Header -->
                                        <div>
                                            <div class="hero-3d-header">
                                                <span class="hero-3d-icon-badge">★</span>
                                                <span class="hero-3d-cat-tag"><?= e(strtoupper($prod['category'])) ?></span>
                                            </div>
                                            <div class="hero-3d-title" title="<?= e($prod['name']) ?>">
                                                <?= e($prod['name']) ?>
                                            </div>
                                        </div>

                                        <!-- Product Image -->
                                        <div class="hero-3d-img-box">
                                            <img src="<?= e($pThumb) ?>" alt="<?= e($prod['name']) ?>" loading="lazy" onerror="this.onerror=null; this.src='assets/images/placeholder.svg';">
                                        </div>

                                        <!-- Card Footer (Price & Action) -->
                                        <div class="hero-3d-footer">
                                            <div>
                                                <div class="hero-3d-price"><?= format_price_no_decimals($prod['price']) ?></div>
                                                <?php if ($prod['mrp'] > $prod['price']): ?>
                                                    <div style="font-size: 0.68rem; color: #94A3B8; text-decoration: line-through;"><?= format_price_no_decimals($prod['mrp']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <span class="hero-3d-btn">VIEW &rarr;</span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- 2. Categories Row (Dynamic Circular Category Roundels from Database) -->
<section class="container categories-section-wrap">
    <div class="categories-header-row">
        <h2 class="categories-section-heading">CATEGORIES</h2>
        <a href="shop.php" class="section-view-all">View All &rarr;</a>
    </div>

    <div class="categories-scroll-track">
        <?php foreach ($categories as $catIdx => $cat): 
            $catImg = !empty($cat['image']) ? $cat['image'] : 'assets/images/products/good_vibes_white.svg';
            $catIcon = $cat['icon'] ?? 'tshirt';
        ?>
            <a href="shop.php?cat=<?= e($cat['cat_key']) ?>" class="category-roundel-item">
                <div class="category-roundel-avatar <?= $catIdx === 1 ? 'active' : '' ?> <?= $cat['cat_key'] === 'new_arrivals' ? 'category-avatar-new' : '' ?>">
                    <span class="category-badge-icon">
                        <?php if ($catIcon === 'box'): ?>
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                        <?php elseif ($catIcon === 'polo'): ?>
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.38 3.46L16 2a4 4 0 01-8 0L3.62 3.46a2 2 0 00-1.34 2.23l.58 3.47a1 1 0 00.99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 002-2V10h2.15a1 1 0 00.99-.84l.58-3.47a2 2 0 00-1.34-2.23z"/><path d="M10 2v6h4V2"/></svg>
                        <?php elseif ($catIcon === 'hoodie'): ?>
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 10l8-6 8 6v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"></path></svg>
                        <?php elseif ($catIcon === 'spark'): ?>
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        <?php else: ?>
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.38 3.46L16 2a4 4 0 01-8 0L3.62 3.46a2 2 0 00-1.34 2.23l.58 3.47a1 1 0 00.99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 002-2V10h2.15a1 1 0 00.99-.84l.58-3.47a2 2 0 00-1.34-2.23z"/></svg>
                        <?php endif; ?>
                    </span>
                    <?php if ($cat['cat_key'] === 'new_arrivals'): ?>
                        <span class="category-new-text">NEW<br>ARRIVALS</span>
                    <?php else: ?>
                        <img src="<?= e($catImg) ?>" alt="<?= e($cat['cat_name']) ?>">
                    <?php endif; ?>
                </div>
                <span class="category-roundel-name"><?= e($cat['cat_name']) ?></span>
                <span class="category-roundel-sub"><?= e($cat['subtext'] ?? '') ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- 3. Trust Features Bar (4 Items matching Image 1 & 2 Blueprint) -->
<div class="container trust-bar-container">
    <div class="trust-bar-grid">
        <div class="trust-bar-col">
            <span class="trust-col-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
            </span>
            <div>
                <h4 class="trust-col-title">PREMIUM QUALITY</h4>
                <span class="trust-col-desc">100% Original Products</span>
            </div>
        </div>
        <div class="trust-bar-col">
            <span class="trust-col-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            </span>
            <div>
                <h4 class="trust-col-title">EASY RETURNS</h4>
                <span class="trust-col-desc">Hassle Free Returns</span>
            </div>
        </div>
        <div class="trust-bar-col">
            <span class="trust-col-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </span>
            <div>
                <h4 class="trust-col-title">SECURE PAYMENT</h4>
                <span class="trust-col-desc">100% Secure Checkout</span>
            </div>
        </div>
        <div class="trust-bar-col">
            <span class="trust-col-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path></svg>
            </span>
            <div>
                <h4 class="trust-col-title">CUSTOMER SUPPORT</h4>
                <span class="trust-col-desc">We're Here to Help</span>
            </div>
        </div>
    </div>
</div>

<!-- 4. Best Sellers (Matching Image 1 & 2 Blueprint) -->
<section class="container bestsellers-section">
    <div class="bestsellers-header-row">
        <div>
            <h2 class="bestsellers-title">BEST SELLERS</h2>
            <span class="bestsellers-sub">Heavyweight 240 GSM Bio Wash Cotton</span>
        </div>
        <a href="shop.php?sort=popularity" class="section-view-all">
            View All &gt;
        </a>
    </div>

    <!-- Product Grid: 4 Columns on Desktop, 2 on Mobile -->
    <div class="products-grid">
        <?php foreach ($bestSellers as $p): 
            $pImg = (strpos($p['thumbnail'], 'http') === 0) ? $p['thumbnail'] : $p['thumbnail'];
        ?>
            <div class="product-card">
                <div class="product-media">
                    <?php if (!empty($p['badge'])): ?>
                        <span class="product-badge"><?= e($p['badge']) ?></span>
                    <?php endif; ?>

                    <button class="wishlist-toggle-btn" data-product-id="<?= $p['id'] ?>" title="Add to Wishlist" aria-label="Add to Wishlist">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    </button>

                    <a href="product.php?id=<?= $p['id'] ?>">
                        <img src="<?= e($pImg) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                    </a>
                </div>

                <div class="product-info">
                    <h3 class="product-name">
                        <a href="product.php?id=<?= $p['id'] ?>"><?= e($p['name']) ?></a>
                    </h3>

                    <div class="product-pricing">
                        <span class="price-current"><?= format_price_no_decimals($p['price']) ?></span>
                        <?php if ($p['mrp'] > $p['price']): ?>
                            <span class="price-mrp"><?= format_price_no_decimals($p['mrp']) ?></span>
                            <span class="price-discount"><?= round((($p['mrp'] - $p['price']) / $p['mrp']) * 100) ?>% OFF</span>
                        <?php endif; ?>
                    </div>

                    <button class="add-to-cart-btn" onclick="window.addToCart(<?= $p['id'] ?>)">
                        ADD TO CART
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- 5. Special Offer Banner (Dynamic from Admin Settings) -->
<?php
$promoBadge = get_setting('promo_badge', 'SPECIAL OFFER');
$promoTitle = get_setting('promo_title', 'GET 10% OFF ON YOUR FIRST ORDER');
$promoCode = get_setting('promo_code', 'WELCOME10');
$promoBtnText = get_setting('promo_button_text', 'SHOP NOW');
$promoBtnUrl = get_setting('promo_button_url', 'shop.php');
$promoImg = get_setting('promo_image', 'assets/images/products/chaos_club_green.svg');
?>
<div class="container promo-banner-container">
    <div class="promo-banner-card">
        <div class="promo-banner-content">
            <span class="promo-banner-badge"><?= e($promoBadge) ?></span>
            <h2 class="promo-banner-title">
                <?= nl2br(e($promoTitle)) ?>
            </h2>
            <a href="<?= e($promoBtnUrl) ?>" class="promo-banner-btn">
                <?= e($promoBtnText) ?>
            </a>
        </div>

        <!-- Stamp Badge -->
        <?php if (!empty($promoCode)): ?>
            <div class="promo-stamp-badge">
                <span>USE CODE<br><strong><?= e($promoCode) ?></strong></span>
            </div>
        <?php endif; ?>

        <!-- Right Model Image -->
        <div class="promo-banner-img">
            <img src="<?= e($promoImg) ?>" alt="Special Offer Drop">
        </div>
    </div>
</div>

<!-- JavaScript for 2-3s Auto-Sliding Horizontal Banner -->
<script>
let currentSlideIdx = 0;
const totalSlides = <?= count($heroBanners) ?>;
let heroInterval = null;

function updateHeroCarousel() {
    const track = document.getElementById('hero-carousel-track');
    if (!track) return;

    const translatePercent = -(currentSlideIdx * (100 / totalSlides));
    track.style.transform = `translateX(${translatePercent}%)`;

    // Update dots
    document.querySelectorAll('.carousel-dot').forEach((dot, idx) => {
        if (idx === currentSlideIdx) {
            dot.style.width = '24px';
            dot.style.background = '#3B82F6';
        } else {
            dot.style.width = '8px';
            dot.style.background = 'rgba(255,255,255,0.4)';
        }
    });
}

function nextHeroSlide() {
    if (totalSlides <= 1) return;
    currentSlideIdx = (currentSlideIdx + 1) % totalSlides;
    updateHeroCarousel();
}

function goToHeroSlide(idx) {
    currentSlideIdx = idx;
    updateHeroCarousel();
    resetHeroTimer();
}

function resetHeroTimer() {
    if (heroInterval) clearInterval(heroInterval);
    heroInterval = setInterval(nextHeroSlide, 2800); // 2.8s auto-scroll
}

// 3D Product Showcase Card Interaction
function handle3DCardClick(e, cardEl, slideIdx) {
    if (!cardEl.classList.contains('pos-center')) {
        e.preventDefault();
        rotate3DStack(slideIdx, parseInt(cardEl.getAttribute('data-index')));
    }
}

function rotate3DStack(slideIdx, targetIdx) {
    const container = document.getElementById('hero-3d-stack-' + slideIdx);
    if (!container) return;
    const cards = container.querySelectorAll('.hero-3d-stack-card');
    const total = cards.length;
    if (total <= 1) return;

    cards.forEach((c) => {
        const cIdx = parseInt(c.getAttribute('data-index'));
        c.classList.remove('pos-center', 'pos-left', 'pos-right', 'pos-hidden');
        if (cIdx === targetIdx) {
            c.classList.add('pos-center');
        } else if (cIdx === (targetIdx + 1) % total) {
            c.classList.add('pos-right');
        } else if (cIdx === (targetIdx - 1 + total) % total) {
            c.classList.add('pos-left');
        } else {
            c.classList.add('pos-hidden');
        }
    });
}

// Start auto-slider on load
document.addEventListener('DOMContentLoaded', () => {
    resetHeroTimer();
    
    // Pause on hover
    const carouselEl = document.getElementById('hero-carousel');
    if (carouselEl) {
        carouselEl.addEventListener('mouseenter', () => {
            if (heroInterval) clearInterval(heroInterval);
        });
        carouselEl.addEventListener('mouseleave', () => {
            resetHeroTimer();
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
