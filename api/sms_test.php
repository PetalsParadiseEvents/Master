<?php
/**
 * SMS / Push Notification Diagnostic Test
 * Petals Paradise Events
 *
 * ⚠️ DELETE this file from the server after testing!
 * Access: https://petalsparadiseevents.com/api/sms_test.php?key=ppe_sms_test_2026
 */

$accessKey = isset($_GET['key']) ? $_GET['key'] : '';
if ($accessKey !== 'ppe_sms_test_2026') {
    http_response_code(403);
    die('Forbidden.');
}

require_once __DIR__ . '/config.php';

$results = [];

// ─── Test 1: email-to-SMS via T-Mobile gateway ────────────────────────────
$gateways = ['8484486993@tmomail.net', '6692248611@tmomail.net'];
foreach ($gateways as $gw) {
    $headers = "From: Petals Paradise <contact@petalsparadiseevents.com>\r\n"
             . "X-Mailer: PHP/" . phpversion();
    $sent = @mail($gw, '', "PPE TEST SMS from server - " . date('H:i:s'), $headers);
    $results["email_sms_$gw"] = $sent ? '✅ mail() accepted' : '❌ mail() rejected';
}

// ─── Test 2: ntfy.sh push notification ────────────────────────────────────
// ntfy.sh is free, no signup needed, instant push to phone app
$ntfyTopic  = defined('NTFY_TOPIC') ? NTFY_TOPIC : '';
$ntfyResult = 'ℹ️ NTFY_TOPIC not configured yet';
if (!empty($ntfyTopic)) {
    $ch = curl_init("https://ntfy.sh/{$ntfyTopic}");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_POSTFIELDS     => "PPE TEST — Push notification working! " . date('H:i:s'),
        CURLOPT_HTTPHEADER     => [
            'Title: PPE Test Notification',
            'Priority: high',
            'Tags: bell',
        ],
    ]);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $ntfyResult = ($httpCode === 200) ? '✅ ntfy push sent!' : "❌ ntfy failed (HTTP {$httpCode}): {$resp}";
}
$results['ntfy_push'] = $ntfyResult;

// ─── Test 3: curl availability (needed for ntfy) ──────────────────────────
$results['curl_available'] = function_exists('curl_init') ? '✅ cURL available' : '❌ cURL not available';

// ─── Test 4: mail() function available ────────────────────────────────────
$results['mail_available'] = function_exists('mail') ? '✅ mail() available' : '❌ mail() not available';

// Output
header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font-family:monospace; font-size:15px; padding:20px;">';
echo "<strong>PPE SMS / Push Notification Diagnostic</strong>\n";
echo str_repeat('─', 50) . "\n\n";
foreach ($results as $test => $result) {
    echo str_pad($test, 35) . " → $result\n";
}
echo "\n<em>Delete this file from the server after testing!</em>";
echo '</pre>';
