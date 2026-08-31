<?php
/**
 * Customer Order Lookup API
 * Petals Paradise Events
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once __DIR__ . '/config.php';

    $query = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';
    if (empty($query)) {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        $query = isset($data['q']) ? trim($data['q']) : '';
    }

    if (empty($query) || strlen($query) < 3) {
        echo json_encode([
            'found' => false,
            'message' => 'Please enter at least 3 characters of your Order Number or Phone Number.'
        ]);
        exit(0);
    }

    $cleanDigits = preg_replace('/[^0-9]/', '', $query);
    $orders = [];

    // 1. Try MySQL Database
    if (function_exists('getDbConnection')) {
        $pdo = getDbConnection();
        if ($pdo) {
            try {
                $sql = "SELECT `id`, `date_added`, `name`, `email`, `phone`, `event_date`, `fulfillment_method`, `delivery_address`, `items`, `subtotal`, `discount`, `delivery_fee`, `setup_fee`, `total`, `status`, `admin_notes` 
                        FROM `orders` 
                        WHERE `id` LIKE :q 
                           OR `phone` LIKE :q";
                
                $params = [':q' => '%' . $query . '%'];

                if (!empty($cleanDigits) && strlen($cleanDigits) >= 3) {
                    $sql .= " OR REPLACE(REPLACE(REPLACE(REPLACE(`phone`, '-', ''), ' ', ''), '(', ''), ')', '') LIKE :digits";
                    $params[':digits'] = '%' . $cleanDigits . '%';
                }

                $sql .= " ORDER BY `date_added` DESC LIMIT 10";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Exception $e) {
                $orders = [];
            }
        }
    }

    // 2. Fallback to orders.json backup file
    if (empty($orders)) {
        $ordersFile = __DIR__ . '/orders.json';
        if (file_exists($ordersFile)) {
            $fileContent = file_get_contents($ordersFile);
            $allOrders = json_decode($fileContent, true) ?: [];
            $qLower = strtolower($query);

            foreach ($allOrders as $ord) {
                $ordId = strtolower($ord['id'] ?? '');
                $ordPhone = preg_replace('/[^0-9]/', '', $ord['phone'] ?? '');

                $match = false;
                if (!empty($qLower) && strpos($ordId, $qLower) !== false) {
                    $match = true;
                } elseif (!empty($cleanDigits) && strlen($cleanDigits) >= 3 && !empty($ordPhone) && strpos($ordPhone, $cleanDigits) !== false) {
                    $match = true;
                }

                if ($match) {
                    $orders[] = [
                        'id'                 => $ord['id'] ?? '',
                        'date_added'         => $ord['date_added'] ?? '',
                        'name'               => $ord['name'] ?? '',
                        'email'              => $ord['email'] ?? '',
                        'phone'              => $ord['phone'] ?? '',
                        'event_date'         => $ord['event_date'] ?? '',
                        'fulfillment_method' => $ord['fulfillment_method'] ?? 'Pickup',
                        'delivery_address'   => $ord['delivery_address'] ?? '',
                        'items'              => $ord['items'] ?? [],
                        'subtotal'           => $ord['subtotal'] ?? 0.00,
                        'discount'           => $ord['discount'] ?? 0.00,
                        'delivery_fee'       => $ord['delivery_fee'] ?? 0.00,
                        'setup_fee'          => $ord['setup_fee'] ?? 0.00,
                        'total'              => $ord['total'] ?? 0.00,
                        'status'             => $ord['status'] ?? 'Pending',
                        'admin_notes'        => $ord['admin_notes'] ?? ''
                    ];
                }
            }
        }
    }

    if (empty($orders)) {
        echo json_encode([
            'found' => false,
            'message' => 'No orders matching your search query were found.'
        ]);
        exit(0);
    }

    foreach ($orders as &$o) {
        if (is_string($o['items'])) {
            $o['items'] = json_decode($o['items'], true) ?: [];
        }

        if (!empty($o['date_added'])) {
            try {
                $rawDate = $o['date_added'];
                // Check if already Eastern Time or UTC
                $dt = new DateTime($rawDate, new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone('America/New_York'));
                $o['date_added_formatted'] = $dt->format('M d, Y h:i A') . ' EDT';
            } catch (Exception $e) {
                $o['date_added_formatted'] = $o['date_added'];
            }
        }
    }

    echo json_encode([
        'found' => true,
        'orders' => $orders
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'found' => false,
        'error' => $e->getMessage(),
        'message' => 'Server diagnostic: ' . $e->getMessage()
    ]);
}
