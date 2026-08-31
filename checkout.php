<?php
/**
 * Master Multi-Step Checkout & UPI Payment
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

// Fetch saved addresses if logged in
$savedAddresses = [];
$defaultAddress = null;
if ($currentUser) {
    $addrStmt = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $addrStmt->execute([$currentUser['id']]);
    $savedAddresses = $addrStmt->fetchAll();
    if (!empty($savedAddresses)) {
        $defaultAddress = $savedAddresses[0];
    }
}

// Handle Order Placement POST Request
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $fullName = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? ($currentUser['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $addressLine1 = trim($_POST['address_line1'] ?? '');
    $addressLine2 = trim($_POST['address_line2'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? 'West Bengal');
    $pincode = trim($_POST['pincode'] ?? '');
    $shippingMethod = trim($_POST['shipping_method'] ?? 'standard');
    $paymentMethod = trim($_POST['payment_method'] ?? 'UPI (Scan & Pay)');
    $utrNumber = trim($_POST['utr_number'] ?? '');
    $customerNote = trim($_POST['customer_note'] ?? '');

    if (empty($fullName) || empty($phone) || empty($addressLine1) || empty($city) || empty($pincode)) {
        $errorMessage = 'Please complete all required shipping address fields.';
    } elseif ($paymentMethod === 'UPI (Scan & Pay)' && empty($utrNumber)) {
        $errorMessage = 'Please enter your 12-digit UPI Transaction / UTR reference number.';
    } else {
        // Calculate exact server-side totals
        $productDeliveryCharge = $cartData['delivery_charge'] ?? 0.00;
        $baseShippingFee = ($productDeliveryCharge > 0) ? $productDeliveryCharge : 0.00;
        $shippingFee = ($shippingMethod === 'express') ? ($baseShippingFee + 99.00) : $baseShippingFee;
        $totalPrice = max(0, $subtotal - $discountAmount + $shippingFee);
        $orderNumber = 'TSC-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        // Handle Payment Proof Screenshot Upload via ImgBB / Local Storage
        $proofPath = '';
        if (!empty($_FILES['payment_proof']['name'])) {
            $proofUp = upload_to_imgbb($_FILES['payment_proof']);
            if ($proofUp['success']) {
                $proofPath = $proofUp['url'] ?? $proofUp['relative_url'];
            }
        }

        $fullShippingText = "{$fullName}\nPhone: {$phone}\n{$addressLine1}" . ($addressLine2 ? "\n{$addressLine2}" : '') . ($landmark ? "\nLandmark: {$landmark}" : '') . "\n{$city}, {$state} - {$pincode}\nIndia";

        try {
            $db->beginTransaction();

            // 1. Determine Customer ID (create customer if guest or not logged in)
            $customerId = $currentUser['id'] ?? 0;
            if (!$customerId) {
                // Check if user with email exists
                $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
                $chk->execute([$email]);
                $userRow = $chk->fetch();
                if ($userRow) {
                    $customerId = (int)$userRow['id'];
                } else {
                    $insU = $db->prepare("INSERT INTO users (fullname, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'customer')");
                    $insU->execute([$fullName, $email, $phone, password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT)]);
                    $customerId = (int)$db->lastInsertId();
                }
            }

            // Save address if requested
            if (!empty($_POST['save_address']) && $customerId) {
                $insA = $db->prepare("INSERT INTO user_addresses (user_id, fullname, phone, address_line1, address_line2, landmark, city, state, pincode, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                $insA->execute([$customerId, $fullName, $phone, $addressLine1, $addressLine2, $landmark, $city, $state, $pincode]);
            }

            // 2. Insert Order
            $orderStmt = $db->prepare("
                INSERT INTO orders (
                    order_number, customer_id, customer_name, customer_email, customer_phone,
                    subtotal, discount_amount, coupon_code, shipping_fee, shipping_method,
                    total_price, status, payment_method, payment_status, shipping_address, notes
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, 'Order Placed', ?, 'Pending', ?, ?
                )
            ");
            $orderStmt->execute([
                $orderNumber, $customerId, $fullName, $email, $phone,
                $subtotal, $discountAmount, ($appliedCoupon['code'] ?? null), $shippingFee, ($shippingMethod === 'express' ? 'Express Shipping (1-2 Days)' : 'Standard Shipping (3-5 Days)'),
                $totalPrice, $paymentMethod, $fullShippingText, $customerNote
            ]);
            $orderId = (int)$db->lastInsertId();

            // 3. Insert Order Items & Deduct Stock
            $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, sku, size, color, image, price, quantity, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stockStmt = $db->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");

            foreach ($cartData['items'] as $item) {
                $itemStmt->execute([
                    $orderId, $item['product_id'], $item['name'], $item['sku'],
                    $item['size'], $item['color'], $item['primary_image'],
                    $item['price'], $item['quantity'], $item['subtotal']
                ]);
                $stockStmt->execute([$item['quantity'], $item['product_id']]);
            }

            // 4. Record Payment Submission
            $payStmt = $db->prepare("
                INSERT INTO payments (order_id, customer_id, amount, payment_method, utr_number, proof_screenshot, customer_note, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
            ");
            $payStmt->execute([$orderId, $customerId, $totalPrice, $paymentMethod, $utrNumber, $proofPath, $customerNote]);

            // 5. Record Coupon Usage
            if ($appliedCoupon) {
                $db->prepare("INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)")
                   ->execute([$appliedCoupon['id'], $customerId, $orderId, $discountAmount]);
                $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$appliedCoupon['id']]);
            }

            // 6. Record Status History & Notifications
            log_order_status_transition($orderId, null, 'Order Placed', 'Order placed with ' . $paymentMethod . ' (UTR: ' . $utrNumber . ')', $fullName);
            create_notification(null, 'New Order Received', 'New order #' . $orderNumber . ' placed by ' . $fullName . ' (' . format_price($totalPrice) . ')', 'order', 'orders.php');

            // 7. Clear Cart & Coupon
            clear_cart();
            unset($_SESSION['applied_coupon']);

            $db->commit();

            // 8. Dispatch Order Confirmation Email via Google SMTP
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
            $errorMessage = 'Order processing failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Secure Checkout | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2rem 1.25rem 5rem;">
    <!-- 3-Step Checkout Stepper -->
    <div class="checkout-stepper">
        <div class="step-node active" id="step-node-1">
            <div class="step-circle">1</div>
            <div class="step-label">Address</div>
        </div>
        <div class="step-node" id="step-node-2">
            <div class="step-circle">2</div>
            <div class="step-label">Payment</div>
        </div>
        <div class="step-node" id="step-node-3">
            <div class="step-circle">3</div>
            <div class="step-label">Review</div>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div style="background: #FEF2F2; border: 1px solid #EF4444; color: #DC2626; padding: 1rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem; font-weight: 700;">
            ⚠️ <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form action="checkout.php" method="POST" enctype="multipart/form-data" id="master-checkout-form">
        <div style="display: grid; grid-template-columns: 1fr; gap: 2.5rem;" class="checkout-grid">
            <!-- Left: Forms and Payment Selection -->
            <div>
                <!-- Section 1: Contact & Shipping Address -->
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; text-transform: uppercase;">1. Delivery Address</h2>
                        <?php if (!$currentUser): ?>
                            <a href="login.php?redirect=checkout.php" style="font-size: 0.82rem; font-weight: 700; color: var(--secondary-light);">Already have an account? Login</a>
                        <?php endif; ?>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem;">
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Full Name *</label>
                            <input type="text" name="fullname" required value="<?= e($defaultAddress['fullname'] ?? ($currentUser['fullname'] ?? 'Souvik Sayan Das')) ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Email Address *</label>
                            <input type="email" name="email" required value="<?= e($currentUser['email'] ?? 'souviksayan@gmail.com') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Phone Number *</label>
                            <input type="text" name="phone" required value="<?= e($defaultAddress['phone'] ?? '+91 98765 43210') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm);">
                        </div>
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Address Line 1 (Flat, House No., Street) *</label>
                            <input type="text" name="address_line1" required value="<?= e($defaultAddress['address_line1'] ?? 'Vill - Fraserganj, PO - Fraserganj') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm);">
                        </div>
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Address Line 2 (Area, Colony, Sector)</label>
                            <input type="text" name="address_line2" value="<?= e($defaultAddress['address_line2'] ?? 'P.S: Fraserganj Coastal') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">City / District *</label>
                            <input type="text" name="city" required value="<?= e($defaultAddress['city'] ?? 'South 24 Parganas') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">State *</label>
                            <input type="text" name="state" required value="<?= e($defaultAddress['state'] ?? 'West Bengal') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">PIN Code *</label>
                            <input type="text" name="pincode" required value="<?= e($defaultAddress['pincode'] ?? '743315') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm);">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Landmark (Optional)</label>
                            <input type="text" name="landmark" value="<?= e($defaultAddress['landmark'] ?? 'Near Sea Beach') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm);">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Shipping Method -->
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
                    <h2 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; text-transform: uppercase; margin-bottom: 1.2rem;">2. Shipping Method</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.2rem; border: 1.5px solid var(--border); border-radius: var(--radius-md); cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 0.8rem;">
                                <input type="radio" name="shipping_method" value="standard" checked style="accent-color: var(--primary);">
                                <div>
                                    <div style="font-weight: 800; font-size: 0.92rem;">Standard Shipping (3-5 Working Days)</div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">Delivered via Delhivery / Bluedart</div>
                                </div>
                            </div>
                            <span style="font-weight: 800; color: #16A34A;">FREE</span>
                        </label>
                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.2rem; border: 1.5px solid var(--border); border-radius: var(--radius-md); cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 0.8rem;">
                                <input type="radio" name="shipping_method" value="express" style="accent-color: var(--primary);">
                                <div>
                                    <div style="font-weight: 800; font-size: 0.92rem;">Express Air Delivery (1-2 Working Days)</div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">Priority express dispatch</div>
                                </div>
                            </div>
                            <span style="font-weight: 800;">₹99.00</span>
                        </label>
                    </div>
                </div>

                <!-- Section 3: Payment Method & UPI QR Flow -->
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
                    <h2 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; text-transform: uppercase; margin-bottom: 1.2rem;">3. Payment Method</h2>

                    <!-- UPI Scan & Pay Card -->
                    <div style="border: 2px solid var(--secondary-light); background: #F8FAFC; border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.6rem;">
                                <input type="radio" name="payment_method" value="UPI (Scan & Pay)" checked style="accent-color: var(--secondary-light);">
                                <strong style="font-size: 1rem;">UPI (Scan & Pay / Any UPI App)</strong>
                            </div>
                            <span style="background: #2563EB; color: #fff; font-size: 0.75rem; font-weight: 800; padding: 0.2rem 0.6rem; border-radius: 4px;">FAST & SECURE</span>
                        </div>

                        <!-- UPI QR Code Showcase -->
                        <div class="qr-box-container">
                            <div style="font-size: 0.82rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">SCAN & PAY VIA ANY UPI APP</div>
                            <div class="qr-image-frame">
                                <img src="assets/images/upi_qr.svg" alt="UPI QR Code">
                            </div>
                            <div style="font-weight: 800; font-size: 0.95rem; color: var(--primary);">
                                UPI ID: <span style="color: var(--secondary-light); user-select: all;"><?= e(get_setting('upi_id', 'thestitchco@upi')) ?></span>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.4rem;">
                                Merchant: <strong><?= e(get_setting('upi_merchant_name', 'The Stitch Co.')) ?></strong>
                            </div>

                            <!-- Mobile UPI Intent Deep Link Buttons -->
                            <?php 
                                $upiIntentUrl = generate_upi_intent_link(get_setting('upi_id', 'thestitchco@upi'), get_setting('upi_merchant_name', 'The Stitch Co.'), $subtotal - $discountAmount, 'TSC-PREVIEW');
                            ?>
                            <div style="margin-top: 1.2rem;">
                                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.6rem;">OR CLICK TO PAY DIRECTLY ON MOBILE:</div>
                                <div class="upi-intent-buttons">
                                    <a href="<?= $upiIntentUrl ?>" class="upi-app-btn" target="_blank">🟢 Google Pay</a>
                                    <a href="<?= $upiIntentUrl ?>" class="upi-app-btn" target="_blank">🟣 PhonePe</a>
                                    <a href="<?= $upiIntentUrl ?>" class="upi-app-btn" target="_blank">🔵 Paytm</a>
                                    <a href="<?= $upiIntentUrl ?>" class="upi-app-btn" target="_blank">🟠 BHIM / Other</a>
                                </div>
                            </div>
                        </div>

                        <!-- UTR & Proof Submission Inputs -->
                        <div style="margin-top: 1.5rem; background: #FFFFFF; border: 1.5px solid var(--border); border-radius: var(--radius-md); padding: 1.2rem;">
                            <h4 style="font-size: 0.9rem; font-weight: 800; margin-bottom: 0.8rem; color: var(--primary);">
                                📝 Submit Payment Confirmation Details
                            </h4>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <div>
                                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">UPI Transaction / UTR Number (12 Digits) *</label>
                                    <input type="text" name="utr_number" placeholder="e.g. 123456789012" required pattern="[A-Za-z0-9]{6,25}" style="width: 100%; padding: 0.7rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 0.9rem; font-weight: 700;">
                                    <span style="font-size: 0.72rem; color: var(--text-muted);">You can find the 12-digit UTR in your Google Pay, PhonePe, or Paytm receipt.</span>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Upload Payment Screenshot (Optional)</label>
                                    <input type="file" name="payment_proof" accept="image/*" style="width: 100%; font-size: 0.82rem;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alternate Mock Payment Options (Disabled with indicator) -->
                    <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 0.9rem 1.2rem; border: 1px solid var(--border); border-radius: var(--radius-md); background: #FAF5FF;">
                            <div style="display: flex; align-items: center; gap: 0.6rem;">
                                <input type="radio" name="payment_method" value="Cards / NetBanking">
                                <span style="font-size: 0.88rem; font-weight: 700;">Credit / Debit Card / Net Banking (Visa, RuPay, Master)</span>
                            </div>
                            <span style="font-size: 0.72rem; color: var(--text-muted);">Instant</span>
                        </label>
                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 0.9rem 1.2rem; border: 1px solid var(--border); border-radius: var(--radius-md); background: #FFFBEB;">
                            <div style="display: flex; align-items: center; gap: 0.6rem;">
                                <input type="radio" name="payment_method" value="Cash on Delivery (COD)">
                                <span style="font-size: 0.88rem; font-weight: 700;">Cash on Delivery (COD)</span>
                            </div>
                            <span style="font-size: 0.72rem; color: var(--text-muted);">Pay on delivery</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right: Order Review Summary Box -->
            <div>
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.8rem; box-shadow: var(--shadow-sm); position: sticky; top: 90px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
                        <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; text-transform: uppercase;">Order Items</h3>
                        <a href="cart.php" style="font-size: 0.78rem; font-weight: 700; color: var(--secondary-light);">Edit Cart</a>
                    </div>

                    <!-- Items Mini List -->
                    <div style="display: flex; flex-direction: column; gap: 0.8rem; max-height: 240px; overflow-y: auto; margin-bottom: 1.5rem; padding-right: 0.4rem;">
                        <?php foreach ($cartData['items'] as $item): ?>
                            <div style="display: flex; gap: 0.8rem; align-items: center;">
                                <img src="<?= e($item['primary_image']) ?>" alt="<?= e($item['name']) ?>" style="width: 50px; height: 60px; object-fit: cover; border-radius: 4px;">
                                <div style="flex: 1; font-size: 0.82rem;">
                                    <div style="font-weight: 700;"><?= e($item['name']) ?></div>
                                    <div style="color: var(--text-muted); font-size: 0.75rem;">Size: <?= e($item['size']) ?> | Qty: <?= $item['quantity'] ?></div>
                                </div>
                                <div style="font-weight: 800; font-size: 0.88rem;">
                                    <?= format_price($item['subtotal']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php 
                        $checkoutDeliveryCharge = $cartData['delivery_charge'] ?? 0.00;
                        $finalShippingFee = $checkoutDeliveryCharge;
                        $checkoutGrandTotal = max(0, $subtotal - $discountAmount + $finalShippingFee);
                    ?>
                    <!-- Pricing Breakdown -->
                    <div style="display: flex; flex-direction: column; gap: 0.7rem; font-size: 0.88rem; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 1rem 0; margin-bottom: 1.2rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Subtotal</span>
                            <span style="font-weight: 700;"><?= format_price($subtotal) ?></span>
                        </div>
                        <?php if ($discountAmount > 0): ?>
                            <div style="display: flex; justify-content: space-between; color: #16A34A;">
                                <span>Discount (<?= e($appliedCoupon['code']) ?>)</span>
                                <span style="font-weight: 800;">- <?= format_price($discountAmount) ?></span>
                            </div>
                        <?php endif; ?>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Delivery / Shipping</span>
                            <span style="font-weight: 700; color: <?= $finalShippingFee == 0 ? '#16A34A' : '#2563EB' ?>;"><?= $finalShippingFee == 0 ? 'FREE' : format_price($finalShippingFee) ?></span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 1.3rem; font-weight: 900; margin-bottom: 1.5rem;">
                        <span>Total to Pay</span>
                        <span style="color: var(--primary);"><?= format_price($checkoutGrandTotal) ?></span>
                    </div>

                    <button type="submit" name="place_order" class="hero-btn" style="width: 100%; background: #000000; color: #FFFFFF; padding: 1rem; font-size: 1rem; border: none; cursor: pointer; text-align: center;">
                        PAY & COMPLETE ORDER →
                    </button>

                    <!-- Trust Markers -->
                    <div style="margin-top: 1.2rem; display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.75rem; color: var(--text-muted);">
                        <div>🔒 100% Secure Encrypted Transaction</div>
                        <div>⚡ Instant UPI Verification Support</div>
                        <div>📦 Fast Dispatch via Tracked Couriers</div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
@media (min-width: 992px) {
    .checkout-grid {
        grid-template-columns: 1.7fr 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
