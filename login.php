<?php
/**
 * Ultra-Premium Customer Authentication Page (Login / Register)
 * The Stitch Co. — A Fashion Brand by MJ Company
 * Matches Exact Mobile/Desktop App Blueprint
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header("Location: account.php");
    exit;
}

$db = get_db();
$error = '';
$success = '';
$action = $_GET['action'] ?? 'login';

if (!empty($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect'];
}

// Handle Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);
            $redirect = $_SESSION['redirect_after_login'] ?? 'account.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: " . $redirect);
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// Handle Register POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_register'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
        $error = 'Please fill out all required fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email address already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $db->prepare("INSERT INTO users (fullname, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'customer')");
            $ins->execute([$fullname, $email, $phone, $hash]);

            $newUserId = (int)$db->lastInsertId();
            login_user(['id' => $newUserId, 'fullname' => $fullname, 'email' => $email, 'role' => 'customer']);

            // Send Welcome Email via Google SMTP
            require_once __DIR__ . '/includes/mailer.php';
            try {
                send_welcome_email(['fullname' => $fullname, 'email' => $email]);
            } catch (Exception $mailEx) {
                error_log("Welcome email error: " . $mailEx->getMessage());
            }

            $redirect = $_SESSION['redirect_after_login'] ?? 'account.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: " . $redirect);
            exit;
        }
    }
}

// Handle Forgot Password POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_forgot'])) {
    $email = trim($_POST['email'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');

    if (empty($email) || empty($new_password)) {
        $error = 'Please provide both your registered email and new password.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } else {
        $stmt = $db->prepare("SELECT id, fullname, email FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $user['id']]);
            $success = 'Your password has been successfully reset! You can now log in below.';
            $action = 'login';
        } else {
            $error = 'No active account found with that email address.';
        }
    }
}

if ($action === 'register') {
    $pageTitle = 'Create Account | ' . STORE_NAME;
} elseif ($action === 'forgot') {
    $pageTitle = 'Reset Password | ' . STORE_NAME;
} else {
    $pageTitle = 'Welcome Back | ' . STORE_NAME;
}
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Premium Auth Styles matching Shared Blueprints */
.auth-wrapper {
    min-height: 75vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1.25rem 5rem;
}

.auth-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 24px;
    padding: 2.8rem 2.2rem;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.07), 0 0 1px 1px rgba(0, 0, 0, 0.02);
    position: relative;
    overflow: hidden;
}

.auth-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #1E3A8A, #2563EB, #3B82F6);
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-logo-img {
    width: 68px;
    height: 68px;
    border-radius: 14px;
    margin: 0 auto 0.9rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border: 1.5px solid #F1F5F9;
}

.auth-title {
    font-family: var(--font-heading);
    font-size: 1.65rem;
    font-weight: 900;
    letter-spacing: -0.5px;
    color: #0F172A;
    margin-bottom: 0.2rem;
}

.auth-subtitle {
    font-size: 0.85rem;
    color: #64748B;
    font-weight: 500;
}

.auth-parent-tag {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 1px;
    color: #1E3A8A;
    background: #EEF2FF;
    padding: 0.2rem 0.6rem;
    border-radius: 4px;
    margin-top: 0.4rem;
}

.input-field-group {
    margin-bottom: 1.25rem;
}

.input-field-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.82rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 0.4rem;
}

.input-control-box {
    position: relative;
    display: flex;
    align-items: center;
}

.auth-input {
    width: 100%;
    padding: 0.85rem 1rem;
    font-size: 0.92rem;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    background: #FAFAFA;
    transition: all 0.2s ease;
    color: #0F172A;
    font-family: var(--font-main);
}

.auth-input:focus {
    outline: none;
    border-color: #2563EB;
    background: #FFFFFF;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
}

.input-icon-right {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: #64748B;
    cursor: pointer;
    font-size: 1.1rem;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
}

.input-icon-right:hover {
    color: #1E3A8A;
}

.btn-auth-primary {
    width: 100%;
    background: #1E3A8A;
    color: #FFFFFF;
    padding: 0.95rem;
    font-size: 0.95rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 4px 14px rgba(30, 58, 138, 0.25);
    margin-top: 0.6rem;
}

.btn-auth-primary:hover {
    background: #1D4ED8;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(30, 58, 138, 0.35);
}

.social-divider {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 1.6rem 0;
    color: #94A3B8;
    font-size: 0.78rem;
    font-weight: 600;
}

.social-divider::before, .social-divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #E2E8F0;
}

.social-divider span {
    padding: 0 0.8rem;
}

.social-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.8rem;
    margin-bottom: 1.5rem;
}

.social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    background: #FFFFFF;
    font-weight: 700;
    font-size: 0.85rem;
    color: #1E293B;
    cursor: pointer;
    transition: all 0.2s;
}

.social-btn:hover {
    background: #F8FAFC;
    border-color: #CBD5E1;
}

.auth-footer-link {
    text-align: center;
    font-size: 0.85rem;
    color: #64748B;
}

.auth-footer-link a {
    color: #1E3A8A;
    font-weight: 800;
    text-decoration: none;
}

