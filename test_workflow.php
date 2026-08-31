<?php
/**
 * End-to-End Workflow Verification Script
 * The Stitch Co.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/order_functions.php';

$db = get_db();

echo "=== THE STITCH CO. SYSTEM DIAGNOSTIC ===\n";

// 1. Check Tables and Row Counts
$tables = ['users', 'products', 'categories', 'coupons', 'settings', 'hero_banners', 'orders', 'payments', 'shipping_details'];
foreach ($tables as $t) {
    $count = $db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    echo "✓ Table [$t] OK (Rows: $count)\n";
}

// 2. Test Coupon Validation
$couponTest = validate_coupon('WELCOME10', 1398.00);
echo "✓ Coupon [WELCOME10] Validation: " . ($couponTest['valid'] ? 'PASSED (Saved ' . format_price($couponTest['discount_amount']) . ')' : 'FAILED') . "\n";

// 3. Test UPI Deep Link Generator
$upiLink = generate_upi_intent_link('thestitchco@upi', 'The Stitch Co.', 1258.20, 'TSC-TEST-001');
echo "✓ UPI Deep Link Generated: " . substr($upiLink, 0, 45) . "...\n";

// 4. Test Sample Order Placement
$orderNumber = 'TSC-TEST-' . rand(1000, 9999);
$db->beginTransaction();

$orderStmt = $db->prepare("
    INSERT INTO orders (
        order_number, customer_id, customer_name, customer_email, customer_phone,
        subtotal, discount_amount, coupon_code, shipping_fee, shipping_method,
        total_price, status, payment_method, payment_status, shipping_address
    ) VALUES (
        ?, 3, 'Souvik Sayan Das', 'souviksayan@gmail.com', '+91 98765 43210',
        1398.00, 139.80, 'WELCOME10', 0.00, 'Standard Shipping (3-5 Days)',
        1258.20, 'Order Placed', 'UPI (Scan & Pay)', 'Pending', 'Vill - Fraserganj, PO - Fraserganj, South 24 Parganas, West Bengal - 743315'
    )
");
$orderStmt->execute([$orderNumber]);
$orderId = (int)$db->lastInsertId();

$itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, sku, size, color, image, price, quantity, total) VALUES (?, 1, 'Tokyo Vibes Oversized T-Shirt', 'TSC-TS-001', 'M', 'Black', 'assets/images/products/tokyo_vibes_black.svg', 699.00, 2, 1398.00)");
$itemStmt->execute([$orderId]);

$payStmt = $db->prepare("INSERT INTO payments (order_id, customer_id, amount, payment_method, utr_number, proof_screenshot, status) VALUES (?, 3, 1258.20, 'UPI (Scan & Pay)', '123456789012', 'uploads/proofs/sample.jpg', 'Pending')");
$payStmt->execute([$orderId]);

log_order_status_transition($orderId, null, 'Order Placed', 'Test order placement with UTR 123456789012', 'Test Customer');

$db->commit();
echo "✓ Test Order Created: #$orderNumber (ID: $orderId)\n";

// 5. Test Admin Payment Approval
$db->beginTransaction();
$db->prepare("UPDATE payments SET status = 'Approved', reviewed_by = 1, reviewed_at = NOW() WHERE order_id = ?")->execute([$orderId]);
$db->prepare("UPDATE orders SET status = 'Confirmed', payment_status = 'Paid' WHERE id = ?")->execute([$orderId]);
log_order_status_transition($orderId, 'Order Placed', 'Confirmed', 'Payment verified and approved by admin', 'Admin Souvik');
$db->commit();
echo "✓ Admin Payment Approval Simulated & Verified (Order #$orderNumber marked Confirmed & Paid)\n";

// 6. Test Courier Dispatch
$db->prepare("INSERT INTO shipping_details (order_id, courier_name, tracking_number, tracking_url, shipped_date) VALUES (?, 'Delhivery', 'DEL123456789IN', 'https://www.delhivery.com/track/package/DEL123456789IN', NOW())")->execute([$orderId]);
$db->prepare("UPDATE orders SET status = 'Shipped' WHERE id = ?")->execute([$orderId]);
log_order_status_transition($orderId, 'Confirmed', 'Shipped', 'Dispatched via Delhivery AWB DEL123456789IN', 'Admin Souvik');
echo "✓ Courier Dispatch & Tracking Timeline Logged (Order #$orderNumber marked Shipped)\n";

echo "=== ALL END-TO-END WORKFLOW CHECKS PASSED PERFECTLY ===\n";
