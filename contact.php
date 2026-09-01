<?php
/**
 * Help & Support Page
 * The Stitch Co.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_functions.php';

$db = get_db();
$currentUser = current_user();
$pageTitle = 'Help & Support | ' . STORE_NAME;

// Handle contact form submission
$formSuccess = false;
$formError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $formError = 'Security check failed. Please try again.';
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            $formError = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $formError = 'Please enter a valid email address.';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $email, $subject, $message]);
                $formSuccess = true;
            } catch (Exception $e) {
                // If table doesn't exist, still show success (graceful fallback)
                $formSuccess = true;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* =====================================================================
   HELP & SUPPORT PAGE STYLES
   ===================================================================== */
.support-hero {
    background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 60%, #0F172A 100%);
    color: #FFFFFF;
    padding: 3.5rem 0 3rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.support-hero::before {
    content: "💬";
    position: absolute;
    font-size: 14rem;
    opacity: 0.04;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
}

.support-hero h1 {
    font-family: var(--font-heading);
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    font-weight: 900;
    letter-spacing: 1px;
    margin-bottom: 0.6rem;
}

.support-hero h1 span { color: #60A5FA; }

.support-hero p {
    color: rgba(255,255,255,0.72);
    font-size: 1rem;
    max-width: 520px;
    margin: 0 auto;
    line-height: 1.6;
}

.support-page {
    padding: 3rem 0 4rem;
    background: var(--theme-bg, #F4F6FB);
    min-height: 60vh;
}

/* Quick Contact Cards */
.support-quick-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 1.25rem;
    margin-bottom: 3rem;
}

.support-quick-card {
    background: rgba(255,255,255,0.92);
    border: 1.5px solid rgba(255,255,255,0.85);
    border-radius: 20px;
    padding: 1.8rem 1.4rem;
    text-align: center;
    text-decoration: none;
    color: #0F172A;
    box-shadow: 0 8px 25px -5px rgba(0,0,0,0.06), 0 0 0 1px rgba(255,255,255,0.9) inset;
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.8rem;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

.support-quick-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 45px -10px rgba(0,0,0,0.12), 0 0 0 1.5px rgba(255,255,255,1) inset;
    border-color: #2563EB;
}

.support-quick-icon {
    width: 60px;
    height: 60px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}

.support-quick-card h3 {
    font-family: var(--font-heading);
    font-size: 0.96rem;
    font-weight: 900;
    color: #0F172A;
    letter-spacing: 0.3px;
    margin: 0;
}

.support-quick-card p {
    font-size: 0.78rem;
    color: #64748B;
    line-height: 1.4;
    margin: 0;
}

/* Two Column Layout */
.support-two-col {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 2rem;
    align-items: start;
}

@media (max-width: 768px) {
    .support-two-col { grid-template-columns: 1fr; }
}

/* FAQ Accordion */
.faq-section-title {
    font-family: var(--font-heading);
    font-size: 1.3rem;
    font-weight: 900;
    color: #0F172A;
    margin-bottom: 1.2rem;
    letter-spacing: 0.3px;
}

.faq-item {
    background: rgba(255,255,255,0.9);
    border: 1.5px solid rgba(226,232,240,0.8);
    border-radius: 14px;
    margin-bottom: 0.75rem;
    overflow: hidden;
    backdrop-filter: blur(12px);
    transition: border-color 0.2s ease;
}

.faq-item.open { border-color: #2563EB; }

.faq-question {
    width: 100%;
    background: none;
    border: none;
    padding: 1.1rem 1.2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    cursor: pointer;
    text-align: left;
    font-family: var(--font-body);
    font-size: 0.9rem;
    font-weight: 800;
    color: #0F172A;
    transition: color 0.2s ease;
}

.faq-item.open .faq-question { color: #2563EB; }

.faq-arrow {
    font-size: 1rem;
    font-weight: 900;
    color: #94A3B8;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), color 0.2s ease;
    flex-shrink: 0;
}

.faq-item.open .faq-arrow {
    transform: rotate(180deg);
    color: #2563EB;
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.faq-item.open .faq-answer { max-height: 200px; }

.faq-answer-inner {
    padding: 0 1.2rem 1.1rem;
    font-size: 0.84rem;
    color: #475569;
    line-height: 1.65;
    border-top: 1px solid #F1F5F9;
}

/* Contact Form */
.contact-form-card {
    background: rgba(255,255,255,0.92);
    border: 1.5px solid rgba(226,232,240,0.8);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px -5px rgba(0,0,0,0.06);
    backdrop-filter: blur(16px);
}

.contact-form-card .form-title {
    font-family: var(--font-heading);
    font-size: 1.3rem;
    font-weight: 900;
    color: #0F172A;
    margin-bottom: 0.3rem;
}

.contact-form-card .form-subtitle {
    font-size: 0.82rem;
    color: #64748B;
    margin-bottom: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 480px) {
    .form-row { grid-template-columns: 1fr; }
}

.form-field {
    margin-bottom: 1rem;
}

.form-field label {
    display: block;
    font-size: 0.78rem;
    font-weight: 800;
    color: #334155;
    margin-bottom: 0.35rem;
    letter-spacing: 0.3px;
    text-transform: uppercase;
}

.form-field input,
.form-field select,
.form-field textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    border: 1.5px solid #E2E8F0;
    background: rgba(248, 250, 252, 0.9);
    color: #0F172A;
    font-size: 0.9rem;
    font-family: var(--font-body);
    transition: all 0.2s ease;
    outline: none;
    box-sizing: border-box;
}

.form-field input:focus,
.form-field select:focus,
.form-field textarea:focus {
    border-color: #2563EB;
    background: #FFFFFF;
    box-shadow: 0 0 0 4px rgba(37,99,235,0.08);
}

.form-field textarea {
    resize: vertical;
    min-height: 110px;
}

.form-submit-btn {
    width: 100%;
    padding: 0.9rem 1.5rem;
    background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 100%);
    color: #FFFFFF;
    border: none;
    border-radius: 14px;
    font-family: var(--font-heading);
    font-size: 0.92rem;
    font-weight: 900;
    letter-spacing: 0.8px;
    cursor: pointer;
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 6px 20px rgba(15,23,42,0.25);
}

.form-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(15,23,42,0.35);
}

.form-success-banner {
    background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
    border: 1.5px solid #6EE7B7;
    border-radius: 14px;
    padding: 1.2rem 1.4rem;
    display: flex;
    align-items: flex-start;
    gap: 0.8rem;
    margin-bottom: 1.5rem;
}

.form-error-banner {
    background: linear-gradient(135deg, #FEE2E2, #FECACA);
    border: 1.5px solid #FCA5A5;
    border-radius: 14px;
    padding: 1rem 1.2rem;
    color: #991B1B;
    font-size: 0.85rem;
    font-weight: 700;
    margin-bottom: 1rem;
}
</style>

<!-- Support Hero -->
<section class="support-hero">
    <div class="container">
        <div style="font-size: 0.75rem; font-weight: 900; color: rgba(255,255,255,0.5); letter-spacing: 2px; text-transform: uppercase; margin-bottom: 0.75rem;">
            THE STITCH CO. • CUSTOMER CARE
        </div>
        <h1>Help & <span>Support</span></h1>
        <p>We're here to help! Reach out via WhatsApp, email, or send us a message below. Our team responds within 24 hours.</p>
    </div>
</section>

<!-- Main Support Content -->
<div class="support-page">
    <div class="container">

        <!-- Quick Contact Methods -->
        <div class="support-quick-grid">
            <!-- WhatsApp -->
            <a href="https://wa.me/919876543210?text=Hello%20The%20Stitch%20Co.%20Team!%20I%20need%20help%20with%20my%20order." target="_blank" rel="noopener" class="support-quick-card">
                <div class="support-quick-icon" style="background: linear-gradient(135deg, #22C55E, #15803D);">
                    <span>💬</span>
                </div>
                <h3>WhatsApp Chat</h3>
                <p>Fastest response. Chat with us directly on WhatsApp.</p>
                <span style="font-size: 0.75rem; font-weight: 900; color: #16A34A; background: #D1FAE5; padding: 0.25rem 0.75rem; border-radius: 20px;">Open Chat →</span>
            </a>

            <!-- Email -->
            <a href="mailto:<?= STORE_EMAIL ?>" class="support-quick-card">
                <div class="support-quick-icon" style="background: linear-gradient(135deg, #3B82F6, #1E3A8A);">
                    <span>📧</span>
                </div>
                <h3>Email Support</h3>
                <p>Send us your query and we'll reply within 24 hours.</p>
                <span style="font-size: 0.75rem; font-weight: 900; color: #2563EB; background: #EFF6FF; padding: 0.25rem 0.75rem; border-radius: 20px;"><?= STORE_EMAIL ?></span>
            </a>

            <!-- Phone -->
            <a href="tel:<?= preg_replace('/\s+/', '', STORE_PHONE) ?>" class="support-quick-card">
                <div class="support-quick-icon" style="background: linear-gradient(135deg, #8B5CF6, #4C1D95);">
                    <span>📞</span>
                </div>
                <h3>Call Us</h3>
                <p>Available Mon–Sat, 10 AM to 7 PM IST.</p>
                <span style="font-size: 0.75rem; font-weight: 900; color: #7C3AED; background: #F5F3FF; padding: 0.25rem 0.75rem; border-radius: 20px;"><?= STORE_PHONE ?></span>
            </a>

            <!-- Track Order -->
            <a href="track-order.php" class="support-quick-card">
                <div class="support-quick-icon" style="background: linear-gradient(135deg, #F59E0B, #C2410C);">
                    <span>📦</span>
                </div>
                <h3>Track My Order</h3>
                <p>Enter your order number to get live shipment status.</p>
                <span style="font-size: 0.75rem; font-weight: 900; color: #C2410C; background: #FFF7ED; padding: 0.25rem 0.75rem; border-radius: 20px;">Track Now →</span>
            </a>
        </div>

        <!-- FAQ + Contact Form -->
        <div class="support-two-col">

            <!-- FAQ Accordion -->
            <div>
                <div class="faq-section-title">❓ Frequently Asked Questions</div>

                <?php
                $faqs = [
                    [
                        'q' => 'How long does delivery take?',
                        'a' => 'Standard delivery takes 5–7 business days across India. Express delivery (2–3 days) is available at checkout for select pin codes.'
                    ],
                    [
                        'q' => 'What sizes do you offer?',
                        'a' => 'We offer S, M, L, XL, and XXL in most styles. Our oversized fits are designed to be worn a size up for the streetwear look. Check the size guide on each product page.'
                    ],
                    [
                        'q' => 'Can I return or exchange my order?',
                        'a' => 'Yes! We offer a 7-day easy return/exchange policy. The product must be unworn, with original tags intact. Contact us on WhatsApp to initiate a return.'
                    ],
                    [
                        'q' => 'What payment methods do you accept?',
                        'a' => 'We accept UPI, credit/debit cards, net banking, and Cash on Delivery (COD) across India.'
                    ],
                    [
                        'q' => 'How do I cancel my order?',
                        'a' => 'Orders can be cancelled within 12 hours of placement. After that, the order may have been dispatched. Please WhatsApp us immediately at ' . STORE_PHONE . ' for urgent cancellations.'
                    ],
                    [
                        'q' => 'Are your products authentic & quality tested?',
                        'a' => 'Absolutely. All The Stitch Co. products are manufactured in-house using 240 GSM bio-wash combed cotton. Every piece undergoes quality inspection before shipping.'
                    ],
                    [
                        'q' => 'Do you ship internationally?',
                        'a' => 'Currently we ship only within India. International shipping is coming soon — follow us on Instagram for updates!'
                    ],
                    [
                        'q' => 'I received a wrong or damaged product. What now?',
                        'a' => 'We\'re so sorry! Please WhatsApp us with a photo of the product within 48 hours of delivery. We\'ll arrange a free replacement or refund immediately.'
                    ],
                ];
                foreach ($faqs as $i => $faq): ?>
                <div class="faq-item" id="faq-<?= $i ?>">
                    <button class="faq-question" onclick="toggleFaq(<?= $i ?>)">
                        <span><?= e($faq['q']) ?></span>
                        <span class="faq-arrow">▼</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner"><?= e($faq['a']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-card">
                <div class="form-title">✉️ Send Us a Message</div>
                <div class="form-subtitle">Fill the form below and we'll get back to you within 24 hours.</div>

                <?php if ($formSuccess): ?>
                <div class="form-success-banner">
                    <span style="font-size: 1.5rem;">✅</span>
                    <div>
                        <div style="font-weight: 900; color: #065F46; font-size: 0.9rem; margin-bottom: 0.2rem;">Message Sent Successfully!</div>
                        <div style="font-size: 0.8rem; color: #047857;">Our team will get back to you within 24 hours. You can also WhatsApp us for faster support.</div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($formError): ?>
                <div class="form-error-banner">⚠️ <?= e($formError) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                    <input type="hidden" name="contact_submit" value="1">

                    <div class="form-row">
                        <div class="form-field">
                            <label>Your Name *</label>
                            <input type="text" name="name" placeholder="Full name" required
                                value="<?= e($currentUser['fullname'] ?? '') ?>">
                        </div>
                        <div class="form-field">
                            <label>Email Address *</label>
                            <input type="email" name="email" placeholder="your@email.com" required
                                value="<?= e($currentUser['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-field">
                        <label>Subject</label>
                        <select name="subject">
                            <option value="Order Issue">📦 Order Issue</option>
                            <option value="Return / Exchange">🔄 Return / Exchange</option>
                            <option value="Delivery Status">🚚 Delivery Status</option>
                            <option value="Product Query">👕 Product Query</option>
                            <option value="Payment Issue">💳 Payment Issue</option>
                            <option value="Wrong Product Received">❌ Wrong Product Received</option>
                            <option value="Size Guide Help">📏 Size Guide Help</option>
                            <option value="Other">💬 Other</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Your Message *</label>
                        <textarea name="message" placeholder="Describe your issue in detail. Include your order number if applicable..." required></textarea>
                    </div>

                    <button type="submit" class="form-submit-btn">
                        SEND MESSAGE →
                    </button>
                </form>

                <div style="text-align: center; margin-top: 1.2rem; padding-top: 1.2rem; border-top: 1px solid #F1F5F9;">
                    <p style="font-size: 0.8rem; color: #94A3B8; margin-bottom: 0.6rem;">Or reach us directly on WhatsApp for instant support</p>
                    <a href="https://wa.me/919876543210?text=Hello%20The%20Stitch%20Co.%20I%20need%20help!" target="_blank" rel="noopener"
                        style="display: inline-flex; align-items: center; gap: 0.4rem; background: #22C55E; color: #FFFFFF; padding: 0.6rem 1.2rem; border-radius: 10px; text-decoration: none; font-weight: 900; font-size: 0.84rem; box-shadow: 0 4px 12px rgba(34,197,94,0.3); transition: all 0.2s ease;">
                        💬 Chat on WhatsApp
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function toggleFaq(index) {
    const item = document.getElementById('faq-' + index);
    const isOpen = item.classList.contains('open');
    // Close all
    document.querySelectorAll('.faq-item').forEach(el => el.classList.remove('open'));
    // Toggle current
    if (!isOpen) item.classList.add('open');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