.auth-footer-link a:hover {
    text-decoration: underline;
}
</style>

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Brand Header matching Blueprint -->
        <div class="auth-header">
            <img src="assets/images/logo.jpg" alt="The Stitch Co." class="auth-logo-img">
            <h1 class="auth-title">
                <?php 
                if ($action === 'register') echo 'Create Account';
                elseif ($action === 'forgot') echo 'Reset Password';
                else echo 'Welcome Back!';
                ?>
            </h1>
            <p class="auth-subtitle">
                <?php 
                if ($action === 'register') echo 'Join us and start shopping premium streetwear.';
                elseif ($action === 'forgot') echo 'Enter your email address and new password below.';
                else echo 'Login to continue to your account.';
                ?>
            </p>
            <div class="auth-parent-tag">THE STITCH CO. • BY MJ COMPANY</div>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: #FEF2F2; border: 1px solid #EF4444; color: #DC2626; padding: 0.8rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 700; margin-bottom: 1.4rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>⚠️</span>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div style="background: #F0FDF4; border: 1px solid #22C55E; color: #15803D; padding: 0.8rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 700; margin-bottom: 1.4rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>✅</span>
                <span><?= e($success) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($action === 'register'): ?>
            <!-- Sign Up Form -->
            <form action="login.php?action=register" method="POST">
                <div class="input-field-group">
                    <label class="input-field-label">Full Name *</label>
                    <div class="input-control-box">
                        <input type="text" name="fullname" placeholder="Enter your full name" required class="auth-input">
                    </div>
                </div>

                <div class="input-field-group">
                    <label class="input-field-label">Email Address *</label>
                    <div class="input-control-box">
                        <input type="email" name="email" placeholder="name@example.com" required class="auth-input">
                    </div>
                </div>

                <div class="input-field-group">
                    <label class="input-field-label">Phone Number *</label>
                    <div class="input-control-box">
                        <input type="text" name="phone" placeholder="+91 98765 43210" required class="auth-input">
                    </div>
                </div>

                <div class="input-field-group">
                    <label class="input-field-label">Password *</label>
                    <div class="input-control-box">
                        <input type="password" id="reg-password" name="password" placeholder="At least 6 characters" required class="auth-input" style="padding-right: 42px;">
                        <button type="button" class="input-icon-right" onclick="togglePasswordVisibility('reg-password', this)" title="Show / Hide Password">
                            👁️
                        </button>
                    </div>
                </div>

                <div style="margin-bottom: 1.2rem; font-size: 0.78rem; color: #64748B;">
                    <label style="display: flex; align-items: flex-start; gap: 0.4rem; cursor: pointer;">
                        <input type="checkbox" required checked style="margin-top: 2px;">
                        <span>I agree to the <a href="#" style="color: #1E3A8A; font-weight: 700;">Terms & Conditions</a> and Privacy Policy.</span>
                    </label>
                </div>

                <button type="submit" name="do_register" class="btn-auth-primary">
                    SIGN UP &rarr;
                </button>
            </form>

            <div class="auth-footer-link" style="margin-top: 1.5rem;">
                Already have an account? <a href="login.php">Login</a>
            </div>

        <?php elseif ($action === 'forgot'): ?>
            <!-- Forgot / Reset Password Form -->
            <form action="login.php?action=forgot" method="POST">
                <div class="input-field-group">
                    <label class="input-field-label">Registered Email Address *</label>
                    <div class="input-control-box">
                        <input type="email" name="email" placeholder="name@example.com" required class="auth-input">
                    </div>
                </div>

                <div class="input-field-group">
                    <label class="input-field-label">New Password *</label>
                    <div class="input-control-box">
                        <input type="password" id="forgot-password" name="new_password" placeholder="Enter new password (min 6 characters)" required class="auth-input" style="padding-right: 42px;">
                        <button type="button" class="input-icon-right" onclick="togglePasswordVisibility('forgot-password', this)" title="Show / Hide Password">
                            👁️
                        </button>
                    </div>
                </div>

                <button type="submit" name="do_forgot" class="btn-auth-primary">
                    SET NEW PASSWORD &rarr;
                </button>
            </form>

            <div class="auth-footer-link" style="margin-top: 1.5rem;">
                Remembered your password? <a href="login.php">Back to Login</a>
            </div>

        <?php else: ?>
            <!-- Login Form -->
            <form action="login.php" method="POST">
                <div class="input-field-group">
                    <label class="input-field-label">Email or Phone *</label>
                    <div class="input-control-box">
                        <input type="email" name="email" placeholder="name@example.com" required class="auth-input">
                    </div>
                </div>

                <div class="input-field-group">
                    <div class="input-field-label">
                        <span>Password *</span>
                        <a href="login.php?action=forgot" style="font-size: 0.78rem; color: #1E3A8A; font-weight: 700; text-decoration: none;">Forgot Password?</a>
                    </div>
                    <div class="input-control-box">
                        <input type="password" id="login-password" name="password" placeholder="Enter password" required class="auth-input" style="padding-right: 42px;">
                        <button type="button" class="input-icon-right" onclick="togglePasswordVisibility('login-password', this)" title="Show / Hide Password">
                            👁️
                        </button>
                    </div>
                </div>

                <button type="submit" name="do_login" class="btn-auth-primary">
                    LOGIN &rarr;
                </button>
            </form>

            <div class="social-divider">
                <span>or continue with</span>
            </div>

            <div class="social-grid">
                <button type="button" class="social-btn">
                    <span>🌐</span>
                    <span>Google</span>
                </button>
                <button type="button" class="social-btn">
                    <span style="color: #1877F2; font-weight: 900;">f</span>
                    <span>Facebook</span>
                </button>
            </div>

            <div class="auth-footer-link">
                Don't have an account? <a href="login.php?action=register">Sign Up</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function togglePasswordVisibility(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁️';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
