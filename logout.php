<?php
/**
 * Logout Handler
 * The Stitch Co.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();
header("Location: index.php");
exit;
