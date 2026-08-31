<?php
/**
 * Admin Category Management Module
 * Full CRUD for Category Story Roundels & Navigation
 * The Stitch Co.
 */

$adminTitle = 'Categories & Story Roundels';
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

// Handle Add or Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $editId = (int)($_POST['category_id'] ?? 0);
    $catName = trim($_POST['cat_name'] ?? '');
    $catKey = strtolower(trim($_POST['cat_key'] ?? preg_replace('/[^A-Za-z0-9_]+/', '_', $catName)));
    $desc = trim($_POST['description'] ?? $_POST['subtext'] ?? '');
    $icon = trim($_POST['icon'] ?? 'tshirt');
    $displayOrder = (int)($_POST['display_order'] ?? 1);
    $imagePath = trim($_POST['image_url'] ?? '');

    if (!empty($_FILES['image_file']['name'])) {
        $up = handle_image_upload($_FILES['image_file'], 'categories', 'cat');
        if ($up['success']) {
            $imagePath = $up['relative_url'];
        }
    }

    if (!empty($catName)) {
        try {
            if ($editId > 0) {
                $sql = "UPDATE categories SET cat_name = ?, cat_key = ?, description = ?, icon = ?, display_order = ?" . (!empty($imagePath) ? ", image = ?" : "") . " WHERE id = ?";
                $params = !empty($imagePath) ? [$catName, $catKey, $desc, $icon, $displayOrder, $imagePath, $editId] : [$catName, $catKey, $desc, $icon, $displayOrder, $editId];
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $msg = 'Category updated successfully!';
            } else {
                if (empty($imagePath)) {
                    $imagePath = 'assets/images/products/good_vibes_white.svg';
                }
                $stmt = $db->prepare("INSERT INTO categories (cat_name, cat_key, description, icon, image, display_order) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$catName, $catKey, $desc, $icon, $imagePath, $displayOrder]);
                $msg = 'Category created successfully!';
            }
        } catch (Exception $e) {
            $err = 'Error saving category: ' . $e->getMessage();
        }
    }
}

// Handle Delete
if (isset($_GET['del'])) {
    $delId = (int)$_GET['del'];
    try {
        $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$delId]);
        $msg = 'Category deleted successfully.';
    } catch (Exception $e) {
        $err = 'Error deleting category: ' . $e->getMessage();
    }
}

$editCat = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editCat = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $editCat->execute([$editId]);
    $editCat = $editCat->fetch();
}

$categories = $db->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.cat_key = p.category GROUP BY c.id ORDER BY c.display_order ASC, c.id ASC")->fetchAll();
?>

<?php if ($msg): ?>
    <div style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">✓ <?= e($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div style="background: #FEF2F2; border: 1px solid #EF4444; color: #DC2626; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">⚠️ <?= e($err) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">
    <!-- Add / Edit Category Form -->
    <div class="admin-card" style="height: fit-content;">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><?= $editCat ? '✏️ Edit Category' : '+ Add New Category' ?></h3>
        </div>
        <div style="padding: 1.5rem;">
            <form action="categories.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="category_id" value="<?= $editCat['id'] ?? 0 ?>">
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Category Display Name *</label>
                    <input type="text" name="cat_name" required value="<?= e($editCat['cat_name'] ?? '') ?>" placeholder="e.g. Acid Wash Tees" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 700;">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Category Slug / Key *</label>
                    <input type="text" name="cat_key" required value="<?= e($editCat['cat_key'] ?? '') ?>" placeholder="e.g. acid_wash" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-family: monospace;">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Subtext (Shown on Desktop Roundels)</label>
                    <input type="text" name="subtext" value="<?= e($editCat['subtext'] ?? '') ?>" placeholder="e.g. Everyday Essential" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Badge Icon</label>
                        <select name="icon" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; background: #fff; font-weight: 700;">
                            <option value="tshirt" <?= ($editCat['icon'] ?? '') === 'tshirt' ? 'selected' : '' ?>>T-Shirt</option>
                            <option value="box" <?= ($editCat['icon'] ?? '') === 'box' ? 'selected' : '' ?>>Oversized Box</option>
                            <option value="polo" <?= ($editCat['icon'] ?? '') === 'polo' ? 'selected' : '' ?>>Polo</option>
                            <option value="hoodie" <?= ($editCat['icon'] ?? '') === 'hoodie' ? 'selected' : '' ?>>Hoodie</option>
                            <option value="spark" <?= ($editCat['icon'] ?? '') === 'spark' ? 'selected' : '' ?>>Spark / New</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Display Order</label>
                        <input type="number" name="display_order" value="<?= e($editCat['display_order'] ?? 1) ?>" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 700;">
                    </div>
                </div>

                <div style="margin-bottom: 1.2rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Roundel Avatar Image Upload</label>
                    <input type="file" name="image_file" accept="image/*" style="width: 100%; font-size: 0.82rem;">
                    <?php if (!empty($editCat['image'])): ?>
                        <div style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <img src="../<?= e($editCat['image']) ?>" alt="Preview" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #000; object-fit: cover;">
                            <span style="font-size: 0.72rem; color: #64748B;"><?= e($editCat['image']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" name="save_category" style="width: 100%; padding: 0.8rem; background: #000; color: #fff; border: none; border-radius: 6px; font-weight: 900; cursor: pointer; letter-spacing: 0.5px;">
                    <?= $editCat ? 'UPDATE CATEGORY' : 'CREATE CATEGORY' ?>
                </button>
                <?php if ($editCat): ?>
                    <a href="categories.php" style="display: block; text-align: center; margin-top: 0.6rem; font-size: 0.82rem; color: #64748B; font-weight: 700; text-decoration: none;">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Category Listing -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Homepage Story Roundels & Categories</h3>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Avatar</th>
                        <th>Category Name</th>
                        <th>Subtext</th>
                        <th>Slug</th>
                        <th>Order</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <div style="width: 36px; height: 36px; border-radius: 50%; background: #000; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid #000;">
                                    <?php if (!empty($cat['image'])): ?>
                                        <img src="../<?= e($cat['image']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <span style="color: #fff; font-size: 0.7rem; font-weight: 800;">CAT</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><strong style="font-weight: 800; color: #000;"><?= e($cat['cat_name']) ?></strong></td>
                            <td style="font-size: 0.82rem; color: #64748B;"><?= e($cat['subtext'] ?: '—') ?></td>
                            <td><code><?= e($cat['cat_key']) ?></code></td>
                            <td><span style="font-weight: 800;"><?= $cat['display_order'] ?></span></td>
                            <td><span style="font-weight: 800;"><?= $cat['product_count'] ?> items</span></td>
                            <td>
                                <div style="display: flex; gap: 0.7rem;">
                                    <a href="categories.php?edit=<?= $cat['id'] ?>" style="color: #2563EB; font-weight: 800; font-size: 0.82rem;">Edit</a>
                                    <a href="categories.php?del=<?= $cat['id'] ?>" onclick="return confirm('Delete this category?')" style="color: #EF4444; font-weight: 800; font-size: 0.82rem;">Delete</a>
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
