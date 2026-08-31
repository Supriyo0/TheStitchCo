<?php
/**
 * Master Application Configuration
 * The Stitch Co. - E-Commerce Platform
 */

if (session_status() === PHP_SESSION_NONE) {
    // Secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting (Dev vs Production)
define('APP_ENV', 'development'); // change to 'production' for live deployment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'u609702858_TheStitchCo');
define('DB_USER', 'u609702858_thestitchco');
define('DB_PASS', 'Thestitch1');
define('DB_CHARSET', 'utf8mb4');

// Base Paths & URLs
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$basePath = rtrim($scriptName, '/');
if (strpos($basePath, '/admin') !== false) {
    $basePath = dirname($basePath);
}
if (strpos($basePath, '/api') !== false) {
    $basePath = dirname($basePath);
}
$basePath = rtrim($basePath, '/');

define('BASE_URL', $protocol . $host . ($basePath ? $basePath : '') . '/');
define('ADMIN_URL', BASE_URL . 'admin/');
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__) . '/');
if (!defined('UPLOAD_PATH')) define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', ROOT_PATH . 'uploads');
if (!defined('ASSETS_PATH')) define('ASSETS_PATH', ROOT_PATH . 'assets/');

// Upload Limits & Allowed Types
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('ALLOWED_IMAGE_EXTS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// Store Identity Defaults
if (!defined('STORE_NAME')) define('STORE_NAME', 'The Stitch Co.');
if (!defined('STORE_TAGLINE')) define('STORE_TAGLINE', 'Wear Your Vibe');
if (!defined('STORE_PHONE')) define('STORE_PHONE', '+91 98765 43210');
if (!defined('STORE_EMAIL')) define('STORE_EMAIL', 'support@thestitchco.shop');
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '&#8377;');

// CSRF Token Helper
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function get_csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}
