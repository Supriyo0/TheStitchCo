<?php
/**
 * Customer Orders & Post-Delivery Return / Refund Hub
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

$pageTitle = 'My Orders & Invoices | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem 5rem;">
    <!-- Top Breadcrumb & Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 900; color: #0F172A; text-transform: uppercase; margin: 0;">
                My Orders (<?= count($myOrders) ?>)
            </h1>
            <span style="font-size: 0.85rem; color: #64748B;">Download official tax invoices, track live deliveries, and request 7-day returns &amp; UPI refunds.</span>
        </div>
        <div>
            <a href="shop.php" class="btn-fintech-pill">
                <span>Explore Drops &rarr;</span>
            </a>
        </div>
    </div>

    <!-- Account Navigation Sub-Bar (Groww Style Pill Subdock) -->
    <div class="account-subdock" style="display: flex; gap: 0.5rem; overflow-x: auto; padding-bottom: 0.8rem; margin-bottom: 2rem;">
        <a href="dashboard.php" style="padding: 0.6rem 1.1rem; border-radius: 9999px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.4rem;">
            <span>Dashboard</span>
        </a>
        <a href="orders.php" style="padding: 0.6rem 1.1rem; border-radius: 9999px; background: #0F172A; color: #FFFFFF; font-weight: 800; font-size: 0.85rem; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.4rem;">
            <span>My Orders (<?= count($myOrders) ?>)</span>
        </a>
        <a href="wishlist.php" style="padding: 0.6rem 1.1rem; border-radius: 9999px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.4rem;">
            <span>Wishlist</span>
        </a>
        <a href="addresses.php" style="padding: 0.6rem 1.1rem; border-radius: 9999px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.4rem;">
            <span>Saved Addresses</span>
        </a>
        <a href="profile.php" style="padding: 0.6rem 1.1rem; border-radius: 9999px; background: transparent; color: #334155; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.4rem;">
            <span>Profile Settings</span>
        </a>
    </div>

    <!-- Orders Content -->
    <?php if (empty($myOrders)): ?>
        <div style="text-align: center; padding: 4.5rem 1rem; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border-radius: 20px; border: 1.5px solid rgba(255, 255, 255, 0.7); box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06);">
            <div style="font-size: 3rem; margin-bottom: 0.8rem; opacity: 0.6;">📦</div>
            <h3 style="font-size: 1.3rem; font-weight: 900; color: #0F172A; margin-bottom: 0.4rem;">No Orders Placed Yet</h3>
            <p style="color: #64748B; font-size: 0.88rem; margin-bottom: 1.5rem;">When you order products, they will appear here with live tracking, invoice downloads, and returns.</p>
            <a href="shop.php" class="hero-btn-primary" style="font-size: 0.85rem; padding: 0.7rem 1.8rem; text-decoration: none;">Explore Streetwear Catalog</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1.8rem;">
            <?php foreach ($myOrders as $ord): 
                $itStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $itStmt->execute([$ord['id']]);
                $orderItemsList = $itStmt->fetchAll();

                $sStmt = $db->prepare("SELECT * FROM shipping_details WHERE order_id = ? LIMIT 1");
                $sStmt->execute([$ord['id']]);
                $ordShipping = $sStmt->fetch();

                // Fetch return status if any
                $retStmt = $db->prepare("SELECT * FROM order_returns WHERE order_id = ? ORDER BY id DESC LIMIT 1");
                $retStmt->execute([$ord['id']]);
                $ordReturn = $retStmt->fetch();

                $isDelivered = ($ord['status'] === 'Delivered');
                $isCancellable = in_array($ord['status'], ['Order Placed', 'Confirmed', 'Processing']) && (empty($ord['cancel_requested']) || (int)$ord['cancel_requested'] === 0);
            ?>
                <div style="background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(20px); border: 1.5px solid rgba(255, 255, 255, 0.8); border-radius: 20px; padding: 1.8rem; box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06);">
                    
                    <!-- Order Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #E2E8F0; padding-bottom: 1rem; margin-bottom: 1.2rem; flex-wrap: wrap; gap: 0.8rem;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 900; color: #0F172A;">
                                    #<?= e($ord['order_number']) ?>
                                </span>
                                <span class="status-pill status-<?= strtolower(str_replace(' ', '', $ord['status'])) ?>">
                                    <?= e($ord['status']) ?>
                                </span>
                            </div>
                            <div style="font-size: 0.78rem; color: #64748B; margin-top: 4px;">
                                Placed on <?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?> &bull; <strong><?= e($ord['payment_method']) ?></strong>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="text-align: right;">
                                <div style="font-size: 0.72rem; color: #64748B; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px;">Grand Total</div>
                                <div style="font-weight: 900; font-size: 1.3rem; color: #0F172A;">
                                    <?= format_price($ord['total_price']) ?>
                                </div>
                            </div>
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

                    <!-- Active Return / Refund Status Alert Box -->
                    <?php if ($ordReturn): ?>
                        <div style="background: #FFFBEB; border: 1.5px solid #FDE68A; border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.8rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 900; font-size: 0.88rem; color: #92400E;">
                                    <span>🔄</span>
                                    <span>Return &amp; Refund: <strong><?= e($ordReturn['status']) ?></strong></span>
                                </div>
                                <div style="font-size: 0.78rem; color: #78350F; margin-top: 2px;">
                                    Reason: <?= e($ordReturn['reason']) ?> &bull; Refund Destination: <strong>UPI (<?= e($ordReturn['upi_id']) ?>)</strong>
                                    <?php if (!empty($ordReturn['pickup_date'])): ?>
                                        &bull; Pickup Date: <strong><?= date('d M Y', strtotime($ordReturn['pickup_date'])) ?></strong>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <?php if ($ordReturn['status'] === 'Refund Processed'): ?>
                                    <span style="background: #10B981; color: #fff; padding: 0.35rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.5px;">
                                        &#10003; REFUNDED <?= format_price($ordReturn['refund_amount']) ?>
                                    </span>
                                <?php elseif ($ordReturn['status'] === 'Rejected'): ?>
                                    <span style="background: #EF4444; color: #fff; padding: 0.35rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.5px;">
                                        ✕ REJECTED
                                    </span>
                                <?php else: ?>
                                    <span style="background: #D97706; color: #fff; padding: 0.35rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.5px;">
                                        IN PROGRESS (<?= format_price($ordReturn['refund_amount']) ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Actions Bar: Courier info + Invoice Download + Track + Return/Cancel -->
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 1rem 1.3rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.8rem;">
                        <div style="font-size: 0.82rem; color: #334155;">
                            Courier: <strong><?= e($ordShipping['courier_name'] ?? 'Delhivery Express') ?></strong> &nbsp;|&nbsp; 
                            AWB / Tracking: <strong style="font-family: monospace;"><?= e($ordShipping['tracking_number'] ?? 'Assigned upon packing') ?></strong>
                        </div>
                        
                        <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
                            <!-- Official Invoice Download Button -->
                            <a href="invoice.php?order_number=<?= urlencode($ord['order_number']) ?>" target="_blank" style="padding: 0.45rem 0.95rem; background: #0F172A; color: #FFFFFF; border-radius: 8px; font-size: 0.78rem; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; box-shadow: 0 2px 6px rgba(0,0,0,0.1);" title="View &amp; Print Tax Invoice">
                                <span>📄</span>
                                <span>Tax Invoice</span>
                            </a>

                            <!-- Live Tracking Button -->
                            <a href="track-order.php?order_number=<?= urlencode($ord['order_number']) ?>" style="padding: 0.45rem 0.95rem; background: #2563EB; color: #FFFFFF; border-radius: 8px; font-size: 0.78rem; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; box-shadow: 0 2px 6px rgba(37,99,235,0.2);">
                                <span>🚚</span>
                                <span>Live Track</span>
                            </a>

                            <!-- Post-Delivery Return & Refund Button -->
                            <?php if ($isDelivered && !$ordReturn): ?>
                                <button type="button" onclick="openReturnModal(<?= $ord['id'] ?>, '<?= e($ord['order_number']) ?>', <?= (float)$ord['total_price'] ?>)" style="padding: 0.45rem 0.95rem; background: #FEF3C7; color: #92400E; border: 1.5px solid #F59E0B; border-radius: 8px; font-size: 0.78rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem;">
                                    <span>🔄</span>
                                    <span>Return &amp; Refund</span>
                                </button>
                            <?php endif; ?>

                            <!-- Pre-Shipment Cancellation Button -->
                            <?php if ($isCancellable): ?>
                                <button type="button" onclick="openCancelModal(<?= $ord['id'] ?>, '<?= e($ord['order_number']) ?>')" style="padding: 0.45rem 0.95rem; background: #FEF2F2; color: #DC2626; border: 1px solid #F87171; border-radius: 8px; font-size: 0.78rem; font-weight: 800; cursor: pointer;">
                                    ✕ Cancel Order
                                </button>
                            <?php elseif (!empty($ord['cancel_requested']) && (int)$ord['cancel_requested'] === 1): ?>
                                <span style="background: #F1F5F9; color: #64748B; padding: 0.45rem 0.8rem; border-radius: 8px; font-size: 0.75rem; font-weight: 800;">
                                    ⏳ Cancellation Under Review
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Return & Refund Request Modal -->
<div id="return-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999; align-items: center; justify-content: center; padding: 1.25rem; backdrop-filter: blur(5px);">
    <div style="background: #FFFFFF; border-radius: 20px; max-width: 520px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; border-bottom: 1px solid #E2E8F0; padding-bottom: 0.8rem;">
            <div>
                <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 900; color: #0F172A; margin: 0;">
                    🔄 Request 7-Day Return &amp; Refund
                </h3>
                <span style="font-size: 0.78rem; color: #64748B;">Order #<strong id="return-modal-order-number" style="color: #2563EB;"></strong></span>
            </div>
            <button onclick="closeReturnModal()" style="background: none; border: none; font-size: 1.6rem; cursor: pointer; color: #64748B;">&times;</button>
        </div>

        <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 10px; padding: 0.85rem 1rem; margin-bottom: 1.4rem; font-size: 0.82rem; color: #1E40AF;">
            ⚡ <strong>100% Refund Guarantee:</strong> Once approved, our courier will pick up the item from your doorstep. Refund of <strong id="return-modal-refund-amount"></strong> will be disbursed directly to your UPI ID.
        </div>

        <form id="return-request-form" onsubmit="submitReturnRequest(event)">
            <input type="hidden" id="return-order-id" name="order_id" value="">

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-size: 0.84rem; font-weight: 800; color: #0F172A; margin-bottom: 0.4rem;">
                    Reason for Return <span style="color: #EF4444;">*</span>
                </label>
                <select name="reason" id="return-reason-select" required style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1.5px solid #CBD5E1; font-size: 0.85rem; font-weight: 600; color: #0F172A; background: #F8FAFC;">
                    <option value="">-- Select a reason --</option>
                    <option value="Sizing / Fit Issue (Too Large)">Sizing / Fit Issue (Too Large)</option>
                    <option value="Sizing / Fit Issue (Too Tight)">Sizing / Fit Issue (Too Tight)</option>
                    <option value="Received Defective or Damaged Garment">Received Defective or Damaged Garment</option>
                    <option value="Fabric quality / Color differs from photos">Fabric quality / Color differs from photos</option>
                    <option value="Wrong product or size variant delivered">Wrong product or size variant delivered</option>
                    <option value="Changed mind / No longer needed">Changed mind / No longer needed</option>
                </select>
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-size: 0.84rem; font-weight: 800; color: #0F172A; margin-bottom: 0.4rem;">
                    UPI ID for Refund Transfer <span style="color: #EF4444;">*</span>
                </label>
                <input type="text" name="upi_id" id="return-upi-id" required placeholder="e.g. yourname@oksbi or 9876543210@paytm" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1.5px solid #CBD5E1; font-size: 0.85rem; font-weight: 600; color: #0F172A;">
                <span style="font-size: 0.74rem; color: #64748B; margin-top: 3px; display: block;">Your refund will be deposited to this account after pickup verification.</span>
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-size: 0.84rem; font-weight: 800; color: #0F172A; margin-bottom: 0.4rem;">
                    Additional Notes / Feedback (Optional)
                </label>
                <textarea name="notes" id="return-notes" rows="2" placeholder="Tell us more about the issue..." style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1.5px solid #CBD5E1; font-size: 0.85rem; font-weight: 500; resize: vertical;"></textarea>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.84rem; font-weight: 800; color: #0F172A; margin-bottom: 0.4rem;">
                    Attach Product Photos (Optional — Helps Faster Approval)
                </label>
                <input type="file" name="img_front" accept="image/*" style="width: 100%; font-size: 0.78rem; margin-bottom: 0.4rem;">
            </div>

            <button type="submit" id="btn-submit-return" style="width: 100%; padding: 0.9rem; background: #0F172A; color: #FFFFFF; font-weight: 900; font-size: 0.9rem; border: none; border-radius: 10px; cursor: pointer; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(15,23,42,0.2);">
                SUBMIT RETURN &amp; REFUND REQUEST &rarr;
            </button>
        </form>
    </div>
</div>

<!-- Cancellation Request Modal -->
<div id="cancel-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999; align-items: center; justify-content: center; padding: 1.25rem; backdrop-filter: blur(5px);">
    <div style="background: #FFFFFF; border-radius: 20px; max-width: 480px; width: 100%; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; border-bottom: 1px solid #E2E8F0; padding-bottom: 0.8rem;">
            <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 900; color: #DC2626; margin: 0;">
                ✕ Request Order Cancellation
            </h3>
            <button onclick="closeCancelModal()" style="background: none; border: none; font-size: 1.6rem; cursor: pointer; color: #64748B;">&times;</button>
        </div>

        <p style="font-size: 0.84rem; color: #64748B; margin-bottom: 1.2rem; line-height: 1.4;">
            Order #<strong id="modal-order-number" style="color: #0F172A;"></strong> is eligible for cancellation. Please select your reason:
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
                CONFIRM CANCELLATION REQUEST
            </button>
        </form>
    </div>
</div>

<script>
// Return Modal Handlers
function openReturnModal(orderId, orderNumber, totalPrice) {
    document.getElementById('return-order-id').value = orderId;
    document.getElementById('return-modal-order-number').textContent = orderNumber;
    document.getElementById('return-modal-refund-amount').textContent = '₹' + Number(totalPrice).toLocaleString('en-IN');
    document.getElementById('return-modal-overlay').style.display = 'flex';
}

function closeReturnModal() {
    document.getElementById('return-modal-overlay').style.display = 'none';
}

function submitReturnRequest(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-return');
    btn.disabled = true;
    btn.textContent = 'Submitting Request...';

    const form = document.getElementById('return-request-form');
    const formData = new FormData(form);
    formData.append('action', 'request_return');

    fetch('api/customer_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Return request submitted successfully!');
            window.location.reload();
        } else {
            alert(data.message || 'Failed to submit return request.');
            btn.disabled = false;
            btn.textContent = 'SUBMIT RETURN & REFUND REQUEST →';
        }
    })
    .catch(() => {
        alert('Network error while submitting return request.');
        btn.disabled = false;
        btn.textContent = 'SUBMIT RETURN & REFUND REQUEST →';
    });
}

// Cancel Modal Handlers
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
    formData.append('action', 'request_cancellation');
    formData.append('order_id', orderId);
    formData.append('cancel_reason', reason);

    fetch('api/customer_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Cancellation request submitted successfully!');
            window.location.reload();
        } else {
            alert(data.message || 'Failed to submit cancellation request.');
            btn.disabled = false;
            btn.textContent = 'CONFIRM CANCELLATION REQUEST';
        }
    })
    .catch(() => {
        alert('Network error while requesting cancellation.');
        btn.disabled = false;
        btn.textContent = 'CONFIRM CANCELLATION REQUEST';
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
