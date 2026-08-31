<?php
/**
 * Shop & Product Catalog Page with Category Selection Hub
 * Full Filter by Category, Style, Size, Price, and Search
 * The Stitch Co. — A Fashion Brand by MJ Company
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_functions.php';

$db = get_db();

$selectedCat = trim($_GET['cat'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');
$sort = trim($_GET['sort'] ?? 'popular');
$sizeFilter = trim($_GET['size'] ?? '');
$maxPrice = (float)($_GET['price'] ?? 0);

// Fetch all active categories with product counts for filter rail
$categoriesList = $db->query("
    SELECT c.*, COUNT(p.id) as count 
    FROM categories c 
    LEFT JOIN products p ON (c.cat_key = p.category AND p.is_active = 1)
    WHERE c.is_active = 1 
    GROUP BY c.id 
    ORDER BY c.display_order ASC, c.id ASC
")->fetchAll();

// Total active product count
$totalProductsCount = (int)$db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();

// Build Product SQL Query
$sql = "SELECT * FROM products WHERE is_active = 1";
$params = [];

if (!empty($selectedCat)) {
    if ($selectedCat === 'new_arrivals') {
        $sql .= " AND is_new_arrival = 1";
    } else {
        $sql .= " AND category = ?";
        $params[] = $selectedCat;
    }
}

if (!empty($searchQuery)) {
    $sql .= " AND (name LIKE ? OR description LIKE ? OR sku LIKE ? OR subcategory LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

if ($maxPrice > 0) {
    $sql .= " AND price <= ?";
    $params[] = $maxPrice;
}

// Sorting logic
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY price DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY id DESC";
        break;
    case 'popular':
    default:
        $sql .= " ORDER BY is_best_seller DESC, is_hero DESC, id DESC";
        break;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Filter sizes in PHP if specified
if (!empty($sizeFilter)) {
    $products = array_filter($products, function($p) use ($sizeFilter) {
        $sizes = json_decode($p['sizes_json'] ?? '[]', true) ?: [];
        return in_array($sizeFilter, $sizes);
    });
}

// Get active category details for title & banner
$currentCatInfo = null;
if (!empty($selectedCat)) {
    foreach ($categoriesList as $c) {
        if ($c['cat_key'] === $selectedCat) {
            $currentCatInfo = $c;
            break;
        }
    }
}

$categoryDisplayName = $currentCatInfo ? $currentCatInfo['cat_name'] : (!empty($selectedCat) ? ucfirst(str_replace('_', ' ', $selectedCat)) : 'All Collections');
$pageTitle = $categoryDisplayName . ' | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Category Header & Filter Rail Styles */
.shop-header-banner {
    background: #FAFAFA;
    border-bottom: 1.5px solid #E2E8F0;
    padding: 1.8rem 0 1.2rem;
    margin-bottom: 1.8rem;
}

.category-filter-rail {
    display: flex;
    gap: 0.6rem;
    overflow-x: auto;
    padding: 0.5rem 0 0.8rem;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}
.category-filter-rail::-webkit-scrollbar {
    display: none;
}

.cat-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.15rem;
    border-radius: 30px;
    font-size: 0.82rem;
    font-weight: 800;
    text-decoration: none;
    white-space: nowrap;
    border: 1.5px solid #E2E8F0;
    background: #FFFFFF;
    color: #334155;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

.cat-pill-btn:hover {
    border-color: #0F172A;
    color: #0F172A;
    transform: translateY(-1px);
}

.cat-pill-btn.active {
    background: #0F172A;
    color: #FFFFFF;
    border-color: #0F172A;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
}

.cat-pill-count {
    font-size: 0.72rem;
    opacity: 0.75;
    font-weight: 700;
}

.shop-controls-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #F1F5F9;
}

.sort-select-box {
    padding: 0.55rem 1rem;
    border: 1.5px solid #E2E8F0;
    border-radius: 8px;
    background: #FFFFFF;
    font-size: 0.82rem;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    outline: none;
}

