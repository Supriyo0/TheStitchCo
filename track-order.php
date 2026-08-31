<?php
/**
 * Live Order Tracking Page
 * The Stitch Co.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_functions.php';

$orderNumber = trim($_GET['order_number'] ?? '');
$order = null;
$orderItems = [];
$shipping = null;
$statusHistory = [];

if (!empty($orderNumber)) {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();

    if ($order) {
        $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$order['id']]);
        $orderItems = $itemStmt->fetchAll();

        $shipStmt = $db->prepare("SELECT * FROM shipping_details WHERE order_id = ? LIMIT 1");
        $shipStmt->execute([$order['id']]);
        $shipping = $shipStmt->fetch();

        $histStmt = $db->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY id ASC");
        $histStmt->execute([$order['id']]);
        $statusHistory = $histStmt->fetchAll();
    }
}

// Map Status Steps
$allSteps = ['Order Placed', 'Confirmed', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered'];
$currentStatus = $order['status'] ?? 'Order Placed';
$currentIndex = array_search($currentStatus, $allSteps);
if ($currentIndex === false) {
    $currentIndex = 0;
}

$myCustomerOrders = [];
if (is_logged_in()) {
    $cUser = current_user();
    $oStmt = $db->prepare("SELECT * FROM orders WHERE customer_id = ? OR customer_email = ? ORDER BY id DESC");
    $oStmt->execute([$cUser['id'], $cUser['email']]);
    $myCustomerOrders = $oStmt->fetchAll();
}

$pageTitle = 'Track Order ' . (!empty($orderNumber) ? '#' . e($orderNumber) : '') . ' | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem 5rem; max-width: 850px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <h1 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 900; text-transform: uppercase; margin: 0;">
            Live Order Tracking
        </h1>
        <a href="account.php?tab=orders" class="hero-btn" style="background: var(--brand-blue); color: #fff; font-size: 0.82rem; padding: 0.55rem 1.2rem;">
            👤 View Profile Orders
        </a>
    </div>

    <!-- Search Form -->
    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
        <form action="track-order.php" method="GET" style="display: flex; gap: 0.8rem;">
            <input type="text" name="order_number" placeholder="Enter Order ID (e.g. TSC-260827-001)" value="<?= e($orderNumber) ?>" required style="flex: 1; padding: 0.8rem 1.2rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 0.95rem; font-weight: 700;">
            <button type="submit" class="hero-btn" style="background: var(--brand-blue); color: #fff; padding: 0 1.8rem; border: none; cursor: pointer;">
                TRACK ORDER
            </button>
        </form>
    </div>

    <?php if (empty($orderNumber) && !empty($myCustomerOrders)): ?>
        <!-- Pre-show All Orders for Logged-in Customer -->
        <div style="margin-bottom: 2rem;">
            <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem;">Select One of Your Recent Orders to Track:</h3>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($myCustomerOrders as $mOrd): ?>
                    <a href="track-order.php?order_number=<?= urlencode($mOrd['order_number']) ?>" style="display: flex; justify-content: space-between; align-items: center; background: var(--surface); border: 1.5px solid var(--border); border-radius: 12px; padding: 1.2rem; text-decoration: none; color: inherit; transition: border 0.2s;">
                        <div>
                            <div style="font-weight: 900; font-size: 1rem; color: #1E3A8A;">#<?= e($mOrd['order_number']) ?></div>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.2rem;">Placed on <?= date('d M Y', strtotime($mOrd['created_at'])) ?> • Total: <?= format_price($mOrd['total_price']) ?></div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.8rem;">
                            <span class="status-pill status-<?= strtolower(str_replace(' ', '', $mOrd['status'])) ?>"><?= e($mOrd['status']) ?></span>
                            <span style="font-weight: 800; color: #2563EB;">Track &rarr;</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($orderNumber) && !$order): ?>
        <div style="background: #FFFBEB; border: 1px solid #F59E0B; color: #B45309; padding: 1.5rem; border-radius: var(--radius-md); text-align: center; font-weight: 700;">
            ⚠️ No order found with tracking number <strong><?= e($orderNumber) ?></strong>. Please double-check your Order ID.
        </div>
    <?php elseif ($order): ?>
        <!-- Tracking Card -->
        <div class="order-tracking-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 1.2rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Order Reference</span>
                    <h2 style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 900; color: var(--primary);"><?= e($order['order_number']) ?></h2>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Status</span>
                    <div>
                        <span class="status-pill status-<?= strtolower(str_replace(' ', '', $order['status'])) ?>"><?= e($order['status']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Stepper Progress Bar -->
            <div style="margin: 2.5rem 0 3rem;">
                <div class="timeline-stepper">
                    <?php foreach ($allSteps as $idx => $stepName): 
                        $isCompleted = ($idx < $currentIndex);
                        $isActive = ($idx === $currentIndex);
                    ?>
                        <div class="timeline-node <?= $isCompleted ? 'done' : ($isActive ? 'active' : '') ?>">
                            <div class="timeline-icon">
                                <?= $isCompleted ? '✓' : ($idx + 1) ?>
                            </div>
                            <div style="font-size: 0.8rem; font-weight: 700; margin-top: 0.4rem; color: <?= $isActive || $isCompleted ? 'var(--primary)' : 'var(--text-muted)' ?>;">
                                <?= $stepName ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Shipping & Courier Details -->
            <?php if ($shipping): ?>
                <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.2rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Courier Partner</div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--primary);"><?= e($shipping['courier_name']) ?></div>
                        <div style="font-size: 0.82rem; color: var(--text-muted);">Tracking AWB: <strong><?= e($shipping['tracking_number']) ?></strong></div>
                    </div>
                    <?php if (!empty($shipping['tracking_url'])): ?>
                        <a href="<?= e($shipping['tracking_url']) ?>" target="_blank" class="hero-btn" style="background: #2563EB; color: #fff; font-size: 0.82rem; padding: 0.5rem 1.2rem;">
                            Track on Courier Website &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Delivery Address -->
            <div style="margin-bottom: 1.5rem; font-size: 0.85rem; border-top: 1px solid var(--border); padding-top: 1.2rem;">
                <h4 style="font-size: 0.85rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.4rem;">Delivery Address</h4>
                <div style="white-space: pre-line; color: var(--text); font-weight: 600; line-height: 1.5;">
                    <?= e($order['shipping_address']) ?>
                </div>
            </div>

            <!-- Order Items -->
            <div style="border-top: 1px solid var(--border); padding-top: 1.2rem;">
                <h4 style="font-size: 0.85rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.8rem;">Ordered Items (<?= count($orderItems) ?>)</h4>
                <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                    <?php foreach ($orderItems as $it): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; background: #F8FAFC; padding: 0.8rem 1rem; border-radius: var(--radius-sm);">
                            <div style="display: flex; gap: 0.8rem; align-items: center;">
                                <img src="<?= e($it['image']) ?>" alt="<?= e($it['product_name']) ?>" style="width: 44px; height: 52px; object-fit: cover; border-radius: 4px;">
                                <div>
                                    <div style="font-weight: 800; font-size: 0.85rem;"><?= e($it['product_name']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Size: <?= e($it['size']) ?> | Color: <?= e($it['color']) ?> | Qty: <?= $it['quantity'] ?></div>
                                </div>
                            </div>
                            <div style="font-weight: 800; font-size: 0.9rem;">
                                <?= format_price($it['total']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
