<?php
/**
 * Professional Printable / PDF Invoice Generator
 * The Stitch Co. - A Brand by MJ Company
 * Matches Exact Blueprint Layout
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$orderNumber = trim($_GET['order_number'] ?? '');
if (empty($orderNumber)) {
    die("Invalid Order Reference.");
}

$db = get_db();
$stmt = $db->prepare("
    SELECT o.*, s.courier_name, s.tracking_number, s.shipped_date 
    FROM orders o 
    LEFT JOIN shipping_details s ON o.id = s.order_id 
    WHERE o.order_number = ? 
    LIMIT 1
");
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found.");
}

$itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$order['id']]);
$items = $itemStmt->fetchAll();

$invoiceNumber = 'TSCINV-' . date('Y', strtotime($order['created_at'])) . '-' . str_pad($order['id'], 5, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice - <?= e($order['order_number']) ?> | The Stitch Co.</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@700;800;900&family=Great+Vibes&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #F1F5F9; color: #1E293B; padding: 2rem; }
        .invoice-box { max-width: 850px; margin: 0 auto; background: #FFFFFF; padding: 3rem; border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        
        .header-row { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000000; padding-bottom: 2rem; margin-bottom: 2rem; }
        .brand-header { display: flex; align-items: center; gap: 1.2rem; }
        .brand-logo-img { width: 70px; height: 70px; border-radius: 8px; border: 1.5px solid #000; }
        .brand-name-text h1 { font-family: 'Outfit', sans-serif; font-size: 1.6rem; font-weight: 900; letter-spacing: 1px; color: #000; }
        .brand-name-text .tagline { font-size: 0.75rem; font-weight: 800; color: #2563EB; letter-spacing: 2px; text-transform: uppercase; margin-top: 0.1rem; }
        .brand-name-text .parent-company { font-size: 0.78rem; font-weight: 800; color: #1E3A8A; letter-spacing: 1px; margin-top: 0.4rem; }

        .invoice-meta-col { text-align: right; font-size: 0.85rem; }
        .invoice-meta-col h2 { font-family: 'Outfit', sans-serif; font-size: 2.2rem; font-weight: 900; color: #000; letter-spacing: 1px; }
        .invoice-meta-number { font-size: 0.88rem; font-weight: 800; color: #2563EB; margin-bottom: 0.8rem; }
        .meta-line { display: flex; justify-content: flex-end; gap: 1rem; color: #64748B; font-size: 0.82rem; margin-bottom: 0.25rem; }
        .meta-line strong { color: #0F172A; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .info-card { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 1.25rem; font-size: 0.82rem; line-height: 1.6; }
        .info-card-header { display: flex; align-items: center; gap: 0.5rem; font-weight: 800; font-size: 0.82rem; color: #1E3A8A; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.6rem; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        .items-table th { background: #000000; color: #FFFFFF; padding: 0.85rem 1rem; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
        .items-table td { padding: 1rem; border-bottom: 1px solid #E2E8F0; font-size: 0.85rem; vertical-align: middle; }
        
        .totals-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem; margin-bottom: 2.5rem; }
        .notes-col { font-size: 0.82rem; color: #475569; }
        .notes-box { background: #F8FAFC; padding: 1rem; border-radius: 8px; border: 1px solid #E2E8F0; margin-bottom: 1rem; }
        .totals-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .totals-table td { padding: 0.4rem 0.6rem; }
        .grand-total-row { background: #000000; color: #FFFFFF; font-weight: 900; font-size: 1.2rem; }
        .grand-total-row td { padding: 0.8rem 0.8rem; }

        .signature-section { text-align: center; margin-bottom: 2rem; }
        .signature-script { font-family: 'Great Vibes', cursive; font-size: 2.5rem; color: #2563EB; }
        .signature-sub { font-size: 0.75rem; font-weight: 800; letter-spacing: 3px; color: #000; text-transform: uppercase; }

        .footer-banner { background: #000000; color: #FFFFFF; border-radius: 8px; padding: 1.2rem 1.5rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; }
        .print-actions { max-width: 850px; margin: 0 auto 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        .btn-print { background: #1E3A8A; color: #fff; padding: 0.7rem 1.5rem; border-radius: 6px; font-weight: 800; border: none; cursor: pointer; }

        @media print {
            body { background: #FFFFFF; padding: 0; }
            .invoice-box { border: none; box-shadow: none; padding: 0; max-width: 100%; }
            .print-actions { display: none; }
        }
    </style>
</head>
<body>

<div class="print-actions">
    <a href="account.php?tab=orders" style="color: #2563EB; font-weight: 700; text-decoration: none;">&larr; Back to Account Orders</a>
    <button class="btn-print" onclick="window.print()">🖨️ Print / Download PDF</button>
</div>

<div class="invoice-box">
    <!-- Header -->
    <div class="header-row">
        <div class="brand-header">
            <img src="assets/images/logo.jpg" alt="Logo" class="brand-logo-img">
            <div class="brand-name-text">
                <h1>THE STITCH CO.</h1>
                <div class="tagline">PREMIUM STREETWEAR</div>
                <div class="parent-company">A BRAND BY MJ COMPANY</div>
            </div>
        </div>
        <div class="invoice-meta-col">
            <h2>INVOICE</h2>
            <div class="invoice-meta-number">#<?= e($invoiceNumber) ?></div>
            <div class="meta-line"><span>Invoice Date :</span> <strong><?= date('d M Y', strtotime($order['created_at'])) ?></strong></div>
            <div class="meta-line"><span>Order Date :</span> <strong><?= date('d M Y', strtotime($order['created_at'])) ?></strong></div>
            <div class="meta-line"><span>Payment Method :</span> <strong><?= e($order['payment_method']) ?></strong></div>
            <div class="meta-line"><span>Payment Status :</span> <strong style="color: <?= $order['payment_status'] === 'Paid' ? '#10B981' : '#F59E0B' ?>;"><?= e($order['payment_status']) ?></strong></div>
        </div>
    </div>

    <!-- Info Cards (BILL TO & ORDER DETAILS) -->
    <div class="info-grid">
        <div class="info-card">
            <div class="info-card-header">
                <span>👤</span>
                <span>BILL TO</span>
            </div>
            <strong style="font-size: 0.95rem; color: #000;"><?= e($order['customer_name']) ?></strong><br>
            <div style="white-space: pre-line; color: #475569; margin: 0.4rem 0;">
                <?= e($order['shipping_address']) ?>
            </div>
            <div>📞 <?= e($order['customer_phone']) ?></div>
            <div>✉️ <?= e($order['customer_email']) ?></div>
        </div>

        <div class="info-card">
            <div class="info-card-header">
                <span>📦</span>
                <span>ORDER DETAILS</span>
            </div>
            <div class="meta-line" style="justify-content: flex-start;"><span>Order ID :</span> <strong>#<?= e($order['order_number']) ?></strong></div>
            <div class="meta-line" style="justify-content: flex-start;"><span>Shipping Method :</span> <strong><?= e($order['shipping_method']) ?></strong></div>
            <div class="meta-line" style="justify-content: flex-start;"><span>Tracking ID :</span> <strong><?= e($order['tracking_number'] ?? 'Pending Dispatch') ?></strong></div>
            <div class="meta-line" style="justify-content: flex-start;"><span>Courier Partner :</span> <strong><?= e($order['courier_name'] ?? 'Delhivery') ?></strong></div>
            <div class="meta-line" style="justify-content: flex-start;"><span>Shipping Date :</span> <strong><?= !empty($order['shipped_date']) ? date('d M Y', strtotime($order['shipped_date'])) : date('d M Y') ?></strong></div>
        </div>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50px;">Item</th>
                <th>Product</th>
                <th style="width: 70px;">Size</th>
                <th style="width: 50px; text-align: center;">Qty</th>
                <th style="width: 110px; text-align: right;">Unit Price</th>
                <th style="width: 120px; text-align: right;">Total Price</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $idx => $it): ?>
                <tr>
                    <td>
                        <img src="<?= e($it['image']) ?>" alt="" style="width: 44px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #E2E8F0;">
                    </td>
                    <td>
                        <strong style="font-weight: 800; font-size: 0.9rem;"><?= e($it['product_name']) ?></strong>
                        <div style="font-size: 0.75rem; color: #64748B;">Color: <?= e($it['color']) ?></div>
                    </td>
                    <td><strong><?= e($it['size']) ?></strong></td>
                    <td style="text-align: center; font-weight: 800;"><?= $it['quantity'] ?></td>
                    <td style="text-align: right;"><?= format_price($it['price']) ?></td>
                    <td style="text-align: right; font-weight: 800;"><?= format_price($it['total']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals & Notes -->
    <div class="totals-grid">
        <div class="notes-col">
            <div class="notes-box">
                <strong style="font-size: 0.8rem; color: #1E3A8A; text-transform: uppercase;">📝 Order Note</strong>
                <p style="margin-top: 0.3rem;">Thank you for shopping with The Stitch Co. We appreciate your trust in us.</p>
            </div>
            <div class="notes-box">
                <strong style="font-size: 0.8rem; color: #1E3A8A; text-transform: uppercase;">🎧 Need Help?</strong>
                <div style="margin-top: 0.3rem;">
                    Email: <strong>thestitchco.official@gmail.com</strong><br>
                    Phone: <strong>+91 7063179581</strong> | WhatsApp: <strong>+91 7047051581</strong><br>
                    GSTIN: <strong>19GWPPD6451K1ZV</strong>
                </div>
            </div>
        </div>

        <div>
            <table class="totals-table">
                <tr>
                    <td style="color: #64748B;">Subtotal (<?= count($items) ?> Items):</td>
                    <td style="text-align: right; font-weight: 700;"><?= format_price($order['subtotal']) ?></td>
                </tr>
                <?php if ($order['discount_amount'] > 0): ?>
                    <tr>
                        <td style="color: #16A34A;">Discount (<?= e($order['coupon_code']) ?>):</td>
                        <td style="text-align: right; color: #16A34A; font-weight: 800;">- <?= format_price($order['discount_amount']) ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td style="color: #64748B;">Shipping Charge:</td>
                    <td style="text-align: right; font-weight: 700; color: <?= $order['shipping_fee'] == 0 ? '#16A34A' : '#1E293B' ?>;">
                        <?= $order['shipping_fee'] == 0 ? '₹0.00' : format_price($order['shipping_fee']) ?>
                    </td>
                </tr>
                <tr>
                    <td style="color: #64748B;">COD Charge:</td>
                    <td style="text-align: right; font-weight: 700;">₹0.00</td>
                </tr>
                <tr class="grand-total-row">
                    <td>TOTAL</td>
                    <td style="text-align: right;"><?= format_price($order['total_price']) ?></td>
                </tr>
            </table>
            <div style="font-size: 0.72rem; color: #64748B; text-align: right; margin-top: 0.4rem;">(Inclusive of all taxes)</div>
        </div>
    </div>

    <!-- Thank You Signature -->
    <div class="signature-section">
        <div class="signature-script">Thank You!</div>
        <div class="signature-sub">FOR SHOPPING WITH US</div>
    </div>

    <!-- Bottom MJ Company Details -->
    <div class="footer-banner">
        <div>
            <strong>THE STITCH CO.</strong><br>
            A Fashion Brand by MJ Company<br>
            GSTIN: 19GWPPD6451K1ZV
        </div>
        <div style="text-align: right; line-height: 1.4;">
            <strong>MJ COMPANY</strong><br>
            Sisir Building, Jetty Ghat Bus Stopage, Fraserganj<br>
            South 24 Parganas, West Bengal, India - 743357<br>
            www.thestitchco.shop
        </div>
    </div>
</div>

</body>
</html>
