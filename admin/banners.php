<?php
/**
 * Admin Hero Banners Management
 * The Stitch Co.
 */

$adminTitle = 'Hero Banners & Promo Slider';
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// Handle Toggle Active
if (isset($_GET['toggle'])) {
    $toggleId = (int)$_GET['toggle'];
    $db->prepare("UPDATE hero_banners SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?")->execute([$toggleId]);
    $msg = 'Banner status updated!';
}

// Auto-migrate schema if featured_products_json missing
try {
    $db->query("SELECT featured_products_json FROM hero_banners LIMIT 1");
} catch (Exception $e) {
    try {
        $db->exec("ALTER TABLE hero_banners ADD COLUMN featured_products_json TEXT NULL AFTER image");
    } catch (Exception $ex) {}
}

// Handle Add / Edit Banner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_banner'])) {
    $bannerId = (int)($_POST['banner_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $tag = trim($_POST['tag'] ?? 'NEW ARRIVALS');
    $btnText = trim($_POST['button_text'] ?? 'SHOP NOW');
    $btnUrl = trim($_POST['button_url'] ?? 'shop.php');
    $displayOrder = (int)($_POST['display_order'] ?? 1);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $featuredProducts = isset($_POST['featured_products']) ? (array)$_POST['featured_products'] : [];
    $featuredProducts = array_slice(array_filter(array_map('intval', $featuredProducts)), 0, 3);
    $featuredProductsJson = json_encode($featuredProducts);

    $imagePath = trim($_POST['banner_url'] ?? '');

    if (!empty($_FILES['banner_image']['name'])) {
        $useImgbb = isset($_POST['upload_to_imgbb']);
        if ($useImgbb) {
            $up = upload_to_imgbb($_FILES['banner_image']);
        } else {
            $up = handle_image_upload($_FILES['banner_image'], 'banners', 'hero');
        }
        if ($up['success']) {
            $imagePath = $up['url'] ?? $up['relative_url'];
        }
    }

    if (empty($imagePath) && !empty($_POST['existing_image'])) {
        $imagePath = $_POST['existing_image'];
    }
    if (empty($imagePath)) {
        $imagePath = 'assets/images/banners/hero_oversized.svg';
    }

    try {
        if ($bannerId > 0) {
            $stmt = $db->prepare("UPDATE hero_banners SET title = ?, subtitle = ?, tag = ?, button_text = ?, button_url = ?, image = ?, featured_products_json = ?, display_order = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$title, $subtitle, $tag, $btnText, $btnUrl, $imagePath, $featuredProductsJson, $displayOrder, $isActive, $bannerId]);
            $msg = 'Banner updated successfully!';
        } else {
            $stmt = $db->prepare("INSERT INTO hero_banners (title, subtitle, tag, button_text, button_url, image, featured_products_json, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $subtitle, $tag, $btnText, $btnUrl, $imagePath, $featuredProductsJson, $displayOrder, $isActive]);
            $msg = 'Banner created successfully!';
        }
    } catch (Exception $e) {
        $err = 'Error saving banner: ' . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['del'])) {
    $delId = (int)$_GET['del'];
    try {
        $db->prepare("DELETE FROM hero_banners WHERE id = ?")->execute([$delId]);
        $msg = 'Banner deleted successfully.';
    } catch (Exception $e) {
        $err = 'Error deleting banner: ' . $e->getMessage();
    }
}

$editBanner = null;
$selectedProductIds = [];
if ($action === 'edit' && $editId > 0) {
    $stmt = $db->prepare("SELECT * FROM hero_banners WHERE id = ?");
    $stmt->execute([$editId]);
    $editBanner = $stmt->fetch();
    if ($editBanner && !empty($editBanner['featured_products_json'])) {
        $selectedProductIds = json_decode($editBanner['featured_products_json'], true) ?: [];
    }
}

$categoriesList = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
$allProducts = $db->query("SELECT id, name, price, category, sku, thumbnail FROM products WHERE is_active = 1 ORDER BY id DESC")->fetchAll();
$banners = $db->query("SELECT * FROM hero_banners ORDER BY display_order ASC, id DESC")->fetchAll();
?>

<?php if ($msg): ?>
    <div style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">✓ <?= e($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div style="background: #FEF2F2; border: 1px solid #EF4444; color: #DC2626; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">⚠️ <?= e($err) ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title"><?= $editBanner ? '✏️ Edit Hero Slider Banner' : 'Homepage Hero Slider Banners' ?></h2>
            <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Manage high-impact promotional banners and select up to 3 showcase products shown on the right side.</span>
        </div>
        <?php if ($editBanner): ?>
            <a href="banners.php" style="padding: 0.4rem 1rem; background: var(--admin-primary); color: #fff; border-radius: 6px; font-weight: 700; font-size: 0.8rem; text-decoration: none;">+ Add New Banner</a>
        <?php endif; ?>
    </div>
    <div style="padding: 1.8rem;">
        <form action="banners.php" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin-bottom: 2rem; background: #F9FAFB; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--admin-border);">
            <input type="hidden" name="banner_id" value="<?= $editBanner['id'] ?? 0 ?>">
            <input type="hidden" name="existing_image" value="<?= e($editBanner['image'] ?? '') ?>">

            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Hero Title *</label>
                <input type="text" name="title" required value="<?= e($editBanner['title'] ?? 'OVERSIZED T-SHIRTS') ?>" placeholder="e.g. OVERSIZED T-SHIRTS" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 700;">
            </div>
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Hero Tag / Badge</label>
                <input type="text" name="tag" value="<?= e($editBanner['tag'] ?? 'NEW ARRIVALS') ?>" placeholder="e.g. NEW ARRIVALS or LIMITED DROP" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
            </div>
            <div style="grid-column: span 2;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Subtitle / Material Specs</label>
                <input type="text" name="subtitle" value="<?= e($editBanner['subtitle'] ?? 'Premium Quality | 180-240 GSM | 100% Combed Cotton') ?>" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
            </div>
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Button Text</label>
                <input type="text" name="button_text" value="<?= e($editBanner['button_text'] ?? 'SHOP NOW') ?>" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 700;">
            </div>
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Button Destination Category / Target Page *</label>
                <select name="button_url" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; background: #fff; font-weight: 700;">
                    <option value="shop.php" <?= ($editBanner['button_url'] ?? '') === 'shop.php' ? 'selected' : '' ?>>🛍️ All Catalog (shop.php)</option>
                    <option value="categories.php" <?= ($editBanner['button_url'] ?? '') === 'categories.php' ? 'selected' : '' ?>>📂 Categories Hub (categories.php)</option>
                    <option value="shop.php?cat=new_arrivals" <?= ($editBanner['button_url'] ?? '') === 'shop.php?cat=new_arrivals' ? 'selected' : '' ?>>⚡ New Arrivals Drop (shop.php?cat=new_arrivals)</option>
                    <option value="shop.php?sort=popular" <?= ($editBanner['button_url'] ?? '') === 'shop.php?sort=popular' ? 'selected' : '' ?>>🔥 Best Sellers Collection (shop.php?sort=popular)</option>
                    <optgroup label="Select Category Collection">
                        <?php foreach ($categoriesList as $catItem): 
                            $targetUrl = 'shop.php?cat=' . $catItem['cat_key'];
                            $isCatSelected = (($editBanner['button_url'] ?? '') === $targetUrl || ($editBanner['button_url'] ?? '') === $catItem['cat_key']);
                        ?>
                            <option value="<?= e($targetUrl) ?>" <?= $isCatSelected ? 'selected' : '' ?>>
                                👕 <?= e($catItem['cat_name']) ?> (<?= e($targetUrl) ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Display Slide Order (1, 2, 3...)</label>
                <input type="number" name="display_order" value="<?= e($editBanner['display_order'] ?? 1) ?>" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 700;">
            </div>
            <div>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; font-weight: 700; margin-top: 1.8rem; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" <?= (!isset($editBanner) || !empty($editBanner['is_active'])) ? 'checked' : '' ?>>
                    <span>Active on Homepage</span>
                </label>
            </div>

            <!-- Banner Image Uploader -->
            <div style="grid-column: span 2; background: #FFFFFF; border: 1.5px solid var(--admin-border); border-radius: 6px; padding: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 800; margin-bottom: 0.5rem; color: #1E3A8A;">🖼️ Banner Background / Artwork Image</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.78rem; font-weight: 700; margin-bottom: 0.2rem;">Upload File from Computer (JPG, PNG, WEBP)</label>
                        <input type="file" name="banner_image" accept="image/*" style="width: 100%; font-size: 0.82rem;">
                        <label style="display: flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.78rem; color: #2563EB; margin-top: 0.4rem; cursor: pointer;">
                            <input type="checkbox" name="upload_to_imgbb" value="1" checked>
                            <span>☁️ Auto-Upload to ImgBB CDN</span>
                        </label>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.78rem; font-weight: 700; margin-bottom: 0.2rem;">Or Direct Image URL / Path</label>
                        <input type="text" name="banner_url" value="<?= e($editBanner['image'] ?? '') ?>" placeholder="https://i.ibb.co/... or uploads/banners/..." style="width: 100%; padding: 0.6rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-size: 0.82rem;">
                    </div>
                </div>
                <?php if (!empty($editBanner['image'])): 
                    $previewSrc = (strpos($editBanner['image'], 'http') === 0) ? $editBanner['image'] : '../' . $editBanner['image'];
                ?>
                    <div style="margin-top: 0.8rem; padding-top: 0.8rem; border-top: 1px dashed var(--admin-border); display: flex; align-items: center; gap: 1rem;">
                        <span style="font-size: 0.78rem; font-weight: 700; color: var(--admin-text-muted);">Current Background:</span>
                        <img src="<?= e($previewSrc) ?>" alt="Banner Preview" style="height: 60px; max-width: 160px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
                    </div>
                <?php endif; ?>
            </div>

            <!-- 3 Featured Products Selection with Live Search (Right-Side 3D Stack) -->
            <div style="grid-column: span 2; background: #FFFFFF; border: 1.5px solid #CBD5E1; border-radius: 10px; padding: 1.2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.8rem;">
                    <div>
                        <label style="font-size: 0.92rem; font-weight: 800; color: #0F172A; display: flex; align-items: center; gap: 0.4rem;">
                            <span>🎯</span> Select 3 Featured Products for Right-Side 3D Card Stack
                        </label>
                        <span style="font-size: 0.78rem; color: var(--admin-text-muted);">These 3 products will be displayed as an interactive 3D card carousel on the right side of this banner.</span>
                    </div>
                    <span id="selected-product-count-badge" style="background: #EEF2FF; color: #1E3A8A; font-size: 0.78rem; font-weight: 800; padding: 0.25rem 0.7rem; border-radius: 20px; border: 1px solid #C7D2FE;">
                        Selected: <span id="sel-count"><?= count($selectedProductIds) ?></span> / 3 Max
                    </span>
                </div>

                <!-- Live Search Bar -->
                <div style="margin-bottom: 1rem; position: relative;">
                    <input type="text" id="prod-search-input" placeholder="🔍 Search products by name, SKU, or category to select..." onkeyup="filterProductSelectionList()" style="width: 100%; padding: 0.65rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem;">
                </div>

                <!-- Products Selection Grid -->
                <div id="product-selection-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0.8rem; max-height: 260px; overflow-y: auto; padding: 0.4rem; background: #F8FAFC; border-radius: 8px; border: 1px solid var(--admin-border);">
                    <?php foreach ($allProducts as $prod): 
                        $isProductChecked = in_array((int)$prod['id'], $selectedProductIds);
                        $prodThumb = (strpos($prod['thumbnail'], 'http') === 0) ? $prod['thumbnail'] : '../' . $prod['thumbnail'];
                    ?>
                        <label class="prod-select-card" data-name="<?= strtolower(e($prod['name'])) ?>" data-sku="<?= strtolower(e($prod['sku'] ?? '')) ?>" data-cat="<?= strtolower(e($prod['category'])) ?>" style="display: flex; align-items: center; gap: 0.7rem; padding: 0.6rem; background: #FFFFFF; border: 1.5px solid <?= $isProductChecked ? '#2563EB' : 'var(--admin-border)' ?>; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                            <input type="checkbox" name="featured_products[]" value="<?= $prod['id'] ?>" class="prod-checkbox" <?= $isProductChecked ? 'checked' : '' ?> onchange="handleProductCheck(this)" style="transform: scale(1.15);">
                            <img src="<?= e($prodThumb) ?>" alt="" style="width: 42px; height: 42px; object-fit: cover; border-radius: 6px; border: 1px solid var(--admin-border);">
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.8rem; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--admin-text-main);">
                                    <?= e($prod['name']) ?>
                                </div>
                                <div style="font-size: 0.72rem; color: #10B981; font-weight: 800;">
                                    <?= format_price($prod['price']) ?> <span style="color: var(--admin-text-muted); font-weight: normal;">• <?= ucfirst($prod['category']) ?></span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="grid-column: span 2;">
                <button type="submit" name="save_banner" style="padding: 0.75rem 2rem; background: var(--admin-primary); color: #fff; border: none; border-radius: 6px; font-weight: 800; cursor: pointer;">
                    <?= $editBanner ? 'UPDATE BANNER' : '+ ADD NEW BANNER' ?>
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Preview</th>
                        <th>Title & Tag</th>
                        <th>Subtitle</th>
                        <th>CTA Link</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($banners)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--admin-text-muted); padding: 2rem;">No banners found. Add your first hero banner above!</td></tr>
                    <?php endif; ?>
                    <?php foreach ($banners as $b): 
                        $bImgSrc = (strpos($b['image'], 'http') === 0) ? $b['image'] : '../' . $b['image'];
                    ?>
                        <tr>
                            <td>
                                <img src="<?= e($bImgSrc) ?>" alt="Banner" style="width: 120px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
                            </td>
                            <td>
                                <strong style="font-weight: 800;"><?= e($b['title']) ?></strong><br>
                                <span style="font-size: 0.75rem; color: #2563EB; font-weight: 700;"><?= e($b['tag']) ?></span>
                            </td>
                            <td><span style="font-size: 0.82rem; color: var(--admin-text-muted);"><?= e($b['subtitle']) ?></span></td>
                            <td><code><?= e($b['button_url']) ?></code></td>
                            <td>
                                <a href="banners.php?toggle=<?= $b['id'] ?>" title="Click to toggle status" class="status-pill status-<?= $b['is_active'] ? 'delivered' : 'cancelled' ?>" style="text-decoration: none; cursor: pointer;">
                                    <?= $b['is_active'] ? 'Active' : 'Inactive' ?>
                                </a>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <a href="banners.php?action=edit&id=<?= $b['id'] ?>" style="padding: 0.35rem 0.65rem; background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; border-radius: 4px; font-weight: 700; font-size: 0.75rem; text-decoration: none;">
                                        ✏️ Edit
                                    </a>
                                    <a href="banners.php?del=<?= $b['id'] ?>" onclick="return confirm('Are you sure you want to delete this banner?')" style="padding: 0.35rem 0.65rem; background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; border-radius: 4px; font-weight: 700; font-size: 0.75rem; text-decoration: none;">
                                        🗑️ Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
<script>
function filterProductSelectionList() {
    const input = document.getElementById('prod-search-input').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.prod-select-card');
    cards.forEach(card => {
        const name = card.getAttribute('data-name') || '';
        const sku = card.getAttribute('data-sku') || '';
        const cat = card.getAttribute('data-cat') || '';
        if (name.includes(input) || sku.includes(input) || cat.includes(input)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function handleProductCheck(checkbox) {
    const checked = document.querySelectorAll('.prod-checkbox:checked');
    if (checked.length > 3) {
        alert('You can select a maximum of 3 showcase products for a banner.');
        checkbox.checked = false;
        return;
    }
    
    // Update card border styling
    const card = checkbox.closest('.prod-select-card');
    if (card) {
        card.style.borderColor = checkbox.checked ? '#2563EB' : 'var(--admin-border)';
    }

    const countEl = document.getElementById('sel-count');
    if (countEl) {
        countEl.textContent = document.querySelectorAll('.prod-checkbox:checked').length;
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
