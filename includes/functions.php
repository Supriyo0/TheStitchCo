<?php
/**
 * Global Utility Functions
 * The Stitch Co.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Safe HTML Output Escaping
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Currency Formatter
function format_price($amount): string {
    $sym = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : "\xE2\x82\xB9";
    return $sym . number_format((float)$amount, 2);
}

function format_price_no_decimals($amount): string {
    $sym = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : "\xE2\x82\xB9";
    return $sym . number_format((float)$amount, 0);
}

// Fetch dynamic store setting from DB with caching
function get_setting(string $key, string $default = ''): string {
    static $settingsCache = null;
    if ($settingsCache === null) {
        $db = get_db();
        try {
            $stmt = $db->query("SELECT setting_key, setting_val FROM settings");
            $settingsCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $settingsCache = [];
        }
    }
    return $settingsCache[$key] ?? $default;
}

// Update or insert store setting
function update_setting(string $key, string $val): bool {
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_val) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val)");
    return $stmt->execute([$key, $val]);
}

// Generate Secure Unique File Name
function generate_safe_filename(string $originalName, string $prefix = ''): string {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return ($prefix ? $prefix . '_' : '') . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
}

/**
 * Upload Image to ImgBB Cloud API
 * Fallback to local storage if API key not set or network fails
 */
function upload_to_imgbb($fileArray, $customApiKey = null) {
    if (!isset($fileArray['tmp_name']) || empty($fileArray['tmp_name']) || !file_exists($fileArray['tmp_name'])) {
        return ['success' => false, 'message' => 'No valid file uploaded.'];
    }

    $apiKey = !empty($customApiKey) ? $customApiKey : get_setting('imgbb_api_key', 'e3a1f81d1ef8fca02d1373e34b171bf7');

    if (!empty($apiKey)) {
        $fileData = @file_get_contents($fileArray['tmp_name']);
        if ($fileData !== false) {
            $base64 = base64_encode($fileData);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.imgbb.com/1/upload?key=' . urlencode($apiKey));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['image' => $base64]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && !empty($response)) {
                $json = json_decode($response, true);
                if (!empty($json['data']['url'])) {
                    return [
                        'success' => true,
                        'url' => $json['data']['url'],
                        'display_url' => $json['data']['display_url'] ?? $json['data']['url'],
                        'thumb_url' => $json['data']['thumb']['url'] ?? $json['data']['url'],
                        'relative_url' => $json['data']['url'],
                        'is_cloud' => true
                    ];
                }
            }
        }
    }

    // Fallback to local upload
    return handle_image_upload($fileArray, 'products', 'img');
}

/**
 * Handle Image Upload (Local Storage with full fallback & path safety)
 */
function handle_image_upload($fileArray, $targetSubfolder = 'products', $prefix = 'img') {
    if (!isset($fileArray['error']) || is_array($fileArray['error'])) {
        return ['success' => false, 'message' => 'Invalid file parameters.'];
    }

    if ($fileArray['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error code: ' . $fileArray['error']];
    }

    if ($fileArray['size'] > 15 * 1024 * 1024) { // 15 MB limit
        return ['success' => false, 'message' => 'File size exceeds 15MB limit.'];
    }

    // Safe extension detection
    $origExt = strtolower(pathinfo($fileArray['name'] ?? '', PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif', 'avif'];
    
    if (!in_array($origExt, $allowedExts)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, WEBP, SVG, GIF.'];
    }

    $baseUploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : (defined('UPLOAD_PATH') ? rtrim(UPLOAD_PATH, '/') : dirname(__DIR__) . '/uploads');
    $uploadDir = $baseUploadDir . '/' . trim($targetSubfolder, '/');
    
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $origExt;
    $targetPath = $uploadDir . '/' . $filename;

    if (@move_uploaded_file($fileArray['tmp_name'], $targetPath)) {
        @chmod($targetPath, 0644);
        $relPath = 'uploads/' . trim($targetSubfolder, '/') . '/' . $filename;
        return [
            'success' => true,
            'filename' => $filename,
            'relative_url' => $relPath,
            'url' => $relPath,
            'is_cloud' => false
        ];
    }

    return ['success' => false, 'message' => 'Failed to write file to ' . $targetPath];
}

// Generate UPI Deep Link for Intent Payments
function generate_upi_intent_link(string $upiId, string $merchantName, float $amount, string $orderNumber): string {
    $pa = urlencode($upiId);
    $pn = urlencode($merchantName);
    $am = number_format($amount, 2, '.', '');
    $tr = urlencode($orderNumber);
    $tn = urlencode("Payment for Order " . $orderNumber);
    $cu = 'INR';

    return "upi://pay?pa={$pa}&pn={$pn}&tr={$tr}&tn={$tn}&am={$am}&cu={$cu}";
}

// Flash Messages
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Log Admin Audit Trail
function log_admin_activity(int $adminId, string $adminName, string $action, string $details = ''): void {
    try {
        $db = get_db();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO admin_audit_logs (admin_id, admin_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$adminId, $adminName, $action, $details, $ip]);
    } catch (Exception $e) {
        error_log("Failed to log admin activity: " . $e->getMessage());
    }
}

// In-app Notifications
function create_notification(?int $userId, string $title, string $message, string $type = 'order', string $link = ''): void {
    try {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $message, $type, $link]);
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
    }
}
