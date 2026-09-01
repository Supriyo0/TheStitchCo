<?php
/**
 * Standalone Profile Settings & Account Security Page
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

// Handle Avatar & Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_avatar'])) {
        if (!empty($_FILES['avatar_file']['name'])) {
            $up = handle_image_upload($_FILES['avatar_file'], 'avatars', 'avatar_' . $userId);
            if ($up['success']) {
                $avatarUrl = $up['url'];
                $db->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$avatarUrl, $userId]);
                $_SESSION['user_avatar'] = $avatarUrl;
                $currentUser = current_user();
                $msg = 'Profile picture updated successfully!';
            } else {
                $errorMsg = $up['message'] ?? 'Failed to upload image.';
            }
        }
    } elseif (isset($_POST['remove_avatar'])) {
        $db->prepare("UPDATE users SET avatar = NULL WHERE id = ?")->execute([$userId]);
        unset($_SESSION['user_avatar']);
        $currentUser = current_user();
        $msg = 'Profile photo removed.';
    } elseif (isset($_POST['update_profile'])) {
        $fn = trim($_POST['fullname'] ?? '');
        $ph = trim($_POST['phone'] ?? '');
        if (!empty($fn)) {
            $db->prepare("UPDATE users SET fullname = ?, phone = ? WHERE id = ?")->execute([$fn, $ph, $userId]);
            $_SESSION['user_name'] = $fn;
            $currentUser = current_user();
            $msg = 'Personal details updated successfully!';
        }
    } elseif (isset($_POST['update_password'])) {
        $curPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confPass = $_POST['confirm_password'] ?? '';

        $uStmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $hash = $uStmt->fetchColumn();

        if (!password_verify($curPass, $hash)) {
            $errorMsg = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $errorMsg = 'New password must be at least 6 characters long.';
        } elseif ($newPass !== $confPass) {
            $errorMsg = 'New passwords do not match.';
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $userId]);
            $msg = 'Password changed successfully!';
        }
    }
}

$orderCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE customer_id = $userId")->fetchColumn();
$wishlistCount = (int)$db->query("SELECT COUNT(*) FROM wishlists WHERE user_id = $userId")->fetchColumn();
$addressCount = (int)$db->query("SELECT COUNT(*) FROM user_addresses WHERE user_id = $userId")->fetchColumn();

$pageTitle = 'Profile Settings | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem 5rem;">
    <!-- Top Breadcrumb & Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 900; color: #0F172A; text-transform: uppercase; margin: 0;">
                Profile Settings
            </h1>
            <span style="font-size: 0.85rem; color: #64748B;">Manage your personal identity, contact details, and account security.</span>
        </div>
    </div>

    <!-- Account Navigation Sub-Bar (iOS Glass Floating Dock) -->
    <div style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.7); border-radius: 16px; padding: 0.5rem; margin-bottom: 2.5rem; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.08); display: flex; gap: 0.5rem; overflow-x: auto; scrollbar-width: none;">
        <a href="dashboard.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">📊 Dashboard</a>
        <a href="orders.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">📦 My Orders (<?= $orderCount ?>)</a>
        <a href="wishlist.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">❤️ Wishlist (<?= $wishlistCount ?>)</a>
        <a href="addresses.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">📍 Saved Addresses (<?= $addressCount ?>)</a>
        <a href="profile.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: #0F172A; color: #FFFFFF; font-weight: 800; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">⚙️ Profile Settings</a>
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
        <!-- Left: Profile Picture & Personal Details -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Profile Photo Box -->
            <div style="background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.75); border-radius: 20px; padding: 1.8rem; box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06);">
                <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 1.2rem;">
                    👤 Profile Picture
                </h3>

                <div style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
                    <div style="width: 84px; height: 84px; border-radius: 50%; overflow: hidden; background: #0F172A; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 900; border: 3px solid #2563EB; box-shadow: 0 8px 20px rgba(0,0,0,0.12); flex-shrink: 0;">
                        <?php if (!empty($currentUser['avatar'])): ?>
                            <img src="<?= e($currentUser['avatar']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($currentUser['fullname'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>

                    <div style="flex: 1; min-width: 200px;">
                        <form action="profile.php" method="POST" enctype="multipart/form-data">
                            <div style="margin-bottom: 0.8rem;">
                                <input type="file" name="avatar_file" accept="image/*" required style="font-size: 0.82rem; width: 100%;">
                            </div>
                            <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                                <button type="submit" name="upload_avatar" class="hero-btn-primary" style="padding: 0.5rem 1.1rem; font-size: 0.8rem; border: none; cursor: pointer; border-radius: 6px;">
                                    Upload Photo 📷
                                </button>
                                <?php if (!empty($currentUser['avatar'])): ?>
                                    <button type="submit" name="remove_avatar" onclick="return confirm('Remove your profile photo?')" style="background: transparent; border: 1.5px solid #EF4444; color: #EF4444; padding: 0.45rem 1rem; border-radius: 6px; font-weight: 800; font-size: 0.78rem; cursor: pointer;">
                                        Remove Photo
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Personal Info Box -->
            <div style="background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.75); border-radius: 20px; padding: 1.8rem; box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06);">
                <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 1.2rem;">
                    📝 Personal Details
                </h3>

                <form action="profile.php" method="POST">
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Full Name *</label>
                            <input type="text" name="fullname" required value="<?= e($currentUser['fullname']) ?>" style="width: 100%; padding: 0.7rem 0.9rem; border: 1.5px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem; font-weight: 700;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Phone Number</label>
                            <input type="text" name="phone" value="<?= e($currentUser['phone'] ?? '') ?>" placeholder="+91 98765 43210" style="width: 100%; padding: 0.7rem 0.9rem; border: 1.5px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem; font-weight: 700;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Email Address (Read-only Login ID)</label>
                            <input type="email" value="<?= e($currentUser['email']) ?>" readonly style="width: 100%; padding: 0.7rem 0.9rem; border: 1.5px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem; font-weight: 700; background: #F1F5F9; color: #64748B; cursor: not-allowed;">
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="hero-btn-primary" style="margin-top: 1.4rem; padding: 0.75rem 1.6rem; font-size: 0.88rem; border: none; cursor: pointer; border-radius: 8px;">
                        SAVE PERSONAL CHANGES
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Change Password / Security -->
        <div>
            <div style="background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.75); border-radius: 20px; padding: 1.8rem; box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06);">
                <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 1.2rem;">
                    🔒 Change Password
                </h3>

                <form action="profile.php" method="POST">
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Current Password *</label>
                            <input type="password" name="current_password" required placeholder="Enter current password" style="width: 100%; padding: 0.7rem 0.9rem; border: 1.5px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">New Password * (Min 6 chars)</label>
                            <input type="password" name="new_password" required minlength="6" placeholder="Enter new password" style="width: 100%; padding: 0.7rem 0.9rem; border: 1.5px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Confirm New Password *</label>
                            <input type="password" name="confirm_password" required minlength="6" placeholder="Re-enter new password" style="width: 100%; padding: 0.7rem 0.9rem; border: 1.5px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem;">
                        </div>
                    </div>

                    <button type="submit" name="update_password" class="hero-btn-primary" style="margin-top: 1.4rem; padding: 0.75rem 1.6rem; font-size: 0.88rem; border: none; cursor: pointer; border-radius: 8px;">
                        UPDATE PASSWORD 🔐
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
