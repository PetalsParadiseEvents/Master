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

$validStatuses = ['Pending', 'Confirmed', 'Order Picked Up', 'Out for Delivery', 'Delivered', 'Returned', 'Completed', 'Cancelled'];

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
$totalAmount   = isset($orderRecord['total']) ? number_format((float)$orderRecord['total'], 2) : '0.00';

// Format items list for email
$rawItems = $orderRecord['items'] ?? [];
$itemsArr = is_string($rawItems) ? json_decode($rawItems, true) : (is_array($rawItems) ? $rawItems : []);
$itemsHtml = "";
if (is_array($itemsArr) && !empty($itemsArr)) {
    foreach ($itemsArr as $it) {
        $title = htmlspecialchars($it['title'] ?? 'Rental Item');
        $qty   = (int)($it['quantity'] ?? 1);
        $price = isset($it['price']) ? number_format((float)$it['price'], 2) : '0.00';
        $itemsHtml .= "<li style='margin-bottom: 4px;'><strong>{$qty}x</strong> {$title} (@ \${$price} each)</li>";
    }
}

if ($notifyCustomer && !empty($customerEmail)) {
    $statusMessages = [
        'Confirmed' => "Great news! Your rental request for Order {$orderId} (Event Date: {$eventDate}) has been officially CONFIRMED and your items are reserved.",
        'Order Picked Up' => "Your rental items for Order {$orderId} have been PICKED UP from Petals Paradise Events! Please ensure items are returned in good condition on your scheduled return date.",
        'Out for Delivery' => "Your rental decor for Order {$orderId} is OUT FOR DELIVERY! Our logistics team is en route to your specified address.",
        'Delivered' => "Your rental decor for Order {$orderId} has been DELIVERED to your event address! Enjoy your celebration.",
        'Returned' => "We have received your RETURNED rental items for Order {$orderId}. Thank you for renting with Petals Paradise Events!",
        'Completed' => "Thank you for choosing Petals Paradise Events! Your rental order {$orderId} is now officially COMPLETED. We hope we helped make your celebration truly unforgettable! Please remember Petals Paradise Events for all your future milestone celebrations, birthdays, weddings, and party decor needs.",
        'Cancelled' => "Your rental request for Order {$orderId} has been CANCELLED as requested.",
        'Pending' => "Your rental request for Order {$orderId} is currently PENDING review."
    ];

    $statusMsgText = $statusMessages[$newStatus] ?? "Your rental order {$orderId} status has been updated to: {$newStatus}.";
    $trackUrl = "https://petalsparadiseevents.com/#track";

    $subject = "🌸 Order Status Update: {$orderId} is now {$newStatus}";
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <title>Order Status Update</title>
        <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333333; line-height: 1.6; background-color: #f9f9f9; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; }
            .header { text-align: center; border-bottom: 2px solid #d4af37; padding-bottom: 15px; margin-bottom: 20px; }
            .header h2 { color: #1a202c; margin: 0; font-size: 22px; }
            .status-badge { display: inline-block; background: rgba(212, 175, 55, 0.15); color: #d4af37; border: 1px solid #d4af37; font-weight: bold; padding: 6px 18px; border-radius: 20px; margin-top: 10px; font-size: 14px; }
            .box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 18px; border-radius: 8px; margin: 18px 0; }
            .track-btn { display: inline-block; background-color: #d4af37; color: #ffffff !important; padding: 14px 32px; text-decoration: none; border-radius: 30px; font-weight: bold; font-size: 16px; margin: 15px 0; text-align: center; }
            .footer { font-size: 13px; color: #718096; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 15px; text-align: center; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🌸 Petals Paradise Events</h2>
                <div class='status-badge'>Status: " . htmlspecialchars($newStatus) . "</div>
            </div>
            
            <p>Hi <strong>" . htmlspecialchars($customerName) . "</strong>,</p>
            <p>" . htmlspecialchars($statusMsgText) . "</p>
            
            <div style='text-align: center; margin: 25px 0;'>
                <a href='{$trackUrl}' class='track-btn' target='_blank'>📦 Track Your Order Live</a>
            </div>

            <div class='box'>
                <h3 style='margin-top:0; color:#2d3748; font-size:16px;'>📋 Order Details Summary</h3>
                <p style='margin: 5px 0;'><strong>Order Confirmation ID:</strong> <span style='font-family: monospace; font-weight: bold; color: #d4af37;'>" . htmlspecialchars($orderId) . "</span></p>
                <p style='margin: 5px 0;'><strong>Current Status:</strong> " . htmlspecialchars($newStatus) . "</p>
                <p style='margin: 5px 0;'><strong>Fulfillment Method:</strong> " . htmlspecialchars($fulfillment) . "</p>" .
                (!empty($eventDate) ? "<p style='margin: 5px 0;'><strong>Event Date:</strong> " . htmlspecialchars($eventDate) . "</p>" : "") .
                (floatval($orderRecord['discount'] ?? 0) > 0 ? "<p style='margin: 5px 0; color: #38a169;'><strong>Discount Applied:</strong> -\$" . number_format((float)$orderRecord['discount'], 2) . "</p>" : "") . "
                <p style='margin: 5px 0;'><strong>Total Estimate:</strong> \$" . htmlspecialchars($totalAmount) . "</p>
            </div>

            <div class='box'>
                <h3 style='margin-top:0; color:#2d3748; font-size:16px;'>🛍️ Requested Items</h3>
                <ul style='padding-left: 20px; margin-bottom: 0;'>" . 
                (!empty($itemsHtml) ? $itemsHtml : "<li>No item details listed.</li>") . "
                </ul>
            </div>

            <p style='font-size: 14px; color: #4a5568;'>Direct tracking link:<br>
            <a href='{$trackUrl}' style='color: #d4af37; text-decoration: underline; word-break: break-all;'>{$trackUrl}</a></p>

            <div class='footer'>
                <p><strong>Petals Paradise Events</strong><br>
                Crafting Unforgettable Moments in Loudoun County & DMV<br>
                Phone: +1 848-448-6993 | Website: <a href='https://petalsparadiseevents.com' style='color:#d4af37;'>petalsparadiseevents.com</a></p>
            </div>
        </div>
    </body>
    </html>";

    $headers = "MIME-Version: 1.0\r\n"
             . "Content-Type: text/html; charset=UTF-8\r\n"
             . "From: Petals Paradise Events <contact@petalsparadiseevents.com>\r\n"
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
