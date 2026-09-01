<?php
/**
 * Standalone Saved Addresses Page
 * The Stitch Co.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_functions.php';

require_login('login.php');

$db = get_db();
$currentUser = current_user();
$userId = (int)$currentUser['id'];

$msg = '';
$errorMsg = '';

// Handle Add Address POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $fn = trim($_POST['fullname'] ?? '');
    $ph = trim($_POST['phone'] ?? '');
    $a1 = trim($_POST['address_line1'] ?? '');
    $a2 = trim($_POST['address_line2'] ?? '');
    $lm = trim($_POST['landmark'] ?? '');
    $ct = trim($_POST['city'] ?? '');
    $st = trim($_POST['state'] ?? 'West Bengal');
    $pc = trim($_POST['pincode'] ?? '');

    if (!empty($fn) && !empty($ph) && !empty($a1) && !empty($ct) && !empty($pc)) {
        $stmt = $db->prepare("INSERT INTO user_addresses (user_id, fullname, phone, address_line1, address_line2, landmark, city, state, pincode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $fn, $ph, $a1, $a2, $lm, $ct, $st, $pc]);
        $msg = 'Address saved successfully!';
    } else {
        $errorMsg = 'Please fill in all required fields.';
    }
}

// Handle Delete Address
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    $stmt = $db->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$delId, $userId]);
    $msg = 'Address deleted.';
}

// Handle Set Default Address
if (isset($_GET['set_default'])) {
    $defId = (int)$_GET['set_default'];
    $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
    $db->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$defId, $userId]);
    $msg = 'Default delivery address updated!';
}

// Fetch Addresses
$addrStmt = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$addrStmt->execute([$userId]);
$myAddresses = $addrStmt->fetchAll();

$orderCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE customer_id = $userId")->fetchColumn();
$wishlistCount = (int)$db->query("SELECT COUNT(*) FROM wishlists WHERE user_id = $userId")->fetchColumn();

$pageTitle = 'Saved Delivery Addresses | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem 5rem;">
    <!-- Top Breadcrumb & Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 900; color: #0F172A; text-transform: uppercase; margin: 0;">
                Saved Delivery Addresses (<?= count($myAddresses) ?>)
            </h1>
            <span style="font-size: 0.85rem; color: #64748B;">Manage your doorstep shipping locations for 1-click checkout.</span>
        </div>
    </div>

    <!-- Account Navigation Sub-Bar (iOS Glass Floating Dock) -->
    <div style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.7); border-radius: 16px; padding: 0.5rem; margin-bottom: 2.5rem; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.08); display: flex; gap: 0.5rem; overflow-x: auto; scrollbar-width: none;">
        <a href="dashboard.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">📊 Dashboard</a>
        <a href="orders.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">📦 My Orders (<?= $orderCount ?>)</a>
        <a href="wishlist.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">❤️ Wishlist (<?= $wishlistCount ?>)</a>
        <a href="addresses.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: #0F172A; color: #FFFFFF; font-weight: 800; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">📍 Saved Addresses (<?= count($myAddresses) ?>)</a>
        <a href="profile.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">⚙️ Profile Settings</a>
    </div>

    <?php if (!empty($msg)): ?>
        <div style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; padding: 1rem 1.2rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 700;">
            ✓ <?= e($msg) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        <div style="background: #FEF2F2; border: 1px solid #EF4444; color: #DC2626; padding: 1rem 1.2rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 700;">
            ✕ <?= e($errorMsg) ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;" class="checkout-grid">
        <!-- Left: Saved Addresses Cards -->
        <div>
            <h2 style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 1.2rem;">
                Your Saved Locations
            </h2>

            <?php if (empty($myAddresses)): ?>
                <div style="text-align: center; padding: 3rem 1rem; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border-radius: 16px; border: 1.5px solid rgba(255, 255, 255, 0.7);">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📍</div>
                    <h4 style="font-size: 1rem; font-weight: 800; color: #0F172A; margin-bottom: 0.2rem;">No Addresses Saved Yet</h4>
                    <p style="font-size: 0.82rem; color: #64748B;">Add your delivery address using the form to checkout faster.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1.2rem;">
                    <?php foreach ($myAddresses as $addr): ?>
                        <div style="background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(20px); border: 1.5px solid <?= !empty($addr['is_default']) ? '#2563EB' : 'rgba(255, 255, 255, 0.75)' ?>; border-radius: 16px; padding: 1.4rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); position: relative;">
                            <?php if (!empty($addr['is_default'])): ?>
                                <span style="position: absolute; top: 12px; right: 12px; background: #2563EB; color: #fff; font-size: 0.7rem; font-weight: 800; padding: 0.2rem 0.6rem; border-radius: 20px;">
                                    DEFAULT
                                </span>
                            <?php endif; ?>

                            <div style="font-weight: 900; font-size: 1rem; color: #0F172A; margin-bottom: 0.3rem;">
                                <?= e($addr['fullname']) ?> &nbsp;<span style="font-weight: 600; font-size: 0.84rem; color: #64748B;">(<?= e($addr['phone']) ?>)</span>
                            </div>
                            <div style="font-size: 0.86rem; color: #334155; line-height: 1.5; margin-bottom: 1rem;">
                                <?= e($addr['address_line1']) ?><?= !empty($addr['address_line2']) ? ', ' . e($addr['address_line2']) : '' ?><br>
                                <?= !empty($addr['landmark']) ? 'Landmark: ' . e($addr['landmark']) . '<br>' : '' ?>
                                <?= e($addr['city']) ?>, <?= e($addr['state']) ?> - <strong><?= e($addr['pincode']) ?></strong>
                            </div>

                            <div style="display: flex; gap: 0.6rem; align-items: center; border-top: 1px dashed #E2E8F0; padding-top: 0.8rem;">
                                <?php if (empty($addr['is_default'])): ?>
                                    <a href="addresses.php?set_default=<?= $addr['id'] ?>" style="font-size: 0.76rem; font-weight: 800; color: #2563EB; text-decoration: none;">
                                        ⚡ Set as Default
                                    </a>
                                    <span style="color: #CBD5E1;">|</span>
                                <?php endif; ?>
                                <a href="addresses.php?delete_id=<?= $addr['id'] ?>" onclick="return confirm('Delete this address?')" style="font-size: 0.76rem; font-weight: 800; color: #DC2626; text-decoration: none;">
                                    🗑️ Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Add New Address Form -->
        <div>
            <div style="background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.75); border-radius: 20px; padding: 1.8rem; box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06);">
                <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 1.2rem;">
                    ➕ Add New Address
                </h3>

                <form action="addresses.php" method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.3rem;">Receiver Name *</label>
                            <input type="text" name="fullname" required value="<?= e($currentUser['fullname']) ?>" style="width: 100%; padding: 0.65rem 0.8rem; border: 1.5px solid #CBD5E1; border-radius: 6px; font-size: 0.88rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.3rem;">Contact Phone *</label>
                            <input type="text" name="phone" required value="<?= e($currentUser['phone'] ?? '') ?>" placeholder="+91 98765 43210" style="width: 100%; padding: 0.65rem 0.8rem; border: 1.5px solid #CBD5E1; border-radius: 6px; font-size: 0.88rem;">
                        </div>
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.3rem;">House / Flat / Street Address *</label>
                            <input type="text" name="address_line1" required placeholder="e.g. Flat 4B, Sunrise Heights, Park Street" style="width: 100%; padding: 0.65rem 0.8rem; border: 1.5px solid #CBD5E1; border-radius: 6px; font-size: 0.88rem;">
                        </div>
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.3rem;">Area / Colony (Optional)</label>
                            <input type="text" name="address_line2" placeholder="e.g. Near City Center" style="width: 100%; padding: 0.65rem 0.8rem; border: 1.5px solid #CBD5E1; border-radius: 6px; font-size: 0.88rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.3rem;">Landmark (Optional)</label>
                            <input type="text" name="landmark" placeholder="e.g. Opposite Metro Gate 2" style="width: 100%; padding: 0.65rem 0.8rem; border: 1.5px solid #CBD5E1; border-radius: 6px; font-size: 0.88rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.3rem;">City *</label>
                            <input type="text" name="city" required placeholder="e.g. Kolkata" style="width: 100%; padding: 0.65rem 0.8rem; border: 1.5px solid #CBD5E1; border-radius: 6px; font-size: 0.88rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.3rem;">State *</label>
                            <input type="text" name="state" required value="West Bengal" style="width: 100%; padding: 0.65rem 0.8rem; border: 1.5px solid #CBD5E1; border-radius: 6px; font-size: 0.88rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.3rem;">Pincode *</label>
                            <input type="text" name="pincode" required placeholder="6-digit Pincode" maxlength="6" style="width: 100%; padding: 0.65rem 0.8rem; border: 1.5px solid #CBD5E1; border-radius: 6px; font-size: 0.88rem; font-weight: 700;">
                        </div>
                    </div>

                    <button type="submit" name="add_address" class="hero-btn-primary" style="width: 100%; margin-top: 1.4rem; padding: 0.85rem; font-size: 0.9rem; border: none; cursor: pointer; border-radius: 8px;">
                        SAVE ADDRESS 📍
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
