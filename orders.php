<?php
/**
 * Standalone My Orders Page
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

// Fetch all customer orders
$stmt = $db->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC");
$stmt->execute([$userId]);
$myOrders = $stmt->fetchAll();

$pageTitle = 'My Orders | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem 5rem;">
    <!-- Top Breadcrumb & Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 900; color: #0F172A; text-transform: uppercase; margin: 0;">
                My Orders (<?= count($myOrders) ?>)
            </h1>
            <span style="font-size: 0.85rem; color: #64748B;">Track real-time shipment status, download receipts, and manage cancellations.</span>
        </div>
        <div>
            <a href="shop.php" class="hero-btn-primary" style="padding: 0.6rem 1.3rem; font-size: 0.85rem; text-decoration: none;">🛍️ Explore Streetwear Drops</a>
        </div>
    </div>

    <!-- Account Navigation Sub-Bar (iOS Glass Floating Dock) -->
    <div style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.7); border-radius: 16px; padding: 0.5rem; margin-bottom: 2.5rem; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.08); display: flex; gap: 0.5rem; overflow-x: auto; scrollbar-width: none;">
        <a href="dashboard.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">📊 Dashboard</a>
        <a href="orders.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: #0F172A; color: #FFFFFF; font-weight: 800; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">📦 My Orders (<?= count($myOrders) ?>)</a>
        <a href="wishlist.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">❤️ Wishlist</a>
        <a href="addresses.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">📍 Saved Addresses</a>
        <a href="profile.php" style="padding: 0.65rem 1.2rem; border-radius: 12px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">⚙️ Profile Settings</a>
    </div>

    <!-- Orders Content -->
    <?php if (empty($myOrders)): ?>
        <div style="text-align: center; padding: 4.5rem 1rem; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border-radius: 20px; border: 1.5px solid rgba(255, 255, 255, 0.7); box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06);">
            <div style="font-size: 3.5rem; margin-bottom: 0.8rem;">📦</div>
            <h3 style="font-size: 1.3rem; font-weight: 900; color: #0F172A; margin-bottom: 0.4rem;">No Orders Placed Yet</h3>
            <p style="color: #64748B; font-size: 0.88rem; margin-bottom: 1.5rem;">When you order products, they will appear here with live tracking.</p>
            <a href="shop.php" class="hero-btn-primary" style="font-size: 0.85rem; padding: 0.7rem 1.8rem; text-decoration: none;">Explore Streetwear Catalog</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1.8rem;">
            <?php foreach ($myOrders as $ord): 
                $itStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $itStmt->execute([$ord['id']]);
                $orderItemsList = $itStmt->fetchAll();

                $allOrderSteps = ['Order Placed', 'Confirmed', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered'];
                $currStepIdx = array_search($ord['status'], $allOrderSteps);
                if ($currStepIdx === false) $currStepIdx = 0;

                $sStmt = $db->prepare("SELECT * FROM shipping_details WHERE order_id = ? LIMIT 1");
                $sStmt->execute([$ord['id']]);
                $ordShipping = $sStmt->fetch();
            ?>
                <div style="background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.75); border-radius: 20px; padding: 1.8rem; box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06);">
                    <!-- Order Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #E2E8F0; padding-bottom: 1rem; margin-bottom: 1.2rem; flex-wrap: wrap; gap: 0.8rem;">
                        <div>
                            <div style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 900; color: #0F172A;">
                                #<?= e($ord['order_number']) ?>
                            </div>
                            <div style="font-size: 0.78rem; color: #64748B; margin-top: 2px;">
                                Placed on <?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?> • <strong><?= e($ord['payment_method']) ?></strong>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.8rem;">
                            <span class="status-pill status-<?= strtolower(str_replace(' ', '', $ord['status'])) ?>">
                                <?= e($ord['status']) ?>
                            </span>
                            <span style="font-weight: 900; font-size: 1.25rem; color: #0F172A;">
                                <?= format_price($ord['total_price']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Ordered Items List -->
                    <div style="display: flex; flex-direction: column; gap: 0.8rem; margin-bottom: 1.4rem;">
                        <?php foreach ($orderItemsList as $it): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; background: #F8FAFC; padding: 0.85rem 1.1rem; border-radius: 12px; border: 1px solid #E2E8F0;">
                                <div style="display: flex; align-items: center; gap: 0.9rem;">
                                    <img src="<?= e($it['image']) ?>" alt="<?= e($it['product_name']) ?>" style="width: 48px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid #CBD5E1;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 0.9rem; color: #0F172A;"><?= e($it['product_name']) ?></div>
                                        <div style="font-size: 0.76rem; color: #64748B;">Size: <?= e($it['size']) ?> | Qty: <?= $it['quantity'] ?></div>
                                    </div>
                                </div>
                                <div style="font-weight: 900; font-size: 0.95rem; color: #0F172A;">
                                    <?= format_price($it['total']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Delivery & Courier Info -->
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 1.1rem 1.3rem; margin-bottom: 1.2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.8rem;">
                        <div style="font-size: 0.82rem; color: #334155;">
                            📦 Courier: <strong><?= e($ordShipping['courier_name'] ?? 'Delhivery Express') ?></strong> &nbsp;|&nbsp; 
                            AWB / Tracking: <strong style="font-family: monospace;"><?= e($ordShipping['tracking_number'] ?? 'Assigned upon packing') ?></strong>
                        </div>
                        <div style="display: flex; gap: 0.6rem; align-items: center;">
                            <a href="track-order.php?order_number=<?= urlencode($ord['order_number']) ?>" style="padding: 0.4rem 0.85rem; background: #2563EB; color: #fff; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-decoration: none;">
                                🚚 Live Track
                            </a>
                            <?php if ($ord['status'] === 'Order Placed' && (empty($ord['cancel_requested']) || (int)$ord['cancel_requested'] === 0)): ?>
                                <button type="button" onclick="openCancelModal(<?= $ord['id'] ?>, '<?= e($ord['order_number']) ?>')" style="padding: 0.4rem 0.85rem; background: #FEF2F2; color: #DC2626; border: 1px solid #F87171; border-radius: 6px; font-size: 0.75rem; font-weight: 800; cursor: pointer;">
                                    ✕ Cancel Order
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Cancellation Request Modal -->
<div id="cancel-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(4px);">
    <div style="background: #FFFFFF; border-radius: 16px; max-width: 480px; width: 100%; padding: 2rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; border-bottom: 1px solid #E2E8F0; padding-bottom: 0.8rem;">
            <h3 style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 900; color: #DC2626; margin: 0;">
                🚫 Request Order Cancellation
            </h3>
            <button onclick="closeCancelModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748B;">&times;</button>
        </div>

        <p style="font-size: 0.84rem; color: #64748B; margin-bottom: 1.2rem; line-height: 1.4;">
            Order #<strong id="modal-order-number" style="color: #0F172A;"></strong> is eligible for cancellation because it has not shipped yet. Please select your reason for our store team:
        </p>

        <form id="cancel-request-form" onsubmit="submitCancelRequest(event)">
            <input type="hidden" id="cancel-order-id" name="order_id" value="">

            <div style="display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1.2rem;">
                <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.86rem; font-weight: 600; cursor: pointer;">
                    <input type="radio" name="cancel_reason" value="Ordered wrong size or color variant" required checked>
                    <span>Ordered wrong size or color variant</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.86rem; font-weight: 600; cursor: pointer;">
                    <input type="radio" name="cancel_reason" value="Found alternative product / Changed mind">
                    <span>Found alternative product / Changed mind</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.86rem; font-weight: 600; cursor: pointer;">
                    <input type="radio" name="cancel_reason" value="Need to change shipping address or contact phone">
                    <span>Need to change shipping address or contact phone</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.86rem; font-weight: 600; cursor: pointer;">
                    <input type="radio" name="cancel_reason" value="Other reason">
                    <span>Other reason</span>
                </label>
            </div>

            <button type="submit" id="btn-submit-cancel" style="width: 100%; padding: 0.85rem; background: #DC2626; color: #FFFFFF; font-weight: 800; font-size: 0.88rem; border: none; border-radius: 8px; cursor: pointer;">
                SUBMIT CANCELLATION REQUEST
            </button>
        </form>
    </div>
</div>

<script>
function openCancelModal(orderId, orderNumber) {
    document.getElementById('cancel-order-id').value = orderId;
    document.getElementById('modal-order-number').textContent = orderNumber;
    document.getElementById('cancel-modal-overlay').style.display = 'flex';
}

function closeCancelModal() {
    document.getElementById('cancel-modal-overlay').style.display = 'none';
}

function submitCancelRequest(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-cancel');
    const orderId = document.getElementById('cancel-order-id').value;
    const reason = document.querySelector('input[name="cancel_reason"]:checked')?.value || 'Customer requested cancellation';

    btn.disabled = true;
    btn.textContent = 'Submitting...';

    const formData = new FormData();
    formData.append('action', 'request_cancel');
    formData.append('order_id', orderId);
    formData.append('reason', reason);

    fetch('api/order_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Cancellation request submitted successfully! Our store team will review it.');
            window.location.reload();
        } else {
            alert(data.message || 'Failed to submit cancellation request.');
            btn.disabled = false;
            btn.textContent = 'SUBMIT CANCELLATION REQUEST';
        }
    })
    .catch(() => {
        alert('Network error while requesting cancellation.');
        btn.disabled = false;
        btn.textContent = 'SUBMIT CANCELLATION REQUEST';
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
