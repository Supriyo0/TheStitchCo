<?php
/**
 * Admin Product Management Module
 * Create, Edit, Delete, Stock Controls & Image Uploads
 * The Stitch Co.
 */

$adminTitle = 'Product Management';
require_once __DIR__ . '/header.php';

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$message = '';
$error = '';

// Handle Delete
if ($action === 'delete' && $editId > 0) {
    $db->prepare("DELETE FROM products WHERE id = ?")->execute([$editId]);
    header("Location: products.php?msg=" . urlencode("Product deleted successfully."));
    exit;
}

// Handle Add / Edit POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'oversized');
    $subcategory = trim($_POST['subcategory'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $mrp = (float)($_POST['mrp'] ?? 0);
    $deliveryCharge = (float)($_POST['delivery_charge'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 10);
    $fabric = trim($_POST['fabric'] ?? '100% Super Combed Cotton | 240 GSM Bio Wash');
    $badge = trim($_POST['badge'] ?? 'Best Seller');
    $description = trim($_POST['description'] ?? '');
    $isBestSeller = isset($_POST['is_best_seller']) ? 1 : 0;
    $isNewArrival = isset($_POST['is_new_arrival']) ? 1 : 0;
    $isHero = isset($_POST['is_hero']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $sku = 'TSC-' . strtoupper(substr($category, 0, 2)) . '-' . rand(100, 999);

    if (empty($name) || $price <= 0) {
        $error = 'Please provide product title and valid price.';
    } else {
        // Collect all images (Existing + Uploaded + Direct URLs)
        $galleryImages = [];

        // 1. Direct URLs from textarea (one per line)
        if (!empty($_POST['gallery_urls'])) {
            $urlLines = explode("\n", $_POST['gallery_urls']);
            foreach ($urlLines as $line) {
                $cleaned = trim($line);
                if (!empty($cleaned)) {
                    $galleryImages[] = $cleaned;
                }
            }
        }

        // 2. Multi-File Uploads
        if (!empty($_FILES['gallery_files']['name'][0])) {
            $useImgbb = isset($_POST['upload_to_imgbb']);
            $fileCount = count($_FILES['gallery_files']['name']);

            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['gallery_files']['error'][$i] === UPLOAD_ERR_OK) {
                    $singleFile = [
                        'name' => $_FILES['gallery_files']['name'][$i],
                        'type' => $_FILES['gallery_files']['type'][$i],
                        'tmp_name' => $_FILES['gallery_files']['tmp_name'][$i],
                        'error' => $_FILES['gallery_files']['error'][$i],
                        'size' => $_FILES['gallery_files']['size'][$i]
                    ];

                    if ($useImgbb) {
                        $up = upload_to_imgbb($singleFile);
                    } else {
                        $up = handle_image_upload($singleFile, 'products', 'prod');
                    }

                    if ($up['success']) {
                        $galleryImages[] = $up['url'] ?? $up['relative_url'];
                    }
                }
            }
        }

        // 3. Single thumbnail upload (if provided)
        if (!empty($_FILES['thumbnail']['name'])) {
            $useImgbb = isset($_POST['upload_to_imgbb']);
            $up = $useImgbb ? upload_to_imgbb($_FILES['thumbnail']) : handle_image_upload($_FILES['thumbnail'], 'products', 'prod');
            if ($up['success']) {
                array_unshift($galleryImages, $up['url'] ?? $up['relative_url']);
            }
        }

        // 4. Fallback to existing images if none added
        if (empty($galleryImages) && !empty($_POST['existing_images_json'])) {
            $galleryImages = json_decode($_POST['existing_images_json'], true) ?: [];
        }

        if (empty($galleryImages)) {
            $galleryImages = [!empty($_POST['existing_thumbnail']) ? $_POST['existing_thumbnail'] : 'assets/images/products/tokyo_vibes_black.svg'];
        }

        // The 1st image is ALWAYS the Primary Front Thumbnail
        $thumbnailPath = $galleryImages[0];
        $imagesJson = json_encode(array_values(array_unique($galleryImages)));
        $sizesJson = json_encode(['S', 'M', 'L', 'XL', 'XXL']);

        if ($editId > 0) {
            $stmt = $db->prepare("
                UPDATE products SET 
                    name = ?, category = ?, subcategory = ?, price = ?, mrp = ?, delivery_charge = ?, stock = ?, 
                    fabric = ?, badge = ?, description = ?, is_best_seller = ?, is_new_arrival = ?, 
                    is_hero = ?, is_active = ?, thumbnail = ?, images_json = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $category, $subcategory, $price, $mrp, $deliveryCharge, $stock,
                $fabric, $badge, $description, $isBestSeller, $isNewArrival,
                $isHero, $isActive, $thumbnailPath, $imagesJson, $editId
            ]);
            $message = "Product updated successfully!";
        } else {
            $stmt = $db->prepare("
                INSERT INTO products (
                    name, slug, sku, category, subcategory, price, mrp, delivery_charge, stock, 
                    fabric, badge, description, is_best_seller, is_new_arrival, 
                    is_hero, is_active, thumbnail, images_json, sizes_json
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?
                )
            ");
            $stmt->execute([
                $name, $slug, $sku, $category, $subcategory, $price, $mrp, $deliveryCharge, $stock,
                $fabric, $badge, $description, $isBestSeller, $isNewArrival,
                $isHero, $isActive, $thumbnailPath, $imagesJson, $sizesJson
            ]);
            $message = "New product created successfully!";
        }
        $action = 'list';
    }
}

// Fetch single product for edit
$editProduct = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$editId]);
    $editProduct = $stmt->fetch();
}

