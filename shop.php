<?php
/**
 * Shop & Product Catalog Page
 * Filter by Category, Size, Price, and Search
 * The Stitch Co.
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
$maxPrice = (float)($_GET['price'] ?? 3000);

// Build SQL Query
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
    $sql .= " AND (name LIKE ? OR description LIKE ? OR sku LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

if ($maxPrice > 0) {
    $sql .= " AND price <= ?";
    $params[] = $maxPrice;
}

// Sorting
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
    default:
        $sql .= " ORDER BY is_best_seller DESC, id DESC";
        break;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Filter sizes in PHP if specified
if (!empty($sizeFilter)) {
    $products = array_filter($products, function($p) use ($sizeFilter) {
        $sizes = json_decode($p['sizes_json'] ?? '[]', true);
        return in_array($sizeFilter, $sizes);
    });
}

// Fetch categories for sidebar filter
$catList = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();

$pageTitle = 'Shop Collection | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1.5rem;">
        <a href="index.php">Home</a> &nbsp;/&nbsp; 
        <strong><?= !empty($selectedCat) ? ucfirst(str_replace('_', ' ', $selectedCat)) : (!empty($searchQuery) ? 'Search: ' . e($searchQuery) : 'All Products') ?></strong>
        <span style="float: right;"><?= count($products) ?> Products found</span>
    </div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
        <?php if (count($products) === 0): ?>
            <div style="text-align: center; padding: 4rem 1rem; background: var(--surface); border-radius: var(--radius-md); border: 1px solid var(--border);">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                <h3 style="font-family: var(--font-heading); font-size: 1.4rem; margin-bottom: 0.5rem;">No Products Found</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">We couldn't find any products matching your criteria.</p>
                <a href="shop.php" class="hero-btn" style="background: var(--primary); color: #fff; font-size: 0.85rem; padding: 0.6rem 1.5rem;">View All Products</a>
            </div>
        <?php else: ?>
            <div class="products-grid" style="margin-bottom: 0;">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-media">
                            <?php if (!empty($product['badge'])): ?>
                                <span class="product-badge"><?= e($product['badge']) ?></span>
                            <?php endif; ?>
                            <button class="wishlist-btn wishlist-toggle-btn" data-product-id="<?= $product['id'] ?>" title="Save to Wishlist">♡</button>
                            <a href="product.php?id=<?= $product['id'] ?>">
                                <img src="<?= e($product['thumbnail']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                            </a>
                        </div>
                        <div class="product-info">
                            <span class="product-category"><?= e($product['category']) ?></span>
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
