<?php
/**
 * Footer Component with Mobile Bottom Navigation Bar
 * The Stitch Co.
 */
?>

<!-- Desktop/Tablet Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div>
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                    <img src="assets/images/logo.jpg" alt="The Stitch Co." style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                    <div>
                        <h3 class="footer-brand-title" style="margin-bottom: 0;">THE STITCH CO.</h3>
                        <span style="font-size: 0.72rem; color: #94A3B8; font-weight: 700; letter-spacing: 0.5px;">A Fashion Brand by MJ Company</span>
                    </div>
                </div>
                <p class="footer-brand-desc">
                    The Stitch Co. is a premium streetwear fashion brand proudly owned and operated by <strong>MJ Company</strong>. Crafted for those who choose to stand out.
                </p>
                <div style="display: flex; gap: 1rem; color: #94A3B8; font-size: 1.2rem;">
                    <a href="#" title="Instagram">📷</a>
                    <a href="#" title="Facebook">👍</a>
                    <a href="#" title="Twitter / X">🐦</a>
                    <a href="#" title="YouTube">📺</a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="footer-heading">Shop</h4>
                <ul class="footer-links">
                    <li><a href="shop.php">All Products</a></li>
                    <li><a href="shop.php?cat=oversized">Oversized Fit</a></li>
                    <li><a href="shop.php?cat=tshirts">Graphic Tees</a></li>
                    <li><a href="shop.php?cat=hoodies">Hoodies</a></li>
                    <li><a href="shop.php?cat=new_arrivals">New Arrivals</a></li>
                </ul>
            </div>

            <!-- Customer Care -->
            <div>
                <h4 class="footer-heading">Customer Care</h4>
                <ul class="footer-links">
                    <li><a href="track-order.php">Track Your Order</a></li>
                    <li><a href="account.php?tab=orders">Shipping & Delivery</a></li>
                    <li><a href="account.php?tab=orders">Returns & Exchanges (7 Days)</a></li>
                    <li><a href="#">Terms & Conditions</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h4 class="footer-heading">Contact Us</h4>
                <ul class="footer-links" style="font-size: 0.85rem; color: #94A3B8;">
                    <li><a href="mailto:thestitchco.official@gmail.com" style="color: #94A3B8;">✉️ thestitchco.official@gmail.com</a></li>
                    <li><a href="tel:+917063179581" style="color: #94A3B8;">📞 +91 7063179581</a></li>
                    <li><a href="https://wa.me/917047051581" target="_blank" style="color: #25D366; font-weight: 700;">💬 WhatsApp: +91 7047051581</a></li>
                    <li style="line-height: 1.4; margin-top: 0.3rem;">📍 Sisir Building, Jetty Ghat Bus Stopage, Fraserganj, South 24 Parganas, West Bengal, India - 743357</li>
                    <li style="margin-top: 0.5rem; font-size: 0.75rem; color: #CBD5E1;">GSTIN: <strong style="color: #fff;">19GWPPD6451K1ZV</strong></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div style="line-height: 1.6;">
                <div>© 2026 The Stitch Co.</div>
                <div style="font-weight: 700; color: #94A3B8;">A Fashion Brand by MJ Company. All Rights Reserved.</div>
            </div>
            <div style="display: flex; gap: 0.8rem; font-size: 0.8rem; color: #94A3B8;">
                <span>🔒 100% Secure Checkout</span>
                <span>•</span>
                <span>UPI / Cards / NetBanking</span>
            </div>
        </div>
    </div>
</footer>

<!-- Mobile Bottom Fixed Navigation Bar (Liquid Glass Dock matching Blueprint) -->
<nav class="mobile-bottom-nav">
    <a href="index.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
        <span class="mobile-nav-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        </span>
        <span>Home</span>
    </a>
    <a href="categories.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">
        <span class="mobile-nav-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        </span>
        <span>Categories</span>
    </a>
    <a href="shop.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) === 'shop.php' && !empty($_GET['q']) ? 'active' : '' ?>">
        <span class="mobile-nav-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </span>
        <span>Search</span>
    </a>
    <a href="cart.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'active' : '' ?>">
        <span class="mobile-nav-icon" style="position: relative;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            <span class="badge-count cart-badge-count" style="top: -6px; right: -8px; font-size: 0.6rem; min-width: 15px; height: 15px; display: <?= ($cartData['count'] ?? 0) > 0 ? 'flex' : 'none' ?>;"><?= $cartData['count'] ?? 0 ?></span>
        </span>
        <span>Cart</span>
    </a>
    <a href="account.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) === 'account.php' ? 'active' : '' ?>">
        <span class="mobile-nav-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        </span>
        <span>Account</span>
    </a>
</nav>

<!-- Scripts -->
<script src="assets/js/main.js"></script>

<?php
$showWelcome = false;
$welcomeName = '';
if (!empty($_SESSION['show_welcome_popup'])) {
    $showWelcome = true;
    $welcomeName = $_SESSION['welcome_user_name'] ?? 'Fashionista';
    unset($_SESSION['show_welcome_popup']);
    unset($_SESSION['welcome_user_name']);
} elseif (isset($_GET['welcome']) && is_logged_in()) {
    $showWelcome = true;
    $welcomeName = ($currentUser['fullname'] ?? 'Fashionista');
}
?>

