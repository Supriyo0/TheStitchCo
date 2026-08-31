<?php
/**
 * Visual Categories Hub & Collection Directory
 * The Stitch Co. — A Fashion Brand by MJ Company
 * Complete Mobile & Desktop Category Experience
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_functions.php';

$db = get_db();

// Fetch all active categories with dynamic product counts
$categories = $db->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON (c.cat_key = p.category AND p.is_active = 1)
    WHERE c.is_active = 1 
    GROUP BY c.id 
    ORDER BY c.display_order ASC, c.id ASC
")->fetchAll();

$pageTitle = 'Explore Categories | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<style>
.categories-hub-hero {
    background: linear-gradient(180deg, #111827 0%, #0B0F17 100%);
    color: #FFFFFF;
    padding: 3rem 1rem 3.5rem;
    text-align: center;
    position: relative;
    overflow: hidden;
    margin-bottom: 2.5rem;
}

.categories-hub-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(circle at center, rgba(37, 99, 235, 0.15) 0%, transparent 70%);
    pointer-events: none;
}

.categories-hub-title {
    font-family: var(--font-heading);
    font-size: 2.2rem;
    font-weight: 900;
    letter-spacing: -0.5px;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
}

.categories-hub-title span {
    color: #3B82F6;
}

.categories-hub-subtitle {
    font-size: 0.95rem;
    color: #94A3B8;
    max-width: 550px;
    margin: 0 auto;
    line-height: 1.5;
}

.categories-grid-hub {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 4rem;
}

.category-card-hub {
    background: #FFFFFF;
    border: 1.5px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    position: relative;
}

.category-card-hub:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 30px -10px rgba(0, 0, 0, 0.12);
    border-color: #2563EB;
}

.category-card-image-box {
    height: 200px;
    position: relative;
    background: #F1F5F9;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.category-card-image-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.category-card-hub:hover .category-card-image-box img {
    transform: scale(1.05);
}

.category-card-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(17, 24, 39, 0.85);
    backdrop-filter: blur(8px);
    color: #FFFFFF;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.3rem 0.7rem;
    border-radius: 20px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.category-card-count {
    position: absolute;
    bottom: 12px;
    right: 12px;
    background: #FFFFFF;
    color: #0F172A;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}

.category-card-body {
    padding: 1.4rem;
    display: flex;
    flex-direction: column;
    flex: 1;
    justify-content: space-between;
}

.category-card-title {
    font-family: var(--font-heading);
    font-size: 1.25rem;
    font-weight: 900;
    color: #0F172A;
    margin-bottom: 0.3rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.category-card-desc {
    font-size: 0.82rem;
    color: #64748B;
    line-height: 1.45;
    margin-bottom: 1.2rem;
}

.category-card-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.82rem;
    font-weight: 800;
    color: #1E3A8A;
    padding-top: 0.8rem;
    border-top: 1px dashed #E2E8F0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.category-card-hub:hover .category-card-btn {
    color: #2563EB;
}

@media (max-width: 640px) {
    .categories-hub-hero {
        padding: 2rem 1rem 2.5rem;
        margin-bottom: 1.5rem;
    }
    .categories-hub-title {
        font-size: 1.65rem;
    }
    .categories-grid-hub {
        grid-template-columns: 1fr 1fr;
        gap: 0.85rem;
    }
    .category-card-image-box {
        height: 140px;
    }
    .category-card-body {
        padding: 0.9rem;
    }
    .category-card-title {
        font-size: 0.95rem;
    }
    .category-card-desc {
        display: none;
    }
    .category-card-btn span:first-child {
        font-size: 0.72rem;
    }
}
</style>

<!-- Categories Hub Hero Banner -->
<div class="categories-hub-hero">
    <div class="container">
        <h1 class="categories-hub-title">SHOP BY <span>COLLECTION</span></h1>
        <p class="categories-hub-subtitle">
            Browse our signature streetwear silhouettes, crafted with ultra-heavyweight combed cotton and iconic drop-cut aesthetics.
        </p>
    </div>
</div>

<div class="container">
    <!-- Quick Horizontal Pill Bar -->
    <div style="display: flex; gap: 0.6rem; overflow-x: auto; padding-bottom: 1.2rem; margin-bottom: 1.5rem; -webkit-overflow-scrolling: touch; scrollbar-width: none;">
        <a href="shop.php" style="white-space: nowrap; padding: 0.5rem 1.1rem; background: #111827; color: #fff; border-radius: 20px; font-size: 0.82rem; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 0.4rem;">
            <span>🛍️</span> All Drops
        </a>
        <?php foreach ($categories as $c): ?>
            <a href="shop.php?cat=<?= e($c['cat_key']) ?>" style="white-space: nowrap; padding: 0.5rem 1.1rem; background: #FFFFFF; color: #334155; border: 1.5px solid #E2E8F0; border-radius: 20px; font-size: 0.82rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 0.4rem;">
                <?= e($c['cat_name']) ?> <span style="font-size: 0.72rem; color: #94A3B8;">(<?= $c['product_count'] ?>)</span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Category Grid Cards -->
    <div class="categories-grid-hub">
        <?php foreach ($categories as $cat): 
            $catImage = !empty($cat['image']) ? $cat['image'] : 'assets/images/products/tokyo_vibes_black.jpg';
        ?>
            <a href="shop.php?cat=<?= e($cat['cat_key']) ?>" class="category-card-hub">
                <div class="category-card-image-box">
                    <span class="category-card-badge">THE STITCH CO.</span>
                    <img src="<?= e($catImage) ?>" alt="<?= e($cat['cat_name']) ?>" loading="lazy">
                    <span class="category-card-count"><?= $cat['product_count'] ?> Items</span>
                </div>
                <div class="category-card-body">
                    <div>
                        <div class="category-card-title">
                            <span><?= e($cat['cat_name']) ?></span>
                            <span style="font-size: 1rem; color: #94A3B8;">&rarr;</span>
                        </div>
                        <p class="category-card-desc">
                            <?= e($cat['description'] ?? 'Explore heavyweight premium streetwear crafted for effortless everyday presence.') ?>
                        </p>
                    </div>
                    <div class="category-card-btn">
                        <span>EXPLORE COLLECTION</span>
                        <span>&rarr;</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
