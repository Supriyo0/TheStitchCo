<?php
/**
 * Admin Coupon Engine Management
 * The Stitch Co.
 */

$adminTitle = 'Coupons & Discounts';
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// Handle Delete Coupon
if (isset($_GET['del'])) {
    $delId = (int)$_GET['del'];
    try {
        $db->prepare("DELETE FROM coupons WHERE id = ?")->execute([$delId]);
        $msg = 'Coupon deleted successfully!';
    } catch (Exception $e) {
        $err = 'Error deleting coupon: ' . $e->getMessage();
    }
}

// Handle Toggle Active Status
if (isset($_GET['toggle'])) {
    $toggleId = (int)$_GET['toggle'];
    $db->prepare("UPDATE coupons SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?")->execute([$toggleId]);
    $msg = 'Coupon status updated!';
}

// Handle Create or Edit Coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_coupon'])) {
    $couponId = (int)($_POST['coupon_id'] ?? 0);
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $desc = trim($_POST['description'] ?? '');
    $type = $_POST['discount_type'] ?? 'percentage';
    $val = (float)($_POST['discount_value'] ?? 10);
    $minCart = (float)($_POST['min_cart_amount'] ?? 0);
    $maxDisc = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (!empty($code) && $val > 0) {
        try {
            if ($couponId > 0) {
                $stmt = $db->prepare("UPDATE coupons SET code = ?, description = ?, discount_type = ?, discount_value = ?, min_cart_amount = ?, max_discount = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$code, $desc, $type, $val, $minCart, $maxDisc, $isActive, $couponId]);
                $msg = 'Coupon code ' . $code . ' updated successfully!';
            } else {
                $stmt = $db->prepare("INSERT INTO coupons (code, description, discount_type, discount_value, min_cart_amount, max_discount, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $desc, $type, $val, $minCart, $maxDisc, $isActive]);
                $msg = 'Coupon code ' . $code . ' created successfully!';
            }
        } catch (Exception $e) {
            $err = 'Error saving coupon: ' . $e->getMessage();
        }
    } else {
        $err = 'Please provide coupon code and valid discount value.';
    }
}

$editCoupon = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = $db->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$editId]);
    $editCoupon = $stmt->fetch();
}

$coupons = $db->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
?>

<?php if ($msg): ?>
    <div style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">✓ <?= e($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div style="background: #FEF2F2; border: 1px solid #EF4444; color: #DC2626; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">⚠️ <?= e($err) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">
    <!-- Add / Edit Coupon Form -->
    <div class="admin-card" style="height: fit-content;">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><?= $editCoupon ? '✏️ Edit Promo Coupon' : '+ Create Promo Coupon' ?></h3>
            <?php if ($editCoupon): ?>
                <a href="coupons.php" style="font-size: 0.75rem; color: #2563EB; font-weight: 700;">+ New Coupon</a>
            <?php endif; ?>
        </div>
        <div style="padding: 1.5rem;">
            <form action="coupons.php" method="POST">
                <input type="hidden" name="coupon_id" value="<?= $editCoupon['id'] ?? 0 ?>">

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Coupon Code *</label>
                    <input type="text" name="code" required value="<?= e($editCoupon['code'] ?? '') ?>" placeholder="e.g. SUMMER20" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; text-transform: uppercase; font-weight: 800;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Description</label>
                    <input type="text" name="description" value="<?= e($editCoupon['description'] ?? '') ?>" placeholder="e.g. 20% OFF on all drops" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Discount Type</label>
                    <select name="discount_type" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; background: #fff;">
                        <option value="percentage" <?= ($editCoupon['discount_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                        <option value="fixed" <?= ($editCoupon['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount (₹)</option>
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Discount Value *</label>
                    <input type="number" step="0.01" name="discount_value" required value="<?= e($editCoupon['discount_value'] ?? '10.00') ?>" placeholder="e.g. 10 or 100" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 800;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Minimum Cart Spend (₹)</label>
                    <input type="number" step="0.01" name="min_cart_amount" value="<?= e($editCoupon['min_cart_amount'] ?? '0.00') ?>" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1.2rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Max Discount Cap (₹ Optional)</label>
                    <input type="number" step="0.01" name="max_discount" value="<?= e($editCoupon['max_discount'] ?? '') ?>" placeholder="e.g. 300.00" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; font-weight: 700; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" <?= (!isset($editCoupon) || !empty($editCoupon['is_active'])) ? 'checked' : '' ?>>
                        <span>Coupon Active / Enabled</span>
                    </label>
                </div>
                <button type="submit" name="save_coupon" style="width: 100%; padding: 0.75rem; background: var(--admin-primary); color: #fff; border: none; border-radius: 6px; font-weight: 800; cursor: pointer;">
                    <?= $editCoupon ? 'UPDATE COUPON' : 'SAVE COUPON' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Coupons Listing -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Active Discount Codes (<?= count($coupons) ?>)</h3>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Min Spend</th>
                        <th>Max Cap</th>
                        <th>Used</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--admin-text-muted); padding: 2rem;">No coupons found. Create your first promo code!</td></tr>
                    <?php endif; ?>
                    <?php foreach ($coupons as $c): ?>
                        <tr>
                            <td>
                                <strong style="font-family: monospace; font-size: 0.95rem; background: #EEF2FF; padding: 0.25rem 0.5rem; border-radius: 4px; color: #1E3A8A;"><?= e($c['code']) ?></strong><br>
                                <span style="font-size: 0.75rem; color: var(--admin-text-muted);"><?= e($c['description']) ?></span>
                            </td>
                            <td>
                                <strong style="font-weight: 800; color: #10B981;">
                                    <?= $c['discount_type'] === 'percentage' ? $c['discount_value'] . '%' : format_price($c['discount_value']) ?>
                                </strong>
                            </td>
                            <td><?= format_price($c['min_cart_amount']) ?></td>
                            <td><?= $c['max_discount'] ? format_price($c['max_discount']) : 'No Cap' ?></td>
                            <td><strong><?= $c['used_count'] ?></strong> times</td>
                            <td>
                                <a href="coupons.php?toggle=<?= $c['id'] ?>" title="Click to toggle status" class="status-pill status-<?= $c['is_active'] ? 'delivered' : 'cancelled' ?>" style="text-decoration: none; cursor: pointer;">
                                    <?= $c['is_active'] ? 'Active' : 'Disabled' ?>
                                </a>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <a href="coupons.php?action=edit&id=<?= $c['id'] ?>" style="padding: 0.35rem 0.65rem; background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; border-radius: 4px; font-weight: 700; font-size: 0.75rem; text-decoration: none;">
                                        ✏️ Edit
                                    </a>
                                    <a href="coupons.php?del=<?= $c['id'] ?>" onclick="return confirm('Are you sure you want to delete coupon <?= e($c['code']) ?>?')" style="padding: 0.35rem 0.65rem; background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; border-radius: 4px; font-weight: 700; font-size: 0.75rem; text-decoration: none;">
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
