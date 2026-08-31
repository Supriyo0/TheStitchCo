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
                <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem;">
                    <img src="assets/images/logo.jpg" alt="The Stitch Co." style="width: 42px; height: 42px; border-radius: 8px; border: 1.5px solid rgba(255,255,255,0.2);">
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
    <a href="shop.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) === 'shop.php' && !empty($_GET['cat']) ? 'active' : '' ?>">
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
</body>
</html>
