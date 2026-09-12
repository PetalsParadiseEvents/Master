<?php
/**
 * Dynamic Square Payment URL Generator API
 * Petals Paradise Events
 */
ob_start();
session_start();

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (ob_get_length()) ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'PHP Fatal Error: ' . $error['message'] . ' in ' . basename($error['file']) . ' on line ' . $error['line']
        ], JSON_UNESCAPED_SLASHES);
    }
});

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (ob_get_length()) ob_clean();
    exit;
}

try {
    require_once __DIR__ . '/config.php';

    $orderId  = !empty($_GET['order_id']) ? trim($_GET['order_id']) : (!empty($_POST['order_id']) ? trim($_POST['order_id']) : '');
    $totalVal = !empty($_GET['total']) ? floatval($_GET['total']) : (!empty($_POST['total']) ? floatval($_POST['total']) : 0);

    if (empty($orderId) || $totalVal <= 0) {
        if (ob_get_length()) ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Order ID and valid total are required.']);
        exit;
    }

    $squarePayUrl = generateSquarePaymentUrl($orderId, $totalVal);

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success'     => true,
        'order_id'    => $orderId,
        'final_total' => $totalVal,
        'payment_url' => $squarePayUrl
    ]);

} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
