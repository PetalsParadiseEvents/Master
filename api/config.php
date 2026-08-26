<?php
/**
 * Petals Paradise Events API & Portal Config
 *
 * Credentials and API keys can be defined here or overridden via:
 * 1. api/secrets.php (gitignored local server file)
 * 2. Server Environment / Apache SetEnv / GitHub Secrets
 */

// 1. Default Configurations
$adminUser   = 'biragonimounika@gmail.com';
$adminPass   = 'Mounika@65';
$adminSecret = 'ppe_admin_2026';
$notificationEmails = [
    'contact@petalsparadiseevents.com',
    'biragonimounika@gmail.com'
];
$apiKey = '';

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
    }
}

// 3. Fallback check for Environment Variables (Apache SetEnv / GitHub Secrets / Server Env)
if (!empty($_SERVER['ADMIN_USER']))       $adminUser = $_SERVER['ADMIN_USER'];
if (!empty($_SERVER['ADMIN_PASS']))       $adminPass = $_SERVER['ADMIN_PASS'];
if (!empty($_SERVER['ADMIN_SECRET']))     $adminSecret = $_SERVER['ADMIN_SECRET'];

if (empty($apiKey)) {
    if (!empty($_SERVER['GEMINI_API_KEY'])) {
        $apiKey = $_SERVER['GEMINI_API_KEY'];
    } elseif (!empty($_ENV['GEMINI_API_KEY'])) {
        $apiKey = $_ENV['GEMINI_API_KEY'];
    } elseif (getenv('GEMINI_API_KEY')) {
        $apiKey = getenv('GEMINI_API_KEY');
    } elseif (!empty($_SERVER['REDIRECT_GEMINI_API_KEY'])) {
        $apiKey = $_SERVER['REDIRECT_GEMINI_API_KEY'];
    }
}

// Define Constants for global script usage
if (!defined('GEMINI_API_KEY'))      define('GEMINI_API_KEY', $apiKey);
if (!defined('ADMIN_USER'))          define('ADMIN_USER', $adminUser);
if (!defined('ADMIN_PASS'))          define('ADMIN_PASS', $adminPass);
if (!defined('ADMIN_SECRET'))        define('ADMIN_SECRET', $adminSecret);
