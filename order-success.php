<?php
/**
 * Order Success & Confirmation Screen
 * Features WhatsApp 1-Click Order Confirmation Draft
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

// Fetch order items
$itemsStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemsStmt->execute([$order['id']]);
$orderItems = $itemsStmt->fetchAll();

// Fetch payment details (if UTR or proof exists)
$payStmt = $db->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
$payStmt->execute([$order['id']]);
$paymentRecord = $payStmt->fetch();

// Format WhatsApp Draft Message
$storePhone = get_setting('store_phone', '+91 98765 43210');
$cleanPhone = preg_replace('/[^0-9]/', '', $storePhone);
if (strlen($cleanPhone) === 10) {
    $cleanPhone = '91' . $cleanPhone;
}

$waLines = [];
$waLines[] = "🔥 *NEW ORDER PLACED - THE STITCH CO.*";
$waLines[] = "------------------------------------------";
$waLines[] = "📦 *Order Number:* #" . $order['order_number'];
$waLines[] = "👤 *Customer:* " . $order['customer_name'];
$waLines[] = "📞 *Phone:* " . $order['customer_phone'];
$waLines[] = "💰 *Total Amount:* ₹" . number_format((float)$order['total_price'], 2);
$waLines[] = "💳 *Payment Method:* " . $order['payment_method'];

$utr = $paymentRecord['utr_number'] ?? '';
if (!empty($utr) && strpos($utr, 'COD') === false) {
    $waLines[] = "🏷️ *UPI UTR / Ref:* " . $utr;
}

if (!empty($order['notes'])) {
    $waLines[] = "📝 *Customer Note:* " . $order['notes'];
}

$waLines[] = "";
$waLines[] = "📍 *Delivery Address:*";
$waLines[] = trim($order['shipping_address']);
$waLines[] = "";
$waLines[] = "🛒 *Items Ordered:*";
foreach ($orderItems as $it) {
    $waLines[] = "• " . $it['quantity'] . "x " . $it['product_name'] . " (Size: " . $it['size'] . ") - ₹" . number_format((float)$it['total'], 2);
}
$waLines[] = "";
$waLines[] = "⚡ *Please confirm my order for fast dispatch!* 🚚✨";

$waDraftText = implode("\n", $waLines);
$waUrl = "https://wa.me/" . $cleanPhone . "?text=" . urlencode($waDraftText);

$pageTitle = 'Order Confirmed #' . $orderNumber . ' | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 3.5rem 1.25rem 6rem; max-width: 620px; text-align: center;">
    <div style="width: 76px; height: 76px; background: #ECFDF5; border: 2.5px solid #10B981; border-radius: 50%; color: #10B981; font-size: 2.4rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; box-shadow: 0 8px 20px rgba(16, 185, 129, 0.2);">
        ✓
    </div>

    <h1 style="font-family: var(--font-heading); font-size: 2.1rem; font-weight: 900; margin-bottom: 0.4rem; color: var(--text-main);">
        Order Placed Successfully!
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.8rem;">
        Thank you for shopping with The Stitch Co. Your order details have been recorded.
    </p>

    <!-- WhatsApp Instant Dispatch Action Card -->
    <div style="background: #F0FDF4; border: 2px solid #86EFAC; border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem; text-align: center; box-shadow: 0 4px 15px rgba(37, 211, 102, 0.15);">
        <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 0.4rem;">
            <span style="font-size: 1.3rem;">⚡</span>
            <strong style="font-size: 1rem; color: #166534; font-weight: 900; text-transform: uppercase;">Instant Verification & Faster Processing</strong>
        </div>
        <p style="font-size: 0.84rem; color: #15803D; margin-bottom: 1.1rem; line-height: 1.4;">
            Send your pre-formatted order draft & payment confirmation directly to our WhatsApp support for priority packing & dispatch!
        </p>
        <a href="<?= $waUrl ?>" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.6rem; width: 100%; padding: 1rem 1.5rem; background: #25D366; color: #FFFFFF; font-family: var(--font-heading); font-size: 0.95rem; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase; border-radius: var(--radius-md); text-decoration: none; box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4); transition: transform 0.2s;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
            <span>SEND TO WHATSAPP FOR FASTER PROCESSING &rarr;</span>
        </a>
    </div>

    <!-- Order Summary Card -->
    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.8rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm); text-align: left;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.2rem;">
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Order Number</span>
                <div style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 900; color: var(--primary);"><?= e($order['order_number']) ?></div>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Status</span>
                <div><span class="status-pill status-confirmed">Order Placed</span></div>
            </div>
        </div>

        <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.2rem;">
            <div>👤 <strong>Customer:</strong> <?= e($order['customer_name']) ?> (<?= e($order['customer_phone']) ?>)</div>
            <div>💰 <strong>Total Amount:</strong> <span style="color: var(--primary); font-weight: 800;"><?= format_price($order['total_price']) ?></span></div>
            <div>💳 <strong>Payment Method:</strong> <?= e($order['payment_method']) ?></div>
            <div>📦 <strong>Shipping:</strong> <?= e($order['shipping_method']) ?></div>
            <div>📍 <strong>Delivery Address:</strong><br><span style="color: var(--text-main); white-space: pre-line; line-height: 1.4;"><?= e($order['shipping_address']) ?></span></div>
        </div>

        <!-- Items Ordered Preview -->
        <div style="border-top: 1px solid var(--border); padding-top: 1rem;">
            <div style="font-size: 0.82rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.6rem;">Items in this order:</div>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <?php foreach ($orderItems as $item): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.82rem;">
                        <span><?= $item['quantity'] ?>x <?= e($item['product_name']) ?> (Size: <?= e($item['size']) ?>)</span>
                        <strong style="color: var(--text-main);"><?= format_price($item['total']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 0.8rem;">
        <a href="track-order.php?order_number=<?= urlencode($order['order_number']) ?>" class="hero-btn" style="background: var(--primary); color: #fff; padding: 0.9rem; font-size: 0.92rem; text-align: center; text-decoration: none;">
            TRACK YOUR ORDER 📦
        </a>
        <a href="invoice.php?order_number=<?= urlencode($order['order_number']) ?>" target="_blank" class="hero-btn" style="background: #FFFFFF; color: #000; border: 1.5px solid var(--border); padding: 0.85rem; font-size: 0.88rem; text-align: center; text-decoration: none;">
            📄 Download / Print Invoice
        </a>
        <a href="shop.php" style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-top: 0.5rem; text-decoration: none;">Continue Shopping →</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
