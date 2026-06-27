<?php
/**
 * Petals Paradise Events AI Chatbot Config
 *
 * The API key is loaded securely from the server environment.
 * It is set via SetEnv in .htaccess and read here automatically.
 *
 * On Hostinger, Apache SetEnv places the value in $_SERVER,
 * so we check $_SERVER, $_ENV, and getenv() for compatibility.
 *
 * Get a free API Key from: https://aistudio.google.com/
 */

// Check local gitignored secrets file (Easiest method for Hostinger File Manager)
$apiKey = '';
$secretsFile = __DIR__ . '/secrets.php';
if (file_exists($secretsFile)) {
    $secrets = require $secretsFile;
    if (is_array($secrets) && !empty($secrets['GEMINI_API_KEY'])) {
        $apiKey = $secrets['GEMINI_API_KEY'];
    }
}

// Fallback to environment variables if secrets.php is not present
if (empty($apiKey)) {
    if (!empty($_SERVER['GEMINI_API_KEY'])) {
        $apiKey = $_SERVER['GEMINI_API_KEY'];
    } elseif (!empty($_ENV['GEMINI_API_KEY'])) {
        $apiKey = $_ENV['GEMINI_API_KEY'];
    } elseif (getenv('GEMINI_API_KEY')) {
        $apiKey = getenv('GEMINI_API_KEY');
    } elseif (!empty($_SERVER['REDIRECT_GEMINI_API_KEY'])) {
        // Apache sometimes prefixes with REDIRECT_ after internal rewrites
        $apiKey = $_SERVER['REDIRECT_GEMINI_API_KEY'];
    }
}

define('GEMINI_API_KEY', $apiKey);
