<?php
/**
 * Order Success & Confirmation Screen
 * The Stitch Co.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_functions.php';

$orderNumber = trim($_GET['order_number'] ?? '');
if (empty($orderNumber)) {
    header("Location: index.php");
    exit;
}

$db = get_db();
$stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit;
}

$pageTitle = 'Order Confirmed #' . $orderNumber . ' | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 4rem 1.25rem 6rem; max-width: 600px; text-align: center;">
    <div style="width: 72px; height: 72px; background: #ECFDF5; border: 2px solid #10B981; border-radius: 50%; color: #10B981; font-size: 2.2rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
        ✓
    </div>

    <h1 style="font-family: var(--font-heading); font-size: 2rem; font-weight: 900; margin-bottom: 0.5rem;">
        Thank You!
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
        Your order has been placed successfully and is currently being verified.
    </p>

    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.8rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm); text-align: left;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1rem;">
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Order Number</span>
                <div style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 900; color: var(--primary);"><?= e($order['order_number']) ?></div>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Payment Status</span>
                <div><span class="status-pill status-pending">Awaiting Verification</span></div>
            </div>
        </div>

        <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.4rem;">
            <div>👤 <strong>Customer:</strong> <?= e($order['customer_name']) ?> (<?= e($order['customer_phone']) ?>)</div>
            <div>💰 <strong>Total Amount:</strong> <?= format_price($order['total_price']) ?></div>
            <div>💳 <strong>Method:</strong> <?= e($order['payment_method']) ?></div>
            <div>📦 <strong>Shipping:</strong> <?= e($order['shipping_method']) ?></div>
        </div>

        <div style="margin-top: 1.2rem; background: #F8FAFC; border-radius: var(--radius-sm); padding: 0.8rem; font-size: 0.78rem; color: var(--text-muted);">
            📧 A confirmation email and tracking link will be sent to <strong><?= e($order['customer_email']) ?></strong> once payment is verified.
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 0.8rem;">
        <a href="track-order.php?order_number=<?= urlencode($order['order_number']) ?>" class="hero-btn" style="background: var(--primary); color: #fff; padding: 0.9rem; font-size: 0.92rem; text-align: center;">
            TRACK YOUR ORDER 📦
        </a>
        <a href="invoice.php?order_number=<?= urlencode($order['order_number']) ?>" target="_blank" class="hero-btn" style="background: #FFFFFF; color: #000; border: 1.5px solid var(--border); padding: 0.85rem; font-size: 0.88rem; text-align: center;">
            📄 Download / Print Invoice
        </a>
        <a href="shop.php" style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-top: 0.5rem;">Continue Shopping →</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
