<?php
/**
 * Admin Hero Banners Management
 * The Stitch Co.
 */

$adminTitle = 'Hero Banners & Promo Slider';
require_once __DIR__ . '/header.php';

$msg = '';

// Handle Add Banner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_banner'])) {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $tag = trim($_POST['tag'] ?? 'NEW ARRIVALS');
    $btnText = trim($_POST['button_text'] ?? 'SHOP NOW');
    $btnUrl = trim($_POST['button_url'] ?? 'shop.php');

    $imagePath = trim($_POST['banner_url'] ?? '');
    if (empty($imagePath)) {
        $imagePath = 'assets/images/banners/hero_oversized.svg';
    }

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

    $displayOrder = (int)($_POST['display_order'] ?? 1);

    $stmt = $db->prepare("INSERT INTO hero_banners (title, subtitle, tag, button_text, button_url, image, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $subtitle, $tag, $btnText, $btnUrl, $imagePath, $displayOrder]);
    $msg = 'Banner created successfully!';
}

// Handle Delete
if (isset($_GET['del'])) {
    $db->prepare("DELETE FROM hero_banners WHERE id = ?")->execute([(int)$_GET['del']]);
    $msg = 'Banner deleted.';
}

$banners = $db->query("SELECT * FROM hero_banners ORDER BY display_order ASC, id DESC")->fetchAll();
?>

<?php if ($msg): ?>
    <div style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">✓ <?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Homepage Hero Slider Banners</h2>
            <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Manage high-impact promotional banners shown at the top of your homepage.</span>
        </div>
    </div>
    <div style="padding: 1.8rem;">
        <form action="banners.php" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin-bottom: 2rem; background: #F9FAFB; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--admin-border);">
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Hero Title *</label>
                <input type="text" name="title" required value="OVERSIZED T-SHIRTS" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
            </div>
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Hero Tag / Badge</label>
                <input type="text" name="tag" value="NEW ARRIVALS" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
            </div>
            <div style="grid-column: span 2;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Subtitle / Material Specs</label>
                <input type="text" name="subtitle" value="Premium Quality | 180-240 GSM | 100% Combed Cotton" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
            </div>
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Button Text</label>
                <input type="text" name="button_text" value="SHOP NOW" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
            </div>
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Button Destination Category / URL *</label>
                <select name="button_url" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; background: #fff; font-weight: 700;">
                    <option value="shop.php?cat=oversized">Oversized Collection (shop.php?cat=oversized)</option>
                    <option value="shop.php?cat=tshirts">Graphic T-Shirts (shop.php?cat=tshirts)</option>
                    <option value="shop.php?cat=hoodies">Heavyweight Hoodies (shop.php?cat=hoodies)</option>
                    <option value="shop.php?cat=polo">Knit Polos (shop.php?cat=polo)</option>
                    <option value="shop.php?cat=new_arrivals">New Drops / Arrivals (shop.php?cat=new_arrivals)</option>
                    <option value="shop.php">All Products (shop.php)</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Display Slide Order (1, 2, 3...)</label>
                <input type="number" name="display_order" value="1" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 700;">
            </div>
            <div style="grid-column: span 2; background: #FFFFFF; border: 1.5px solid var(--admin-border); border-radius: 6px; padding: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 800; margin-bottom: 0.5rem; color: #1E3A8A;">🖼️ Banner Image Source (Upload, ImgBB, or Storage)</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.78rem; font-weight: 700; margin-bottom: 0.2rem;">Upload File</label>
                        <input type="file" name="banner_image" accept="image/*" style="width: 100%; font-size: 0.82rem;">
                        <label style="display: flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.78rem; color: #2563EB; margin-top: 0.4rem; cursor: pointer;">
                            <input type="checkbox" name="upload_to_imgbb" value="1" checked>
                            <span>☁️ Auto-Upload to ImgBB CDN</span>
                        </label>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.78rem; font-weight: 700; margin-bottom: 0.2rem;">Or Direct ImgBB / Image URL</label>
                        <input type="text" name="banner_url" placeholder="https://i.ibb.co/... or assets/images/..." style="width: 100%; padding: 0.6rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-size: 0.82rem;">
                    </div>
                </div>
            </div>
            <div style="grid-column: span 2;">
                <button type="submit" name="save_banner" style="padding: 0.75rem 2rem; background: var(--admin-primary); color: #fff; border: none; border-radius: 6px; font-weight: 800; cursor: pointer;">
                    + ADD NEW BANNER
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
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
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
                            <td><span class="status-pill status-delivered"><?= $b['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                            <td>
                                <a href="banners.php?del=<?= $b['id'] ?>" onclick="return confirm('Delete banner?')" style="color: #EF4444; font-weight: 700; font-size: 0.82rem;">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
