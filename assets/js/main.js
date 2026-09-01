/**
 * The Stitch Co. - Main Client-Side JavaScript
 * Dynamic interactions, AJAX Cart, Wishlist, Loaders, Toasts
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Brand Loader — First visit only (sessionStorage flag)
    const loader = document.getElementById('brand-loader');
    if (loader) {
        const hasSeenLoader = sessionStorage.getItem('stitch_loader_shown');
        if (!hasSeenLoader) {
            // First visit in this session — show full 3s brand loader
            sessionStorage.setItem('stitch_loader_shown', '1');
            setTimeout(() => {
                loader.classList.add('fade-out');
                setTimeout(() => loader.remove(), 500);
            }, 3000);
        } else {
            // Returning within same session — skip instantly
            loader.remove();
        }
    }

    // 1b. Navigation Page Spinner
    const navSpinner = document.getElementById('nav-page-spinner');
    if (navSpinner) {
        navSpinner.style.opacity = '0';
        setTimeout(() => navSpinner.remove(), 180);
    }

    // High-Speed Link Prefetching Cache
    const prefetchedUrls = new Set();
    function prefetchPage(url) {
        if (!url || prefetchedUrls.has(url)) return;
        prefetchedUrls.add(url);
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        document.head.appendChild(link);
    }

    // Instant prefetch on mouseover/touchstart (65ms intent threshold)
    document.addEventListener('mouseover', (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('mailto:') ||
            href.startsWith('tel:') || href.startsWith('javascript:') ||
            link.target === '_blank' || href.startsWith('http')) return;
        prefetchPage(href);
    }, { passive: true });

    document.addEventListener('touchstart', (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;
        const href = link.getAttribute('href');
        if (href && !href.startsWith('#') && !href.startsWith('http')) prefetchPage(href);
    }, { passive: true });

    // Show lightweight nav spinner on click
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('mailto:') ||
            href.startsWith('tel:') || href.startsWith('javascript:') ||
            link.target === '_blank' || href.startsWith('http')) return;
        if (link.hasAttribute('download') || link.getAttribute('onclick')) return;

        let sp = document.getElementById('nav-page-spinner');
        if (!sp) {
            sp = document.createElement('div');
            sp.id = 'nav-page-spinner';
            document.body.appendChild(sp);
        }
        sp.style.opacity = '1';
    });

    // 2. Mobile Slide Drawer Toggle
    window.openMobileDrawer = function() {
        const mobileDrawer = document.getElementById('mobile-drawer');
        const drawerOverlay = document.getElementById('mobile-drawer-overlay');
        if (mobileDrawer && drawerOverlay) {
            mobileDrawer.classList.add('active');
            drawerOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeMobileDrawer = function() {
        const mobileDrawer = document.getElementById('mobile-drawer');
        const drawerOverlay = document.getElementById('mobile-drawer-overlay');
        if (mobileDrawer && drawerOverlay) {
            mobileDrawer.classList.remove('active');
            drawerOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    const mobileToggle = document.getElementById('mobile-menu-toggle') || document.getElementById('mobile-menu-btn');
    const drawerClose = document.getElementById('drawer-close-btn');
    const drawerOverlay = document.getElementById('mobile-drawer-overlay');

    if (mobileToggle) mobileToggle.addEventListener('click', window.openMobileDrawer);
    if (drawerClose) drawerClose.addEventListener('click', window.closeMobileDrawer);
    if (drawerOverlay) drawerOverlay.addEventListener('click', window.closeMobileDrawer);

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

    // 7. Live AJAX Search Auto-Suggestions
    function initLiveSearch(formSelector) {
        document.querySelectorAll(formSelector).forEach(form => {
            const input = form.querySelector('input[name="q"]');
            if (!input) return;

            // Create suggestions panel if not exists
            let panel = form.querySelector('.search-suggestions-panel');
            if (!panel) {
                panel = document.createElement('div');
                panel.className = 'search-suggestions-panel';
                form.appendChild(panel);
            }

            let debounceTimer = null;

            function highlightText(text, query) {
                if (!query) return text;
                const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const regex = new RegExp(`(${escaped})`, 'gi');
                return text.replace(regex, '<mark>$1</mark>');
            }

            function fetchSuggestions(query) {
                if (!query || query.trim().length < 1) {
                    panel.classList.remove('show');
                    panel.innerHTML = '';
                    return;
                }

                fetch(`api/search.php?q=${encodeURIComponent(query.trim())}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data) return;

                        let html = '';

                        // Matched Categories
                        if (data.categories && data.categories.length > 0) {
                            html += `<div class="search-section-header">Suggested Collections</div>`;
                            html += `<div class="search-category-chips">`;
                            data.categories.forEach(cat => {
                                let icon = '👕';
                                if (cat.cat_key === 'oversized') icon = '🔥';
                                else if (cat.cat_key === 'polo') icon = '👔';
                                else if (cat.cat_key === 'hoodies') icon = '🧥';
                                else if (cat.cat_key === 'acid_wash') icon = '⚡';
                                else if (cat.cat_key === 'bottoms') icon = '👖';
                                html += `<a href="shop.php?cat=${encodeURIComponent(cat.cat_key)}" class="search-cat-chip">
                                    <span>${icon}</span>
                                    <span>${cat.cat_name}</span>
                                </a>`;
                            });
                            html += `</div>`;
                        }

                        // Matched Products
                        if (data.products && data.products.length > 0) {
                            html += `<div class="search-section-header">Matching Streetwear Drops</div>`;
                            data.products.forEach(p => {
                                html += `
                                <a href="${p.url}" class="search-item-row">
                                    <div class="search-item-left">
                                        <div class="search-item-thumb">
                                            <img src="${p.thumbnail_url}" alt="${p.name}" onerror="this.onerror=null; this.src='assets/images/placeholder.svg';">
                                        </div>
                                        <div class="search-item-info">
                                            <div class="search-item-name">${highlightText(p.name, query)}</div>
                                            <div class="search-item-cat">${p.category} ${p.badge ? '• ' + p.badge : ''}</div>
                                        </div>
                                    </div>
                                    <div class="search-item-right">
                                        <div class="search-item-price">${p.price_formatted}</div>
                                        ${p.discount_percent > 0 ? `<div class="search-item-discount">${p.discount_percent}% OFF</div>` : ''}
                                    </div>
                                </a>`;
                            });

                            // View All Results Footer
                            html += `
                            <a href="shop.php?q=${encodeURIComponent(query)}" class="search-footer-action">
                                <span>🔍</span>
                                <span>View all ${data.total} results for "<strong>${query}</strong>" &rarr;</span>
                            </a>`;
                        } else if (!data.categories || data.categories.length === 0) {
                            // No exact results
                            html += `
                            <div class="search-no-results">
                                <div class="search-no-results-text">No exact matches found for "<strong>${query}</strong>"</div>
                                <div style="font-size: 0.7rem; font-weight: 800; color: #94A3B8; margin-bottom: 0.5rem; text-transform: uppercase;">Trending Searches:</div>
                                <div class="search-popular-tags">
                                    <a href="shop.php?cat=oversized" class="search-popular-tag">🔥 Oversized</a>
                                    <a href="shop.php?cat=acid_wash" class="search-popular-tag">⚡ Acid Wash</a>
                                    <a href="shop.php?cat=hoodies" class="search-popular-tag">🧥 Hoodies</a>
                                    <a href="shop.php?cat=bottoms" class="search-popular-tag">👖 Cargo</a>
                                </div>
                            </div>`;
                        }

                        panel.innerHTML = html;
                        panel.classList.add('show');
                    })
                    .catch(() => {
                        panel.classList.remove('show');
                    });
            }

            input.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                const val = e.target.value;
                debounceTimer = setTimeout(() => fetchSuggestions(val), 180);
            });

            input.addEventListener('focus', (e) => {
                if (e.target.value.trim().length >= 1) {
                    fetchSuggestions(e.target.value);
                }
            });

            // Keyboard navigation in suggestions list
            input.addEventListener('keydown', (e) => {
                const items = panel.querySelectorAll('.search-item-row, .search-cat-chip, .search-footer-action');
                if (!items.length || !panel.classList.contains('show')) return;

                let activeItem = panel.querySelector('.search-item-row.selected');
                let activeIndex = Array.from(items).indexOf(activeItem);

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (activeItem) activeItem.classList.remove('selected');
                    activeIndex = (activeIndex + 1) % items.length;
                    items[activeIndex].classList.add('selected');
                    items[activeIndex].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (activeItem) activeItem.classList.remove('selected');
                    activeIndex = (activeIndex - 1 + items.length) % items.length;
                    items[activeIndex].classList.add('selected');
                    items[activeIndex].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter') {
                    if (activeItem) {
                        e.preventDefault();
                        window.location.href = activeItem.getAttribute('href');
                    }
                } else if (e.key === 'Escape') {
                    panel.classList.remove('show');
                }
            });

            // Click outside closes panel
            document.addEventListener('click', (e) => {
                if (!form.contains(e.target)) {
                    panel.classList.remove('show');
                }
            });
        });
    }

    initLiveSearch('.header-search-form');
    initLiveSearch('.mobile-search-form');
    initLiveSearch('.drawer-search-form');
});

// ==========================================
// iOS Liquid Glass Profile Dropdown Controller
// ==========================================
function toggleProfileDropdown(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const menu = document.getElementById('profile-glass-menu');
    const btn = document.getElementById('profile-dropdown-btn');
    if (!menu) return;

    const isActive = menu.classList.contains('active');
    if (isActive) {
        menu.classList.remove('active');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    } else {
        menu.classList.add('active');
        if (btn) btn.setAttribute('aria-expanded', 'true');
    }
}

// Global click outside and escape key listeners for profile dropdown
document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('profile-dropdown-wrapper');
    const menu = document.getElementById('profile-glass-menu');
    const btn = document.getElementById('profile-dropdown-btn');
    if (wrapper && !wrapper.contains(e.target) && menu && menu.classList.contains('active')) {
        menu.classList.remove('active');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const menu = document.getElementById('profile-glass-menu');
        const btn = document.getElementById('profile-dropdown-btn');
        if (menu && menu.classList.contains('active')) {
            menu.classList.remove('active');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
    }
});

