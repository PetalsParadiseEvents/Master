<?php
/**
 * Petals Paradise Events AI Chatbot Debugger
 * Visit: https://petalsparadiseevents.com/api/debug.php
 * DELETE THIS FILE AFTER DEBUGGING.
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== PETALS PARADISE AI DEBUGGER ===\n\n";

// 1. Check PHP Version
echo "PHP Version: " . PHP_VERSION . "\n\n";

// 2. Check config.php
$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    echo "✅ api/config.php exists.\n";
    $configContent = file_get_contents($configFile);
    if (strpos($configContent, 'secrets.php') !== false) {
        echo "✅ api/config.php is the UPDATED version (includes secrets.php check).\n";
    } else {
        echo "❌ api/config.php is the OLD version! Please upload the new config.php from GitHub.\n";
    }
} else {
    echo "❌ api/config.php does not exist!\n";
}
echo "\n";

// 3. Check secrets.php
$secretsFile = __DIR__ . '/secrets.php';
if (file_exists($secretsFile)) {
    echo "✅ api/secrets.php exists.\n";
    
    // Attempt to load it
    try {
        $secrets = require $secretsFile;
        if (is_array($secrets)) {
            echo "✅ api/secrets.php returns a valid array.\n";
            if (!empty($secrets['GEMINI_API_KEY'])) {
                $key = $secrets['GEMINI_API_KEY'];
                $len = strlen($key);
                $preview = substr($key, 0, 6) . '...' . substr($key, -4);
                echo "✅ GEMINI_API_KEY is defined in secrets.php.\n";
                echo "   Key preview: $preview (length: $len)\n";
            } else {
                echo "❌ GEMINI_API_KEY is empty or missing in secrets.php array!\n";
            }
        } else {
            echo "❌ api/secrets.php does not return an array! Make sure it starts with '<?php return ['...\n";
        }
    } catch (\Throwable $e) {
        echo "❌ Error loading api/secrets.php: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ api/secrets.php does not exist in: $secretsFile\n";
    echo "   (Make sure the filename is exactly 'secrets.php' and is inside the 'api' folder)\n";
}
echo "\n";

// 4. Check active GEMINI_API_KEY constant
require_once $configFile;
if (defined('GEMINI_API_KEY')) {
    $activeKey = GEMINI_API_KEY;
    if (empty($activeKey)) {
        echo "❌ Constant GEMINI_API_KEY is defined but is EMPTY.\n";
    } else {
        $preview = substr($activeKey, 0, 6) . '...' . substr($activeKey, -4);
        echo "✅ Active GEMINI_API_KEY constant: $preview\n";
    }
} else {
    echo "❌ Constant GEMINI_API_KEY is NOT defined.\n";
}
// 5. Check Admin Credentials
echo "Active ADMIN_USER: " . ADMIN_USER . "\n";
echo "Active ADMIN_PASS: " . ADMIN_PASS . "\n";
echo "Active ADMIN_SECRET: " . ADMIN_SECRET . "\n\n";

echo "\n=== END OF DEBUG ===\n";
