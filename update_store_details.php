<?php
/**
 * Official Store Contact & Admin Account Setup
 * The Stitch Co.
 */
require_once __DIR__ . '/config/database.php';

$db = get_db();

// 1. Update store settings with exact user values
$settings = [
    'store_phone' => '7063179581',
    'store_whatsapp' => '7047051581',
    'store_email' => 'thestitchco.official@gmail.com',
    'gstin' => '19GWPPD6451K1ZV',
    'store_address' => 'Sisir Building, Jetty Ghat Bus Stopage, Fraserganj, South 24 Parganas, West Bengal, India, 743357'
];

foreach ($settings as $k => $v) {
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_val) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val)");
    $stmt->execute([$k, $v]);
}

// 2. Setup Super Admin Account: sd029900@gmail.com / 123456
$hash = password_hash('123456', PASSWORD_DEFAULT);

$check = $db->prepare("SELECT id FROM users WHERE email = 'sd029900@gmail.com'");
$check->execute();
$existing = $check->fetch();

if ($existing) {
    $up = $db->prepare("UPDATE users SET password_hash = ?, phone = '7063179581', role = 'super_admin', status = 'active' WHERE id = ?");
    $up->execute([$hash, $existing['id']]);
} else {
    $ins = $db->prepare("INSERT INTO users (fullname, email, phone, password_hash, role, status) VALUES ('Super Admin', 'sd029900@gmail.com', '7063179581', ?, 'super_admin', 'active')");
    $ins->execute([$hash]);
}

echo "✓ Settings and Admin user [sd029900@gmail.com] created with password [123456] successfully!\n";
