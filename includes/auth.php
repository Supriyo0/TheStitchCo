<?php
/**
 * Authentication & Role Authorization Middleware
 * The Stitch Co.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function current_user() {
    if (!empty($_SESSION['user_id'])) {
        try {
            $db = get_db();
            $stmt = $db->prepare("SELECT id, fullname, email, phone, role, avatar FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $u = $stmt->fetch();
            if ($u) {
                return $u;
            }
        } catch (Exception $e) {
            // fallback
        }
        return [
            'id' => $_SESSION['user_id'],
            'fullname' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? '',
            'phone' => $_SESSION['user_phone'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'customer',
            'avatar' => $_SESSION['user_avatar'] ?? null
        ];
    }
    return null;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return is_logged_in() && in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin']);
}

function is_super_admin(): bool {
    return is_logged_in() && ($_SESSION['user_role'] ?? '') === 'super_admin';
}

function require_login($redirect = 'login.php') {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: " . BASE_URL . $redirect);
        exit;
    }
}

function require_admin() {
    if (!is_admin()) {
        header("Location: " . ADMIN_URL . "login.php");
        exit;
    }
}

function require_super_admin() {
    if (!is_super_admin()) {
        $_SESSION['flash_error'] = "Super Admin privileges required.";
        header("Location: " . ADMIN_URL . "index.php");
        exit;
    }
}

function login_user($user) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['fullname'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_avatar'] = $user['avatar'] ?? null;
    
    // Merge session cart to user cart if any
    if (function_exists('merge_session_cart_to_user')) {
        merge_session_cart_to_user($user['id']);
    }
}

function logout_user() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
