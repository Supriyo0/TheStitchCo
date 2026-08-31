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
            $stmt = $db->prepare("UPDATE hero_banners SET title = ?, subtitle = ?, tag = ?, button_text = ?, button_url = ?, image = ?, display_order = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$title, $subtitle, $tag, $btnText, $btnUrl, $imagePath, $displayOrder, $isActive, $bannerId]);
            $msg = 'Banner updated successfully!';
        } else {
            $stmt = $db->prepare("INSERT INTO hero_banners (title, subtitle, tag, button_text, button_url, image, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $subtitle, $tag, $btnText, $btnUrl, $imagePath, $displayOrder, $isActive]);
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
if ($action === 'edit' && $editId > 0) {
    $stmt = $db->prepare("SELECT * FROM hero_banners WHERE id = ?");
    $stmt->execute([$editId]);
    $editBanner = $stmt->fetch();
}

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
            <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Manage high-impact promotional banners shown at the top of your homepage.</span>
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
                <input type="text" name="title" required value="<?= e($editBanner['title'] ?? 'OVERSIZED T-SHIRTS') ?>" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 700;">
            </div>
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Hero Tag / Badge</label>
                <input type="text" name="tag" value="<?= e($editBanner['tag'] ?? 'NEW ARRIVALS') ?>" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
            </div>
            <div style="grid-column: span 2;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Subtitle / Material Specs</label>
                <input type="text" name="subtitle" value="<?= e($editBanner['subtitle'] ?? 'Premium Quality | 180-240 GSM | 100% Combed Cotton') ?>" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
            </div>
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Button Text</label>
                <input type="text" name="button_text" value="<?= e($editBanner['button_text'] ?? 'SHOP NOW') ?>" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
            </div>
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Button Destination Category / URL *</label>
                <input type="text" name="button_url" value="<?= e($editBanner['button_url'] ?? 'shop.php?cat=oversized') ?>" placeholder="e.g. shop.php?cat=oversized" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; background: #fff; font-weight: 700;">
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
            <div style="grid-column: span 2; background: #FFFFFF; border: 1.5px solid var(--admin-border); border-radius: 6px; padding: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 800; margin-bottom: 0.5rem; color: #1E3A8A;">🖼️ Banner Image Source (Upload or Direct URL)</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.78rem; font-weight: 700; margin-bottom: 0.2rem;">Upload File from Computer</label>
                        <input type="file" name="banner_image" accept="image/*" style="width: 100%; font-size: 0.82rem;">
                        <label style="display: flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.78rem; color: #2563EB; margin-top: 0.4rem; cursor: pointer;">
                            <input type="checkbox" name="upload_to_imgbb" value="1" checked>
                            <span>☁️ Auto-Upload to ImgBB CDN</span>
                        </label>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.78rem; font-weight: 700; margin-bottom: 0.2rem;">Or Direct Image URL / Path</label>
                        <input type="text" name="banner_url" value="<?= e($editBanner['image'] ?? '') ?>" placeholder="https://i.ibb.co/... or assets/images/..." style="width: 100%; padding: 0.6rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-size: 0.82rem;">
                    </div>
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
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
