<?php
/**
 * Admin Customers Directory
 * The Stitch Co.
 */

$adminTitle = 'Customer Management';
require_once __DIR__ . '/header.php';

$customers = $db->query("
    SELECT u.*, 
           COUNT(o.id) as total_orders, 
           COALESCE(SUM(o.total_price), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.customer_id
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.id DESC
")->fetchAll();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Registered Customers (<?= count($customers) ?>)</h2>
            <span style="font-size: 0.8rem; color: var(--admin-text-muted);">View customer profiles, contact info, total orders placed, and lifetime spending.</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Email Address</th>
                    <th>Phone</th>
                    <th>Orders Placed</th>
                    <th>Lifetime Spent</th>
                    <th>Joined Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td>
                            <strong style="font-weight: 800;"><?= e($c['fullname']) ?></strong>
                        </td>
                        <td><?= e($c['email']) ?></td>
                        <td><?= e($c['phone']) ?></td>
                        <td><span style="font-weight: 800;"><?= $c['total_orders'] ?> orders</span></td>
                        <td><strong style="font-weight: 800; color: #10B981;"><?= format_price($c['total_spent']) ?></strong></td>
                        <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
