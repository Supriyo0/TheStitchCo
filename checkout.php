<?php
/**
 * Step 1: Delivery Address & Shipping Selection
 * Saved Addresses Selector + New Address Entry with Account Save Option
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

// Fetch user saved addresses
$savedAddresses = [];
if ($currentUser) {
    try {
        $db->exec("DELETE FROM user_addresses WHERE fullname LIKE '%Souvik%' OR phone LIKE '%98765 43210%'");
    } catch (Exception $e) {}

    $addrStmt = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $addrStmt->execute([$currentUser['id']]);
    $savedAddresses = $addrStmt->fetchAll();
}

$sessionShipping = $_SESSION['checkout_shipping'] ?? [];

// Handle Step 1 Submission: Save Shipping & Proceed to Payment
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_to_payment'])) {
    $addressChoice = trim($_POST['address_choice'] ?? 'new');
    $shippingMethod = trim($_POST['shipping_method'] ?? 'standard');

    $finalShipping = null;

    if ($addressChoice !== 'new' && (int)$addressChoice > 0) {
        // User selected an existing saved address
        $selectedId = (int)$addressChoice;
        $stmt = $db->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$selectedId, $currentUser['id']]);
        $pickedAddr = $stmt->fetch();

        if ($pickedAddr) {
            $finalShipping = [
                'fullname' => $pickedAddr['fullname'],
                'email' => $currentUser['email'] ?? '',
                'phone' => $pickedAddr['phone'],
                'address_line1' => $pickedAddr['address_line1'],
                'address_line2' => $pickedAddr['address_line2'] ?? '',
                'landmark' => $pickedAddr['landmark'] ?? '',
                'city' => $pickedAddr['city'],
                'state' => $pickedAddr['state'],
                'pincode' => $pickedAddr['pincode'],
                'shipping_method' => $shippingMethod
            ];
        } else {
            $errorMessage = 'Selected saved address could not be found.';
        }
    } else {
        // User entered a new address
        $fullName = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? ($currentUser['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $addressLine1 = trim($_POST['address_line1'] ?? '');
        $addressLine2 = trim($_POST['address_line2'] ?? '');
        $landmark = trim($_POST['landmark'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? 'West Bengal');
        $pincode = trim($_POST['pincode'] ?? '');
        $addressType = trim($_POST['address_type'] ?? 'home');
        $saveAddressToAccount = isset($_POST['save_address_to_account']) ? 1 : 0;

        if (empty($fullName) || empty($phone) || empty($addressLine1) || empty($city) || empty($pincode)) {
            $errorMessage = 'Please complete all required address fields (*).';
        } else {
            $finalShipping = [
                'fullname' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'address_line1' => $addressLine1,
                'address_line2' => $addressLine2,
                'landmark' => $landmark,
                'city' => $city,
                'state' => $state,
                'pincode' => $pincode,
                'shipping_method' => $shippingMethod
            ];

            // Save address to user account for future use
            if ($saveAddressToAccount && $currentUser) {
                try {
                    $isDef = empty($savedAddresses) ? 1 : 0;
                    $db->prepare("
                        INSERT INTO user_addresses (user_id, fullname, phone, address_line1, address_line2, landmark, city, state, pincode, address_type, is_default)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ")->execute([$currentUser['id'], $fullName, $phone, $addressLine1, $addressLine2, $landmark, $city, $state, $pincode, $addressType, $isDef]);
                } catch (Exception $ex) {}
            }
        }
    }

    if ($finalShipping && empty($errorMessage)) {
        $_SESSION['checkout_shipping'] = $finalShipping;
        header("Location: payment.php");
        exit;
    }
}

// Calculate initial preview shipping
$selectedShippingMethod = $sessionShipping['shipping_method'] ?? 'standard';
$productDeliveryCharge = $cartData['delivery_charge'] ?? 0.00;
$baseShippingFee = ($productDeliveryCharge > 0) ? $productDeliveryCharge : 0.00;
$shippingFee = ($selectedShippingMethod === 'express') ? ($baseShippingFee + 99.00) : $baseShippingFee;
$totalPrice = max(0, $subtotal - $discountAmount + $shippingFee);

$pageTitle = 'Step 1: Delivery Address | ' . STORE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2rem 1.25rem 5rem;">
    <!-- 2-Step Checkout Stepper -->
    <div class="checkout-stepper">
        <div class="step-node active" id="step-node-1">
            <div class="step-circle">1</div>
            <div class="step-label">Delivery Address</div>
        </div>
        <div class="step-line"></div>
        <div class="step-node" id="step-node-2">
            <div class="step-circle">2</div>
            <div class="step-label">Payment & Confirmation</div>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div style="background: #FEF2F2; border: 1.5px solid #EF4444; color: #DC2626; padding: 1rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem; font-weight: 700;">
            ⚠️ <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form action="checkout.php" method="POST" id="address-step-form">
        <div style="display: grid; grid-template-columns: 1fr; gap: 2.5rem;" class="checkout-grid">
            <!-- Left: Delivery Address & Shipping Form -->
            <div>
                <!-- Section 1: Saved Addresses Selector (If User Has Any) -->
                <?php if (!empty($savedAddresses)): ?>
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
                            <h2 style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; text-transform: uppercase;">
                                📍 Choose Saved Delivery Address
                            </h2>
                            <span style="font-size: 0.78rem; font-weight: 800; color: #16A34A;">✓ <?= count($savedAddresses) ?> Saved Address(es)</span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                            <?php foreach ($savedAddresses as $idx => $addr): 
                                $isFirst = ($idx === 0);
                            ?>
                                <label class="saved-addr-box" style="display: block; padding: 1.2rem; border: 2px solid <?= $isFirst ? '#2563EB' : 'var(--border)' ?>; background: <?= $isFirst ? '#EFF6FF' : '#F8FAFC' ?>; border-radius: var(--radius-md); cursor: pointer; transition: all 0.2s; position: relative;">
                                    <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.6rem;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <input type="radio" name="address_choice" value="<?= $addr['id'] ?>" <?= $isFirst ? 'checked' : '' ?> onchange="toggleAddressForm(false)" style="accent-color: #2563EB; transform: scale(1.15);">
                                            <strong style="font-size: 0.95rem; color: #0F172A;"><?= e($addr['fullname']) ?></strong>
                                        </div>
                                        <span style="background: #000; color: #fff; font-size: 0.68rem; font-weight: 800; padding: 0.2rem 0.5rem; border-radius: 4px; text-transform: uppercase;">
                                            <?= e($addr['address_type'] ?? 'Home') ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 0.84rem; color: #475569; line-height: 1.45; margin-left: 1.5rem;">
                                        <?= e($addr['address_line1']) ?><?= !empty($addr['address_line2']) ? ', ' . e($addr['address_line2']) : '' ?><br>
                                        <?php if (!empty($addr['landmark'])): ?>Landmark: <?= e($addr['landmark']) ?><br><?php endif; ?>
                                        <?= e($addr['city']) ?>, <?= e($addr['state']) ?> - <strong><?= e($addr['pincode']) ?></strong><br>
                                        📞 <strong>Phone:</strong> <?= e($addr['phone']) ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Or Select New Address Option -->
                        <label style="display: flex; align-items: center; gap: 0.6rem; padding: 0.9rem 1.2rem; background: #FFFFFF; border: 1.5px dashed var(--border); border-radius: var(--radius-md); cursor: pointer;">
                            <input type="radio" name="address_choice" value="new" id="choice_new_address" onchange="toggleAddressForm(true)" style="accent-color: var(--primary); transform: scale(1.15);">
                            <strong style="font-size: 0.9rem; color: var(--primary);">+ Enter a Different / New Delivery Address</strong>
                        </label>
                    </div>
                <?php endif; ?>

                <!-- Section 2: New Address Entry Form (Shown when 'new' is picked or user has no saved address) -->
                <div id="new-address-container" style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm); display: <?= empty($savedAddresses) ? 'block' : 'none' ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; text-transform: uppercase;">
                            <?= empty($savedAddresses) ? '1. Enter Delivery Address' : 'New Delivery Address Details' ?>
                        </h2>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem;">
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Full Name *</label>
                            <input type="text" name="fullname" id="input_fullname" placeholder="e.g. Rahul Sharma" value="<?= e($currentUser['fullname'] ?? '') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-weight: 600;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Email Address *</label>
                            <input type="email" name="email" id="input_email" placeholder="e.g. rahul@example.com" value="<?= e($currentUser['email'] ?? '') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-weight: 600;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Phone Number (for Courier & Tracking) *</label>
                            <input type="tel" name="phone" id="input_phone" placeholder="e.g. 9876543210" value="<?= e($currentUser['phone'] ?? '') ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-weight: 600;">
                        </div>
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">House / Flat No., Building, Street Name *</label>
                            <input type="text" name="address_line1" id="input_address_line1" placeholder="e.g. Flat 4B, Greenfield Heights, Park Street" value="" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-weight: 600;">
                        </div>
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Area, Colony, Sector, Locality</label>
                            <input type="text" name="address_line2" id="input_address_line2" placeholder="e.g. Near City Center Mall" value="" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-weight: 600;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">City / District *</label>
                            <input type="text" name="city" id="input_city" placeholder="e.g. Kolkata" value="" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-weight: 600;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">State *</label>
                            <input type="text" name="state" id="input_state" placeholder="e.g. West Bengal" value="West Bengal" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-weight: 600;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">PIN Code (6 Digits) *</label>
                            <input type="text" name="pincode" id="input_pincode" pattern="[0-9]{6}" placeholder="e.g. 700001" value="" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-weight: 600;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Nearby Landmark (Optional)</label>
                            <input type="text" name="landmark" id="input_landmark" placeholder="e.g. Opposite State Bank" value="" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-weight: 600;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">Address Type</label>
                            <select name="address_type" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-weight: 700; background: #fff;">
                                <option value="home">🏠 Home (All day delivery)</option>
                                <option value="work">🏢 Work / Office (Delivery 9 AM - 6 PM)</option>
                                <option value="other">📍 Other</option>
                            </select>
                        </div>
                        <div style="grid-column: span 2; margin-top: 0.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 700; color: var(--text-main); cursor: pointer;">
                                <input type="checkbox" name="save_address_to_account" value="1" checked style="accent-color: var(--primary);">
                                <span>Save this address to my account for future orders</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Shipping Method -->
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
                    <h2 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; text-transform: uppercase; margin-bottom: 1.2rem;">
                        <?= !empty($savedAddresses) ? '2. Choose Shipping Speed' : '2. Choose Shipping Speed' ?>
                    </h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.2rem; border: 1.5px solid var(--border); border-radius: var(--radius-md); cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 0.8rem;">
                                <input type="radio" name="shipping_method" value="standard" <?= ($selectedShippingMethod === 'standard') ? 'checked' : '' ?> style="accent-color: var(--primary);">
                                <div>
                                    <div style="font-weight: 800; font-size: 0.92rem;">Standard Delivery (3-5 Business Days)</div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">Delivered via Delhivery / Blue Dart surface express</div>
                                </div>
                            </div>
                            <span style="font-weight: 800; color: #16A34A;"><?= ($baseShippingFee > 0) ? format_price($baseShippingFee) : 'FREE' ?></span>
                        </label>
                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.2rem; border: 1.5px solid var(--border); border-radius: var(--radius-md); cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 0.8rem;">
                                <input type="radio" name="shipping_method" value="express" <?= ($selectedShippingMethod === 'express') ? 'checked' : '' ?> style="accent-color: var(--primary);">
                                <div>
                                    <div style="font-weight: 800; font-size: 0.92rem;">⚡ Express Air Shipping (1-2 Business Days)</div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">Priority air courier dispatch</div>
                                </div>
                            </div>
                            <span style="font-weight: 800;"><?= format_price($baseShippingFee + 99.00) ?></span>
                        </label>
                    </div>
                </div>

                <!-- Submit Button to Proceed to Step 2 -->
                <button type="submit" name="proceed_to_payment" style="width: 100%; padding: 1.1rem; background: var(--primary); color: #FFFFFF; font-family: var(--font-heading); font-size: 1rem; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; border: none; border-radius: var(--radius-md); cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: var(--transition);">
                    PROCEED TO PAYMENT &rarr;
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
                            <span style="color: var(--text-muted);">Shipping Fee</span>
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

<script>
function toggleAddressForm(showNew) {
    const container = document.getElementById('new-address-container');
    if (container) {
        container.style.display = showNew ? 'block' : 'none';
        if (showNew) {
            container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    // Update card borders
    document.querySelectorAll('.saved-addr-box').forEach(box => {
        const radio = box.querySelector('input[type="radio"]');
        if (radio && radio.checked) {
            box.style.borderColor = '#2563EB';
            box.style.backgroundColor = '#EFF6FF';
        } else {
            box.style.borderColor = 'var(--border)';
            box.style.backgroundColor = '#F8FAFC';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
