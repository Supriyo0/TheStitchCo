<?php
/**
 * Admin Store Settings Configuration
 * UPI ID, QR Upload, Shipping Charges & Identity
 * The Stitch Co.
 */

$adminTitle = 'Store Settings';
require_once __DIR__ . '/header.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $settingsData = $_POST['settings'] ?? [];
    
    // Explicitly handle checkboxes
    $settingsData['announcement_bar_enabled'] = isset($settingsData['announcement_bar_enabled']) ? '1' : '0';
    $settingsData['maintenance_mode'] = isset($settingsData['maintenance_mode']) ? '1' : '0';
    $settingsData['phonepe_enabled'] = isset($settingsData['phonepe_enabled']) ? '1' : '0';

    foreach ($settingsData as $key => $val) {
        update_setting($key, trim($val));
    }

    // Handle UPI QR Code Upload
    if (!empty($_FILES['upi_qr_file']['name'])) {
        $up = handle_image_upload($_FILES['upi_qr_file'], 'qrcodes', 'upi_qr');
        if ($up['success']) {
            update_setting('upi_qr_image', $up['relative_url']);
        }
    }

    // Handle Promo Banner Image Upload
    if (!empty($_FILES['promo_image_file']['name'])) {
        $upPromo = handle_image_upload($_FILES['promo_image_file'], 'banners', 'promo_banner');
        if ($upPromo['success']) {
            update_setting('promo_image', $upPromo['relative_url']);
        }
    }

    $msg = 'Settings updated successfully!';
}
?>

