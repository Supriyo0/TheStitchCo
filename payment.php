<?php
/**
 * Step 2: UPI Payment & Order Final Confirmation
 * The Stitch Co.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_functions.php';

$db = get_db();

if (!is_logged_in()) {
    header("Location: login.php?redirect=checkout.php");
    exit;
}

$cartData = get_cart_contents();

if (empty($cartData['items'])) {
    header("Location: cart.php");
    exit;
}

$shipping = $_SESSION['checkout_shipping'] ?? null;
if (empty($shipping) || empty($shipping['fullname']) || empty($shipping['address_line1'])) {
    header("Location: checkout.php");
    exit;
}

$currentUser = current_user();
$appliedCoupon = $_SESSION['applied_coupon'] ?? null;

$subtotal = $cartData['subtotal'];
$discountAmount = 0.00;

if ($appliedCoupon) {
    $val = validate_coupon($appliedCoupon['code'], $subtotal);
    if ($val['valid']) {
        $discountAmount = $val['discount_amount'];
    } else {
        unset($_SESSION['applied_coupon']);
        $appliedCoupon = null;
    }
}

// Calculate exact server-side totals
$shippingMethod = $shipping['shipping_method'] ?? 'standard';
$productDeliveryCharge = $cartData['delivery_charge'] ?? 0.00;
$baseShippingFee = ($productDeliveryCharge > 0) ? $productDeliveryCharge : 0.00;
$shippingFee = ($shippingMethod === 'express') ? ($baseShippingFee + 99.00) : $baseShippingFee;
$totalPrice = max(0, $subtotal - $discountAmount + $shippingFee);

$errorMessage = '';

// Handle Final Order Placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $paymentMethod = trim($_POST['payment_method'] ?? 'UPI (Scan & Pay)');
    $utrNumber = trim($_POST['utr_number'] ?? '');
    $customerNote = trim($_POST['customer_note'] ?? '');

    $orderNumber = 'TSC-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        // Handle Payment Screenshot Upload via ImgBB / Local Storage
        $proofPath = '';
        if (!empty($_FILES['payment_proof']['name'])) {
            $proofUp = upload_to_imgbb($_FILES['payment_proof']);
            if ($proofUp['success']) {
                $proofPath = $proofUp['url'] ?? $proofUp['relative_url'];
            }
        }

        $fullName = $shipping['fullname'];
        $email = $shipping['email'];
        $phone = $shipping['phone'];
        $fullShippingText = "{$fullName}\nPhone: {$phone}\n{$shipping['address_line1']}" . 
                            (!empty($shipping['address_line2']) ? "\n{$shipping['address_line2']}" : '') . 
                            (!empty($shipping['landmark']) ? "\nLandmark: {$shipping['landmark']}" : '') . 
                            "\n{$shipping['city']}, {$shipping['state']} - {$shipping['pincode']}\nIndia";

        try {
            $db->beginTransaction();

            $customerId = $currentUser['id'] ?? 0;

            // 1. Insert Order Record
            $orderStmt = $db->prepare("
                INSERT INTO orders (
                    order_number, customer_id, customer_name, customer_email, customer_phone,
                    shipping_address, shipping_method, payment_method, subtotal,
                    discount_amount, coupon_code, shipping_fee, total_price,
                    status, payment_status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Order Placed', 'Pending', ?)
            ");
            $orderStmt->execute([
                $orderNumber,
                $customerId,
                $fullName,
                $email,
                $phone,
                $fullShippingText,
                $shippingMethod,
                $paymentMethod,
                $subtotal,
                $discountAmount,
                $appliedCoupon['code'] ?? null,
                $shippingFee,
                $totalPrice,
                $customerNote
            ]);
            $orderId = (int)$db->lastInsertId();

            // 2. Insert Order Items & Decrement Inventory
            $itemStmt = $db->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, size, quantity, price, total, image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stockStmt = $db->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");

            foreach ($cartData['items'] as $item) {
                $itemTotal = $item['price'] * $item['quantity'];
                $itemStmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['name'],
                    $item['size'],
                    $item['quantity'],
                    $item['price'],
                    $itemTotal,
                    $item['thumbnail']
                ]);
                $stockStmt->execute([$item['quantity'], $item['product_id']]);
            }

            // 3. Record Payment Submission
            $payStmt = $db->prepare("
                INSERT INTO payments (order_id, customer_id, amount, payment_method, utr_number, proof_screenshot, customer_note, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
            ");
            $payStmt->execute([$orderId, $customerId, $totalPrice, $paymentMethod, $utrNumber, $proofPath, $customerNote]);

            // 4. Record Coupon Usage
            if ($appliedCoupon) {
                $db->prepare("INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)")
                   ->execute([$appliedCoupon['id'], $customerId, $orderId, $discountAmount]);
                $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$appliedCoupon['id']]);
            }

            // 5. Record Status History & Admin Notification
            log_order_status_transition($orderId, null, 'Order Placed', 'Order placed with ' . $paymentMethod . ' (UTR: ' . $utrNumber . ')', $fullName);
            create_notification(null, 'New Order Received', 'New order #' . $orderNumber . ' placed by ' . $fullName . ' (' . format_price($totalPrice) . ')', 'order', 'orders.php');

            // 6. Clear Cart, Shipping Session & Coupon
            clear_cart();
            unset($_SESSION['applied_coupon']);
            unset($_SESSION['checkout_shipping']);

            $db->commit();

            // 7. Dispatch Confirmation Email
            require_once __DIR__ . '/includes/mailer.php';
            try {
                $orderData = [
                    'order_number' => $orderNumber,
                    'customer_name' => $fullName,
                    'customer_email' => $email,
                    'total_price' => $totalPrice,
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'discount_amount' => $discountAmount,
                    'payment_method' => $paymentMethod,
                    'shipping_address' => $fullShippingText
                ];
                send_order_confirmation_email($orderData, $cartData['items']);
            } catch (Exception $mailEx) {
                error_log("Order confirmation email error: " . $mailEx->getMessage());
            }

            header("Location: order-success.php?order_number=" . urlencode($orderNumber));
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errorMessage = 'Order placement failed: ' . $e->getMessage();
        }
    }
}

// UPI Dynamic QR & Deep Link
$upiMerchantId = get_setting('upi_id', 'thestitchco@upi');
$upiMerchantName = get_setting('upi_merchant_name', 'The Stitch Co.');
$upiIntentUrl = generate_upi_intent_link($upiMerchantId, $upiMerchantName, $totalPrice, 'TSC-ORDER');
$upiQrImageUrl = get_setting('upi_qr_image', 'assets/images/upi_qr.svg');

$pageTitle = 'Step 2: Payment & Final Confirmation | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2rem 1.25rem 5rem;">
    <!-- 2-Step Checkout Stepper -->
    <div class="checkout-stepper">
        <a href="checkout.php" class="step-node completed" style="text-decoration: none; color: inherit;">
            <div class="step-circle">✓</div>
            <div class="step-label">Delivery Address</div>
        </a>
        <div class="step-line active"></div>
        <div class="step-node active" id="step-node-2">
            <div class="step-circle">2</div>
            <div class="step-label">Payment & Confirmation</div>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div style="background: #FEF2F2; border: 1.5px solid #EF4444; color: #DC2626; padding: 1rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem; font-weight: 700;">
            ⚠️ <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form action="payment.php" method="POST" enctype="multipart/form-data" id="payment-step-form">
        <div style="display: grid; grid-template-columns: 1fr; gap: 2.5rem;" class="checkout-grid">
            <!-- Left: Deliver-To Summary + Payment Submission -->
            <div>
                <!-- Deliver-To Summary Banner -->
                <div style="background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 800; color: #2563EB; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.3rem;">
                            📦 Delivering To:
                        </div>
                        <div style="font-size: 1rem; font-weight: 800; color: var(--text-main);">
                            <?= e($shipping['fullname']) ?> &nbsp;<span style="font-size: 0.85rem; font-weight: normal; color: var(--text-muted);">(<?= e($shipping['phone']) ?>)</span>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem; line-height: 1.4;">
                            <?= e($shipping['address_line1']) ?><?= !empty($shipping['address_line2']) ? ', ' . e($shipping['address_line2']) : '' ?><br>
                            <?= e($shipping['city']) ?>, <?= e($shipping['state']) ?> - <strong><?= e($shipping['pincode']) ?></strong>
                        </div>
                        <div style="font-size: 0.75rem; color: #16A34A; font-weight: 700; margin-top: 0.4rem;">
                            ⚡ Shipping: <?= ($shippingMethod === 'express') ? 'Express Air Delivery (1-2 Days)' : 'Standard Delivery (3-5 Days)' ?>
                        </div>
                    </div>
                    <a href="checkout.php" style="padding: 0.45rem 0.9rem; background: #FFFFFF; border: 1.5px solid var(--border); border-radius: 6px; font-size: 0.78rem; font-weight: 800; color: var(--text-main); text-decoration: none; white-space: nowrap; transition: all 0.2s;">
                        ✏️ Change Address
                    </a>
                </div>

                <!-- Payment Method Card (UPI Scan & Pay) -->
                <div style="background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
                        <h2 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; text-transform: uppercase;">
                            Select Payment Method
                        </h2>
                        <span style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; font-size: 0.72rem; font-weight: 800; padding: 0.2rem 0.6rem; border-radius: 4px;">
                            🔒 ZERO EXTRA CHARGES
                        </span>
                    </div>

                    <!-- UPI Scan & Pay Box -->
                    <div style="border: 2px solid var(--primary); background: #FAF5FF; border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.2rem;">
                            <div style="display: flex; align-items: center; gap: 0.6rem;">
                                <input type="radio" name="payment_method" value="UPI (Scan & Pay)" checked style="accent-color: var(--primary); transform: scale(1.15);">
                                <strong style="font-size: 1rem; color: #1E1B4B;">UPI (Scan QR / GPay / PhonePe / Paytm)</strong>
                            </div>
                            <span style="background: #2563EB; color: #fff; font-size: 0.72rem; font-weight: 800; padding: 0.2rem 0.6rem; border-radius: 4px;">INSTANT</span>
                        </div>

                        <!-- QR Code Box -->
                        <div class="qr-box-container" style="text-align: center; background: #FFFFFF; border: 1.5px solid var(--border); border-radius: var(--radius-md); padding: 1.5rem;">
                            <div style="font-size: 0.8rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.8rem;">
                                Scan QR & Pay Exact Amount: <strong style="color: #000; font-size: 1.05rem;"><?= format_price($totalPrice) ?></strong>
                            </div>
                            
                            <div class="qr-image-frame" style="width: 200px; height: 200px; margin: 0 auto 1rem; border-radius: 12px; overflow: hidden; border: 2px solid #E2E8F0; padding: 0.5rem; background: #fff;">
                                <img src="<?= e($upiQrImageUrl) ?>" alt="UPI QR Code" style="width: 100%; height: 100%; object-fit: contain;">
                            </div>

                            <div style="font-weight: 800; font-size: 0.95rem; color: var(--primary);">
                                UPI ID: <span style="color: #2563EB; user-select: all; font-family: monospace; background: #EFF6FF; padding: 0.2rem 0.6rem; border-radius: 4px;"><?= e($upiMerchantId) ?></span>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.4rem;">
                                Verified Merchant: <strong><?= e($upiMerchantName) ?></strong>
                            </div>

                            <!-- Mobile UPI Deep Link Apps -->
                            <div style="margin-top: 1.2rem; border-top: 1px dashed var(--border); padding-top: 1rem;">
                                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.6rem;">OR CLICK TO PAY DIRECTLY VIA UPI APPS:</div>
                                <div class="upi-intent-buttons" style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                    <a href="<?= $upiIntentUrl ?>" class="upi-app-btn" target="_blank" style="padding: 0.5rem 0.9rem; background: #F8FAFC; border: 1.5px solid var(--border); border-radius: 6px; font-weight: 700; font-size: 0.8rem; text-decoration: none; color: #1E293B;">🟢 Google Pay</a>
                                    <a href="<?= $upiIntentUrl ?>" class="upi-app-btn" target="_blank" style="padding: 0.5rem 0.9rem; background: #F8FAFC; border: 1.5px solid var(--border); border-radius: 6px; font-weight: 700; font-size: 0.8rem; text-decoration: none; color: #1E293B;">🟣 PhonePe</a>
                                    <a href="<?= $upiIntentUrl ?>" class="upi-app-btn" target="_blank" style="padding: 0.5rem 0.9rem; background: #F8FAFC; border: 1.5px solid var(--border); border-radius: 6px; font-weight: 700; font-size: 0.8rem; text-decoration: none; color: #1E293B;">🔵 Paytm</a>
                                    <a href="<?= $upiIntentUrl ?>" class="upi-app-btn" target="_blank" style="padding: 0.5rem 0.9rem; background: #F8FAFC; border: 1.5px solid var(--border); border-radius: 6px; font-weight: 700; font-size: 0.8rem; text-decoration: none; color: #1E293B;">🟠 Any UPI App</a>
                                </div>
                            </div>
                        </div>

                        <!-- UTR Reference & Proof Screenshot Form -->
                        <div style="margin-top: 1.5rem; background: #FFFFFF; border: 1.5px solid var(--border); border-radius: var(--radius-md); padding: 1.2rem;">
                            <h4 style="font-size: 0.9rem; font-weight: 800; margin-bottom: 0.8rem; color: var(--primary);">
                                📝 Enter Payment Confirmation Details
                            </h4>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <div>
                                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">UPI Reference / UTR Number (Optional)</label>
                                    <input type="text" name="utr_number" placeholder="e.g. 324156789012 (Optional)" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 0.95rem; font-weight: 700; letter-spacing: 0.5px;">
                                    <span style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem; display: block;">You can find the 12-digit UTR/Ref number in your GPay / PhonePe / Paytm transaction receipt, or send proof via WhatsApp.</span>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Upload Payment Screenshot (Optional for Instant Verification)</label>
                                    <input type="file" name="payment_proof" accept="image/*" style="width: 100%; font-size: 0.82rem;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Order / Delivery Note (Optional)</label>
                                    <input type="text" name="customer_note" placeholder="e.g. Please deliver after 5 PM / Leave with security" style="width: 100%; padding: 0.65rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 0.85rem;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirm & Place Order CTA Button -->
                <button type="submit" name="place_order" style="width: 100%; padding: 1.2rem; background: #16A34A; color: #FFFFFF; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; border: none; border-radius: var(--radius-md); cursor: pointer; box-shadow: 0 4px 20px rgba(22, 163, 74, 0.35); transition: var(--transition);">
                    CONFIRM & PLACE ORDER (<?= format_price($totalPrice) ?>) 🔒
                </button>
            </div>

            <!-- Right: Order Summary Sidebar -->
            <div>
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.8rem; position: sticky; top: 90px; box-shadow: var(--shadow-sm);">
                    <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; text-transform: uppercase; margin-bottom: 1.2rem; border-bottom: 1px solid var(--border); padding-bottom: 0.8rem;">
                        Order Summary (<?= count($cartData['items']) ?> Items)
                    </h3>

                    <!-- Mini Item List -->
                    <div style="display: flex; flex-direction: column; gap: 1rem; max-height: 240px; overflow-y: auto; margin-bottom: 1.2rem; padding-right: 0.3rem;">
                        <?php foreach ($cartData['items'] as $item): ?>
                            <div style="display: flex; gap: 0.8rem; align-items: center;">
                                <img src="<?= e($item['thumbnail']) ?>" alt="" style="width: 50px; height: 60px; object-fit: cover; border-radius: 6px; background: #f0f0f0;">
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 0.85rem; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($item['name']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Size: <?= e($item['size']) ?> | Qty: <?= $item['quantity'] ?></div>
                                    <div style="font-size: 0.85rem; font-weight: 800; color: var(--primary);"><?= format_price($item['price'] * $item['quantity']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Price Calculations -->
                    <div style="display: flex; flex-direction: column; gap: 0.6rem; border-top: 1px solid var(--border); padding-top: 1rem; font-size: 0.9rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Subtotal</span>
                            <span style="font-weight: 700;"><?= format_price($subtotal) ?></span>
                        </div>
                        <?php if ($discountAmount > 0): ?>
                            <div style="display: flex; justify-content: space-between; color: #16A34A; font-weight: 700;">
                                <span>Discount (<?= e($appliedCoupon['code']) ?>)</span>
                                <span>- <?= format_price($discountAmount) ?></span>
                            </div>
                        <?php endif; ?>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Shipping (<?= ucfirst($shippingMethod) ?>)</span>
                            <span style="font-weight: 700;"><?= ($shippingFee > 0) ? format_price($shippingFee) : 'FREE' ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 900; border-top: 1.5px solid var(--border); padding-top: 0.8rem; margin-top: 0.4rem;">
                            <span>Total Payable</span>
                            <span style="color: var(--primary);"><?= format_price($totalPrice) ?></span>
                        </div>
                    </div>

                    <div style="margin-top: 1.2rem; padding: 0.8rem; background: #F8FAFC; border-radius: 6px; font-size: 0.75rem; color: var(--text-muted); text-align: center;">
                        🔒 100% Safe & Encrypted Checkout | The Stitch Co.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
