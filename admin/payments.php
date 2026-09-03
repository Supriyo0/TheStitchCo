<?php
/**
 * Admin Payments Verification Module
 * The Stitch Co.
 */

$adminTitle = 'Payment Verification & Gateway Logs';
require_once __DIR__ . '/header.php';

$filter = $_GET['filter'] ?? 'all';
$sql = "
    SELECT p.*, o.order_number, o.customer_name, o.customer_email, o.customer_phone, o.payment_method AS order_payment_method
    FROM payments p
    JOIN orders o ON p.order_id = o.id
";

if ($filter !== 'all') {
    $sql .= " WHERE p.status = " . $db->quote($filter);
}
$sql .= " ORDER BY p.id DESC";

$payments = $db->query($sql)->fetchAll();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Payments & Gateway Transactions</h2>
            <span style="font-size: 0.8rem; color: var(--admin-text-muted);">View PhonePe automated transactions, COD orders, and payment statuses.</span>
        </div>
        <div class="filter-tabs">
            <a href="payments.php?filter=all" class="filter-tab-btn <?= $filter === 'all' ? 'active' : '' ?>">All Payments</a>
            <a href="payments.php?filter=Approved" class="filter-tab-btn <?= $filter === 'Approved' ? 'active' : '' ?>">Approved / Paid</a>
            <a href="payments.php?filter=Pending" class="filter-tab-btn <?= $filter === 'Pending' ? 'active' : '' ?>">Pending</a>
            <a href="payments.php?filter=Rejected" class="filter-tab-btn <?= $filter === 'Rejected' ? 'active' : '' ?>">Rejected / Failed</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Order Ref</th>
                    <th>Customer</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Transaction Ref / UTR</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--admin-text-muted);">No payments in this queue.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><strong>#PAY-<?= $p['id'] ?></strong></td>
                            <td>
                                <a href="orders.php?search=<?= urlencode($p['order_number']) ?>" style="font-weight: 800; color: #2563EB;"><?= e($p['order_number']) ?></a><br>
                                <span style="font-size: 0.72rem; color: var(--admin-text-muted);"><?= date('d M Y, h:i A', strtotime($p['created_at'])) ?></span>
                            </td>
                            <td>
                                <strong><?= e($p['customer_name']) ?></strong><br>
                                <span style="font-size: 0.75rem; color: var(--admin-text-muted);"><?= e($p['customer_phone']) ?></span>
                            </td>
                            <td>
                                <?php if (stripos($p['payment_method'], 'PhonePe') !== false): ?>
                                    <span style="background: #FAF5FF; border: 1px solid #D8B4FE; color: #6739B7; font-size: 0.75rem; font-weight: 800; padding: 0.25rem 0.55rem; border-radius: 4px;">
                                        🟣 PhonePe PG
                                    </span>
                                <?php elseif (stripos($p['payment_method'], 'Cash') !== false || stripos($p['payment_method'], 'COD') !== false): ?>
                                    <span style="background: #F0FDF4; border: 1px solid #86EFAC; color: #166534; font-size: 0.75rem; font-weight: 800; padding: 0.25rem 0.55rem; border-radius: 4px;">
                                        💵 COD
                                    </span>
                                <?php else: ?>
                                    <span style="background: #F8FAFC; border: 1px solid #CBD5E1; color: #334155; font-size: 0.75rem; font-weight: 800; padding: 0.25rem 0.55rem; border-radius: 4px;">
                                        <?= e($p['payment_method']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><strong style="font-size: 1rem; font-weight: 800;"><?= format_price($p['amount']) ?></strong></td>
                            <td>
                                <span style="font-family: monospace; font-weight: 800; background: #EEF2FF; padding: 0.25rem 0.55rem; border-radius: 4px; color: #1E3A8A; user-select: all; font-size: 0.8rem;">
                                    <?= e($p['utr_number'] ?? 'N/A') ?>
                                </span>
                                <?php if (!empty($p['admin_notes'])): ?>
                                    <div style="font-size: 0.7rem; color: #64748B; margin-top: 0.2rem;"><?= e($p['admin_notes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-pill status-<?= strtolower($p['status']) ?>"><?= e($p['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($p['status'] === 'Pending'): ?>
                                    <div style="display: flex; gap: 0.4rem;">
                                        <button onclick="verifyPayment(<?= $p['id'] ?>, 'Approved')" style="background: #10B981; color: #fff; border: none; padding: 0.35rem 0.75rem; border-radius: 4px; font-size: 0.75rem; font-weight: 800; cursor: pointer;">✓ Approve</button>
                                        <button onclick="verifyPayment(<?= $p['id'] ?>, 'Rejected')" style="background: #EF4444; color: #fff; border: none; padding: 0.35rem 0.75rem; border-radius: 4px; font-size: 0.75rem; font-weight: 800; cursor: pointer;">✕ Reject</button>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 0.75rem; color: var(--admin-text-muted);">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function verifyPayment(paymentId, status) {
    let notes = '';
    if (status === 'Rejected') {
        notes = prompt('Reason for rejection:');
        if (notes === null) return;
    } else {
        if (!confirm('Approve this payment and confirm order?')) return;
    }

    const formData = new FormData();
    formData.append('action', 'verify_payment');
    formData.append('payment_id', paymentId);
    formData.append('status', status);
    formData.append('admin_notes', notes);

    fetch('../api/admin_actions.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error processing action');
            }
        });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
