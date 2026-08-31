<?php
/**
 * Admin Dashboard Overview
 * Matches localhost:3001 Screenshot
 * The Stitch Co.
 */

$adminTitle = 'Dashboard Overview';
require_once __DIR__ . '/header.php';

// 1. Calculate Real Metrics
$totalSales = (float)$db->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE payment_status = 'Paid'")->fetchColumn();
$totalOrders = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalCustomers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$activeProducts = (int)$db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();

// 2. Order Status Breakdown
$statusCounts = [
    'Pending Approval' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'Pending' AND status != 'Cancelled'")->fetchColumn(),
    'Confirmed' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'Confirmed'")->fetchColumn(),
    'Processing' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'Processing'")->fetchColumn(),
    'Shipped' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'Shipped'")->fetchColumn(),
    'Delivered' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'Delivered'")->fetchColumn(),
    'Cancelled' => (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'Cancelled'")->fetchColumn(),
];

// 3. Fetch Recent Orders
$recentOrders = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();

// 4. Fetch Top Selling Products
$topProducts = $db->query("
    SELECT p.name, p.price, p.thumbnail, COALESCE(SUM(oi.quantity), 0) as units_sold, COALESCE(SUM(oi.total), 0) as revenue
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    GROUP BY p.id
    ORDER BY units_sold DESC, p.id DESC
    LIMIT 5
")->fetchAll();

// 5. Fetch Low Stock Products
$lowStock = $db->query("SELECT name, stock, category FROM products WHERE stock <= 15 ORDER BY stock ASC LIMIT 5")->fetchAll();
?>

<!-- KPI Top Summary Grid -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon-wrap kpi-icon-purple">₹</div>
        <div class="kpi-details">
            <h3>Total Sales</h3>
            <div class="kpi-value"><?= format_price($totalSales) ?></div>
            <div class="kpi-trend">↑ Live real revenue</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon-wrap kpi-icon-green">📦</div>
        <div class="kpi-details">
            <h3>Total Orders</h3>
            <div class="kpi-value"><?= $totalOrders ?></div>
            <div class="kpi-trend">↑ Live all-time orders</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon-wrap kpi-icon-orange">👥</div>
        <div class="kpi-details">
            <h3>Total Customers</h3>
            <div class="kpi-value"><?= $totalCustomers ?></div>
            <div class="kpi-trend">↑ Live registered accounts</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon-wrap kpi-icon-blue">👕</div>
        <div class="kpi-details">
            <h3>Active Products</h3>
            <div class="kpi-value"><?= $activeProducts ?></div>
            <div class="kpi-trend">↑ Live in catalog</div>
        </div>
    </div>
</div>

<!-- Breakdown & Recent Orders Split Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.8rem;">
    <!-- Order Status Breakdown -->
    <div class="admin-card" style="margin-bottom: 0;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Order Status Breakdown</h3>
        </div>
        <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 0.9rem;">
            <?php foreach ($statusCounts as $sLabel => $sCount): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.88rem;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: <?= $sLabel === 'Confirmed' ? '#10B981' : ($sLabel === 'Pending Approval' ? '#F59E0B' : ($sLabel === 'Cancelled' ? '#EF4444' : '#3B82F6')) ?>;"></span>
                        <span style="font-weight: 600;"><?= e($sLabel) ?></span>
                    </div>
                    <span style="font-weight: 800; font-size: 0.95rem;"><?= $sCount ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="admin-card" style="margin-bottom: 0;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Recent Orders</h3>
            <a href="orders.php" style="font-size: 0.8rem; font-weight: 700; color: var(--admin-primary); text-decoration: none;">View All Orders</a>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--admin-text-muted);">No orders recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr>
                                <td>
                                    <strong style="font-weight: 800;"><?= e($ro['order_number']) ?></strong><br>
                                    <span style="font-size: 0.72rem; color: var(--admin-text-muted);"><?= date('d M, h:i a', strtotime($ro['created_at'])) ?></span>
                                </td>
                                <td><?= e($ro['customer_name']) ?></td>
                                <td style="font-weight: 800;"><?= format_price($ro['total_price']) ?></td>
                                <td>
                                    <span class="status-pill status-<?= strtolower(str_replace(' ', '', $ro['status'])) ?>"><?= e($ro['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Top Products & Low Stock Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
    <!-- Top Products -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Top Selling Products</h3>
            <a href="products.php" style="font-size: 0.8rem; font-weight: 700; color: var(--admin-primary); text-decoration: none;">Catalog</a>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue Generated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topProducts as $tp): ?>
                        <tr>
                            <td style="font-weight: 700;"><?= e($tp['name']) ?></td>
                            <td style="font-weight: 800;"><?= $tp['units_sold'] ?></td>
                            <td style="font-weight: 800;"><?= format_price($tp['revenue']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Low Stock Alert</h3>
            <a href="products.php" style="font-size: 0.8rem; font-weight: 700; color: var(--admin-primary); text-decoration: none;">Inventory</a>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Stock Left</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lowStock)): ?>
                        <tr><td colspan="3" style="text-align: center; color: #10B981; font-weight: 700;">All product inventory levels are healthy.</td></tr>
                    <?php else: ?>
                        <?php foreach ($lowStock as $ls): ?>
                            <tr>
                                <td style="font-weight: 700;"><?= e($ls['name']) ?></td>
                                <td><?= ucfirst(e($ls['category'])) ?></td>
                                <td>
                                    <span class="status-pill status-cancelled"><?= $ls['stock'] ?> Left</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