$products = $db->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$categories = $db->query("SELECT * FROM categories WHERE is_active = 1")->fetchAll();
?>

<?php if (!empty($message) || !empty($_GET['msg'])): ?>
    <div style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">
        ✓ <?= e($message ?: $_GET['msg']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div style="background: #FEF2F2; border: 1px solid #EF4444; color: #DC2626; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">
        ⚠️ <?= e($error) ?>
    </div>
<?php endif; ?>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <!-- Product Edit / Create Form -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><?= $action === 'edit' ? 'Edit Product' : 'Add New Streetwear Product' ?></h2>
            <a href="products.php" class="view-store-btn">&larr; Back to Catalog</a>
        </div>
        <div style="padding: 1.8rem;">
            <form action="products.php<?= $action === 'edit' ? '?action=edit&id=' . $editId : '' ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="existing_thumbnail" value="<?= e($editProduct['thumbnail'] ?? '') ?>">
                <input type="hidden" name="existing_images_json" value="<?= e($editProduct['images_json'] ?? '') ?>">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Product Name *</label>
                        <input type="text" name="name" required value="<?= e($editProduct['name'] ?? '') ?>" placeholder="e.g. Tokyo Vibes Oversized Graphic T-Shirt" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 8px; font-weight: 700;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Category *</label>
                        <select name="category" required style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 8px; background: #fff;">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= e($c['cat_key']) ?>" <?= ($editProduct['category'] ?? '') === $c['cat_key'] ? 'selected' : '' ?>><?= e($c['cat_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Subcategory / Style</label>
                        <input type="text" name="subcategory" value="<?= e($editProduct['subcategory'] ?? '') ?>" placeholder="e.g. Anime & Japanese Streetwear" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Selling Price (₹) *</label>
                        <input type="number" step="0.01" name="price" required value="<?= e($editProduct['price'] ?? '699.00') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 8px; font-weight: 800;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">MRP / Original Price (₹)</label>
                        <input type="number" step="0.01" name="mrp" value="<?= e($editProduct['mrp'] ?? '1299.00') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Delivery Charge (₹) - Enter 0 for Free Delivery</label>
                        <input type="number" step="0.01" name="delivery_charge" value="<?= e($editProduct['delivery_charge'] ?? '0.00') ?>" placeholder="0.00" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 8px; font-weight: 800; color: #2563EB;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Inventory Stock Qty *</label>
                        <input type="number" name="stock" required value="<?= e($editProduct['stock'] ?? '50') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Badge (e.g. Best Seller, Hot Drop)</label>
                        <input type="text" name="badge" value="<?= e($editProduct['badge'] ?? 'Best Seller') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 8px;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Fabric Specs</label>
                        <input type="text" name="fabric" value="<?= e($editProduct['fabric'] ?? '100% Super Combed Cotton | 240 GSM Bio Wash') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 8px;">
                    </div>

                    <!-- Multi-Image Product Gallery Section -->
                    <div style="grid-column: span 2; background: #F8FAFC; border: 1.5px solid var(--admin-border); border-radius: 12px; padding: 1.4rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem; flex-wrap: wrap; gap: 0.5rem;">
                            <label style="font-size: 0.95rem; font-weight: 900; color: #1E3A8A; margin: 0;">
                                📸 Product Gallery Photos (Upload 3–4+ Images)
                            </label>
                            <span style="background: #FEF3C7; color: #92400E; font-size: 0.75rem; font-weight: 800; padding: 0.25rem 0.6rem; border-radius: 4px; border: 1px solid #FDE68A;">
                                ⭐ 1st Image is ALWAYS showed in Front as the Primary Cover
                            </span>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1rem;">
                            <!-- Method 1: Multi-file Upload -->
                            <div style="background: #FFFFFF; border: 1.5px solid var(--admin-border); border-radius: 8px; padding: 1rem;">
                                <label style="display: block; font-size: 0.82rem; font-weight: 800; margin-bottom: 0.3rem;">
                                    Option A: Upload Multiple Images from Computer
                                </label>
                                <span style="font-size: 0.72rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.5rem;">
                                    Hold Ctrl/Cmd or Shift to select 3 to 4 images at once.
                                </span>
                                <input type="file" name="gallery_files[]" multiple accept="image/*" style="width: 100%; font-size: 0.85rem;">
                                
                                <label style="display: flex; align-items: center; gap: 0.4rem; font-weight: 800; font-size: 0.8rem; color: #2563EB; margin-top: 0.6rem; cursor: pointer;">
                                    <input type="checkbox" name="upload_to_imgbb" value="1" checked>
                                    <span>☁️ Auto-Upload to ImgBB Cloud CDN</span>
                                </label>
                            </div>

                            <!-- Method 2: Direct Image URLs -->
                            <div style="background: #FFFFFF; border: 1.5px solid var(--admin-border); border-radius: 8px; padding: 1rem;">
                                <label style="display: block; font-size: 0.82rem; font-weight: 800; margin-bottom: 0.3rem;">
                                    Option B: Direct ImgBB / Image URLs (One URL per line)
                                </label>
                                <span style="font-size: 0.72rem; color: var(--admin-text-muted); display: block; margin-bottom: 0.5rem;">
                                    Line 1 = Front Image, Line 2 = Back Image, Line 3 = Fit/Detail
                                </span>
                                <?php
                                    $existingUrlsText = '';
                                    if (!empty($editProduct['images_json'])) {
                                        $imgsArr = json_decode($editProduct['images_json'], true) ?: [];
                                        $existingUrlsText = implode("\n", $imgsArr);
                                    } elseif (!empty($editProduct['thumbnail'])) {
                                        $existingUrlsText = $editProduct['thumbnail'];
                                    }
                                ?>
                                <textarea name="gallery_urls" rows="3" placeholder="https://i.ibb.co/image1.jpg&#10;https://i.ibb.co/image2.jpg&#10;https://i.ibb.co/image3.jpg" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-size: 0.8rem; font-family: monospace;"><?= e($existingUrlsText) ?></textarea>
                                <div style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 0.2rem;">
                                    Tip: You can also copy paths from <a href="media.php" target="_blank" style="color: #2563EB; font-weight: 700;">Media Storage &rarr;</a>
                                </div>
                            </div>
                        </div>

                        <!-- Existing Images Gallery Preview -->
                        <?php if (!empty($editProduct)): 
                            $currGallery = json_decode($editProduct['images_json'] ?? '[]', true) ?: [$editProduct['thumbnail']];
                        ?>
                            <div style="margin-top: 1.2rem; padding-top: 1rem; border-top: 1px dashed var(--admin-border);">
                                <div style="font-size: 0.8rem; font-weight: 800; color: #1E293B; margin-bottom: 0.6rem;">
                                    Current Product Photos Gallery (<?= count($currGallery) ?>):
                                </div>
                                <div style="display: flex; gap: 0.8rem; flex-wrap: wrap;">
                                    <?php foreach ($currGallery as $idx => $gImg): 
                                        $gSrc = (strpos($gImg, 'http') === 0) ? $gImg : '../' . $gImg;
                                    ?>
                                        <div style="position: relative; text-align: center; border: 1.5px solid <?= $idx === 0 ? '#2563EB' : 'var(--admin-border)' ?>; border-radius: 6px; padding: 4px; background: #fff;">
                                            <img src="<?= e($gSrc) ?>" alt="" style="width: 65px; height: 75px; object-fit: cover; border-radius: 4px;">
                                            <div style="font-size: 0.68rem; font-weight: 800; color: <?= $idx === 0 ? '#2563EB' : 'var(--admin-text-muted)' ?>; margin-top: 2px;">
                                                <?= $idx === 0 ? '⭐ Front Cover' : 'Pic ' . ($idx + 1) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Full Description</label>
                        <textarea name="description" rows="4" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 8px;"><?= e($editProduct['description'] ?? '') ?></textarea>
                    </div>
                    <div style="grid-column: span 2; display: flex; gap: 1.5rem; flex-wrap: wrap;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; font-size: 0.85rem;">
                            <input type="checkbox" name="is_best_seller" value="1" <?= !empty($editProduct['is_best_seller']) ? 'checked' : '' ?>>
                            <span>Best Seller</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; font-size: 0.85rem;">
                            <input type="checkbox" name="is_new_arrival" value="1" <?= !empty($editProduct['is_new_arrival']) ? 'checked' : '' ?>>
                            <span>New Arrival Drop</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; font-size: 0.85rem;">
                            <input type="checkbox" name="is_active" value="1" <?= (!isset($editProduct) || !empty($editProduct['is_active'])) ? 'checked' : '' ?>>
                            <span>Active / Live on Storefront</span>
                        </label>
                    </div>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" name="save_product" style="padding: 0.8rem 2rem; background: var(--admin-primary); color: #fff; border: none; border-radius: 8px; font-weight: 800; cursor: pointer;">
                        <?= $action === 'edit' ? 'Update Product' : 'Save Product' ?>
                    </button>
                    <a href="products.php" style="padding: 0.8rem 1.5rem; background: var(--admin-bg); color: var(--admin-text-main); border-radius: 8px; font-weight: 700; text-decoration: none;">Cancel</a>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- Products Table Listing -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">All Products (<?= count($products) ?>)</h2>
                <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Manage your streetwear catalog, inventory levels, and prices.</span>
            </div>
            <a href="products.php?action=create" style="padding: 0.5rem 1.2rem; background: var(--admin-primary); color: #fff; border-radius: 8px; font-size: 0.82rem; font-weight: 800; text-decoration: none;">+ Add Product</a>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>MRP</th>
                        <th>Delivery</th>
                        <th>Stock</th>
                        <th>Badge</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): 
                        $thumbSrc = (strpos($p['thumbnail'], 'http') === 0) ? $p['thumbnail'] : '../' . $p['thumbnail'];
                    ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.8rem;">
                                    <img src="<?= e($thumbSrc) ?>" alt="<?= e($p['name']) ?>" style="width: 44px; height: 52px; object-fit: cover; border-radius: 6px; border: 1px solid var(--admin-border);">
                                    <div>
                                        <strong style="font-weight: 700; font-size: 0.9rem;"><?= e($p['name']) ?></strong><br>
                                        <span style="font-size: 0.72rem; color: var(--admin-text-muted);">SKU: <?= e($p['sku']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= ucfirst(e($p['category'])) ?></td>
                            <td><strong style="font-weight: 800;"><?= format_price($p['price']) ?></strong></td>
                            <td style="color: var(--admin-text-muted); text-decoration: line-through;"><?= format_price($p['mrp']) ?></td>
                            <td>
                                <span style="font-weight: 700; color: <?= ($p['delivery_charge'] ?? 0) > 0 ? '#2563EB' : '#10B981' ?>;">
                                    <?= ($p['delivery_charge'] ?? 0) > 0 ? format_price($p['delivery_charge']) : 'FREE' ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-weight: 800; color: <?= $p['stock'] < 10 ? '#EF4444' : '#10B981' ?>;">
                                    <?= $p['stock'] ?> in stock
                                </span>
                            </td>
                            <td><span style="background: #EEF2FF; color: #4338CA; font-size: 0.72rem; font-weight: 800; padding: 0.2rem 0.5rem; border-radius: 4px;"><?= e($p['badge']) ?></span></td>
                            <td>
                                <span class="status-pill status-<?= $p['is_active'] ? 'delivered' : 'cancelled' ?>">
                                    <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="products.php?action=edit&id=<?= $p['id'] ?>" style="color: #2563EB; font-weight: 700; font-size: 0.82rem;">Edit</a>
                                    <span>•</span>
                                    <a href="products.php?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Are you sure you want to delete this product?')" style="color: #EF4444; font-weight: 700; font-size: 0.82rem;">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