.active-filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: #EEF2FF;
    color: #1E3A8A;
    font-size: 0.78rem;
    font-weight: 800;
    padding: 0.3rem 0.75rem;
    border-radius: 20px;
    text-decoration: none;
}
</style>

<!-- Shop Header Banner & Category Filter Rail -->
<div class="shop-header-banner">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <div style="font-size: 0.78rem; font-weight: 800; color: #64748B; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 0.2rem;">
                    <a href="index.php" style="color: #64748B; text-decoration: none;">HOME</a> / <a href="categories.php" style="color: #64748B; text-decoration: none;">CATEGORIES</a> / <span style="color: #0F172A;"><?= e(strtoupper($categoryDisplayName)) ?></span>
                </div>
                <h1 style="font-family: var(--font-heading); font-size: 1.85rem; font-weight: 900; color: #0F172A; margin: 0; text-transform: uppercase;">
                    <?= e($categoryDisplayName) ?>
                </h1>
                <?php if ($currentCatInfo && !empty($currentCatInfo['description'])): ?>
                    <p style="font-size: 0.85rem; color: #64748B; margin: 0.3rem 0 0; max-width: 600px;">
                        <?= e($currentCatInfo['description']) ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <a href="categories.php" style="font-size: 0.82rem; font-weight: 800; color: #2563EB; text-decoration: none; display: flex; align-items: center; gap: 0.3rem;">
                <span>View Visual Catalog</span> &rarr;
            </a>
        </div>

        <!-- Horizontal Tap-Friendly Category Selection Pills (Mobile & Desktop) -->
        <div class="category-filter-rail">
            <!-- All Products Pill -->
            <a href="shop.php<?= !empty($searchQuery) ? '?q=' . urlencode($searchQuery) : '' ?>" class="cat-pill-btn <?= empty($selectedCat) ? 'active' : '' ?>">
                <span>🛍️</span>
                <span>All Drops</span>
                <span class="cat-pill-count">(<?= $totalProductsCount ?>)</span>
            </a>

            <!-- New Arrivals Pill -->
            <a href="shop.php?cat=new_arrivals" class="cat-pill-btn <?= $selectedCat === 'new_arrivals' ? 'active' : '' ?>">
                <span>⚡</span>
                <span>New Drops</span>
            </a>

            <!-- Dynamic Categories from Database -->
            <?php foreach ($categoriesList as $catItem): 
                $isActiveCat = ($selectedCat === $catItem['cat_key']);
                $iconEmoji = '👕';
                if ($catItem['cat_key'] === 'oversized') $iconEmoji = '🔥';
                elseif ($catItem['cat_key'] === 'polo') $iconEmoji = '👔';
                elseif ($catItem['cat_key'] === 'hoodies') $iconEmoji = '🧥';
                elseif ($catItem['cat_key'] === 'tshirts') $iconEmoji = '✨';
            ?>
                <a href="shop.php?cat=<?= e($catItem['cat_key']) ?>" class="cat-pill-btn <?= $isActiveCat ? 'active' : '' ?>">
                    <span><?= $iconEmoji ?></span>
                    <span><?= e($catItem['cat_name']) ?></span>
                    <span class="cat-pill-count">(<?= $catItem['count'] ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="container" style="padding-bottom: 5rem;">
    <!-- Controls & Quick Filters Bar -->
    <div class="shop-controls-bar">
        <div style="display: flex; align-items: center; gap: 0.8rem; flex-wrap: wrap;">
            <span style="font-size: 0.85rem; font-weight: 800; color: #0F172A;">
                Showing <?= count($products) ?> streetwear items
            </span>

            <?php if (!empty($selectedCat)): ?>
                <a href="shop.php" class="active-filter-tag" title="Clear category filter">
                    Category: <?= e($categoryDisplayName) ?> ✕
                </a>
            <?php endif; ?>

            <?php if (!empty($searchQuery)): ?>
                <a href="shop.php<?= !empty($selectedCat) ? '?cat=' . urlencode($selectedCat) : '' ?>" class="active-filter-tag" title="Clear search query">
                    Search: "<?= e($searchQuery) ?>" ✕
                </a>
            <?php endif; ?>
        </div>

        <!-- Sort Filter -->
        <div style="display: flex; align-items: center; gap: 0.6rem;">
            <label style="font-size: 0.8rem; font-weight: 700; color: #64748B;">Sort By:</label>
            <form action="shop.php" method="GET" id="sort-form" style="margin: 0;">
                <?php if (!empty($selectedCat)): ?>
                    <input type="hidden" name="cat" value="<?= e($selectedCat) ?>">
                <?php endif; ?>
                <?php if (!empty($searchQuery)): ?>
                    <input type="hidden" name="q" value="<?= e($searchQuery) ?>">
                <?php endif; ?>
                <select name="sort" class="sort-select-box" onchange="document.getElementById('sort-form').submit()">
                    <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>🔥 Best Sellers & Trending</option>
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>⚡ Newest Releases</option>
                    <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>💰 Price: Low to High</option>
                    <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>💎 Price: High to Low</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Products Grid -->
    <div>
        <?php if (count($products) === 0): ?>
            <div style="text-align: center; padding: 4.5rem 1.5rem; background: #FAFAFA; border-radius: 16px; border: 1.5px dashed #CBD5E1; max-width: 600px; margin: 2rem auto;">
                <div style="font-size: 3.5rem; margin-bottom: 1rem;">🛍️</div>
                <h3 style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 900; margin-bottom: 0.5rem; color: #0F172A;">No Products in this Category Yet</h3>
                <p style="color: #64748B; font-size: 0.9rem; margin-bottom: 1.8rem; line-height: 1.5;">We are crafting fresh drops for this collection. Explore all other available streetwear designs!</p>
                <div style="display: flex; gap: 0.8rem; justify-content: center; flex-wrap: wrap;">
                    <a href="shop.php" style="padding: 0.75rem 1.5rem; background: #0F172A; color: #fff; border-radius: 8px; font-weight: 800; font-size: 0.85rem; text-decoration: none;">View All Catalog</a>
                    <a href="categories.php" style="padding: 0.75rem 1.5rem; background: #FFFFFF; color: #0F172A; border: 1.5px solid #E2E8F0; border-radius: 8px; font-weight: 800; font-size: 0.85rem; text-decoration: none;">Browse Categories</a>
                </div>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): 
                    $thumbSrc = (strpos($product['thumbnail'], 'http') === 0) ? $product['thumbnail'] : $product['thumbnail'];
                ?>
                    <div class="product-card">
                        <div class="product-media">
                            <?php if (!empty($product['badge'])): ?>
                                <span class="product-badge"><?= e($product['badge']) ?></span>
                            <?php endif; ?>
                            <button class="wishlist-btn wishlist-toggle-btn" data-product-id="<?= $product['id'] ?>" title="Save to Wishlist">♡</button>
                            <a href="product.php?id=<?= $product['id'] ?>">
                                <img src="<?= e($thumbSrc) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                            </a>
                        </div>
                        <div class="product-info">
                            <span class="product-category"><?= e(ucfirst($product['category'])) ?></span>
                            <a href="product.php?id=<?= $product['id'] ?>" class="product-name"><?= e($product['name']) ?></a>
                            <div class="product-pricing">
                                <span class="price-current"><?= format_price_no_decimals($product['price']) ?></span>
                                <?php if ($product['mrp'] > $product['price']): ?>
                                    <span class="price-mrp"><?= format_price_no_decimals($product['mrp']) ?></span>
                                    <span class="price-discount"><?= round((($product['mrp'] - $product['price']) / $product['mrp']) * 100) ?>% OFF</span>
                                <?php endif; ?>
                            </div>
                            <button class="add-to-cart-btn" onclick="addToCart(<?= $product['id'] ?>, 1, 'M', 'Black')">Add to Cart</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
