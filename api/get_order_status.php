<?php
/**
 * Customer Order Lookup API
 * Petals Paradise Events
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$query = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';
if (empty($query)) {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    $query = isset($data['q']) ? trim($data['q']) : '';
}

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter an Order ID, Email, or Phone Number.']);
    exit(0);
}

$orders = [];
$pdo = getDbConnection();

if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT `id`, `date_added`, `name`, `email`, `phone`, `event_date`, `fulfillment_method`, `delivery_address`, `items`, `total`, `status` FROM `orders` WHERE `id` LIKE :q OR `phone` LIKE :q OR `email` LIKE :q ORDER BY `date_added` DESC LIMIT 5");
        $stmt->execute([':q' => '%' . $query . '%']);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        $orders = [];
    }
}

if (empty($orders)) {
    $ordersFile = __DIR__ . '/orders.json';
    if (file_exists($ordersFile)) {
        $fileContent = file_get_contents($ordersFile);
        $allOrders = json_decode($fileContent, true) ?: [];
        foreach ($allOrders as $ord) {
            $qLower = strtolower($query);
            if (
                (isset($ord['id']) && strpos(strtolower($ord['id']), $qLower) !== false) ||
                (isset($ord['phone']) && strpos(strtolower($ord['phone']), $qLower) !== false) ||
                (isset($ord['email']) && strpos(strtolower($ord['email']), $qLower) !== false)
            ) {
                $orders[] = [
                    'id' => $ord['id'] ?? '',
                    'date_added' => $ord['date_added'] ?? '',
                    'name' => $ord['name'] ?? '',
                    'email' => $ord['email'] ?? '',
                    'phone' => $ord['phone'] ?? '',
                    'event_date' => $ord['event_date'] ?? '',
                    'fulfillment_method' => $ord['fulfillment_method'] ?? 'Pickup',
                    'delivery_address' => $ord['delivery_address'] ?? '',
                    'items' => $ord['items'] ?? [],
                    'total' => $ord['total'] ?? 0.00,
                    'status' => $ord['status'] ?? 'Pending'
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

// Sanitize email and phone for public response Privacy
foreach ($orders as &$o) {
    if (is_string($o['items'])) {
        $o['items'] = json_decode($o['items'], true);
    }
}

echo json_encode([
    'found' => true,
    'orders' => $orders
]);
