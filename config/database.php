<?php
/**
 * Database Connection Singleton (PDO)
 * The Stitch Co.
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (APP_ENV === 'development') {
                    die("Database Connection Error: " . $e->getMessage());
                } else {
                    error_log("Database Connection Error: " . $e->getMessage());
                    die("A temporary database error occurred. Please try again later.");
                }
            }
        }

        return self::$instance;
    }
}

// Global helper for PDO
function get_db(): PDO {
    return Database::getConnection();
}
