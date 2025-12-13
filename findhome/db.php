<?php
// db.php - PDO connection helper for the project
// Edit these values to match your environment
$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_NAME = 'homebuilder_app';   // <- DB you created
$DB_USER = 'root';
$DB_PASS = 'aditya29';                 // <- set to 'aditya29' if that's your Laragon root password

// DSN and PDO options
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $DB_HOST, $DB_PORT, $DB_NAME);
$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                  // use native prepares when available
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $pdoOptions);
} catch (PDOException $e) {
    // Log the real error for debugging, but show generic message to the browser
    if (function_exists('error_log')) {
        error_log('Database connection failed: ' . $e->getMessage());
    }
    // For development you can echo the message, but it's safer to show a generic one
    http_response_code(500);
    exit('Database connection failed. Check server logs for details.');
}

/**
 * Optional helper: function to get the PDO instance
 * Use require_once 'db.php'; then call getPDO()
 */
if (!function_exists('getPDO')) {
    function getPDO(): PDO {
        global $pdo;
        return $pdo;
    }
}
