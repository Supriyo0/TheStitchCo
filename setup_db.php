<?php
/**
 * One-click Database Setup and Seeder for The Stitch Co.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = get_db();
    
    // Read database.sql
    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("database.sql file not found!");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remove CREATE DATABASE and USE statements so it uses the hostinger DB directly
    $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS.*?;/i', '', $sql);
    $sql = preg_replace('/USE `.*?`;/i', '', $sql);
    
    // Execute full database schema
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec($sql);
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    // Run Seeder
    if (file_exists(__DIR__ . '/database_seed.php')) {
        require_once __DIR__ . '/database_seed.php';
    }
    
    echo "<!DOCTYPE html><html><head><title>Database Setup Complete</title><style>body{font-family:sans-serif;background:#0d1117;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}.card{background:#161b22;padding:30px;border-radius:12px;text-align:center;box-shadow:0 8px 24px rgba(0,0,0,0.5);border:1px solid #30363d;max-width:500px;}h1{color:#2ea043;margin-top:0;}a{display:inline-block;margin-top:20px;padding:12px 24px;background:#e50914;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;}</style></head><body><div class='card'><h1>🎉 Setup Completed Successfully!</h1><p>All database tables (products, categories, hero banners, coupons, admin accounts, settings) have been created and populated.</p><a href='index.php'>Go to Website Homepage &rarr;</a></div></body></html>";
    
} catch (Exception $e) {
    echo "<!DOCTYPE html><html><head><title>Database Setup Error</title><style>body{font-family:sans-serif;background:#0d1117;color:#fff;padding:40px;}.error{background:#f8514922;border:1px solid #f85149;color:#f85149;padding:20px;border-radius:8px;}</style></head><body><div class='error'><h2>Database Setup Error</h2><p>" . htmlspecialchars($e->getMessage()) . "</p></div></body></html>";
}
