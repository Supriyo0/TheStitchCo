<?php
/**
 * Customer Account Portal
 * Orders, Wishlist, Addresses, Settings
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
$userId = $currentUser['id'];
$tab = $_GET['tab'] ?? 'dashboard';

// Fetch Customer Stats
$orderCount = $db->query("SELECT COUNT(*) FROM orders WHERE customer_id = $userId")->fetchColumn();
$wishlistCount = $db->query("SELECT COUNT(*) FROM wishlists WHERE user_id = $userId")->fetchColumn();
$addressCount = $db->query("SELECT COUNT(*) FROM user_addresses WHERE user_id = $userId")->fetchColumn();

// Handle Profile / Avatar Actions POST
$msg = '';
$errorMsg = '';

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
                $errorMsg = $up['message'] ?? 'Failed to upload profile picture.';
            }
        } else {
            $errorMsg = 'Please choose an image file to upload.';
        }
    } elseif (isset($_POST['remove_avatar'])) {
        $db->prepare("UPDATE users SET avatar = NULL WHERE id = ?")->execute([$userId]);
        unset($_SESSION['user_avatar']);
        $currentUser = current_user();
        $msg = 'Profile picture removed.';
    } elseif (isset($_POST['update_profile'])) {
        $newFn = trim($_POST['fullname'] ?? '');
        $newPh = trim($_POST['phone'] ?? '');
        if (!empty($newFn)) {
            $db->prepare("UPDATE users SET fullname = ?, phone = ? WHERE id = ?")->execute([$newFn, $newPh, $userId]);
            $_SESSION['user_name'] = $newFn;
            $currentUser = current_user();
            $msg = 'Profile details updated successfully!';
        }
    } elseif (isset($_POST['add_address'])) {
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
            $msg = 'Address added successfully!';
            $tab = 'addresses';
        }
    }
}

// Fetch Orders
$orders = $db->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC");
$orders->execute([$userId]);
$myOrders = $orders->fetchAll();

// Fetch Wishlist
$wishStmt = $db->prepare("
    SELECT p.* FROM wishlists w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ? AND p.is_active = 1
");
$wishStmt->execute([$userId]);
$myWishlist = $wishStmt->fetchAll();

// Fetch Addresses
try {
    $db->exec("DELETE FROM user_addresses WHERE fullname LIKE '%Souvik%' OR phone LIKE '%98765 43210%'");
} catch (Exception $e) {}

$addrStmt = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$addrStmt->execute([$userId]);
$myAddresses = $addrStmt->fetchAll();

$pageTitle = 'My Account | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem 5rem;">
    <?php if (!empty($msg)): ?>
        <div style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 0.9rem 1.2rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 700;">
            ✓ <?= e($msg) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 0.9rem 1.2rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 700;">
            ✕ <?= e($errorMsg) ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;" class="account-grid">
        <!-- Sidebar Navigation -->
        <div>
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <!-- User Card with Avatar Upload Trigger -->
                <div style="display: flex; align-items: center; gap: 1rem; padding-bottom: 1.2rem; border-bottom: 1px solid var(--border); margin-bottom: 1.2rem;">
                    <div style="position: relative; width: 56px; height: 56px; flex-shrink: 0;">
                        <?php if (!empty($currentUser['avatar'])): ?>
                            <img src="<?= e($currentUser['avatar']) ?>" alt="<?= e($currentUser['fullname']) ?>" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #2563EB;">
                        <?php else: ?>
                            <div style="width: 56px; height: 56px; border-radius: 50%; background: #2563EB; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 800;">
                                <?= strtoupper(substr($currentUser['fullname'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <a href="account.php?tab=profile" title="Change Avatar" style="position: absolute; bottom: -2px; right: -2px; width: 22px; height: 22px; background: #000; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; border: 2px solid #fff; text-decoration: none;">📷</a>
                    </div>
                    <div style="overflow: hidden;">
                        <h3 style="font-size: 1.05rem; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($currentUser['fullname']) ?></h3>
                        <span style="font-size: 0.78rem; color: var(--text-muted); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($currentUser['email']) ?></span>
                    </div>
                </div>

                <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem; font-weight: 700;">
                    <li><a href="account.php?tab=dashboard" style="display: block; padding: 0.6rem 0.8rem; border-radius: var(--radius-sm); <?= $tab === 'dashboard' ? 'background: var(--primary); color: #fff;' : '' ?>">📊 Dashboard</a></li>
                    <li><a href="account.php?tab=orders" style="display: block; padding: 0.6rem 0.8rem; border-radius: var(--radius-sm); <?= $tab === 'orders' ? 'background: var(--primary); color: #fff;' : '' ?>">📦 My Orders (<?= $orderCount ?>)</a></li>
                    <li><a href="account.php?tab=wishlist" style="display: block; padding: 0.6rem 0.8rem; border-radius: var(--radius-sm); <?= $tab === 'wishlist' ? 'background: var(--primary); color: #fff;' : '' ?>">♡ Wishlist (<?= $wishlistCount ?>)</a></li>
                    <li><a href="account.php?tab=addresses" style="display: block; padding: 0.6rem 0.8rem; border-radius: var(--radius-sm); <?= $tab === 'addresses' ? 'background: var(--primary); color: #fff;' : '' ?>">📍 Saved Addresses (<?= $addressCount ?>)</a></li>
                    <li><a href="account.php?tab=profile" style="display: block; padding: 0.6rem 0.8rem; border-radius: var(--radius-sm); <?= $tab === 'profile' ? 'background: var(--primary); color: #fff;' : '' ?>">⚙️ Profile & Photo</a></li>
                    <?php if (in_array($currentUser['role'], ['admin', 'super_admin'])): ?>
                        <li><a href="admin/index.php" style="display: block; padding: 0.6rem 0.8rem; border-radius: var(--radius-sm); color: #2563EB;">⚡ Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" style="display: block; padding: 0.6rem 0.8rem; border-radius: var(--radius-sm); color: #EF4444;">🚪 Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Account Content -->
        <div>
            <?php if ($tab === 'dashboard'): ?>
                <!-- KPI Counter Cards matching Desktop Screenshot -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.2rem; text-align: center;">
                        <div style="font-size: 1.8rem; font-weight: 900; font-family: var(--font-heading); color: var(--primary);"><?= $orderCount ?></div>
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">Total Orders</div>
                    </div>
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.2rem; text-align: center;">
                        <div style="font-size: 1.8rem; font-weight: 900; font-family: var(--font-heading); color: #EF4444;"><?= $wishlistCount ?></div>
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">Wishlist</div>
                    </div>
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.2rem; text-align: center;">
                        <div style="font-size: 1.8rem; font-weight: 900; font-family: var(--font-heading); color: #10B981;"><?= $addressCount ?></div>
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">Addresses</div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.8rem; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
                        <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; text-transform: uppercase;">Recent Orders</h3>
                        <a href="account.php?tab=orders" style="font-size: 0.82rem; font-weight: 700; color: var(--secondary-light);">View All &rarr;</a>
                    </div>
                    <?php if (empty($myOrders)): ?>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">No orders placed yet.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach (array_slice($myOrders, 0, 3) as $ord): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border: 1px solid var(--border); border-radius: var(--radius-sm); flex-wrap: wrap; gap: 0.8rem;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 0.9rem;"><?= e($ord['order_number']) ?></div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);"><?= date('d M Y', strtotime($ord['created_at'])) ?> • <?= format_price($ord['total_price']) ?></div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                                        <span class="status-pill status-<?= strtolower(str_replace(' ', '', $ord['status'])) ?>"><?= e($ord['status']) ?></span>
                                        <a href="track-order.php?order_number=<?= urlencode($ord['order_number']) ?>" style="font-size: 0.82rem; font-weight: 700; color: var(--secondary-light);">Track</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($tab === 'orders'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 800;">My Order History (<?= count($myOrders) ?>)</h2>
                    <a href="shop.php" class="hero-btn" style="background: var(--brand-blue); color: #fff; font-size: 0.8rem; padding: 0.45rem 1rem;">+ New Order</a>
                </div>

                <?php if (empty($myOrders)): ?>
                    <div style="text-align: center; padding: 4rem 1rem; background: var(--surface); border-radius: var(--radius-md); border: 1px solid var(--border);">
                        <div style="font-size: 3rem; margin-bottom: 0.8rem;">📦</div>
                        <h3 style="font-size: 1.2rem; font-weight: 800;">No Orders Placed Yet</h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">When you order products, they will appear here with live tracking.</p>
                        <a href="shop.php" class="hero-btn" style="background: var(--brand-blue); color: #fff; font-size: 0.85rem; padding: 0.6rem 1.5rem;">Explore Streetwear Catalog</a>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <?php foreach ($myOrders as $ord): 
                            // Fetch items for this order
                            $itStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
                            $itStmt->execute([$ord['id']]);
                            $orderItemsList = $itStmt->fetchAll();
                        ?>
                            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.8rem; box-shadow: var(--shadow-sm);">
                                <!-- Order Header -->
                                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.2rem; flex-wrap: wrap; gap: 0.8rem;">
                                    <div>
                                        <div style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 900; color: var(--primary);">
                                            #<?= e($ord['order_number']) ?>
                                        </div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.2rem;">
                                            Placed on <?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?>
                                        </div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                                        <span class="status-pill status-<?= strtolower(str_replace(' ', '', $ord['status'])) ?>">
                                            <?= e($ord['status']) ?>
                                        </span>
                                        <span style="font-weight: 900; font-size: 1.2rem; color: var(--primary);">
                                            <?= format_price($ord['total_price']) ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Ordered Items Previews -->
                                <div style="display: flex; flex-direction: column; gap: 0.8rem; margin-bottom: 1.2rem;">
                                    <?php foreach ($orderItemsList as $it): ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; background: #F8FAFC; padding: 0.8rem 1rem; border-radius: 8px;">
                                            <div style="display: flex; align-items: center; gap: 0.8rem;">
                                                <img src="<?= e($it['image']) ?>" alt="<?= e($it['product_name']) ?>" style="width: 44px; height: 52px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border);">
                                                <div>
                                                    <div style="font-weight: 800; font-size: 0.88rem;"><?= e($it['product_name']) ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Size: <?= e($it['size']) ?> | Color: <?= e($it['color']) ?> | Qty: <?= $it['quantity'] ?></div>
                                                </div>
                                            </div>
                                            <div style="font-weight: 800; font-size: 0.9rem;">
                                                <?= format_price($it['total']) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Live Shipment Stepper Timeline inside Order Card -->
                                <?php
                                    $allOrderSteps = ['Order Placed', 'Confirmed', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered'];
                                    $currStepIdx = array_search($ord['status'], $allOrderSteps);
                                    if ($currStepIdx === false) $currStepIdx = 0;

                                    // Fetch shipping info
                                    $sStmt = $db->prepare("SELECT * FROM shipping_details WHERE order_id = ? LIMIT 1");
                                    $sStmt->execute([$ord['id']]);
                                    $ordShipping = $sStmt->fetch();

                                    // Fetch return info
                                    $retStmt = $db->prepare("SELECT * FROM order_returns WHERE order_id = ? LIMIT 1");
                                    $retStmt->execute([$ord['id']]);
                                    $ordReturn = $retStmt->fetch();
                                ?>
                                <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 1.2rem 1.5rem; margin-bottom: 1.2rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                                        <div style="font-size: 0.82rem; font-weight: 800; color: #1E3A8A; text-transform: uppercase; letter-spacing: 0.5px;">
                                            📦 Live Shipment Timeline
                                        </div>
                                        <?php if ($ordShipping && !empty($ordShipping['tracking_number'])): ?>
                                            <div style="font-size: 0.78rem; color: #475569;">
                                                Courier: <strong><?= e($ordShipping['courier_name']) ?></strong> | AWB: <strong style="color: #2563EB;"><?= e($ordShipping['tracking_number']) ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Stepper Visual -->
                                    <div class="timeline-stepper" style="margin: 0; padding: 0;">
                                        <?php foreach ($allOrderSteps as $sIdx => $sName): 
                                            $isDone = ($sIdx < $currStepIdx);
                                            $isCurr = ($sIdx === $currStepIdx);
                                        ?>
                                            <div class="timeline-node <?= $isDone ? 'done' : ($isCurr ? 'active' : '') ?>">
                                                <div class="timeline-icon" style="width: 26px; height: 26px; font-size: 0.75rem;">
                                                    <?= $isDone ? '✓' : ($sIdx + 1) ?>
                                                </div>
                                                <div style="font-size: 0.72rem; font-weight: 700; color: <?= $isCurr || $isDone ? 'var(--primary)' : 'var(--text-muted)' ?>;">
                                                    <?= $sName ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if ($ordShipping && !empty($ordShipping['tracking_url'])): ?>
                                        <div style="margin-top: 1rem; text-align: right;">
                                            <a href="<?= e($ordShipping['tracking_url']) ?>" target="_blank" style="font-size: 0.78rem; font-weight: 800; color: #2563EB; text-decoration: underline;">
                                                Track on <?= e($ordShipping['courier_name']) ?> Portal &rarr;
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Post-Delivery Return & Refund Status Banner -->
                                <?php if ($ordReturn): ?>
                                    <div style="background: #F0FDF4; border: 1.5px solid #86EFAC; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                                            <div>
                                                <strong style="color: #166534; font-size: 0.88rem;">🔄 Return & Refund Request: <?= e($ordReturn['status']) ?></strong>
                                                <div style="font-size: 0.78rem; color: #15803D; margin-top: 2px;">
                                                    Reason: <strong><?= e($ordReturn['reason']) ?></strong> | Refund Amount: <strong><?= format_price($ordReturn['refund_amount']) ?></strong> to UPI <code><?= e($ordReturn['upi_id']) ?></code>
                                                </div>
                                            </div>
                                            <span style="font-size: 0.75rem; background: #DCFCE7; color: #15803D; padding: 0.3rem 0.6rem; border-radius: 4px; font-weight: 800;">
                                                <?= e($ordReturn['status']) ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($ordReturn['pickup_date']) && strpos($ordReturn['status'], 'Approved') !== false): ?>
                                            <div style="margin-top: 0.6rem; font-size: 0.8rem; background: #EFF6FF; border: 1px solid #BFDBFE; color: #1E40AF; padding: 0.5rem 0.8rem; border-radius: 6px; font-weight: 700;">
                                                🚚 Reverse Pickup Scheduled: <strong><?= date('d M Y', strtotime($ordReturn['pickup_date'])) ?></strong> via <?= e($ordReturn['courier_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($ordReturn['admin_note'])): ?>
                                            <div style="margin-top: 0.5rem; font-size: 0.78rem; color: #166534; background: #DCFCE7; padding: 0.4rem 0.6rem; border-radius: 4px;">
                                                📝 <strong>Store Update:</strong> <?= e($ordReturn['admin_note']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($ordReturn['refund_ref'])): ?>
                                            <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #047857; font-weight: 800;">
                                                💸 UPI Payout Reference / UTR: <code><?= e($ordReturn['refund_ref']) ?></code>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Pre-Shipment Order Cancellation Status & Store Notes -->
                                <?php if (!empty($ord['cancel_requested']) && (int)$ord['cancel_requested'] === 1 && $ord['status'] !== 'Cancelled'): ?>
                                    <div style="background: #FFFBEB; border: 1.5px solid #FCD34D; border-radius: 8px; padding: 0.8rem 1rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.6rem;">
                                        <div>
                                            <strong style="color: #B45309; font-size: 0.85rem;">⏳ Cancellation Request Under Review</strong>
                                            <div style="font-size: 0.76rem; color: #92400E; margin-top: 2px;">Reason: <?= e($ord['cancel_reason']) ?></div>
                                        </div>
                                        <span style="font-size: 0.75rem; background: #FEF3C7; color: #92400E; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 800;">Pending Admin Review</span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($ord['admin_note']) && empty($ordReturn)): ?>
                                    <div style="background: #EFF6FF; border: 1.5px solid #BFDBFE; border-radius: 8px; padding: 0.8rem 1rem; margin-bottom: 1rem; font-size: 0.82rem; color: #1E40AF;">
                                        <strong>📝 Store Cancellation / Status Note:</strong> <?= e($ord['admin_note']) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Order Actions & Details -->
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                                    <div style="font-size: 0.82rem; color: var(--text-muted);">
                                        Payment: <strong style="color: var(--primary);"><?= e($ord['payment_method']) ?></strong> 
                                        (Status: <strong style="color: <?= $ord['payment_status'] === 'Paid' ? '#10B981' : ($ord['payment_status'] === 'Refunded' ? '#3B82F6' : '#F59E0B') ?>;"><?= e($ord['payment_status']) ?></strong>)
                                    </div>
                                    <div style="display: flex; gap: 0.7rem; flex-wrap: wrap; align-items: center;">
                                        <?php if (!in_array($ord['status'], ['Shipped', 'Out for Delivery', 'Delivered', 'Cancelled']) && empty($ord['cancel_requested'])): ?>
                                            <button type="button" onclick="openCancelModal(<?= $ord['id'] ?>, '<?= e($ord['order_number']) ?>')" style="background: #FEF2F2; color: #DC2626; border: 1.5px solid #FECACA; font-size: 0.8rem; font-weight: 800; padding: 0.5rem 1rem; border-radius: var(--radius-sm); cursor: pointer; transition: all 0.2s;">
                                                🚫 Request Cancellation
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($ord['status'] === 'Delivered' && empty($ordReturn)): ?>
                                            <button type="button" onclick="openReturnModal(<?= $ord['id'] ?>, '<?= e($ord['order_number']) ?>', <?= $ord['total_price'] ?>)" style="background: #ECFDF5; color: #059669; border: 1.5px solid #A7F3D0; font-size: 0.8rem; font-weight: 800; padding: 0.5rem 1rem; border-radius: var(--radius-sm); cursor: pointer; transition: all 0.2s;">
                                                🔄 7-Day Return / Refund
                                            </button>
                                        <?php endif; ?>

                                        <a href="track-order.php?order_number=<?= urlencode($ord['order_number']) ?>" class="hero-btn" style="background: var(--brand-blue); color: #fff; font-size: 0.82rem; padding: 0.55rem 1.2rem; text-decoration: none;">
                                            Full Tracking 📦
                                        </a>
                                        <a href="invoice.php?order_number=<?= urlencode($ord['order_number']) ?>" target="_blank" class="hero-btn" style="background: #fff; color: #000; border: 1.5px solid var(--border); font-size: 0.82rem; padding: 0.55rem 1.2rem; text-decoration: none;">
                                            Invoice 📄
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($tab === 'wishlist'): ?>
                <h2 style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 800; margin-bottom: 1.5rem;">My Wishlist</h2>
                <?php if (empty($myWishlist)): ?>
                    <p style="color: var(--text-muted);">Your wishlist is empty.</p>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($myWishlist as $p): ?>
                            <div class="product-card">
                                <div class="product-media">
                                    <a href="product.php?id=<?= $p['id'] ?>">
                                        <img src="<?= e($p['thumbnail']) ?>" alt="<?= e($p['name']) ?>">
                                    </a>
                                </div>
                                <div class="product-info">
                                    <a href="product.php?id=<?= $p['id'] ?>" class="product-name"><?= e($p['name']) ?></a>
                                    <div class="product-pricing">
                                        <span class="price-current"><?= format_price_no_decimals($p['price']) ?></span>
                                    </div>
                                    <button class="add-to-cart-btn" onclick="addToCart(<?= $p['id'] ?>, 1, 'M', 'Black')">Add to Cart</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($tab === 'addresses'): ?>
                <h2 style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 800; margin-bottom: 1.5rem;">Saved Delivery Addresses</h2>
                <?php if (!empty($msg)): ?>
                    <div style="background: #ECFDF5; color: #10B981; padding: 0.8rem; border-radius: var(--radius-sm); margin-bottom: 1rem; font-weight: 700;"><?= e($msg) ?></div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.2rem; margin-bottom: 2rem;">
                    <?php foreach ($myAddresses as $addr): ?>
                        <div style="background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-md); padding: 1.5rem; position: relative;">
                            <?php if ($addr['is_default']): ?>
                                <span style="position: absolute; top: 10px; right: 10px; background: #2563EB; color: #fff; font-size: 0.7rem; font-weight: 800; padding: 0.2rem 0.5rem; border-radius: 4px;">DEFAULT</span>
                            <?php endif; ?>
                            <h4 style="font-weight: 800; margin-bottom: 0.3rem;"><?= e($addr['fullname']) ?></h4>
                            <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.8rem;">
                                <?= e($addr['address_line1']) ?><br>
                                <?= e($addr['address_line2']) ?><br>
                                <?= e($addr['city']) ?>, <?= e($addr['state']) ?> - <?= e($addr['pincode']) ?><br>
                                Phone: <?= e($addr['phone']) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Add New Address Form -->
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.8rem;">
                    <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; margin-bottom: 1.2rem;">+ Add New Address</h3>
                    <form action="account.php?tab=addresses" method="POST">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Full Name *</label>
                                <input type="text" name="fullname" required style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--border); border-radius: 6px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Phone Number *</label>
                                <input type="text" name="phone" required style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--border); border-radius: 6px;">
                            </div>
                            <div style="grid-column: span 2;">
                                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Address Line 1 *</label>
                                <input type="text" name="address_line1" required style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--border); border-radius: 6px;">
                            </div>
                            <div style="grid-column: span 2;">
                                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Address Line 2</label>
                                <input type="text" name="address_line2" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--border); border-radius: 6px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">City *</label>
                                <input type="text" name="city" required style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--border); border-radius: 6px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">PIN Code *</label>
                                <input type="text" name="pincode" required style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--border); border-radius: 6px;">
                            </div>
                        </div>
                        <button type="submit" name="add_address" class="hero-btn" style="background: var(--primary); color: #fff; margin-top: 1.2rem; font-size: 0.85rem; padding: 0.65rem 1.5rem; border: none; cursor: pointer;">
                            SAVE ADDRESS
                        </button>
                    </form>
                </div>

            <?php elseif ($tab === 'profile'): ?>
                <h2 style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 800; margin-bottom: 1.5rem;">Profile & Account Settings</h2>

                <!-- 1. Profile Picture Management Card -->
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.8rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
                    <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; margin-bottom: 1.2rem; text-transform: uppercase;">Profile Picture</h3>
                    
                    <div style="display: flex; align-items: center; gap: 1.8rem; flex-wrap: wrap;">
                        <!-- Current Avatar Visual -->
                        <div style="width: 90px; height: 90px; border-radius: 50%; overflow: hidden; flex-shrink: 0; background: #2563EB; display: flex; align-items: center; justify-content: center; border: 3px solid var(--border); box-shadow: var(--shadow-sm);">
                            <?php if (!empty($currentUser['avatar'])): ?>
                                <img src="<?= e($currentUser['avatar']) ?>" alt="<?= e($currentUser['fullname']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <span style="font-size: 2.2rem; font-weight: 900; color: #FFFFFF;"><?= strtoupper(substr($currentUser['fullname'], 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Upload / Remove Actions -->
                        <div style="flex: 1; min-width: 240px;">
                            <form action="account.php?tab=profile" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 0.8rem;">
                                <div>
                                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem;">
                                        Upload a new image (JPG, PNG, WEBP, GIF - Max 10MB)
                                    </label>
                                    <input type="file" name="avatar_file" accept="image/jpeg,image/png,image/webp,image/gif" required style="font-size: 0.85rem; padding: 0.4rem 0;">
                                </div>
                                <div style="display: flex; gap: 0.8rem; align-items: center; flex-wrap: wrap;">
                                    <button type="submit" name="upload_avatar" class="hero-btn-primary" style="padding: 0.55rem 1.4rem; font-size: 0.85rem; border: none; cursor: pointer;">
                                        Upload Photo 📷
                                    </button>
                                    <?php if (!empty($currentUser['avatar'])): ?>
                                        <button type="submit" name="remove_avatar" onclick="return confirm('Remove your profile photo?')" style="background: none; border: 1.5px solid #EF4444; color: #EF4444; padding: 0.5rem 1.2rem; border-radius: 6px; font-weight: 700; font-size: 0.82rem; cursor: pointer;">
                                            Remove Photo
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 2. Personal Information Card -->
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.8rem; box-shadow: var(--shadow-sm);">
                    <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; margin-bottom: 1.2rem; text-transform: uppercase;">Personal Details</h3>
                    
                    <form action="account.php?tab=profile" method="POST">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem;">
                            <div>
                                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.35rem;">Full Name *</label>
                                <input type="text" name="fullname" value="<?= e($currentUser['fullname']) ?>" required style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--border); border-radius: 6px; font-weight: 600;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.35rem;">Phone Number</label>
                                <input type="text" name="phone" value="<?= e($currentUser['phone'] ?? '') ?>" placeholder="+91 9876543210" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--border); border-radius: 6px; font-weight: 600;">
                            </div>
                            <div style="grid-column: span 2;">
                                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.35rem;">Email Address (Account ID)</label>
                                <input type="email" value="<?= e($currentUser['email']) ?>" readonly style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--border); border-radius: 6px; background: #F1F5F9; color: var(--text-muted); font-weight: 600; cursor: not-allowed;">
                            </div>
                        </div>

                        <button type="submit" name="update_profile" class="hero-btn" style="background: var(--primary); color: #fff; margin-top: 1.5rem; font-size: 0.85rem; padding: 0.65rem 1.6rem; border: none; cursor: pointer;">
                            SAVE CHANGES
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cancellation Request Modal -->
<div id="cancel-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(4px);">
    <div style="background: #FFFFFF; border-radius: 16px; max-width: 480px; width: 100%; padding: 2rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; border-bottom: 1px solid var(--border); padding-bottom: 0.8rem;">
            <h3 style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 900; color: #DC2626; margin: 0;">
                🚫 Request Order Cancellation
            </h3>
            <button onclick="closeCancelModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <p style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 1.2rem; line-height: 1.4;">
            Order #<strong id="modal-order-number" style="color: var(--primary);"></strong> is eligible for cancellation because it has not shipped yet. Please select your reason for our store team:
        </p>

        <form id="cancel-request-form" onsubmit="submitCancelRequest(event)">
            <input type="hidden" id="cancel-order-id" name="order_id" value="">

            <div style="display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1.2rem;">
                <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.86rem; font-weight: 600; cursor: pointer;">
                    <input type="radio" name="cancel_reason" value="Ordered wrong size or color variant" required checked>
                    <span>Ordered wrong size or color variant</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.86rem; font-weight: 600; cursor: pointer;">
                    <input type="radio" name="cancel_reason" value="Need to change delivery address or phone">
                    <span>Need to change delivery address or phone</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.86rem; font-weight: 600; cursor: pointer;">
                    <input type="radio" name="cancel_reason" value="Placed duplicate order by mistake">
                    <span>Placed duplicate order by mistake</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.86rem; font-weight: 600; cursor: pointer;">
                    <input type="radio" name="cancel_reason" value="Delivery time is longer than expected">
                    <span>Delivery time is longer than expected</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.86rem; font-weight: 600; cursor: pointer;">
                    <input type="radio" name="cancel_reason" value="Other reason">
                    <span>Other reason</span>
                </label>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.3rem;">Additional Notes (Optional):</label>
                <textarea id="cancel-notes" placeholder="Explain details for support team..." rows="2" style="width: 100%; padding: 0.6rem; border: 1.5px solid var(--border); border-radius: 6px; font-size: 0.85rem;"></textarea>
            </div>

            <div style="display: flex; gap: 0.8rem; justify-content: flex-end;">
                <button type="button" onclick="closeCancelModal()" style="padding: 0.65rem 1.2rem; background: #F1F5F9; color: var(--text-main); border: 1px solid var(--border); border-radius: 6px; font-weight: 700; font-size: 0.85rem; cursor: pointer;">
                    Keep Order
                </button>
                <button type="submit" id="btn-submit-cancel" style="padding: 0.65rem 1.4rem; background: #DC2626; color: #FFFFFF; border: none; border-radius: 6px; font-weight: 800; font-size: 0.85rem; cursor: pointer;">
                    SUBMIT CANCELLATION REQUEST
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCancelModal(orderId, orderNo) {
    document.getElementById('cancel-order-id').value = orderId;
    document.getElementById('modal-order-number').textContent = orderNo;
    document.getElementById('cancel-notes').value = '';
    const overlay = document.getElementById('cancel-modal-overlay');
    overlay.style.display = 'flex';
}

function closeCancelModal() {
    document.getElementById('cancel-modal-overlay').style.display = 'none';
}

function submitCancelRequest(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-cancel');
    btn.disabled = true;
    btn.textContent = 'Submitting...';

    const orderId = document.getElementById('cancel-order-id').value;
    const reasonEl = document.querySelector('input[name="cancel_reason"]:checked');
    const reason = reasonEl ? reasonEl.value : 'Customer requested cancellation';
    const notes = document.getElementById('cancel-notes').value;

    const formData = new FormData();
    formData.append('action', 'request_cancellation');
    formData.append('order_id', orderId);
    formData.append('cancel_reason', reason);
    formData.append('additional_notes', notes);

    fetch('api/customer_actions.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload();
            } else {
                btn.disabled = false;
                btn.textContent = 'SUBMIT CANCELLATION REQUEST';
            }
        })
        .catch(() => {
            alert('Failed to connect to server. Please try again.');
            btn.disabled = false;
            btn.textContent = 'SUBMIT CANCELLATION REQUEST';
        });
}

function openReturnModal(orderId, orderNo, totalAmount) {
    document.getElementById('return-order-id').value = orderId;
    document.getElementById('modal-return-order-no').textContent = orderNo;
    document.getElementById('modal-return-amount').textContent = '₹' + Number(totalAmount).toFixed(2);
    document.getElementById('return-modal-overlay').style.display = 'flex';
}

function closeReturnModal() {
    document.getElementById('return-modal-overlay').style.display = 'none';
}

function previewUpload(input, previewId) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewEl = document.getElementById(previewId);
            previewEl.src = e.target.result;
            previewEl.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
}

function submitReturnRequest(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-return');
    btn.disabled = true;
    btn.textContent = 'Uploading Photos & Submitting...';

    const form = document.getElementById('return-request-form');
    const formData = new FormData(form);
    formData.append('action', 'request_return');

    fetch('api/customer_actions.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload();
            } else {
                btn.disabled = false;
                btn.textContent = 'SUBMIT RETURN & REFUND REQUEST';
            }
        })
        .catch(() => {
            alert('Failed to connect to server. Please try again.');
            btn.disabled = false;
            btn.textContent = 'SUBMIT RETURN & REFUND REQUEST';
        });
}
</script>

<!-- 7-Day Return / Refund Modal -->
<div id="return-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(5px);">
    <div style="background: #FFFFFF; border-radius: 18px; max-width: 540px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; border-bottom: 1px solid var(--border); padding-bottom: 0.8rem;">
            <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 900; color: #059669; margin: 0;">
                🔄 7-Day Easy Return & Refund
            </h3>
            <button onclick="closeReturnModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <div style="background: #F0FDF4; border: 1.5px solid #86EFAC; border-radius: 8px; padding: 0.8rem 1rem; margin-bottom: 1.2rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span style="font-size: 0.75rem; color: #166534; font-weight: 700; text-transform: uppercase;">Order Reference</span>
                <div style="font-weight: 900; font-size: 1.05rem; color: #15803D;" id="modal-return-order-no"></div>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 0.75rem; color: #166534; font-weight: 700; text-transform: uppercase;">Refund Amount</span>
                <div style="font-weight: 900; font-size: 1.15rem; color: #15803D;" id="modal-return-amount"></div>
            </div>
        </div>

        <form id="return-request-form" onsubmit="submitReturnRequest(event)" enctype="multipart/form-data">
            <input type="hidden" id="return-order-id" name="order_id" value="">

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 800; margin-bottom: 0.35rem;">Reason for Return *</label>
                <select name="reason" required style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--border); border-radius: 6px; font-weight: 600; background: #fff;">
                    <option value="">-- Select Reason --</option>
                    <option value="Size issue (Too tight / small)">Size issue (Too tight / small)</option>
                    <option value="Size issue (Too loose / big)">Size issue (Too loose / big)</option>
                    <option value="Defective / Damaged garment">Defective / Damaged garment</option>
                    <option value="Received incorrect item / color">Received incorrect item / color</option>
                    <option value="Fabric quality not as expected">Fabric quality not as expected</option>
                    <option value="Other reason">Other reason</option>
                </select>
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 800; margin-bottom: 0.35rem;">Explain Issue / Comments</label>
                <textarea name="notes" placeholder="Please describe the fit or reason for support review..." rows="2" style="width: 100%; padding: 0.6rem; border: 1.5px solid var(--border); border-radius: 6px; font-size: 0.84rem;"></textarea>
            </div>

            <!-- 3 Required Photos Upload Box -->
            <div style="background: #F8FAFC; border: 1.5px dashed #CBD5E1; border-radius: 12px; padding: 1.2rem; margin-bottom: 1.2rem;">
                <div style="font-size: 0.85rem; font-weight: 900; color: #1E293B; margin-bottom: 0.3rem;">
                    📷 Upload 3 Product Verification Photos (Required)
                </div>
                <div style="font-size: 0.75rem; color: #64748B; margin-bottom: 1rem; line-height: 1.3;">
                    Ensure brand tags are intact for instant pickup approval.
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.8rem;">
                    <!-- 1. Front View -->
                    <div style="text-align: center;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 800; color: #334155; margin-bottom: 0.3rem;">1. Front View *</label>
                        <input type="file" name="img_front" accept="image/*" required onchange="previewUpload(this, 'prev-front')" style="display: none;" id="input-front">
                        <label for="input-front" style="display: block; background: #FFFFFF; border: 1.5px solid #CBD5E1; border-radius: 8px; padding: 0.6rem 0.4rem; cursor: pointer; font-size: 0.72rem; font-weight: 700; color: #2563EB;">
                            📁 Choose Photo
                        </label>
                        <img id="prev-front" src="" alt="Preview" style="display: none; width: 100%; height: 60px; object-fit: cover; border-radius: 4px; margin-top: 0.4rem; border: 1px solid var(--border);">
                    </div>

                    <!-- 2. Back View -->
                    <div style="text-align: center;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 800; color: #334155; margin-bottom: 0.3rem;">2. Back View *</label>
                        <input type="file" name="img_back" accept="image/*" required onchange="previewUpload(this, 'prev-back')" style="display: none;" id="input-back">
                        <label for="input-back" style="display: block; background: #FFFFFF; border: 1.5px solid #CBD5E1; border-radius: 8px; padding: 0.6rem 0.4rem; cursor: pointer; font-size: 0.72rem; font-weight: 700; color: #2563EB;">
                            📁 Choose Photo
                        </label>
                        <img id="prev-back" src="" alt="Preview" style="display: none; width: 100%; height: 60px; object-fit: cover; border-radius: 4px; margin-top: 0.4rem; border: 1px solid var(--border);">
                    </div>

                    <!-- 3. Brand Tag View -->
                    <div style="text-align: center;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 800; color: #334155; margin-bottom: 0.3rem;">3. Brand Tag *</label>
                        <input type="file" name="img_tag" accept="image/*" required onchange="previewUpload(this, 'prev-tag')" style="display: none;" id="input-tag">
                        <label for="input-tag" style="display: block; background: #FFFFFF; border: 1.5px solid #CBD5E1; border-radius: 8px; padding: 0.6rem 0.4rem; cursor: pointer; font-size: 0.72rem; font-weight: 700; color: #2563EB;">
                            📁 Choose Photo
                        </label>
                        <img id="prev-tag" src="" alt="Preview" style="display: none; width: 100%; height: 60px; object-fit: cover; border-radius: 4px; margin-top: 0.4rem; border: 1px solid var(--border);">
                    </div>
                </div>
            </div>

            <!-- Customer UPI ID for Instant Refund -->
            <div style="background: #FFFBEB; border: 1.5px solid #FCD34D; border-radius: 12px; padding: 1.1rem; margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 900; color: #92400E; margin-bottom: 0.3rem;">
                    💸 Your UPI ID for 100% Refund Payout *
                </label>
                <div style="font-size: 0.75rem; color: #B45309; margin-bottom: 0.6rem; line-height: 1.3;">
                    Required for instant automated bank transfer (even for Cash On Delivery orders).
                </div>
                <input type="text" name="upi_id" required placeholder="e.g. yourname@oksbi or 9876543210@paytm" style="width: 100%; padding: 0.65rem; border: 1.5px solid #F59E0B; border-radius: 6px; font-weight: 800; font-family: monospace; font-size: 0.95rem; background: #FFFFFF;">
            </div>

            <div style="display: flex; gap: 0.8rem; justify-content: flex-end;">
                <button type="button" onclick="closeReturnModal()" style="padding: 0.65rem 1.2rem; background: #F1F5F9; color: var(--text-main); border: 1px solid var(--border); border-radius: 6px; font-weight: 700; font-size: 0.85rem; cursor: pointer;">
                    Cancel
                </button>
                <button type="submit" id="btn-submit-return" style="padding: 0.65rem 1.5rem; background: #059669; color: #FFFFFF; border: none; border-radius: 6px; font-weight: 800; font-size: 0.85rem; cursor: pointer; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);">
                    SUBMIT RETURN & REFUND REQUEST
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
