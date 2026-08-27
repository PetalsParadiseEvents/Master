<?php
/**
 * Petals Paradise Events API & Portal Config
 *
 * Credentials, Database, and API keys can be defined here or overridden via:
 * 1. api/secrets.php (gitignored local server file)
 * 2. Server Environment / Apache SetEnv / GitHub Secrets
 */

// 1. Default Configurations
$adminUser   = 'admin@petalsparadiseevents.com'; // Override this in api/secrets.php on your server
$adminPass   = 'ReplaceWithSecurePassword123!';  // Override this in api/secrets.php on your server
$adminSecret = 'ppe_admin_2026';
$notificationEmails = [
    'contact@petalsparadiseevents.com',
    'biragonimounika@gmail.com'
];
$apiKey = '';

// Database Defaults (Hostinger phpMyAdmin)
$dbHost  = 'localhost';
$dbName  = 'u704222898_ParadiseDB';
$dbUser  = '';
$dbPass  = '';
$dbTable = 'leads';

// 2. Load from gitignored secrets.php if present on server
$secretsFile = __DIR__ . '/secrets.php';
if (file_exists($secretsFile)) {
    $secrets = require $secretsFile;
    if (is_array($secrets)) {
        if (!empty($secrets['ADMIN_USER']))          $adminUser = $secrets['ADMIN_USER'];
        if (!empty($secrets['ADMIN_PASS']))          $adminPass = $secrets['ADMIN_PASS'];
        if (!empty($secrets['ADMIN_SECRET']))        $adminSecret = $secrets['ADMIN_SECRET'];
        if (!empty($secrets['NOTIFICATION_EMAILS']))  $notificationEmails = (array)$secrets['NOTIFICATION_EMAILS'];
        if (!empty($secrets['GEMINI_API_KEY']))      $apiKey = $secrets['GEMINI_API_KEY'];
        
        // Database secrets
        if (!empty($secrets['DB_HOST']))  $dbHost = $secrets['DB_HOST'];
        if (!empty($secrets['DB_NAME']))  $dbName = $secrets['DB_NAME'];
        if (!empty($secrets['DB_USER']))  $dbUser = $secrets['DB_USER'];
        if (!empty($secrets['DB_PASS']))  $dbPass = $secrets['DB_PASS'];
        if (!empty($secrets['DB_TABLE'])) $dbTable = $secrets['DB_TABLE'];
    }
}

// 3. Fallback check for Environment Variables
if (!empty($_SERVER['ADMIN_USER']))   $adminUser = $_SERVER['ADMIN_USER'];
if (!empty($_SERVER['ADMIN_PASS']))   $adminPass = $_SERVER['ADMIN_PASS'];
if (!empty($_SERVER['DB_USER']))     $dbUser = $_SERVER['DB_USER'];
if (!empty($_SERVER['DB_PASS']))     $dbPass = $_SERVER['DB_PASS'];

if (empty($apiKey)) {
    if (!empty($_SERVER['GEMINI_API_KEY'])) {
        $apiKey = $_SERVER['GEMINI_API_KEY'];
    } elseif (!empty($_ENV['GEMINI_API_KEY'])) {
        $apiKey = $_ENV['GEMINI_API_KEY'];
    } elseif (getenv('GEMINI_API_KEY')) {
        $apiKey = getenv('GEMINI_API_KEY');
    }
}

// Define Constants
if (!defined('GEMINI_API_KEY'))  define('GEMINI_API_KEY', $apiKey);
if (!defined('ADMIN_USER'))      define('ADMIN_USER', $adminUser);
if (!defined('ADMIN_PASS'))      define('ADMIN_PASS', $adminPass);
if (!defined('ADMIN_SECRET'))    define('ADMIN_SECRET', $adminSecret);
if (!defined('DB_HOST'))        define('DB_HOST', $dbHost);
if (!defined('DB_NAME'))        define('DB_NAME', $dbName);
if (!defined('DB_USER'))        define('DB_USER', $dbUser);
if (!defined('DB_PASS'))        define('DB_PASS', $dbPass);
if (!defined('DB_TABLE'))       define('DB_TABLE', $dbTable);

/**
 * PDO Database Helper Function
 * Connects to Hostinger MySQL Database and auto-creates the clean `leads` table.
 */
function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    
    $host   = DB_HOST;
    $dbname = DB_NAME;
    $user   = DB_USER;
    $pass   = DB_PASS;
    
    if (empty($dbname) || empty($user) || empty($pass)) return null;
    
    try {
        $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        initLeadsTable($pdo);
        initOrdersTable($pdo);
        return $pdo;
    } catch (Exception $e) {
        return null; // Gracefully fall back to leads.json if DB connection isn't configured yet
    }
}

/**
 * Initializes the clean `leads` table with Primary Keys
 */
function initLeadsTable($pdo) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `leads` (
            `id` VARCHAR(64) PRIMARY KEY,
            `date_added` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) DEFAULT '',
            `phone` VARCHAR(64) DEFAULT '',
            `event_type` VARCHAR(128) DEFAULT '',
            `service_tier` VARCHAR(128) DEFAULT '',
            `guest_count` VARCHAR(64) DEFAULT '',
            `budget` VARCHAR(64) DEFAULT '',
            `event_date` VARCHAR(64) DEFAULT '',
            `location` TEXT,
            `source` VARCHAR(128) DEFAULT '',
            `notes` TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql);
    } catch (Exception $e) {
        // Silently continue
    }
}

/**
 * Initializes the clean `orders` table with Primary Keys
 */
function initOrdersTable($pdo) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `orders` (
            `id` VARCHAR(64) PRIMARY KEY,
            `date_added` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(64) NOT NULL,
            `event_date` VARCHAR(64) NOT NULL,
            `venue_location` VARCHAR(255) DEFAULT '',
            `fulfillment_method` VARCHAR(64) NOT NULL,
            `delivery_address` TEXT,
            `pickup_date` VARCHAR(64) DEFAULT '',
            `pickup_time` VARCHAR(64) DEFAULT '',
            `return_date` VARCHAR(64) DEFAULT '',
            `return_time` VARCHAR(64) DEFAULT '',
            `delivery_date` VARCHAR(64) DEFAULT '',
            `delivery_time` VARCHAR(64) DEFAULT '',
            `collection_date` VARCHAR(64) DEFAULT '',
            `collection_time` VARCHAR(64) DEFAULT '',
            `special_requests` TEXT,
            `items` TEXT NOT NULL,
            `subtotal` DECIMAL(10,2) NOT NULL,
            `discount` DECIMAL(10,2) DEFAULT 0.00,
            `delivery_fee` DECIMAL(10,2) DEFAULT 0.00,
            `setup_fee` DECIMAL(10,2) DEFAULT 0.00,
            `total` DECIMAL(10,2) NOT NULL,
            `status` VARCHAR(64) DEFAULT 'Pending',
            `admin_notes` TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql);

        // Auto-migrate missing columns for existing tables
        @$pdo->exec("ALTER TABLE `orders` ADD COLUMN `delivery_fee` DECIMAL(10,2) DEFAULT 0.00");
        @$pdo->exec("ALTER TABLE `orders` ADD COLUMN `setup_fee` DECIMAL(10,2) DEFAULT 0.00");
        @$pdo->exec("ALTER TABLE `orders` ADD COLUMN `admin_notes` TEXT");
    } catch (Exception $e) {
        // Silently continue
    }
}
