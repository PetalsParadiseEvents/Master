<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config.php';

echo "=== PETALS PARADISE CREDENTIALS CHECK ===\n\n";
echo "Config ADMIN_USER:   " . ADMIN_USER . "\n";
echo "Config ADMIN_PASS:   " . ADMIN_PASS . "\n";
echo "Config ADMIN_SECRET: " . ADMIN_SECRET . "\n\n";

echo "Is secrets.php loaded? " . (file_exists(__DIR__ . '/secrets.php') ? "Yes" : "No") . "\n";
if (file_exists(__DIR__ . '/secrets.php')) {
    $s = require __DIR__ . '/secrets.php';
    echo "secrets ADMIN_USER:  " . ($s['ADMIN_USER'] ?? 'Not set in secrets.php') . "\n";
    echo "secrets ADMIN_PASS:  " . ($s['ADMIN_PASS'] ?? 'Not set in secrets.php') . "\n";
}
echo "\n=== END CHECK ===\n";
