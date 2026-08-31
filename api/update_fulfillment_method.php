<?php
session_start();
/**
 * Fulfillment Method Update API (Pickup vs Delivery)
 * Petals Paradise Events
 */

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // 1. Admin Authentication
    $adminUser   = ADMIN_USER;
    $adminPass   = ADMIN_PASS;
    $adminSecret = ADMIN_SECRET;
    $cookieHash  = md5($adminUser . $adminPass . $adminSecret);

    $providedKey     = isset($_GET['key']) ? $_GET['key'] : '';
    $isBypassed      = (!empty($adminSecret) && $providedKey === $adminSecret);
    $isCookieValid   = (isset($_COOKIE['ppe_auth']) && $_COOKIE['ppe_auth'] === $cookieHash);
    $isAuthenticated = $isBypassed || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || $isCookieValid;

    if (!$isAuthenticated) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized admin access.']);
        exit(0);
    }

    // 2. Parse Input Data
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $orderId           = isset($data['order_id']) ? trim($data['order_id']) : '';
    $fulfillmentMethod = isset($data['fulfillment_method']) ? trim($data['fulfillment_method']) : 'Pickup';

    if (empty($orderId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID is required.']);
        exit(0);
    }

    // Standardize to Pickup or Delivery
    if (strcasecmp($fulfillmentMethod, 'Delivery') === 0) {
        $fulfillmentMethod = 'Delivery';
    } else {
        $fulfillmentMethod = 'Pickup';
    }

    $pdo = getDbConnection();
    if ($pdo) {
        ensureOrderColumnsExist($pdo);
        try {
            $updateStmt = $pdo->prepare("UPDATE `orders` SET `fulfillment_method` = :method WHERE `id` = :id");
            $updateStmt->execute([':method' => $fulfillmentMethod, ':id' => $orderId]);
        } catch (Exception $e) {
            // Ignored
        }
    }

    // Update in orders.json backup file
    $ordersFile = __DIR__ . '/orders.json';
    if (file_exists($ordersFile)) {
        $fileContent = file_get_contents($ordersFile);
        if (!empty($fileContent)) {
            $ordersList = json_decode($fileContent, true) ?: [];
            foreach ($ordersList as &$ord) {
                if (isset($ord['id']) && $ord['id'] === $orderId) {
                    $ord['fulfillment_method'] = $fulfillmentMethod;
                }
            }
            file_put_contents($ordersFile, json_encode($ordersList, JSON_PRETTY_PRINT));
        }
    }

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'fulfillment_method' => $fulfillmentMethod
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server diagnostic error: ' . $e->getMessage()]);
}