<?php if ($msg): ?>
    <div style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">✓ <?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">General & Payment Configuration</h2>
            <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Manage brand identity, contact details, UPI merchant settings, and checkout fees.</span>
        </div>
    </div>
    <div style="padding: 1.8rem;">
        <form action="settings.php" method="POST" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                
                <!-- Maintenance Mode Emergency Box -->
                <?php $isMaintActive = (int)get_setting('maintenance_mode', '0') === 1; ?>
                <div style="grid-column: span 2; background: <?= $isMaintActive ? '#FEF2F2' : '#F8FAFC' ?>; border: 1.5px solid <?= $isMaintActive ? '#FCA5A5' : 'var(--admin-border)' ?>; border-radius: 12px; padding: 1.3rem; margin-bottom: 0.5rem; transition: all 0.3s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem; flex-wrap: wrap; gap: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <span style="font-size: 1.3rem;">🛑</span>
                            <div>
                                <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.05rem; font-weight: 800; color: <?= $isMaintActive ? '#991B1B' : '#1E293B' ?>; margin: 0;">Store Maintenance Mode (Offline Screen)</h3>
                                <span style="font-size: 0.78rem; color: var(--admin-text-muted);">When enabled, public visitors are redirected to the animated offline screen. Admins can still access the admin panel and preview the store.</span>
                            </div>
                        </div>
                        <label style="display: flex; align-items: center; gap: 0.6rem; font-weight: 800; font-size: 0.88rem; color: <?= $isMaintActive ? '#DC2626' : '#64748B' ?>; cursor: pointer; background: #FFFFFF; padding: 0.45rem 0.9rem; border-radius: 20px; border: 1.5px solid <?= $isMaintActive ? '#DC2626' : '#CBD5E1' ?>;">
                            <input type="checkbox" name="settings[maintenance_mode]" value="1" <?= $isMaintActive ? 'checked' : '' ?> style="accent-color: #DC2626; transform: scale(1.2);">
                            <span><?= $isMaintActive ? '🔴 Maintenance Mode is ON' : '⚪ Maintenance Mode is OFF' ?></span>
                        </label>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem; color: #475569;">Offline Notice Message for Customers</label>
                        <input type="text" name="settings[maintenance_message]" value="<?= e(get_setting('maintenance_message', 'We are currently upgrading our core infrastructure, fine-tuning checkout performance, and preparing exclusive new streetwear drops.')) ?>" placeholder="Message displayed on the maintenance page..." style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 600; font-size: 0.88rem; background: #FFFFFF;">
                    </div>
                </div>

                <!-- Top Announcement Bar Controls -->
                <div style="grid-column: span 2; background: #F8FAFC; border: 1.5px solid var(--admin-border); border-radius: 8px; padding: 1.2rem; margin-bottom: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
                        <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.05rem; font-weight: 800; color: #1E3A8A;">📢 Top Header Announcement Bar</h3>
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 800; font-size: 0.85rem; color: #16A34A; cursor: pointer;">
                            <input type="checkbox" name="settings[announcement_bar_enabled]" value="1" <?= (get_setting('announcement_bar_enabled', '1') == '1') ? 'checked' : '' ?>>
                            <span>Show Announcement Bar on Storefront</span>
                        </label>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Announcement Message (Supports HTML & Emojis)</label>
                        <input type="text" name="settings[announcement_bar_text]" value="<?= e(get_setting('announcement_bar_text', 'FREE SHIPPING ON PREPAID ORDERS ABOVE ₹999 🚚 &nbsp;|&nbsp; USE CODE <strong>WELCOME10</strong> FOR 10% OFF')) ?>" placeholder="e.g. FREE SHIPPING ON PREPAID ORDERS ABOVE ₹999 🚚 | USE CODE WELCOME10 FOR 10% OFF" style="width: 100%; padding: 0.75rem 1rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 700; font-size: 0.88rem;">
                    </div>
                </div>

                <!-- Store Info -->
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Store Name</label>
                    <input type="text" name="settings[store_name]" value="<?= e(get_setting('store_name', 'The Stitch Co.')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Tagline</label>
                    <input type="text" name="settings[store_tagline]" value="<?= e(get_setting('store_tagline', 'Wear Your Vibe')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Store Support Email</label>
                    <input type="email" name="settings[store_email]" value="<?= e(get_setting('store_email', 'support@thestitchco.shop')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Support Phone</label>
                    <input type="text" name="settings[store_phone]" value="<?= e(get_setting('store_phone', '+91 98765 43210')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div style="grid-column: span 2;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Registered Physical Address</label>
                    <input type="text" name="settings[store_address]" value="<?= e(get_setting('store_address', 'Stitch House, Streetwear Lane, Kolkata, West Bengal, 700001')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">GSTIN / Tax ID</label>
                    <input type="text" name="settings[gstin]" value="<?= e(get_setting('gstin', '19AAACT0000A1Z5')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">ImgBB Cloud API Key (Optional)</label>
                    <input type="text" name="settings[imgbb_api_key]" value="<?= e(get_setting('imgbb_api_key', 'e3a1f81d1ef8fca02d1373e34b171bf7')) ?>" placeholder="ImgBB API Key" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-family: monospace;">
                </div>

                <!-- PhonePe Payment Gateway Configuration -->
                <div style="grid-column: span 2; border-top: 1px solid var(--admin-border); padding-top: 1.5rem; margin-top: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 800; color: #6739B7; margin-bottom: 0.2rem;">
                                🟣 PhonePe Payment Gateway Configuration
                            </h3>
                            <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Configure PhonePe Standard Checkout API (PG V1 / Hermes) for instant UPI & Card payments.</span>
                        </div>
                        <label style="display: flex; align-items: center; gap: 0.5rem; background: #FAF5FF; border: 1.5px solid #D8B4FE; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer;">
                            <input type="checkbox" name="settings[phonepe_enabled]" value="1" <?= (get_setting('phonepe_enabled', '1') == '1') ? 'checked' : '' ?> style="accent-color: #6739B7; transform: scale(1.15);">
                            <span style="font-size: 0.82rem; font-weight: 800; color: #581C87;">Enable PhonePe Gateway</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Environment Mode *</label>
                    <?php $peMode = get_setting('phonepe_mode', 'production'); ?>
                    <select name="settings[phonepe_mode]" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 700;">
                        <option value="production" <?= $peMode === 'production' ? 'selected' : '' ?>>🟢 Production / Live Mode (Real Transactions)</option>
                        <option value="sandbox" <?= $peMode === 'sandbox' ? 'selected' : '' ?>>🟡 UAT / Sandbox Mode (Testing)</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Merchant ID / Client ID *</label>
                    <input type="text" name="settings[phonepe_merchant_id]" value="<?= e(get_setting('phonepe_merchant_id', 'SU2508281240185820112176')) ?>" placeholder="e.g. SU2508281240185820112176" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-family: monospace; font-weight: 700; color: #1E3A8A;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Salt Key / Secret Key *</label>
                    <input type="text" name="settings[phonepe_salt_key]" value="<?= e(get_setting('phonepe_salt_key', 'a987a9bc-cf7e-417b-a627-21105e2de2d7')) ?>" placeholder="e.g. a987a9bc-cf7e-417b-a627-21105e2de2d7" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-family: monospace; font-size: 0.82rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Salt Index *</label>
                    <input type="text" name="settings[phonepe_salt_index]" value="<?= e(get_setting('phonepe_salt_index', '1')) ?>" placeholder="e.g. 1" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-family: monospace; font-weight: 700;">
                </div>

                <div style="grid-column: span 2; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 8px; padding: 1rem 1.2rem;">
                    <div style="font-size: 0.82rem; font-weight: 800; color: #334155; margin-bottom: 0.5rem; text-transform: uppercase;">
                        🔗 PhonePe Webhook & Callback URLs (Copy for PhonePe Merchant Dashboard):
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.78rem;">
                        <div>
                            <span style="color: var(--admin-text-muted); font-weight: 600;">Callback / Redirect URL:</span><br>
                            <code style="background: #FFFFFF; border: 1px solid #CBD5E1; padding: 0.3rem 0.6rem; border-radius: 4px; display: block; margin-top: 0.2rem; user-select: all;"><?= BASE_URL ?>phonepe-response.php</code>
                        </div>
                        <div>
                            <span style="color: var(--admin-text-muted); font-weight: 600;">Server-to-Server Webhook URL:</span><br>
                            <code style="background: #FFFFFF; border: 1px solid #CBD5E1; padding: 0.3rem 0.6rem; border-radius: 4px; display: block; margin-top: 0.2rem; user-select: all;"><?= BASE_URL ?>api/phonepe-webhook.php</code>
                        </div>
                    </div>
                </div>

                <!-- Shipping & Fees -->
                <div style="grid-column: span 2; border-top: 1px solid var(--admin-border); padding-top: 1.5rem; margin-top: 0.5rem;">
                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem;">Shipping Thresholds</h3>
                </div>

                <!-- Google SMTP Email Gateway Configuration -->
                <div style="grid-column: span 2; border-top: 1px solid var(--admin-border); padding-top: 1.5rem; margin-top: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; font-weight: 800; color: #2563EB;">📧 Google SMTP Mail Server Configuration</h3>
                        <span style="font-size: 0.8rem; background: #ECFDF5; color: #059669; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 800;">Active & Verified</span>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">SMTP Host</label>
                    <input type="text" name="settings[smtp_host]" value="<?= e(get_setting('smtp_host', 'smtp.gmail.com')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">SMTP Port (TLS 587 / SSL 465)</label>
                    <input type="number" name="settings[smtp_port]" value="<?= e(get_setting('smtp_port', '587')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Google Account Email / Username</label>
                    <input type="email" name="settings[smtp_username]" value="<?= e(get_setting('smtp_username', 'thestitchco.official@gmail.com')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 700;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Google App Password (16-char)</label>
                    <input type="password" name="settings[smtp_password]" value="<?= e(get_setting('smtp_password', 'mbslyojqdzwbugjb')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-family: monospace;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Sender From Email</label>
                    <input type="email" name="settings[smtp_from_email]" value="<?= e(get_setting('smtp_from_email', 'thestitchco.official@gmail.com')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Sender From Name</label>
                    <input type="text" name="settings[smtp_from_name]" value="<?= e(get_setting('smtp_from_name', 'The Stitch Co.')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>

                <!-- Free Shipping & Order Thresholds -->
                <div style="grid-column: span 2; border-top: 1px solid var(--admin-border); padding-top: 1.5rem; margin-top: 0.5rem;">
                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem; color: #16A34A;">🚚 Shipping & Order Thresholds</h3>
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Free Shipping Minimum Amount (₹)</label>
                    <input type="number" name="settings[free_shipping_threshold]" value="<?= e(get_setting('free_shipping_threshold', '999')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 800;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Default Flat Delivery Charge for Orders below Minimum (₹)</label>
                    <input type="number" name="settings[default_delivery_charge]" value="<?= e(get_setting('default_delivery_charge', '0')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>

                <!-- Mid-Page Promotional Banner Deal Controls -->
                <div style="grid-column: span 2; border-top: 1px solid var(--admin-border); padding-top: 1.5rem; margin-top: 0.5rem;">
                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem; color: #7C3AED;">🏷️ Mid-Page Special Offer Promotional Banner</h3>
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Promo Badge Tag</label>
                    <input type="text" name="settings[promo_badge]" value="<?= e(get_setting('promo_badge', 'SPECIAL OFFER')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Promo Coupon Code Stamp</label>
                    <input type="text" name="settings[promo_code]" value="<?= e(get_setting('promo_code', 'WELCOME10')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 800; color: #10B981;">
                </div>
                <div style="grid-column: span 2;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Promo Main Headline</label>
                    <input type="text" name="settings[promo_title]" value="<?= e(get_setting('promo_title', 'GET 10% OFF ON YOUR FIRST ORDER')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 800;">
                </div>
                <div style="grid-column: span 2;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Promo Subtitle / Description</label>
                    <input type="text" name="settings[promo_subtitle]" value="<?= e(get_setting('promo_subtitle', 'Fresh oversized graphic drops crafted with heavyweight bio-washed cotton.')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">CTA Button Text</label>
                    <input type="text" name="settings[promo_button_text]" value="<?= e(get_setting('promo_button_text', 'SHOP NOW')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px; font-weight: 800;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">CTA Button Link</label>
                    <input type="text" name="settings[promo_button_url]" value="<?= e(get_setting('promo_button_url', 'shop.php')) ?>" style="width: 100%; padding: 0.7rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div style="grid-column: span 2;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.3rem;">Upload Custom Promo Banner Image</label>
                    <input type="file" name="promo_image_file" accept="image/*" style="width: 100%; font-size: 0.85rem;">
                    <div style="margin-top: 0.6rem; display: flex; align-items: center; gap: 0.8rem;">
                        <img src="../<?= e(get_setting('promo_image', 'assets/images/products/chaos_club_green.svg')) ?>" alt="Promo Image Preview" style="width: 60px; height: 60px; border: 1px solid var(--admin-border); border-radius: 4px; padding: 2px; object-fit: contain;">
                        <span style="font-size: 0.75rem; color: var(--admin-text-muted);">Current Active Image: <?= e(get_setting('promo_image', 'assets/images/products/chaos_club_green.svg')) ?></span>
                    </div>
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem; align-items: center;">
                <button type="submit" name="save_settings" style="padding: 0.9rem 2.8rem; background: #000000; color: #fff; border: none; border-radius: 6px; font-weight: 900; font-size: 0.95rem; cursor: pointer; letter-spacing: 0.5px;">
                    SAVE ALL CONFIGURATION
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