<?php if ($showWelcome): ?>
<!-- Registration Welcome Celebration Popup Modal -->
<div id="welcome-popup-overlay" style="position: fixed; inset: 0; background: rgba(10, 15, 29, 0.75); z-index: 999999; display: flex; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(8px); animation: welcomeFadeIn 0.3s ease;">
    <div style="background: #FFFFFF; border-radius: 20px; max-width: 460px; width: 100%; padding: 2.2rem 1.8rem; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); position: relative; border: 1.5px solid rgba(255, 255, 255, 0.2); animation: welcomeScaleUp 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);">
        
        <!-- Close Button -->
        <button onclick="closeWelcomePopup()" style="position: absolute; top: 1rem; right: 1rem; background: #F1F5F9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; color: #64748B; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">&times;</button>

        <!-- Animated Party Icon -->
        <div style="width: 76px; height: 76px; background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); border: 3px solid #3B82F6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.3rem; margin: 0 auto 1.2rem; box-shadow: 0 8px 20px rgba(59, 130, 246, 0.25);">
            🎉
        </div>

        <span style="display: inline-block; padding: 0.3rem 0.8rem; background: #EFF6FF; color: #2563EB; border-radius: 20px; font-size: 0.75rem; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 0.6rem;">
            Welcome to the Fam!
        </span>

        <h2 style="font-family: var(--font-heading); font-size: 1.55rem; font-weight: 900; color: #0F172A; margin-bottom: 0.4rem; line-height: 1.2;">
            Hey, <?= e($welcomeName) ?>! 👋
        </h2>

        <p style="font-size: 0.88rem; color: #64748B; margin-bottom: 1.4rem; line-height: 1.4;">
            Your account has been created successfully. Here is an exclusive welcome gift to kickstart your streetwear collection:
        </p>

        <!-- Exclusive Coupon Gift Box -->
        <div style="background: linear-gradient(135deg, #F8FAFC 0%, #EEF2F6 100%); border: 2px dashed #3B82F6; border-radius: 12px; padding: 1.1rem; margin-bottom: 1.4rem; position: relative;">
            <div style="font-size: 0.78rem; font-weight: 800; color: #1E40AF; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.3rem;">
                🎁 FLAT 10% OFF YOUR FIRST ORDER
            </div>
            <div style="display: flex; align-items: center; justify-content: center; gap: 0.8rem; margin-top: 0.4rem;">
                <code id="welcome-coupon-code" style="font-family: monospace; font-size: 1.25rem; font-weight: 900; color: #1E3A8A; background: #FFFFFF; padding: 0.3rem 0.8rem; border-radius: 6px; border: 1px solid #CBD5E1; letter-spacing: 1px;">WELCOME10</code>
                <button onclick="copyWelcomeCoupon()" id="copy-coupon-btn" style="padding: 0.45rem 0.9rem; background: #2563EB; color: #FFFFFF; border: none; border-radius: 6px; font-weight: 800; font-size: 0.78rem; cursor: pointer; transition: background 0.2s;">
                    📋 Copy Code
                </button>
            </div>
            <div style="font-size: 0.72rem; color: #64748B; margin-top: 0.5rem;">
                Apply at checkout on any order over ₹999
            </div>
        </div>

        <!-- Perks List -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; text-align: left; margin-bottom: 1.6rem; font-size: 0.78rem; color: #334155; font-weight: 700;">
            <div style="display: flex; align-items: center; gap: 0.4rem;">⚡ Priority Dispatch</div>
            <div style="display: flex; align-items: center; gap: 0.4rem;">🚚 Free Shipping Available</div>
            <div style="display: flex; align-items: center; gap: 0.4rem;">💬 24/7 WhatsApp Support</div>
            <div style="display: flex; align-items: center; gap: 0.4rem;">🔄 7-Day Easy Exchange</div>
        </div>

        <!-- CTAs -->
        <div style="display: flex; flex-direction: column; gap: 0.7rem;">
            <a href="shop.php" class="hero-btn" style="background: #000000; color: #FFFFFF; padding: 0.85rem 1.5rem; font-size: 0.92rem; font-weight: 900; border-radius: 10px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                <span>EXPLORE STREETWEAR DROPS</span>
                <span>&rarr;</span>
            </a>
            <a href="account.php" style="font-size: 0.82rem; font-weight: 800; color: #64748B; text-decoration: none; padding: 0.3rem;">
                View My Profile Dashboard
            </a>
        </div>
    </div>
</div>

<style>
@keyframes welcomeFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes welcomeScaleUp {
    from { transform: scale(0.85); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>

<script>
function closeWelcomePopup() {
    const overlay = document.getElementById('welcome-popup-overlay');
    if (overlay) {
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 0.25s ease';
        setTimeout(() => overlay.remove(), 250);
    }
}

function copyWelcomeCoupon() {
    const code = document.getElementById('welcome-coupon-code').textContent;
    const btn = document.getElementById('copy-coupon-btn');
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(() => {
            btn.textContent = '✓ Copied!';
            btn.style.background = '#10B981';
            setTimeout(() => {
                btn.textContent = '📋 Copy Code';
                btn.style.background = '#2563EB';
            }, 2500);
        });
    } else {
        prompt('Copy coupon code:', code);
    }
}
</script>
<?php endif; ?>

</body>
</html>
