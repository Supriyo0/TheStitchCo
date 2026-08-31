<?php
/**
 * Customer Orders & Payment Verification Management
 * Matches localhost:3001/orders Screenshot
 * The Stitch Co.
 */

$adminTitle = 'Order Management';
require_once __DIR__ . '/header.php';

$filterStatus = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT o.*, p.id AS payment_id, p.utr_number, p.proof_screenshot, p.status AS p_status, s.courier_name, s.tracking_number
    FROM orders o
    LEFT JOIN payments p ON o.id = p.order_id
    LEFT JOIN shipping_details s ON o.id = s.order_id
    WHERE 1=1
";
$params = [];

if ($filterStatus === 'pending_upi') {
    $sql .= " AND o.payment_status = 'Pending' AND o.status != 'Cancelled'";
} elseif ($filterStatus !== 'all' && !empty($filterStatus)) {
    $sql .= " AND o.status = ?";
    $params[] = $filterStatus;
}

if (!empty($search)) {
    $sql .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ? OR p.utr_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY o.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Customer Orders</h2>
            <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Manage customer orders, verify UPI payments, and track order fulfillment.</span>
        </div>
        <div style="display: flex; gap: 0.8rem;">
            <a href="orders.php" class="view-store-btn">🔄 Refresh</a>
        </div>
    </div>

    <!-- Filter Tabs & Search Bar -->
    <div style="padding: 1.2rem 1.5rem; background: #FAFBFD; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div class="filter-tabs">
            <a href="orders.php?status=all" class="filter-tab-btn <?= $filterStatus === 'all' ? 'active' : '' ?>">All Orders</a>
            <a href="orders.php?status=pending_upi" class="filter-tab-btn <?= $filterStatus === 'pending_upi' ? 'active' : '' ?>">Pending UPI Approval</a>
            <a href="orders.php?status=Confirmed" class="filter-tab-btn <?= $filterStatus === 'Confirmed' ? 'active' : '' ?>">Confirmed</a>
            <a href="orders.php?status=Processing" class="filter-tab-btn <?= $filterStatus === 'Processing' ? 'active' : '' ?>">Processing</a>
            <a href="orders.php?status=Shipped" class="filter-tab-btn <?= $filterStatus === 'Shipped' ? 'active' : '' ?>">Shipped</a>
            <a href="orders.php?status=Delivered" class="filter-tab-btn <?= $filterStatus === 'Delivered' ? 'active' : '' ?>">Delivered</a>
            <a href="orders.php?status=Cancelled" class="filter-tab-btn <?= $filterStatus === 'Cancelled' ? 'active' : '' ?>">Cancelled</a>
        </div>

        <form action="orders.php" method="GET" style="display: flex; gap: 0.4rem;">
            <?php if ($filterStatus !== 'all'): ?>
                <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="Search Order #, Customer, UTR..." value="<?= e($search) ?>" style="padding: 0.45rem 0.85rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.82rem; width: 220px;">
            <button type="submit" class="filter-tab-btn" style="background: var(--admin-sidebar-bg); color: #fff;">SEARCH</button>
        </form>
    </div>

    <!-- Orders Table matching localhost:3001/orders -->
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order Details</th>
                    <th>Customer</th>
                    <th>Payment & UTR</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions / Approval</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--admin-text-muted);">
                            No orders found matching this criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td>
                                <strong style="font-weight: 800;"><?= e($ord['order_number']) ?></strong><br>
                                <span style="font-size: 0.72rem; color: var(--admin-text-muted);">🕒 <?= date('d M, h:i a', strtotime($ord['created_at'])) ?></span>
                            </td>
                            <td>
                                <strong style="font-weight: 700;"><?= e($ord['customer_name']) ?></strong><br>
                                <span style="font-size: 0.75rem; color: var(--admin-text-muted);"><?= e($ord['customer_email']) ?></span><br>
                                <span style="font-size: 0.75rem; color: var(--admin-text-muted);"><?= e($ord['customer_phone']) ?></span>
                            </td>
                            <td>
                                <div style="font-size: 0.82rem; font-weight: 700; color: #2563EB;">
                                    💳 <?= e($ord['payment_method']) ?>
                                </div>
                                <?php if (!empty($ord['utr_number'])): ?>
                                    <div style="font-size: 0.75rem; font-family: monospace; color: var(--admin-text-main); font-weight: 800;">
                                        UTR: <?= e($ord['utr_number']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($ord['proof_screenshot'])): ?>
                                    <button onclick="viewProofModal('<?= e($ord['proof_screenshot']) ?>')" style="background: none; border: none; color: #2563EB; font-size: 0.72rem; font-weight: 700; cursor: pointer; padding: 0; text-decoration: underline; margin-top: 0.2rem;">
                                        📷 View Proof Screenshot
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="font-weight: 800; font-size: 0.95rem;"><?= format_price($ord['total_price']) ?></strong>
                            </td>
                            <td>
                                <span class="status-pill status-<?= strtolower(str_replace(' ', '', $ord['status'])) ?>">
                                    <?= e($ord['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.45rem;">
                                    <!-- Status Selector -->
                                    <select onchange="handleStatusChange(<?= $ord['id'] ?>, this.value, '<?= e($ord['courier_name'] ?? 'Delhivery') ?>', '<?= e($ord['tracking_number'] ?? '') ?>')" style="padding: 0.35rem 0.6rem; border-radius: 6px; border: 1.5px solid var(--admin-border); font-size: 0.8rem; font-weight: 700; background: #FFFFFF;">
                                        <?php foreach (['Order Placed', 'Confirmed', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'] as $stOpt): ?>
                                            <option value="<?= $stOpt ?>" <?= $ord['status'] === $stOpt ? 'selected' : '' ?>><?= $stOpt ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <!-- Quick Action Buttons -->
                                    <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                        <?php if ($ord['payment_status'] === 'Pending' && !empty($ord['payment_id'])): ?>
                                            <button onclick="approvePayment(<?= $ord['payment_id'] ?>)" style="background: #10B981; color: #fff; border: none; padding: 0.25rem 0.6rem; border-radius: 4px; font-size: 0.72rem; font-weight: 800; cursor: pointer;">✓ Verify</button>
                                        <?php endif; ?>
                                        <button onclick="openShippingModal(<?= $ord['id'] ?>, '<?= e($ord['order_number']) ?>', '<?= e($ord['courier_name'] ?? 'Delhivery') ?>', '<?= e($ord['tracking_number'] ?? '') ?>')" style="background: #2563EB; color: #fff; border: none; padding: 0.25rem 0.6rem; border-radius: 4px; font-size: 0.72rem; font-weight: 800; cursor: pointer;">
                                            🚚 Shipping
                                        </button>
                                        <a href="../invoice.php?order_number=<?= urlencode($ord['order_number']) ?>" target="_blank" style="font-size: 0.72rem; color: var(--admin-text-muted); font-weight: 700; text-decoration: underline; align-self: center;">Invoice</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Shipping Dispatch Details Modal -->
<div class="admin-modal-overlay" id="shipping-modal-overlay">
    <div class="admin-modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 800;" id="ship-modal-title">Update Shipping Details</h3>
            <button onclick="closeShippingModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form id="shipping-details-form" onsubmit="submitShippingDetails(event)">
            <input type="hidden" id="ship-order-id" name="order_id">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Courier / Shipping Partner *</label>
                <select id="ship-courier" name="courier_name" required style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; background: #fff; font-weight: 700;">
                    <option value="Delhivery">Delhivery</option>
                    <option value="BlueDart">BlueDart</option>
                    <option value="DTDC">DTDC Express</option>
                    <option value="XpressBees">XpressBees</option>
                    <option value="Shadowfax">Shadowfax</option>
                    <option value="India Post Speed Post">India Post Speed Post</option>
                </select>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Tracking Number / AWB *</label>
                <input type="text" id="ship-tracking-no" name="tracking_number" required placeholder="e.g. DEL123456789IN" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 800;">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Tracking URL (Optional)</label>
                <input type="url" id="ship-tracking-url" name="tracking_url" placeholder="https://www.delhivery.com/track/package/..." style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
            </div>
            <div style="display: flex; gap: 0.8rem;">
                <button type="submit" style="flex: 1; padding: 0.75rem; background: #2563EB; color: #fff; border: none; border-radius: 6px; font-weight: 800; cursor: pointer;">
                    SAVE & DISPATCH ORDER 🚚
                </button>
                <button type="button" onclick="closeShippingModal()" style="padding: 0.75rem 1rem; background: #F3F4F6; border: 1px solid var(--admin-border); border-radius: 6px; font-weight: 700; cursor: pointer;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function handleStatusChange(orderId, newStatus, currentCourier, currentTracking) {
    if (newStatus === 'Shipped') {
        openShippingModal(orderId, '', currentCourier, currentTracking);
    } else {
        updateOrderStatus(orderId, newStatus);
    }
}

function openShippingModal(orderId, orderNo, courier, tracking) {
    document.getElementById('ship-order-id').value = orderId;
    if (orderNo) document.getElementById('ship-modal-title').textContent = 'Shipping Details for #' + orderNo;
    if (courier) document.getElementById('ship-courier').value = courier;
    if (tracking) document.getElementById('ship-tracking-no').value = tracking;
    document.getElementById('shipping-modal-overlay').classList.add('active');
}

function closeShippingModal() {
    document.getElementById('shipping-modal-overlay').classList.remove('active');
}

function submitShippingDetails(e) {
    e.preventDefault();
    const orderId = document.getElementById('ship-order-id').value;
    const courier = document.getElementById('ship-courier').value;
    const trackingNo = document.getElementById('ship-tracking-no').value;
    const trackingUrl = document.getElementById('ship-tracking-url').value;

    const formData = new FormData();
    formData.append('action', 'update_shipping');
    formData.append('order_id', orderId);
    formData.append('courier_name', courier);
    formData.append('tracking_number', trackingNo);
    formData.append('tracking_url', trackingUrl);

    fetch('../api/admin_actions.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeShippingModal();
                location.reload();
            } else {
                alert(data.message || 'Error saving shipping details');
            }
        });
}

function updateOrderStatus(orderId, newStatus) {
    const formData = new FormData();
    formData.append('action', 'update_order_status');
    formData.append('order_id', orderId);
    formData.append('status', newStatus);

    fetch('../api/admin_actions.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error updating status');
            }
        });
}

function approvePayment(paymentId) {
    if (!confirm('Are you sure you want to approve this UPI payment?')) return;
    const formData = new FormData();
    formData.append('action', 'verify_payment');
    formData.append('payment_id', paymentId);
    formData.append('status', 'Approved');

    fetch('../api/admin_actions.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error verifying payment');
            }
        });
}

function rejectPayment(paymentId) {
    const reason = prompt('Please enter reason for rejection:');
    if (reason === null) return;

    const formData = new FormData();
    formData.append('action', 'verify_payment');
    formData.append('payment_id', paymentId);
    formData.append('status', 'Rejected');
    formData.append('admin_notes', reason);

    fetch('../api/admin_actions.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error updating payment');
            }
        });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
