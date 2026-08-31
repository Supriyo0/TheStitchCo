<?php
/**
 * Admin Coupon Engine Management
 * The Stitch Co.
 */

$adminTitle = 'Coupons & Discounts';
require_once __DIR__ . '/header.php';

$msg = '';

// Handle Create Coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $desc = trim($_POST['description'] ?? '');
    $type = $_POST['discount_type'] ?? 'percentage';
    $val = (float)($_POST['discount_value'] ?? 10);
    $minCart = (float)($_POST['min_cart_amount'] ?? 0);
    $maxDisc = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;

    if (!empty($code) && $val > 0) {
        $stmt = $db->prepare("INSERT INTO coupons (code, description, discount_type, discount_value, min_cart_amount, max_discount) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $desc, $type, $val, $minCart, $maxDisc]);
        $msg = 'Coupon code ' . $code . ' created successfully!';
    }
}

if (isset($_GET['del'])) {
    $db->prepare("DELETE FROM coupons WHERE id = ?")->execute([(int)$_GET['del']]);
    $msg = 'Coupon deleted.';
}

$coupons = $db->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
?>

<?php if ($msg): ?>
    <div style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">✓ <?= e($msg) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">
    <!-- Add Coupon Form -->
    <div class="admin-card" style="height: fit-content;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">+ Create Promo Coupon</h3>
        </div>
        <div style="padding: 1.5rem;">
            <form action="coupons.php" method="POST">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Coupon Code *</label>
                    <input type="text" name="code" required placeholder="e.g. SUMMER20" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; text-transform: uppercase; font-weight: 800;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Description</label>
                    <input type="text" name="description" placeholder="e.g. 20% OFF on all drops" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Discount Type</label>
                    <select name="discount_type" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; background: #fff;">
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount (₹)</option>
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Discount Value *</label>
                    <input type="number" step="0.01" name="discount_value" required placeholder="e.g. 10 or 100" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 800;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Minimum Cart Spend (₹)</label>
                    <input type="number" step="0.01" name="min_cart_amount" value="0.00" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Max Discount Cap (₹ Optional)</label>
                    <input type="number" step="0.01" name="max_discount" placeholder="e.g. 300.00" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <button type="submit" name="add_coupon" style="width: 100%; padding: 0.75rem; background: var(--admin-primary); color: #fff; border: none; border-radius: 6px; font-weight: 800; cursor: pointer;">
                    SAVE COUPON
                </button>
            </form>
        </div>
    </div>

    <!-- Coupons Listing -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Active Discount Codes</h3>
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
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
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
                                <a href="coupons.php?del=<?= $c['id'] ?>" onclick="return confirm('Delete this coupon?')" style="color: #EF4444; font-weight: 700; font-size: 0.82rem;">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
