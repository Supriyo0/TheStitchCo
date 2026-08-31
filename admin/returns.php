<?php
/**
 * Post-Delivery Returns, Reverse Pickups & UPI Refund Management Module
 * The Stitch Co.
 */

$adminTitle = 'Returns & UPI Refunds';
require_once __DIR__ . '/header.php';

// Ensure table exists
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS `order_returns` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `order_number` VARCHAR(50) NOT NULL,
            `customer_id` INT NOT NULL,
            `customer_name` VARCHAR(255) NOT NULL,
            `customer_phone` VARCHAR(50) NOT NULL,
            `customer_email` VARCHAR(255) NOT NULL,
            `reason` VARCHAR(255) NOT NULL,
            `notes` TEXT DEFAULT NULL,
            `img_front` VARCHAR(255) NOT NULL,
            `img_back` VARCHAR(255) NOT NULL,
            `img_tag` VARCHAR(255) NOT NULL,
            `upi_id` VARCHAR(100) DEFAULT NULL,
            `return_type` ENUM('refund', 'exchange') DEFAULT 'refund',
            `status` ENUM('Pending Review', 'Approved - Pickup Scheduled', 'Pickup Completed', 'Refund Processed', 'Rejected') DEFAULT 'Pending Review',
            `admin_note` TEXT DEFAULT NULL,
            `pickup_date` DATE DEFAULT NULL,
            `courier_name` VARCHAR(100) DEFAULT 'Delhivery Reverse Pickup',
            `tracking_number` VARCHAR(100) DEFAULT NULL,
            `refund_amount` DECIMAL(10,2) NOT NULL,
            `refund_ref` VARCHAR(100) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`order_id`),
            INDEX (`customer_id`),
            INDEX (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Exception $e) {}

$filterStatus = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT r.*, o.shipping_address, o.payment_method FROM order_returns r JOIN orders o ON r.order_id = o.id WHERE 1=1";
$params = [];

if ($filterStatus !== 'all' && !empty($filterStatus)) {
    $sql .= " AND r.status = ?";
    $params[] = $filterStatus;
}

