<?php
/**
 * Admin Multi-Administrator Management
 * Super Admin Role Controls
 * The Stitch Co.
 */

$adminTitle = 'Administrator Accounts';
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

// Handle Create Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    if (!is_super_admin()) {
        $err = 'Only Super Admins can add new administrators.';
    } else {
        $name = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'admin';

        if (!empty($name) && !empty($email) && !empty($password)) {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $err = 'Email already in use.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $db->prepare("INSERT INTO users (fullname, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)");
                $ins->execute([$name, $email, $phone, $hash, $role]);
                $msg = 'Administrator account created successfully.';
            }
        }
    }
}

$admins = $db->query("SELECT * FROM users WHERE role IN ('admin', 'super_admin') ORDER BY id ASC")->fetchAll();
?>

<?php if ($msg): ?>
    <div style="background: #ECFDF5; border: 1px solid #10B981; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">✓ <?= e($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div style="background: #FEF2F2; border: 1px solid #EF4444; color: #DC2626; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">⚠️ <?= e($err) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">
    <!-- Add Admin Form -->
    <div class="admin-card" style="height: fit-content;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">+ Create Admin Account</h3>
        </div>
        <div style="padding: 1.5rem;">
            <form action="admins.php" method="POST">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Full Name *</label>
                    <input type="text" name="fullname" required placeholder="e.g. Operations Manager" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Email Address *</label>
                    <input type="email" name="email" required placeholder="manager@thestitchco.shop" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Phone</label>
                    <input type="text" name="phone" placeholder="+91 98765 43210" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Password *</label>
                    <input type="password" name="password" required placeholder="Secure password" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.3rem;">Role Level</label>
                    <select name="role" style="width: 100%; padding: 0.65rem; border: 1.5px solid var(--admin-border); border-radius: 6px; background: #fff;">
                        <option value="admin">Store Admin (Orders & Products)</option>
                        <option value="super_admin">Super Admin (Full Access)</option>
                    </select>
                </div>
                <button type="submit" name="add_admin" style="width: 100%; padding: 0.75rem; background: var(--admin-primary); color: #fff; border: none; border-radius: 6px; font-weight: 800; cursor: pointer;">
                    CREATE ADMIN
                </button>
            </form>
        </div>
    </div>

    <!-- Admin List Table -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Authorized Staff & Admins</h3>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $ad): ?>
                        <tr>
                            <td><strong><?= e($ad['fullname']) ?></strong></td>
                            <td><?= e($ad['email']) ?></td>
                            <td>
                                <span style="background: <?= $ad['role'] === 'super_admin' ? '#EEF2FF' : '#F3F4F6' ?>; color: <?= $ad['role'] === 'super_admin' ? '#4338CA' : '#1F2937' ?>; font-size: 0.75rem; font-weight: 800; padding: 0.2rem 0.5rem; border-radius: 4px;">
                                    <?= ucfirst(str_replace('_', ' ', $ad['role'])) ?>
                                </span>
                            </td>
                            <td><span class="status-pill status-delivered">Active</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
