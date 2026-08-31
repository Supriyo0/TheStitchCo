<?php
/**
 * Admin Panel Header Component
 * Matches localhost:3001 and Blueprint Designs
 * The Stitch Co.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$db = get_db();
$adminUser = current_user();
$currentFile = basename($_SERVER['PHP_SELF']);

// Count Pending Orders & Pending Payments
$pendingOrdersCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'Order Placed' OR payment_status = 'Pending'")->fetchColumn();
$pendingPaymentCount = (int)$db->query("SELECT COUNT(*) FROM payments WHERE status = 'Pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle ?? 'Admin Dashboard') ?> | <?= STORE_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" href="../assets/images/logo.jpg" type="image/jpeg">
</head>
<body class="admin-body">

<!-- Left Sidebar Navigation -->
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo-box">
            <img src="../assets/images/logo.jpg" alt="Logo">
        </div>
        <div class="sidebar-brand-text">
            <h2>THE STITCH CO.</h2>
            <span>WEAR YOUR VIBE</span>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li>
            <a href="index.php" class="sidebar-link <?= $currentFile === 'index.php' ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <span class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    </span>
                    <span>Dashboard</span>
                </div>
            </a>
        </li>
        <li>
            <a href="orders.php" class="sidebar-link <?= $currentFile === 'orders.php' ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <span class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
                    </span>
                    <span>Orders</span>
                </div>
                <?php if ($pendingOrdersCount > 0): ?>
                    <span class="nav-badge-pill"><?= $pendingOrdersCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="payments.php" class="sidebar-link <?= $currentFile === 'payments.php' ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <span class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                    </span>
                    <span>Payment Approval</span>
                </div>
                <?php if ($pendingPaymentCount > 0): ?>
                    <span class="nav-badge-pill" style="background: #2563EB;"><?= $pendingPaymentCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="products.php" class="sidebar-link <?= $currentFile === 'products.php' ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <span class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.38 3.46L16 2a4 4 0 01-8 0L3.62 3.46a2 2 0 00-1.34 2.23l.58 3.47a1 1 0 00.99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 002-2V10h2.15a1 1 0 00.99-.84l.58-3.47a2 2 0 00-1.34-2.23z"/></svg>
                    </span>
                    <span>Products</span>
                </div>
            </a>
        </li>
        <li>
            <a href="categories.php" class="sidebar-link <?= $currentFile === 'categories.php' ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <span class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                    </span>
                    <span>Categories & Roundels</span>
                </div>
            </a>
        </li>
        <li>
            <a href="banners.php" class="sidebar-link <?= $currentFile === 'banners.php' ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <span class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    </span>
                    <span>Hero Banners</span>
                </div>
            </a>
        </li>
        <li>
            <a href="coupons.php" class="sidebar-link <?= $currentFile === 'coupons.php' ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <span class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"></path></svg>
                    </span>
                    <span>Coupons</span>
                </div>
            </a>
        </li>
        <li>
            <a href="customers.php" class="sidebar-link <?= $currentFile === 'customers.php' ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <span class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </span>
                    <span>Customers</span>
                </div>
            </a>
        </li>
        <li>
            <a href="media.php" class="sidebar-link <?= $currentFile === 'media.php' ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <span class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                    </span>
                    <span>Media Storage</span>
                </div>
            </a>
        </li>
        <li>
            <a href="settings.php" class="sidebar-link <?= $currentFile === 'settings.php' ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <span class="sidebar-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    </span>
                    <span>Store & Deals Settings</span>
                </div>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <a href="logout.php" class="sidebar-link" style="color: #EF4444;">
            <div class="sidebar-link-content">
                <span class="sidebar-icon">🚪</span>
                <span>Logout</span>
            </div>
        </a>
    </div>
</aside>

<!-- Admin Main View Area -->
<div class="admin-main">
    <header class="admin-topbar">
        <div class="topbar-left">
            <h1 class="page-title"><?= e($adminTitle ?? 'Dashboard') ?></h1>
        </div>
        <div class="topbar-right">
            <a href="../index.php" target="_blank" class="view-store-btn">
                <span>View Store</span>
                <span>↗</span>
            </a>
            <div class="admin-profile-pill">
                <div class="admin-avatar">
                    <?= strtoupper(substr($adminUser['fullname'] ?? 'A', 0, 2)) ?>
                </div>
                <div class="admin-info">
                    <h4><?= e($adminUser['fullname'] ?? 'Administrator') ?></h4>
                    <span><?= ucfirst(str_replace('_', ' ', $adminUser['role'] ?? 'Admin')) ?></span>
                </div>
            </div>
        </div>
    </header>
    <main class="admin-container">
