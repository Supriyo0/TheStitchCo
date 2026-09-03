<?php
/**
 * Step 2: Payment & Order Final Confirmation
 * The Stitch Co.
 *
 * Official PhonePe Payment Gateway Integration
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_functions.php';
require_once __DIR__ . '/includes/phonepe.php';

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

$errorMessage = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);

// PhonePe Gateway Config
$phonePeConfig = phonepe_get_config();

// Handle Order Placement & Gateway Redirection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $paymentMethod = trim($_POST['payment_method'] ?? 'PhonePe Gateway');
    $customerNote = trim($_POST['customer_note'] ?? '');

    $orderNumber = 'TSC-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    $fullName = $shipping['fullname'];
    $email = $shipping['email'];
    $phone = $shipping['phone'];
    $fullShippingText = "{$fullName}\nPhone: {$phone}\n{$shipping['address_line1']}" . 
                        (!empty($shipping['address_line2']) ? "\n{$shipping['address_line2']}" : '') . 
                        (!empty($shipping['landmark']) ? "\nLandmark: {$shipping['landmark']}" : '') . 
                        "\n{$shipping['city']}, {$shipping['state']} - {$shipping['pincode']}\nIndia";

    $customerId = $currentUser['id'] ?? 0;
    $nowIst = date('Y-m-d H:i:s');

    if ($paymentMethod === 'Cash on Delivery (COD)') {
        // ============================================
        // 1. CASH ON DELIVERY (COD) FLOW
        // ============================================
        try {
            $db->beginTransaction();

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

            $payStmt = $db->prepare("
                INSERT INTO payments (order_id, customer_id, amount, payment_method, utr_number, customer_note, status)
                VALUES (?, ?, ?, ?, 'COD (Pay on Delivery)', ?, 'Pending')
            ");
            $payStmt->execute([$orderId, $customerId, $totalPrice, $paymentMethod, $customerNote]);

            if ($appliedCoupon) {
                $db->prepare("INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)")
                   ->execute([$appliedCoupon['id'], $customerId, $orderId, $discountAmount]);
                $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$appliedCoupon['id']]);
            }

            log_order_status_transition($orderId, null, 'Order Placed', 'Order placed with Cash on Delivery', $fullName);
            create_notification(null, 'New COD Order Received', 'New COD order #' . $orderNumber . ' placed by ' . $fullName . ' (' . format_price($totalPrice) . ')', 'order', 'orders.php');

            clear_cart();
            unset($_SESSION['applied_coupon']);
            unset($_SESSION['checkout_shipping']);

            $db->commit();

            require_once __DIR__ . '/includes/mailer.php';
            try {
                $orderData = [
                    'order_number'     => $orderNumber,
                    'customer_name'    => $fullName,
                    'customer_email'   => $email,
                    'total_price'      => $totalPrice,
                    'subtotal'         => $subtotal,
                    'shipping_fee'     => $shippingFee,
                    'discount_amount'  => $discountAmount,
                    'payment_method'   => $paymentMethod,
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

    } else {
        // ============================================
        // 2. PHONEPE PAYMENT GATEWAY HOSTED FLOW
        // ============================================
        
        $tempOrderId = rand(10000, 99999);
        $redirectUrl = BASE_URL . 'phonepe-response.php';
        $callbackUrl = BASE_URL . 'api/phonepe-webhook.php';

        $orderPayload = [
            'order_id'       => $tempOrderId,
            'order_number'   => $orderNumber,
            'amount'         => $totalPrice,
            'customer_id'    => $customerId,
            'customer_name'  => $fullName,
            'customer_phone' => $phone,
            'customer_email' => $email
        ];

        // Call PhonePe Initiation API
        $phonePeRes = phonepe_initiate_payment($orderPayload, $redirectUrl, $callbackUrl);

        if ($phonePeRes['success'] && !empty($phonePeRes['redirect_url'])) {
            try {
                $db->beginTransaction();

                // Create Order record with Pending Payment status
                $orderStmt = $db->prepare("
                    INSERT INTO orders (
                        order_number, customer_id, customer_name, customer_email, customer_phone,
                        shipping_address, shipping_method, payment_method, subtotal,
                        discount_amount, coupon_code, shipping_fee, total_price,
                        status, payment_status, notes, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'PhonePe Payment Gateway', ?, ?, ?, ?, ?, 'Order Placed', 'Pending', ?, ?)
                ");
                $orderStmt->execute([
                    $orderNumber,
                    $customerId,
                    $fullName,
                    $email,
                    $phone,
                    $fullShippingText,
                    $shippingMethod,
                    $subtotal,
                    $discountAmount,
                    $appliedCoupon['code'] ?? null,
                    $shippingFee,
                    $totalPrice,
                    $customerNote,
                    $nowIst
                ]);
                $orderId = (int)$db->lastInsertId();

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

                $payStmt = $db->prepare("
                    INSERT INTO payments (order_id, customer_id, amount, payment_method, utr_number, customer_note, status)
                    VALUES (?, ?, ?, 'PhonePe Gateway', ?, ?, 'Pending')
                ");
                $payStmt->execute([$orderId, $customerId, $totalPrice, $phonePeRes['merchant_txn_id'], $customerNote]);

                $db->commit();

                $_SESSION['phonepe_order_id'] = $orderId;
                $_SESSION['phonepe_merchant_txn_id'] = $phonePeRes['merchant_txn_id'];

                // Seamlessly redirect customer to the official PhonePe Payment Gateway page
                header("Location: " . $phonePeRes['redirect_url']);
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                $errorMessage = 'Payment initiation database error: ' . $e->getMessage();
            }

        } else {
            $rawMsg = $phonePeRes['message'] ?? 'Could not initialize gateway connection.';
            $errorMessage = "PhonePe Gateway Error: {$rawMsg}. Please ensure your PhonePe Merchant ID (MID) and Salt Key are correctly configured in Admin Settings.";
        }
    }
}

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
        <div style="background: #FEF2F2; border: 1.5px solid #EF4444; color: #DC2626; padding: 1.2rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem; font-weight: 700; display: flex; align-items: flex-start; gap: 0.8rem; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);">
            <span style="font-size: 1.4rem;">⚠️</span>
            <div style="line-height: 1.4;"><?= e($errorMessage) ?></div>
        </div>
    <?php endif; ?>

    <form action="payment.php" method="POST" id="payment-step-form">
        <div style="display: grid; grid-template-columns: 1fr; gap: 2.5rem;" class="checkout-grid">
            <!-- Left: Deliver-To Summary + Payment Options -->
            <div>
                <!-- Deliver-To Summary Banner -->
                <div style="background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 800; color: #6739B7; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.3rem;">
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

                <!-- Payment Method Selection Card -->
                <div style="background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.5rem;">
                        <h2 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 900; text-transform: uppercase; margin: 0;">
                            Select Payment Method
                        </h2>
                        <span style="background: #EDE9FE; border: 1px solid #C4B5FD; color: #5B21B6; font-size: 0.72rem; font-weight: 800; padding: 0.25rem 0.65rem; border-radius: 20px;">
                            🔒 100% SECURE CHECKOUT
                        </span>
                    </div>

                    <!-- Payment Options Radio Stack -->
                    <div style="display: flex; flex-direction: column; gap: 1.2rem; margin-bottom: 1.5rem;">
                        
                        <!-- Option 1: Official PhonePe Payment Gateway -->
                        <div id="payment-card-phonepe" style="border: 2px solid #6739B7; background: #FAF5FF; border-radius: 12px; padding: 1.4rem; transition: all 0.25s ease;">
                            <label style="display: flex; align-items: flex-start; justify-content: space-between; cursor: pointer; gap: 0.75rem;">
                                <div style="display: flex; align-items: flex-start; gap: 0.85rem;">
                                    <input type="radio" name="payment_method" value="PhonePe Gateway" checked onchange="togglePaymentMethod('phonepe')" style="accent-color: #6739B7; transform: scale(1.3); margin-top: 0.25rem;">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                            <strong style="font-size: 1.05rem; color: #0F172A; display: inline-flex; align-items: center; gap: 0.35rem;">
                                                <span style="color: #6739B7;">🟣 PhonePe</span> Payment Gateway
                                            </strong>
                                            <?php if ($phonePeConfig['mode'] === 'sandbox'): ?>
                                                <span style="font-size: 0.65rem; background: #FEF3C7; color: #92400E; border: 1px solid #FCD34D; padding: 0.15rem 0.45rem; border-radius: 4px; font-weight: 800;">SANDBOX MODE</span>
                                            <?php endif; ?>
                                        </div>
                                        <span style="font-size: 0.78rem; color: #64748B; display: block; margin-top: 0.2rem;">
                                            Pay via PhonePe, Google Pay, Paytm, UPI, Credit / Debit Cards &amp; NetBanking
                                        </span>
                                    </div>
                                </div>
                                <span style="background: #6739B7; color: #fff; font-size: 0.72rem; font-weight: 900; padding: 0.3rem 0.65rem; border-radius: 6px; letter-spacing: 0.4px; white-space: nowrap;">
                                    FAST DISPATCH ⚡
                                </span>
                            </label>

                            <!-- Supported Channels Badge Row -->
                            <div id="phonepe-details-container" style="margin-top: 1.2rem; padding-top: 1.2rem; border-top: 1px dashed #DDD6FE;">
                                <div style="background: #FFFFFF; border: 1.5px solid #E9D5FF; border-radius: 10px; padding: 1.2rem;">
                                    <div style="font-size: 0.75rem; font-weight: 800; color: #6B21A8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.8rem;">
                                        Supported Payment Channels (On PhonePe Secure Checkout):
                                    </div>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.8rem;">
                                        <span style="background: #FAF5FF; border: 1px solid #D8B4FE; border-radius: 6px; padding: 0.35rem 0.7rem; font-size: 0.8rem; font-weight: 800; color: #581C87;">🟣 PhonePe App / UPI</span>
                                        <span style="background: #F0FDF4; border: 1px solid #86EFAC; border-radius: 6px; padding: 0.35rem 0.7rem; font-size: 0.8rem; font-weight: 800; color: #166534;">🟢 Google Pay</span>
                                        <span style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 6px; padding: 0.35rem 0.7rem; font-size: 0.8rem; font-weight: 800; color: #1E40AF;">🔵 Paytm / BHIM UPI</span>
                                        <span style="background: #F8FAFC; border: 1px solid #CBD5E1; border-radius: 6px; padding: 0.35rem 0.7rem; font-size: 0.8rem; font-weight: 800; color: #334155;">💳 Credit &amp; Debit Cards</span>
                                        <span style="background: #F8FAFC; border: 1px solid #CBD5E1; border-radius: 6px; padding: 0.35rem 0.7rem; font-size: 0.8rem; font-weight: 800; color: #334155;">🏦 Net Banking</span>
                                    </div>
                                    <div style="font-size: 0.76rem; color: #475569; display: flex; align-items: center; gap: 0.4rem;">
                                        <span>🔒</span>
                                        <span>Clicking the button below will securely open the official <strong>PhonePe Hosted Payment Page</strong>.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Option 2: Cash on Delivery (COD) -->
                        <div id="payment-card-cod" style="border: 1.5px solid #CBD5E1; background: #FFFFFF; border-radius: 12px; padding: 1.4rem; transition: all 0.25s ease;">
                            <label style="display: flex; align-items: flex-start; justify-content: space-between; cursor: pointer; gap: 0.75rem;">
                                <div style="display: flex; align-items: flex-start; gap: 0.85rem;">
                                    <input type="radio" name="payment_method" value="Cash on Delivery (COD)" onchange="togglePaymentMethod('cod')" style="accent-color: #16A34A; transform: scale(1.3); margin-top: 0.25rem;">
                                    <div>
                                        <strong style="font-size: 1.05rem; color: #0F172A; display: block;">💵 Cash on Delivery (COD)</strong>
                                        <span style="font-size: 0.78rem; color: #64748B; display: block; margin-top: 0.2rem;">
                                            Pay via Cash or UPI at your doorstep upon parcel arrival
                                        </span>
                                    </div>
                                </div>
                                <span style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; font-size: 0.72rem; font-weight: 900; padding: 0.3rem 0.65rem; border-radius: 6px; white-space: nowrap;">
                                    PAY AT DOORSTEP 🚚
                                </span>
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
                <button type="submit" name="place_order" id="btn-submit-order" style="width: 100%; padding: 1.2rem; background: #6739B7; color: #FFFFFF; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; border: none; border-radius: var(--radius-md); cursor: pointer; box-shadow: 0 4px 20px rgba(103, 57, 183, 0.35); transition: var(--transition);">
                    PAY VIA PHONEPE (<?= format_price($totalPrice) ?>) 🔒
                </button>
            </div>

            <script>
            function togglePaymentMethod(method) {
                const cardPhonepe = document.getElementById('payment-card-phonepe');
                const cardCod = document.getElementById('payment-card-cod');
                const phonepeContainer = document.getElementById('phonepe-details-container');
                const codContainer = document.getElementById('cod-details-container');
                const submitBtn = document.getElementById('btn-submit-order');
                const formattedTotal = '<?= format_price($totalPrice) ?>';

                if (method === 'cod') {
                    cardCod.style.borderColor = '#16A34A';
                    cardCod.style.backgroundColor = '#F0FDF4';
                    cardPhonepe.style.borderColor = '#CBD5E1';
                    cardPhonepe.style.backgroundColor = '#FFFFFF';
                    
                    phonepeContainer.style.display = 'none';
                    codContainer.style.display = 'block';

                    submitBtn.textContent = 'PLACE CASH ON DELIVERY ORDER (' + formattedTotal + ') 🚚';
                    submitBtn.style.backgroundColor = '#0F172A';
                    submitBtn.style.boxShadow = '0 4px 20px rgba(15, 23, 42, 0.35)';
                } else {
                    cardPhonepe.style.borderColor = '#6739B7';
                    cardPhonepe.style.backgroundColor = '#FAF5FF';
                    cardCod.style.borderColor = '#CBD5E1';
                    cardCod.style.backgroundColor = '#FFFFFF';
                    
                    phonepeContainer.style.display = 'block';
                    codContainer.style.display = 'none';

                    submitBtn.textContent = 'PAY VIA PHONEPE (' + formattedTotal + ') 🔒';
                    submitBtn.style.backgroundColor = '#6739B7';
                    submitBtn.style.boxShadow = '0 4px 20px rgba(103, 57, 183, 0.35)';
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
