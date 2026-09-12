<?php
/**
 * Diagnostic & Testing Script for Petals Paradise Events Payment System
 * Access via browser: https://petalsparadiseevents.com/api/test_payment_api.php?key=ppe_admin_2026
 */
ob_start();
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

$providedKey = isset($_GET['key']) ? $_GET['key'] : '';
$adminSecret = ADMIN_SECRET;
$isAuthenticated = (!empty($adminSecret) && $providedKey === $adminSecret) || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);

if (!$isAuthenticated) {
    http_response_code(401);
    echo "<h2>401 Unauthorized</h2><p>Please provide valid admin key: <code>?key=ppe_admin_2026</code></p>";
    exit;
}

echo "<h1>🌸 Petals Paradise Payment System Diagnostic</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s T') . "</p>";

// 1. Secrets File Test
$secretsFile = __DIR__ . '/secrets.php';
echo "<h3>1. Secrets File Verification</h3>";
if (file_exists($secretsFile)) {
    echo "✅ <code>api/secrets.php</code> exists on host server.<br>";
} else {
    echo "⚠️ <code>api/secrets.php</code> DOES NOT exist! Using config defaults.<br>";
}

// 2. Square Constants Test
echo "<h3>2. Square API Tokens & Credentials</h3>";
$sqToken = defined('SQUARE_ACCESS_TOKEN') ? SQUARE_ACCESS_TOKEN : '';
$sqLoc   = defined('SQUARE_LOCATION_ID') ? SQUARE_LOCATION_ID : '';

if (!empty($sqToken)) {
    $maskedToken = substr($sqToken, 0, 6) . '...' . substr($sqToken, -4);
    echo "✅ <strong>SQUARE_ACCESS_TOKEN</strong> detected: <code>{$maskedToken}</code> (Length: " . strlen($sqToken) . ")<br>";
} else {
    echo "❌ <strong>SQUARE_ACCESS_TOKEN</strong> is EMPTY! Online dynamic links will fall back to default prefilled static links.<br>";
}

echo "ℹ️ <strong>SQUARE_LOCATION_ID</strong>: <code>{$sqLoc}</code><br>";

// 3. Test Square API Connection
if (!empty($sqToken)) {
    echo "<h3>3. Live Square API Test (GET /v2/locations)</h3>";
    $ch = curl_init('https://connect.squareup.com/v2/locations');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $sqToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    echo "HTTP Status Code: <strong>{$httpCode}</strong><br>";
    if ($httpCode === 200) {
        echo "✅ Square API Connection SUCCESSFUL!<br>";
        $data = json_decode($resp, true);
        if (!empty($data['locations'])) {
            echo "Found Locations: <ul>";
            foreach ($data['locations'] as $loc) {
                echo "<li>Location ID: <code>{$loc['id']}</code> | Name: <strong>" . htmlspecialchars($loc['name'] ?? 'N/A') . "</strong></li>";
            }
            echo "</ul>";
        }
    } else {
        echo "❌ Square API Error! HTTP Code: {$httpCode}<br>";
        if ($err) echo "cURL Error: " . htmlspecialchars($err) . "<br>";
        echo "Raw Response: <pre>" . htmlspecialchars($resp) . "</pre>";
    }

    // 4. Test Payment Link Generation Helper Function
    echo "<h3>4. Test Payment Link Creation Helper</h3>";
    $testUrl = generateSquarePaymentUrl('TEST-ORDER-101', 206.51);
    echo "Generated Payment URL for $206.51:<br>";
    echo "<a href='" . htmlspecialchars($testUrl) . "' target='_blank'>" . htmlspecialchars($testUrl) . "</a><br>";
}

// 5. Database Connection Test
echo "<h3>5. MySQL Database Connection</h3>";
$pdo = getDbConnection();
if ($pdo) {
    echo "✅ Database connection SUCCESSFUL!<br>";
} else {
    echo "⚠️ Database connection failed or not configured (Using JSON fallback files).<br>";
}

echo "<hr><p style='color: #666;'>Diagnostic completed.</p>";
