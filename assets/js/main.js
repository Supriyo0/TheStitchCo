/**
 * The Stitch Co. - Main Client-Side JavaScript
 * Dynamic interactions, AJAX Cart, Wishlist, Loaders, Toasts
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Brand Loader dismiss
    const loader = document.getElementById('brand-loader');
    if (loader) {
        setTimeout(() => {
            loader.classList.add('fade-out');
            setTimeout(() => loader.remove(), 400);
        }, 500);
    }

    // 2. Mobile Slide Drawer Toggle
    const mobileToggle = document.getElementById('mobile-menu-toggle');
    const mobileDrawer = document.getElementById('mobile-drawer');
    const drawerOverlay = document.getElementById('mobile-drawer-overlay');
    const drawerClose = document.getElementById('drawer-close-btn');

    function openDrawer() {
        if (mobileDrawer && drawerOverlay) {
            mobileDrawer.classList.add('active');
            drawerOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeDrawer() {
        if (mobileDrawer && drawerOverlay) {
            mobileDrawer.classList.remove('active');
            drawerOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (mobileToggle) mobileToggle.addEventListener('click', openDrawer);
    if (drawerClose) drawerClose.addEventListener('click', closeDrawer);
    if (drawerOverlay) drawerOverlay.addEventListener('click', closeDrawer);

    // 3. Global Toast Notifications
    window.showToast = function(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<span>${type === 'success' ? '✓' : '✕'}</span> <span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    };

    // 4. AJAX Cart Operations
    window.addToCart = function(productId, quantity = 1, size = 'M', color = 'Black') {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        formData.append('size', size);
        formData.append('color', color);

        fetch('api/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                updateCartBadges(data.cart_count);
            } else if (data.redirect) {
                showToast(data.message || 'Please log in to add items to your cart.', 'error');
                setTimeout(() => {
                    const currentUrl = window.location.pathname.split('/').pop() + window.location.search;
                    window.location.href = data.redirect + '?redirect=' + encodeURIComponent(currentUrl || 'index.php');
                }, 800);
            } else {
                showToast(data.message || 'Error adding to cart', 'error');
            }
        })
        .catch(() => showToast('Network error while adding to cart', 'error'));
    };

    function updateCartBadges(count) {
        document.querySelectorAll('.cart-badge-count').forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'flex' : 'none';
        });
    }

    // 5. AJAX Wishlist Toggle
    document.querySelectorAll('.wishlist-toggle-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const productId = btn.getAttribute('data-product-id');
            if (!productId) return;

            const formData = new FormData();
            formData.append('product_id', productId);

            fetch('api/wishlist.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'added') {
                        btn.classList.add('active');
                        btn.innerHTML = '♥';
                        showToast(data.message, 'success');
                    } else {
                        btn.classList.remove('active');
                        btn.innerHTML = '♡';
                        showToast(data.message, 'success');
                    }
                    if (data.wishlist_count !== undefined) {
                        document.querySelectorAll('.wishlist-badge-count').forEach(el => {
                            el.textContent = data.wishlist_count;
                            el.style.display = data.wishlist_count > 0 ? 'flex' : 'none';
                        });
                    }
                } else if (data.redirect) {
                    showToast(data.message || 'Please log in to save items to your wishlist.', 'error');
                    setTimeout(() => {
                        const currentUrl = window.location.pathname.split('/').pop() + window.location.search;
                        window.location.href = data.redirect + '?redirect=' + encodeURIComponent(currentUrl || 'index.php');
                    }, 800);
                } else {
                    showToast(data.message || 'Error updating wishlist', 'error');
                }
            })
            .catch(() => showToast('Error connecting to server', 'error'));
        });
    });

    // 6. PDP Thumbnail Switcher
    const mainPdpImage = document.getElementById('pdp-main-img');
    document.querySelectorAll('.thumb-item').forEach(thumb => {
        thumb.addEventListener('click', () => {
            document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
            const targetSrc = thumb.getAttribute('data-img-src');
            if (mainPdpImage && targetSrc) {
                mainPdpImage.src = targetSrc;
            }
        });
    });
});
