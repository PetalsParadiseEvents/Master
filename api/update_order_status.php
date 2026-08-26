<?php
session_start();
/**
 * Order Status Update & Customer Email Notification API
 * Petals Paradise Events
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 1. Authenticate admin session or cookie
$adminUser = ADMIN_USER;
$adminPass = ADMIN_PASS;
$adminSecret = ADMIN_SECRET;
$cookieHash = md5($adminUser . $adminPass . $adminSecret);

$providedKey = isset($_GET['key']) ? $_GET['key'] : '';
$isBypassed = (!empty($adminSecret) && $providedKey === $adminSecret);
$isCookieValid = (isset($_COOKIE['ppe_auth']) && $_COOKIE['ppe_auth'] === $cookieHash);
$isAuthenticated = $isBypassed || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || $isCookieValid;

if (!$isAuthenticated) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized admin access.']);
    exit(0);
}

// 2. Parse input JSON payload
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

$orderId = isset($data['order_id']) ? trim($data['order_id']) : '';
$newStatus = isset($data['status']) ? trim($data['status']) : '';
$notifyCustomer = isset($data['notify']) ? (bool)$data['notify'] : true;

$validStatuses = ['Pending', 'Confirmed', 'Order Picked Up', 'Out for Delivery', 'Delivered', 'Returned', 'Cancelled'];

if (empty($orderId) || !in_array($newStatus, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID or status value.']);
    exit(0);
}

// 3. Update Status in Database & JSON File
$pdo = getDbConnection();
$orderRecord = null;
$dbUpdated = false;

if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM `orders` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $orderId]);
        $orderRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        $updateStmt = $pdo->prepare("UPDATE `orders` SET `status` = :status WHERE `id` = :id");
        $updateStmt->execute([':status' => $newStatus, ':id' => $orderId]);
        $dbUpdated = true;
    } catch (Exception $e) {
        // Fallback to JSON update
    }
}

// Update in orders.json backup file as well
$ordersFile = __DIR__ . '/orders.json';
if (file_exists($ordersFile)) {
    $fileContent = file_get_contents($ordersFile);
    if (!empty($fileContent)) {
        $ordersList = json_decode($fileContent, true) ?: [];
        foreach ($ordersList as &$ord) {
            if (isset($ord['id']) && $ord['id'] === $orderId) {
                $ord['status'] = $newStatus;
                if (!$orderRecord) {
                    $orderRecord = $ord;
                }
            }
        }
        file_put_contents($ordersFile, json_encode($ordersList, JSON_PRETTY_PRINT));
    }
}

// 4. Send Custom Status Email Notification to Customer
$emailSent = false;
$customerEmail = $orderRecord['email'] ?? '';
$customerName  = $orderRecord['name'] ?? 'Valued Customer';
$eventDate     = $orderRecord['event_date'] ?? '';
$fulfillment   = $orderRecord['fulfillment_method'] ?? 'Pickup';

if ($notifyCustomer && !empty($customerEmail)) {
    $statusMessages = [
        'Confirmed' => "Great news! Your rental request for Order {$orderId} (Event Date: {$eventDate}) has been officially CONFIRMED and your items are reserved.",
        'Order Picked Up' => "Your rental items for Order {$orderId} have been PICKED UP from Petals Paradise Events! Please ensure items are returned in good condition on your scheduled return date.",
        'Out for Delivery' => "Your rental decor for Order {$orderId} is OUT FOR DELIVERY! Our logistics team is en route to your specified address.",
        'Delivered' => "Your rental decor for Order {$orderId} has been DELIVERED to your event address! Enjoy your celebration.",
        'Returned' => "We have received your RETURNED rental items for Order {$orderId}. Thank you for renting with Petals Paradise Events!",
        'Cancelled' => "Your rental request for Order {$orderId} has been CANCELLED as requested.",
        'Pending' => "Your rental request for Order {$orderId} is currently PENDING review."
    ];

    $statusMsgText = $statusMessages[$newStatus] ?? "Your rental order {$orderId} status has been updated to: {$newStatus}.";

    $subject = "🌸 Order Status Update: {$orderId} is now {$newStatus}";
    $message = "Hi {$customerName},\n\n"
             . "{$statusMsgText}\n\n"
             . "ORDER DETAILS SUMMARY:\n"
             . "----------------------------------------\n"
             . "Order Confirmation ID: {$orderId}\n"
             . "Current Status: {$newStatus}\n"
             . "Fulfillment Method: {$fulfillment}\n"
             . (!empty($eventDate) ? "Event Date: {$eventDate}\n" : "")
             . "----------------------------------------\n\n"
             . "You can track your order status anytime on our website or reply directly to this email if you have questions.\n\n"
             . "Warm regards,\n"
             . "Petals Paradise Events Team\n"
             . "Phone: +1 848-448-6993\n"
             . "Website: https://petalsparadiseevents.com\n";

    $headers = "From: Petals Paradise Events <contact@petalsparadiseevents.com>\r\n"
             . "Reply-To: contact@petalsparadiseevents.com\r\n"
             . "X-Mailer: PHP/" . phpversion();

    $emailSent = @mail($customerEmail, $subject, $message, $headers);
}

echo json_encode([
    'success' => true,
    'order_id' => $orderId,
    'status' => $newStatus,
    'email_sent' => $emailSent
]);