if (!empty($search)) {
    $sql .= " AND (r.order_number LIKE ? OR r.customer_name LIKE ? OR r.customer_phone LIKE ? OR r.upi_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY r.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$returns = $stmt->fetchAll();

$pendingCount = (int)$db->query("SELECT COUNT(*) FROM order_returns WHERE status = 'Pending Review'")->fetchColumn();
$approvedCount = (int)$db->query("SELECT COUNT(*) FROM order_returns WHERE status = 'Approved - Pickup Scheduled'")->fetchColumn();
$pickupDoneCount = (int)$db->query("SELECT COUNT(*) FROM order_returns WHERE status = 'Pickup Completed'")->fetchColumn();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Customer Returns & UPI Refunds</h2>
            <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Inspect customer product verification photos, schedule reverse pickups, and disburse UPI refunds.</span>
        </div>
        <div style="display: flex; gap: 0.8rem;">
            <a href="returns.php" class="view-store-btn">🔄 Refresh</a>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div style="padding: 1.2rem 1.5rem; background: #FAFBFD; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div class="filter-tabs">
            <a href="returns.php?status=all" class="filter-tab-btn <?= $filterStatus === 'all' ? 'active' : '' ?>">All Requests</a>
            <a href="returns.php?status=Pending Review" class="filter-tab-btn <?= $filterStatus === 'Pending Review' ? 'active' : '' ?>" style="<?= $pendingCount > 0 ? 'background: #FEF2F2; color: #DC2626; border-color: #F87171; font-weight: 800;' : '' ?>">
                ⏳ Pending Review <?= $pendingCount > 0 ? "({$pendingCount})" : '' ?>
            </a>
            <a href="returns.php?status=Approved - Pickup Scheduled" class="filter-tab-btn <?= $filterStatus === 'Approved - Pickup Scheduled' ? 'active' : '' ?>" style="<?= $approvedCount > 0 ? 'background: #EFF6FF; color: #2563EB; font-weight: 800;' : '' ?>">
                🚚 Pickups Scheduled <?= $approvedCount > 0 ? "({$approvedCount})" : '' ?>
            </a>
            <a href="returns.php?status=Pickup Completed" class="filter-tab-btn <?= $filterStatus === 'Pickup Completed' ? 'active' : '' ?>" style="<?= $pickupDoneCount > 0 ? 'background: #FEF3C7; color: #92400E; font-weight: 800;' : '' ?>">
                📦 Pickup Completed (Need Refund) <?= $pickupDoneCount > 0 ? "({$pickupDoneCount})" : '' ?>
            </a>
            <a href="returns.php?status=Refund Processed" class="filter-tab-btn <?= $filterStatus === 'Refund Processed' ? 'active' : '' ?>">✓ Refunded</a>
            <a href="returns.php?status=Rejected" class="filter-tab-btn <?= $filterStatus === 'Rejected' ? 'active' : '' ?>">✕ Rejected</a>
        </div>

        <form action="returns.php" method="GET" style="display: flex; gap: 0.4rem;">
            <?php if ($filterStatus !== 'all'): ?>
                <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="Search Order #, Phone, UPI..." value="<?= e($search) ?>" style="padding: 0.45rem 0.85rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.82rem; width: 220px;">
            <button type="submit" class="filter-tab-btn" style="background: var(--admin-sidebar-bg); color: #fff;">SEARCH</button>
        </form>
    </div>

    <!-- Returns Table -->
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order & Customer</th>
                    <th>Reason & Remarks</th>
                    <th>3 Verification Photos</th>
                    <th>Refund Amount & UPI</th>
                    <th>Status</th>
                    <th>Actions & Reverse Pickup</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($returns)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem; color: var(--admin-text-muted);">
                            No return or refund requests found matching this filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($returns as $ret): ?>
                        <tr>
                            <td>
                                <strong style="font-weight: 800; font-size: 0.95rem;"><?= e($ret['order_number']) ?></strong><br>
                                <span style="font-size: 0.72rem; color: var(--admin-text-muted);">🕒 Requested: <?= date('d M Y, h:i A', strtotime($ret['created_at'])) ?></span>
                                <div style="margin-top: 0.4rem; font-size: 0.82rem;">
                                    <strong><?= e($ret['customer_name']) ?></strong><br>
                                    <span style="color: var(--admin-text-muted);"><?= e($ret['customer_phone']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 800; color: #DC2626; font-size: 0.85rem; margin-bottom: 0.2rem;">
                                    <?= e($ret['reason']) ?>
                                </div>
                                <?php if (!empty($ret['notes'])): ?>
                                    <div style="font-size: 0.78rem; color: #4B5563; background: #F3F4F6; padding: 0.4rem 0.6rem; border-radius: 4px; line-height: 1.3;">
                                        "<?= e($ret['notes']) ?>"
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($ret['admin_note'])): ?>
                                    <div style="margin-top: 0.4rem; font-size: 0.74rem; background: #EFF6FF; border: 1px solid #BFDBFE; color: #1E40AF; padding: 0.3rem 0.6rem; border-radius: 4px; font-weight: 700;">
                                        📝 Admin Note: <?= e($ret['admin_note']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.4rem; align-items: center;">
                                    <!-- Front Photo -->
                                    <div style="text-align: center;">
                                        <img src="../<?= e($ret['img_front']) ?>" onclick="viewPhotoModal('../<?= e($ret['img_front']) ?>', 'Front View Photo')" alt="Front" style="width: 50px; height: 60px; object-fit: cover; border-radius: 4px; border: 1.5px solid var(--admin-border); cursor: pointer;">
                                        <div style="font-size: 0.65rem; font-weight: 700; color: var(--admin-text-muted);">Front</div>
                                    </div>
                                    <!-- Back Photo -->
                                    <div style="text-align: center;">
                                        <img src="../<?= e($ret['img_back']) ?>" onclick="viewPhotoModal('../<?= e($ret['img_back']) ?>', 'Back View Photo')" alt="Back" style="width: 50px; height: 60px; object-fit: cover; border-radius: 4px; border: 1.5px solid var(--admin-border); cursor: pointer;">
                                        <div style="font-size: 0.65rem; font-weight: 700; color: var(--admin-text-muted);">Back</div>
                                    </div>
                                    <!-- Tag Photo -->
                                    <div style="text-align: center;">
                                        <img src="../<?= e($ret['img_tag']) ?>" onclick="viewPhotoModal('../<?= e($ret['img_tag']) ?>', 'Brand / Price Tag Photo')" alt="Tag" style="width: 50px; height: 60px; object-fit: cover; border-radius: 4px; border: 1.5px solid #3B82F6; cursor: pointer;">
                                        <div style="font-size: 0.65rem; font-weight: 800; color: #2563EB;">🏷️ Tag</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 1rem; font-weight: 900; color: var(--admin-primary); margin-bottom: 0.3rem;">
                                    <?= format_price($ret['refund_amount']) ?>
                                </div>
                                <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 6px; padding: 0.4rem 0.6rem;">
                                    <span style="font-size: 0.68rem; font-weight: 800; color: #166534; text-transform: uppercase; display: block;">UPI ID FOR REFUND</span>
                                    <div style="font-family: monospace; font-size: 0.85rem; font-weight: 800; color: #15803D; word-break: break-all;">
                                        <?= e($ret['upi_id']) ?>
                                    </div>
                                    <button onclick="copyToClipboard('<?= e($ret['upi_id']) ?>')" style="padding: 0.2rem 0.5rem; background: #22C55E; color: #fff; border: none; border-radius: 4px; font-size: 0.68rem; font-weight: 800; cursor: pointer; margin-top: 0.2rem;">
                                        📋 Copy UPI ID
                                    </button>
                                </div>
                                <?php if (!empty($ret['refund_ref'])): ?>
                                    <div style="font-size: 0.72rem; color: #059669; font-weight: 700; margin-top: 0.3rem;">
                                        Payout Ref: <code><?= e($ret['refund_ref']) ?></code>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    $pillClass = 'status-pending';
                                    if (strpos($ret['status'], 'Approved') !== false) $pillClass = 'status-confirmed';
                                    elseif ($ret['status'] === 'Pickup Completed') $pillClass = 'status-processing';
                                    elseif ($ret['status'] === 'Refund Processed') $pillClass = 'status-delivered';
                                    elseif ($ret['status'] === 'Rejected') $pillClass = 'status-cancelled';
                                ?>
                                <span class="status-pill <?= $pillClass ?>">
                                    <?= e($ret['status']) ?>
                                </span>
                                <?php if (!empty($ret['pickup_date']) && $ret['status'] === 'Approved - Pickup Scheduled'): ?>
                                    <div style="font-size: 0.72rem; color: #2563EB; font-weight: 800; margin-top: 0.3rem;">
                                        🚚 Pickup: <?= date('d M Y', strtotime($ret['pickup_date'])) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.45rem;">
                                    <?php if ($ret['status'] === 'Pending Review'): ?>
                                        <!-- Step 1: Approve & Schedule Pickup -->
                                        <button onclick="openPickupModal(<?= $ret['id'] ?>, '<?= e($ret['order_number']) ?>')" style="padding: 0.45rem 0.75rem; background: #2563EB; color: #fff; border: none; border-radius: 6px; font-weight: 800; font-size: 0.78rem; cursor: pointer;">
                                            ✓ Approve & Schedule Pickup
                                        </button>
                                        <button onclick="rejectReturnModal(<?= $ret['id'] ?>, '<?= e($ret['order_number']) ?>')" style="padding: 0.4rem 0.75rem; background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; border-radius: 6px; font-weight: 800; font-size: 0.75rem; cursor: pointer;">
                                            ✕ Reject Request
                                        </button>

                                    <?php elseif ($ret['status'] === 'Approved - Pickup Scheduled'): ?>
                                        <!-- Step 2: Mark Pickup Done -->
                                        <button onclick="completePickup(<?= $ret['id'] ?>, '<?= e($ret['order_number']) ?>')" style="padding: 0.45rem 0.75rem; background: #F59E0B; color: #fff; border: none; border-radius: 6px; font-weight: 800; font-size: 0.78rem; cursor: pointer;">
                                            📦 Mark Pickup Completed
                                        </button>
                                        <button onclick="openRefundModal(<?= $ret['id'] ?>, '<?= e($ret['order_number']) ?>', '<?= e($ret['upi_id']) ?>', <?= $ret['refund_amount'] ?>)" style="padding: 0.45rem 0.75rem; background: #10B981; color: #fff; border: none; border-radius: 6px; font-weight: 800; font-size: 0.78rem; cursor: pointer;">
                                            💸 Issue Refund Now
                                        </button>

                                    <?php elseif ($ret['status'] === 'Pickup Completed'): ?>
                                        <!-- Step 3: Issue UPI Refund -->
                                        <button onclick="openRefundModal(<?= $ret['id'] ?>, '<?= e($ret['order_number']) ?>', '<?= e($ret['upi_id']) ?>', <?= $ret['refund_amount'] ?>)" style="padding: 0.5rem 0.85rem; background: #10B981; color: #fff; border: none; border-radius: 6px; font-weight: 900; font-size: 0.82rem; cursor: pointer; box-shadow: 0 4px 12px rgba(16,185,129,0.3);">
                                            💸 Process UPI Refund (<?= format_price($ret['refund_amount']) ?>)
                                        </button>

                                    <?php else: ?>
                                        <span style="font-size: 0.75rem; color: var(--admin-text-muted); font-weight: 700;">Completed</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Schedule Pickup -->
<div class="admin-modal-overlay" id="pickup-modal-overlay">
    <div class="admin-modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 800;">Approve Return & Schedule Pickup</h3>
            <button onclick="closePickupModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form onsubmit="submitPickupSchedule(event)">
            <input type="hidden" id="modal-pickup-return-id" value="">

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Pickup Date *</label>
                <input type="date" id="modal-pickup-date" required value="<?= date('Y-m-d') ?>" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 700;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Reverse Logistics Partner</label>
                <select id="modal-pickup-courier" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 700; background: #fff;">
                    <option value="Delhivery Reverse Pickup">Delhivery Reverse Logistics (Recommended)</option>
                    <option value="Shadowfax Reverse">Shadowfax Reverse Delivery</option>
                    <option value="Bluedart Surface Return">Bluedart Surface Return</option>
                    <option value="XpressBees Returns">XpressBees Returns</option>
                    <option value="Store Executive Pickup">Local Store Executive Pickup</option>
                </select>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Note / Message to Customer</label>
                <textarea id="modal-pickup-note" rows="2" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-size: 0.82rem;">Return approved! Our courier pickup executive will visit your address for product pickup today. Please keep the item packed with tags intact.</textarea>
            </div>

            <div style="display: flex; gap: 0.8rem; justify-content: flex-end;">
                <button type="button" onclick="closePickupModal()" style="padding: 0.65rem 1.2rem; background: #F3F4F6; color: #374151; border: none; border-radius: 6px; font-weight: 700; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 0.65rem 1.5rem; background: #2563EB; color: #fff; border: none; border-radius: 6px; font-weight: 800; cursor: pointer;">CONFIRM PICKUP SCHEDULE</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Process UPI Refund -->
<div class="admin-modal-overlay" id="refund-modal-overlay">
    <div class="admin-modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 800;">Disburse Customer UPI Refund</h3>
            <button onclick="closeRefundModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form onsubmit="submitRefundDisbursement(event)">
            <input type="hidden" id="modal-refund-return-id" value="">

            <div style="background: #F0FDF4; border: 1.5px solid #86EFAC; border-radius: 8px; padding: 1rem; margin-bottom: 1.2rem;">
                <div style="font-size: 0.8rem; font-weight: 700; color: #166534; margin-bottom: 0.2rem;">PAYOUT DESTINATION:</div>
                <div style="font-family: monospace; font-size: 1.1rem; font-weight: 900; color: #15803D;" id="modal-refund-upi"></div>
                <div style="font-size: 0.9rem; font-weight: 800; color: #166534; margin-top: 0.4rem;" id="modal-refund-amount"></div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">UPI Payout / Bank Reference Number (UTR) *</label>
                <input type="text" id="modal-refund-ref" required placeholder="e.g. 423156789012" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 800; font-family: monospace;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Refund Completion Note for Customer</label>
                <textarea id="modal-refund-note" rows="2" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-size: 0.82rem;">Refund processed and credited to your UPI ID. Thank you for your patience!</textarea>
            </div>

            <div style="display: flex; gap: 0.8rem; justify-content: flex-end;">
                <button type="button" onclick="closeRefundModal()" style="padding: 0.65rem 1.2rem; background: #F3F4F6; color: #374151; border: none; border-radius: 6px; font-weight: 700; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 0.65rem 1.5rem; background: #10B981; color: #fff; border: none; border-radius: 6px; font-weight: 800; cursor: pointer;">CONFIRM REFUND DISBURSED</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Lightbox View Photo -->
<div class="admin-modal-overlay" id="photo-modal-overlay">
    <div class="admin-modal-box" style="text-align: center; max-width: 520px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; font-weight: 800;" id="photo-modal-title">Verification Photo</h3>
            <button onclick="closePhotoModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div style="max-height: 480px; overflow-y: auto; background: #F9FAFB; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--admin-border);">
            <img id="photo-modal-img" src="" alt="Verification Proof" style="max-width: 100%; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        </div>
        <button onclick="closePhotoModal()" style="padding: 0.6rem 1.5rem; background: #111827; color: #fff; border-radius: 6px; border: none; font-weight: 700; cursor: pointer;">
            Close
        </button>
    </div>
</div>

<script>
function viewPhotoModal(src, title) {
    document.getElementById('photo-modal-img').src = src;
    document.getElementById('photo-modal-title').textContent = title;
    document.getElementById('photo-modal-overlay').classList.add('active');
}

function closePhotoModal() {
    document.getElementById('photo-modal-overlay').classList.remove('active');
}

function openPickupModal(returnId, orderNo) {
    document.getElementById('modal-pickup-return-id').value = returnId;
    document.getElementById('pickup-modal-overlay').classList.add('active');
}

function closePickupModal() {
    document.getElementById('pickup-modal-overlay').classList.remove('active');
}

function submitPickupSchedule(e) {
    e.preventDefault();
    const returnId = document.getElementById('modal-pickup-return-id').value;
    const pickupDate = document.getElementById('modal-pickup-date').value;
    const courier = document.getElementById('modal-pickup-courier').value;
    const note = document.getElementById('modal-pickup-note').value;

    const formData = new FormData();
    formData.append('action', 'approve_return_pickup');
    formData.append('return_id', returnId);
    formData.append('pickup_date', pickupDate);
    formData.append('courier_name', courier);
    formData.append('admin_note', note);

    fetch('../api/admin_actions.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
}

function completePickup(returnId, orderNo) {
    if (!confirm('Confirm that returned product for order #' + orderNo + ' has been picked up & received?')) return;
    const note = prompt('Enter inspection note (e.g. "Item received with tags intact"):', 'Product received at store warehouse. Verified in good condition.');
    if (note === null) return;

    const formData = new FormData();
    formData.append('action', 'complete_return_pickup');
    formData.append('return_id', returnId);
    formData.append('admin_note', note);

    fetch('../api/admin_actions.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
}

function openRefundModal(returnId, orderNo, upiId, amount) {
    document.getElementById('modal-refund-return-id').value = returnId;
    document.getElementById('modal-refund-upi').textContent = upiId;
    document.getElementById('modal-refund-amount').textContent = 'Refund Amount: ₹' + Number(amount).toFixed(2);
    document.getElementById('modal-refund-ref').value = '';
    document.getElementById('refund-modal-overlay').classList.add('active');
}

function closeRefundModal() {
    document.getElementById('refund-modal-overlay').classList.remove('active');
}

function submitRefundDisbursement(e) {
    e.preventDefault();
    const returnId = document.getElementById('modal-refund-return-id').value;
    const refundRef = document.getElementById('modal-refund-ref').value;
    const note = document.getElementById('modal-refund-note').value;

    const formData = new FormData();
    formData.append('action', 'process_return_refund');
    formData.append('return_id', returnId);
    formData.append('refund_ref', refundRef);
    formData.append('admin_note', note);

    fetch('../api/admin_actions.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
}

function rejectReturnModal(returnId, orderNo) {
    const reason = prompt('Please enter the reason for rejecting this return request (Required):', 'Photos indicate the brand tag has been removed or product shows heavy wear.');
    if (reason === null) return;
    if (!reason.trim()) {
        alert('Rejection reason is required.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'reject_return');
    formData.append('return_id', returnId);
    formData.append('admin_note', reason);

    fetch('../api/admin_actions.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
