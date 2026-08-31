<?php
/**
 * Lightweight Store Status API
 * The Stitch Co.
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$isMaintenance = (int)get_setting('maintenance_mode', '0') === 1;

echo json_encode([
    'status' => 'ok',
    'maintenance' => $isMaintenance,
    'timestamp' => time()
]);
