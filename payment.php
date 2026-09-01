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

    if ($paymentMethod === 'Cash on Delivery (COD)') {
        $utrNumber = 'COD (Pay on Delivery)';
    }

    $orderNumber = 'TSC-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    // Handle Payment Screenshot Upload via ImgBB / Local Storage (Only if UPI)
    $proofPath = '';
    if ($paymentMethod !== 'Cash on Delivery (COD)' && !empty($_FILES['payment_proof']['name'])) {
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

            $nowIst = date('Y-m-d H:i:s');

            // 1. Insert Order Record
            $orderStmt = $db->prepare("
                INSERT INTO orders (
                    order_number, customer_id, customer_name, customer_email, customer_phone,
                    shipping_address, shipping_method, payment_method, subtotal,
                    discount_amount, coupon_code, shipping_fee, total_price,
                    status, payment_status, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Order Placed', 'Pending', ?, ?)
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
                $customerNote,
                $nowIst
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

                <!-- Payment Method Selection Card (UPI + Cash on Delivery) -->
                <div style="background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.5rem;">
                        <h2 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 900; text-transform: uppercase; margin: 0;">
                            Select Payment Method
                        </h2>
                        <span style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; font-size: 0.72rem; font-weight: 800; padding: 0.25rem 0.65rem; border-radius: 20px;">
                            🔒 100% SECURE CHECKOUT
                        </span>
                    </div>

                    <!-- Payment Options Radio Stack -->
                    <div style="display: flex; flex-direction: column; gap: 1.2rem; margin-bottom: 1.5rem;">
                        
                        <!-- Option 1: UPI Scan & Pay -->
                        <div id="payment-card-upi" style="border: 2px solid #2563EB; background: #F8FAFC; border-radius: 12px; padding: 1.2rem; transition: all 0.25s ease;">
                            <label style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <input type="radio" name="payment_method" value="UPI (Scan & Pay)" checked onchange="togglePaymentMethod('upi')" style="accent-color: #2563EB; transform: scale(1.2);">
                                    <div>
                                        <strong style="font-size: 1rem; color: #0F172A; display: block;">📱 UPI (Scan QR / GPay / PhonePe / Paytm)</strong>
                                        <span style="font-size: 0.76rem; color: #64748B;">Instant online payment with zero processing fee</span>
                                    </div>
                                </div>
                                <span style="background: #2563EB; color: #fff; font-size: 0.72rem; font-weight: 900; padding: 0.25rem 0.6rem; border-radius: 6px; letter-spacing: 0.4px;">FAST DISPATCH ⚡</span>
                            </label>

                            <!-- QR Code & UPI Deep Links Box (Shown when UPI is active) -->
                            <div id="upi-details-container" style="margin-top: 1.2rem; padding-top: 1.2rem; border-top: 1px dashed #CBD5E1;">
                                <div class="qr-box-container" style="text-align: center; background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 12px; padding: 1.4rem;">
                                    <div style="font-size: 0.8rem; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.8rem;">
                                        Scan QR & Pay Exact Amount: <strong style="color: #0F172A; font-size: 1.1rem;"><?= format_price($totalPrice) ?></strong>
                                    </div>
                                    
                                    <div class="qr-image-frame" style="width: 190px; height: 190px; margin: 0 auto 1rem; border-radius: 12px; overflow: hidden; border: 2px solid #E2E8F0; padding: 0.5rem; background: #fff;">
                                        <img src="<?= e($upiQrImageUrl) ?>" alt="UPI QR Code" style="width: 100%; height: 100%; object-fit: contain;">
                                    </div>

                                    <div style="font-weight: 800; font-size: 0.95rem; color: #0F172A;">
                                        UPI ID: <span style="color: #2563EB; user-select: all; font-family: monospace; background: #EFF6FF; padding: 0.25rem 0.6rem; border-radius: 4px; border: 1px solid #BFDBFE;"><?= e($upiMerchantId) ?></span>
                                    </div>
                                    <div style="font-size: 0.78rem; color: #64748B; margin-top: 0.4rem;">
                                        Verified Merchant: <strong><?= e($upiMerchantName) ?></strong>
                                    </div>

                                    <!-- Mobile UPI Deep Link Apps -->
                                    <div style="margin-top: 1.2rem; border-top: 1px dashed #E2E8F0; padding-top: 1rem;">
                                        <div style="font-size: 0.75rem; font-weight: 800; color: #64748B; margin-bottom: 0.6rem; text-transform: uppercase;">OR CLICK TO PAY VIA UPI APPS:</div>
                                        <div class="upi-intent-buttons" style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                            <a href="<?= $upiIntentUrl ?>" class="upi-app-btn" target="_blank" style="padding: 0.45rem 0.85rem; background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: 6px; font-weight: 800; font-size: 0.78rem; text-decoration: none; color: #1E293B;">🟢 Google Pay</a>
                                            <a href="<?= $upiIntentUrl ?>" class="upi-app-btn" target="_blank" style="padding: 0.45rem 0.85rem; background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: 6px; font-weight: 800; font-size: 0.78rem; text-decoration: none; color: #1E293B;">🟣 PhonePe</a>
                                            <a href="<?= $upiIntentUrl ?>" class="upi-app-btn" target="_blank" style="padding: 0.45rem 0.85rem; background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: 6px; font-weight: 800; font-size: 0.78rem; text-decoration: none; color: #1E293B;">🔵 Paytm</a>
                                            <a href="<?= $upiIntentUrl ?>" class="upi-app-btn" target="_blank" style="padding: 0.45rem 0.85rem; background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: 6px; font-weight: 800; font-size: 0.78rem; text-decoration: none; color: #1E293B;">🟠 Any UPI App</a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Optional UTR and Proof Attachment -->
                                <div style="margin-top: 1.2rem; background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 12px; padding: 1.2rem;">
                                    <h4 style="font-size: 0.88rem; font-weight: 800; margin-bottom: 0.8rem; color: #0F172A;">
                                        📝 Enter Payment Reference (Optional)
                                    </h4>
                                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                                        <div>
                                            <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.3rem;">UPI Reference / UTR Number</label>
                                            <input type="text" name="utr_number" placeholder="e.g. 324156789012 (Optional)" style="width: 100%; padding: 0.7rem 0.9rem; border: 1.5px solid #CBD5E1; border-radius: 6px; font-size: 0.9rem; font-weight: 700; letter-spacing: 0.5px;">
                                            <span style="font-size: 0.72rem; color: #64748B; margin-top: 0.2rem; display: block;">You can also share your payment receipt via WhatsApp after placing the order.</span>
                                        </div>
                                        <div>
                                            <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 0.3rem;">Upload Payment Screenshot (Optional)</label>
                                            <input type="file" name="payment_proof" accept="image/*" style="width: 100%; font-size: 0.82rem;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Option 2: Cash on Delivery (COD) -->
                        <div id="payment-card-cod" style="border: 1.5px solid #CBD5E1; background: #FFFFFF; border-radius: 12px; padding: 1.2rem; transition: all 0.25s ease;">
                            <label style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <input type="radio" name="payment_method" value="Cash on Delivery (COD)" onchange="togglePaymentMethod('cod')" style="accent-color: #16A34A; transform: scale(1.2);">
                                    <div>
                                        <strong style="font-size: 1rem; color: #0F172A; display: block;">💵 Cash on Delivery (COD)</strong>
                                        <span style="font-size: 0.76rem; color: #64748B;">Pay via Cash or UPI at your doorstep upon parcel arrival</span>
                                    </div>
                                </div>
                                <span style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; font-size: 0.72rem; font-weight: 900; padding: 0.25rem 0.6rem; border-radius: 6px;">PAY AT DOORSTEP 🚚</span>
                            </label>

                            <!-- COD Instructions Box (Shown when COD is active) -->
                            <div id="cod-details-container" style="display: none; margin-top: 1.2rem; padding-top: 1.2rem; border-top: 1px dashed #CBD5E1;">
                                <div style="background: #F0FDF4; border: 1.5px solid #86EFAC; border-radius: 10px; padding: 1rem 1.2rem;">
                                    <div style="font-size: 0.85rem; font-weight: 800; color: #166534; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem;">
                                        <span>✓</span>
                                        <span>Cash on Delivery is Available for Your Pincode!</span>
                                    </div>
                                    <ul style="font-size: 0.78rem; color: #15803D; line-height: 1.5; margin-left: 1.2rem;">
                                        <li>Please keep exact cash amount <strong>(<?= format_price($totalPrice) ?>)</strong> or UPI app ready during delivery.</li>
                                        <li>Our courier delivery partner (Delhivery / Blue Dart) will hand over the sealed parcel upon payment receipt.</li>
                                        <li>All standard 7-day return and exchange policies apply fully.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shared Delivery / Order Note Field -->
                    <div style="margin-top: 1rem;">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem; color: #334155;">Special Delivery Instructions / Note (Optional)</label>
                        <input type="text" name="customer_note" placeholder="e.g. Please deliver after 4 PM / Call before delivery" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #CBD5E1; border-radius: 8px; font-size: 0.88rem;">
                    </div>
                </div>

                <!-- Confirm & Place Order CTA Button -->
                <button type="submit" name="place_order" id="btn-submit-order" style="width: 100%; padding: 1.2rem; background: #16A34A; color: #FFFFFF; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; border: none; border-radius: var(--radius-md); cursor: pointer; box-shadow: 0 4px 20px rgba(22, 163, 74, 0.35); transition: var(--transition);">
                    CONFIRM & PAY VIA UPI (<?= format_price($totalPrice) ?>) 🔒
                </button>
            </div>

            <script>
            function togglePaymentMethod(method) {
                const cardUpi = document.getElementById('payment-card-upi');
                const cardCod = document.getElementById('payment-card-cod');
                const upiContainer = document.getElementById('upi-details-container');
                const codContainer = document.getElementById('cod-details-container');
                const submitBtn = document.getElementById('btn-submit-order');
                const formattedTotal = '<?= format_price($totalPrice) ?>';

                if (method === 'cod') {
                    cardCod.style.borderColor = '#16A34A';
                    cardCod.style.backgroundColor = '#F0FDF4';
                    cardUpi.style.borderColor = '#CBD5E1';
                    cardUpi.style.backgroundColor = '#FFFFFF';
                    
                    upiContainer.style.display = 'none';
                    codContainer.style.display = 'block';

                    submitBtn.textContent = 'PLACE CASH ON DELIVERY ORDER (' + formattedTotal + ') 🚚';
                    submitBtn.style.backgroundColor = '#0F172A';
                    submitBtn.style.boxShadow = '0 4px 20px rgba(15, 23, 42, 0.35)';
                } else {
                    cardUpi.style.borderColor = '#2563EB';
                    cardUpi.style.backgroundColor = '#F8FAFC';
                    cardCod.style.borderColor = '#CBD5E1';
                    cardCod.style.backgroundColor = '#FFFFFF';
                    
                    upiContainer.style.display = 'block';
                    codContainer.style.display = 'none';

                    submitBtn.textContent = 'CONFIRM & PAY VIA UPI (' + formattedTotal + ') 🔒';
                    submitBtn.style.backgroundColor = '#16A34A';
                    submitBtn.style.boxShadow = '0 4px 20px rgba(22, 163, 74, 0.35)';
                }
            }
            </script>

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
