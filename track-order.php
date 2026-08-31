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
$orderReturn = null;
$db = get_db();

// Safe self-healing schema checks
try {
    $chkCol = $db->query("SHOW COLUMNS FROM `orders` LIKE 'cancel_requested'")->fetch();
    if (!$chkCol) {
        $db->exec("ALTER TABLE `orders` ADD COLUMN `cancel_requested` TINYINT(1) DEFAULT 0");
        $db->exec("ALTER TABLE `orders` ADD COLUMN `cancel_requested_at` DATETIME NULL DEFAULT NULL");
        $db->exec("ALTER TABLE `orders` ADD COLUMN `cancel_reason` TEXT DEFAULT NULL");
        $db->exec("ALTER TABLE `orders` ADD COLUMN `cancel_admin_note` TEXT DEFAULT NULL");
    }
} catch (Exception $e) {}

if (!empty($orderNumber)) {
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

        try {
            $histStmt = $db->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY id ASC");
            $histStmt->execute([$order['id']]);
            $statusHistory = $histStmt->fetchAll();
        } catch (Exception $e) {}

        try {
            $retStmt = $db->prepare("SELECT * FROM order_returns WHERE order_id = ? LIMIT 1");
            $retStmt->execute([$order['id']]);
            $orderReturn = $retStmt->fetch();
        } catch (Exception $e) {}
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
            <div style="border-top: 1px solid var(--border); padding-top: 1.2rem; margin-bottom: 1.5rem;">
                <h4 style="font-size: 0.85rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.8rem;">Ordered Items (<?= count($orderItems) ?>)</h4>
                <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                    <?php foreach ($orderItems as $it): 
                        $itThumb = get_media_url($it['image'] ?? '');
                    ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; background: #F8FAFC; padding: 0.8rem 1rem; border-radius: var(--radius-sm);">
                            <div style="display: flex; gap: 0.8rem; align-items: center;">
                                <img src="<?= e($itThumb) ?>" alt="<?= e($it['product_name']) ?>" onerror="this.onerror=null; this.src='assets/images/placeholder.svg';" style="width: 44px; height: 52px; object-fit: cover; border-radius: 4px;">
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
            <!-- Post-Delivery Return & Refund Status Banner -->
            <?php if (!empty($orderReturn)): ?>
                <div style="background: #F0FDF4; border: 1.5px solid #86EFAC; border-radius: 8px; padding: 1.2rem; margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <strong style="color: #166534; font-size: 0.95rem;">🔄 Return & Refund Status: <?= e($orderReturn['status']) ?></strong>
                            <div style="font-size: 0.82rem; color: #15803D; margin-top: 3px;">
                                Reason: <strong><?= e($orderReturn['reason']) ?></strong> | Refund: <strong><?= format_price($orderReturn['refund_amount']) ?></strong> to UPI <code><?= e($orderReturn['upi_id']) ?></code>
                            </div>
                        </div>
                        <span style="font-size: 0.8rem; background: #DCFCE7; color: #15803D; padding: 0.35rem 0.75rem; border-radius: 6px; font-weight: 800;">
                            <?= e($orderReturn['status']) ?>
                        </span>
                    </div>
                    <?php if (!empty($orderReturn['pickup_date']) && strpos($orderReturn['status'], 'Approved') !== false): ?>
                        <div style="margin-top: 0.8rem; font-size: 0.84rem; background: #EFF6FF; border: 1px solid #BFDBFE; color: #1E40AF; padding: 0.6rem 0.9rem; border-radius: 6px; font-weight: 700;">
                            🚚 Reverse Logistics Pickup Scheduled: <strong><?= date('d M Y', strtotime($orderReturn['pickup_date'])) ?></strong> via <?= e($orderReturn['courier_name']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($orderReturn['admin_note'])): ?>
                        <div style="margin-top: 0.6rem; font-size: 0.82rem; color: #166534; background: #DCFCE7; padding: 0.5rem 0.8rem; border-radius: 6px;">
                            📝 <strong>Store Update:</strong> <?= e($orderReturn['admin_note']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($orderReturn['refund_ref'])): ?>
                        <div style="margin-top: 0.6rem; font-size: 0.85rem; color: #047857; font-weight: 800;">
                            💸 UPI Payout Reference / UTR: <code><?= e($orderReturn['refund_ref']) ?></code>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Pre-Shipment Cancellation Banner / Store Notes -->
            <?php if (!empty($order['cancel_requested']) && (int)$order['cancel_requested'] === 1 && $order['status'] !== 'Cancelled'): ?>
                <div style="background: #FFFBEB; border: 1.5px solid #FCD34D; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.6rem;">
                    <div>
                        <strong style="color: #B45309; font-size: 0.88rem;">⏳ Cancellation Request Submitted (Under Review)</strong>
                        <div style="font-size: 0.78rem; color: #92400E; margin-top: 2px;">Reason: <?= e($order['cancel_reason']) ?></div>
                    </div>
                    <span style="font-size: 0.75rem; background: #FEF3C7; color: #92400E; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 800;">Pending Admin Review</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($order['admin_note']) && empty($orderReturn)): ?>
                <div style="background: #EFF6FF; border: 1.5px solid #BFDBFE; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; font-size: 0.84rem; color: #1E40AF;">
                    <strong>📝 Store Cancellation / Status Note:</strong> <?= e($order['admin_note']) ?>
                </div>
            <?php endif; ?>

            <!-- Order Action Buttons -->
            <div style="display: flex; justify-content: flex-end; gap: 0.8rem; margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1.2rem; flex-wrap: wrap;">
                <?php if (!in_array($order['status'], ['Shipped', 'Out for Delivery', 'Delivered', 'Cancelled']) && empty($order['cancel_requested'])): ?>
                    <button type="button" onclick="openCancelModal(<?= $order['id'] ?>, '<?= e($order['order_number']) ?>')" style="background: #FEF2F2; color: #DC2626; border: 1.5px solid #FECACA; font-size: 0.82rem; font-weight: 800; padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); cursor: pointer;">
                        🚫 Request Cancellation
                    </button>
                <?php endif; ?>

                <?php if ($order['status'] === 'Delivered' && empty($orderReturn)): ?>
                    <button type="button" onclick="openReturnModal(<?= $order['id'] ?>, '<?= e($order['order_number']) ?>', <?= $order['total_price'] ?>)" style="background: #ECFDF5; color: #059669; border: 1.5px solid #A7F3D0; font-size: 0.82rem; font-weight: 800; padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); cursor: pointer;">
                        🔄 7-Day Return / Refund
                    </button>
                <?php endif; ?>

                <a href="invoice.php?order_number=<?= urlencode($order['order_number']) ?>" target="_blank" class="hero-btn" style="background: #fff; color: #000; border: 1.5px solid var(--border); font-size: 0.82rem; padding: 0.6rem 1.2rem; text-decoration: none;">
                    Download Invoice 📄
                </a>
            </div>
        </div>
    <?php endif; ?>
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
