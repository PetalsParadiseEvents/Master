<?php
ob_start();
session_start();
/**
 * Send Online Payment Link & QR Code Email API
 * Petals Paradise Events
 */

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
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (ob_get_length()) ob_clean();
    exit;
}

try {
    require_once __DIR__ . '/config.php';

    // 1. Parse Input Data
    $rawInput = file_get_contents('php://input');
    $data     = json_decode($rawInput, true) ?: [];

    // Admin Authentication
    $adminUser   = ADMIN_USER;
    $adminPass   = ADMIN_PASS;
    $adminSecret = ADMIN_SECRET;
    $cookieHash  = md5($adminUser . $adminPass . $adminSecret);

    $providedKey     = isset($data['key']) ? trim($data['key']) : (isset($_GET['key']) ? $_GET['key'] : (isset($_POST['key']) ? $_POST['key'] : ''));
    $isBypassed      = (!empty($adminSecret) && $providedKey === $adminSecret);
    $isCookieValid   = (isset($_COOKIE['ppe_auth']) && $_COOKIE['ppe_auth'] === $cookieHash);
    $isAuthenticated = $isBypassed || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || $isCookieValid;

    if (!$isAuthenticated) {
        if (ob_get_length()) ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized admin access.']);
        exit;
    }

    $orderId    = !empty($data['order_id']) ? trim($data['order_id']) : (!empty($_POST['order_id']) ? trim($_POST['order_id']) : (!empty($_GET['order_id']) ? trim($_GET['order_id']) : ''));
    $customNote = !empty($data['notes']) ? trim($data['notes']) : (!empty($_POST['notes']) ? trim($_POST['notes']) : (!empty($_GET['notes']) ? trim($_GET['notes']) : ''));
    $emailParam = !empty($data['email']) ? trim($data['email']) : (!empty($_POST['email']) ? trim($_POST['email']) : (!empty($_GET['email']) ? trim($_GET['email']) : ''));

    if (empty($orderId)) {
        if (ob_get_length()) ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Order ID is required.']);
        exit;
    }

    $pdo = getDbConnection();
    $orderRecord = null;

    // Load Order from MySQL Database
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `orders` WHERE `id` = :id LIMIT 1");
            $stmt->execute([':id' => $orderId]);
            $orderRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Fall through to JSON
        }
    }

    // Load Order from orders.json fallback
    $ordersFile = __DIR__ . '/orders.json';
    if (!$orderRecord && file_exists($ordersFile)) {
        $fileContent = file_get_contents($ordersFile);
        if (!empty($fileContent)) {
            $ordersList = json_decode($fileContent, true) ?: [];
            foreach ($ordersList as $ord) {
                if (isset($ord['id']) && $ord['id'] === $orderId) {
                    $orderRecord = $ord;
                    break;
                }
            }
        }
    }

    if (!$orderRecord) {
        if (ob_get_length()) ob_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => "Order {$orderId} not found."]);
        exit;
    }

    $customerEmail = !empty($emailParam) ? $emailParam : ($orderRecord['email'] ?? '');
    $customerName  = $orderRecord['name'] ?? 'Valued Customer';
    $eventDate     = $orderRecord['event_date'] ?? '';
    $fulfillment   = $orderRecord['fulfillment_method'] ?? 'Pickup';
    $subtotalVal  = floatval($orderRecord['subtotal'] ?? 0);
    $discountVal  = floatval($orderRecord['discount'] ?? 0);
    $deliveryVal  = floatval($orderRecord['delivery_fee'] ?? 0);
    $setupVal     = floatval($orderRecord['setup_fee'] ?? 0);

    $baseTotal    = max(0, $subtotalVal - $discountVal + $deliveryVal + $setupVal);
    if ($baseTotal <= 0 && floatval($orderRecord['total'] ?? 0) > 0) {
        $baseTotal = floatval($orderRecord['total']);
    }

    $onlineTaxVal  = round($baseTotal * 0.059, 2);
    $finalTotalVal = round($baseTotal + $onlineTaxVal, 2);

    $subtotalFmt   = number_format($subtotalVal, 2);
    $discountFmt   = number_format($discountVal, 2);
    $baseTotalFmt  = number_format($baseTotal, 2);
    $onlineTaxFmt  = number_format($onlineTaxVal, 2);
    $finalTotalFmt = number_format($finalTotalVal, 2);
    $promoCode     = $orderRecord['promo_code'] ?? '';

    if (empty($customerEmail)) {
        if (ob_get_length()) ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "No valid email address found for Order {$orderId}."]);
        exit;
    }

    // Format items list
    $rawItems  = $orderRecord['items'] ?? [];
    $itemsArr  = is_string($rawItems) ? json_decode($rawItems, true) : (is_array($rawItems) ? $rawItems : []);
    $itemsHtml = "";
    if (is_array($itemsArr) && !empty($itemsArr)) {
        foreach ($itemsArr as $it) {
            $title = htmlspecialchars($it['title'] ?? 'Rental Item');
            $qty   = (int)($it['quantity'] ?? 1);
            $price = isset($it['price']) ? number_format((float)$it['price'], 2) : '0.00';
            $itemsHtml .= "<li style='margin-bottom: 4px;'><strong>{$qty}x</strong> {$title} (@ \${$price} each)</li>";
        }
    }

    // Square link with pre-filled amount & QR code URLs
    // Square link with pre-filled amount & QR code URLs
    $squarePayUrl = function_exists('generateSquarePaymentUrl') ? generateSquarePaymentUrl($orderId, $finalTotalVal) : ((defined('SQUARE_PAYMENT_URL') ? SQUARE_PAYMENT_URL : 'https://square.link/u/xV2eBBtG') . '?src=embed&amount=' . urlencode($finalTotalFmt) . '&total=' . urlencode($finalTotalFmt) . '&price=' . urlencode($finalTotalFmt));
    $squareQrUrl   = 'https://petalsparadiseevents.com/square-qr-code.png';
    $trackUrl      = "https://petalsparadiseevents.com/#track";

    // Notes block if provided
    $notesHtml = "";
    if (!empty($customNote)) {
        $notesHtml = "
        <div style='background: #fff8e6; border: 1px solid #ffe58f; padding: 15px; border-radius: 8px; margin: 18px 0;'>
            <h4 style='margin-top: 0; color: #d48806; font-size: 15px;'>💬 Note from Petals Paradise Events:</h4>
            <p style='margin: 0; color: #595959; font-size: 14px; white-space: pre-wrap;'>" . htmlspecialchars($customNote) . "</p>
        </div>";
    }

    $subject = "💳 Payment Request (\${$finalTotalFmt}) - Order {$orderId} - Petals Paradise Events";

    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <title>Online Payment Request</title>
        <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333333; line-height: 1.6; background-color: #f9f9f9; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; }
            .header { text-align: center; border-bottom: 2px solid #d4af37; padding-bottom: 15px; margin-bottom: 20px; }
            .header h2 { color: #1a202c; margin: 0; font-size: 22px; }
            .status-badge { display: inline-block; background: rgba(0, 106, 255, 0.1); color: #006aff; border: 1px solid #006aff; font-weight: bold; padding: 6px 18px; border-radius: 20px; margin-top: 10px; font-size: 14px; }
            .box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 18px; border-radius: 8px; margin: 18px 0; }
            .pay-btn { display: inline-block; font-size: 18px; line-height: 48px; height: 48px; color: #ffffff !important; min-width: 212px; background-color: #006aff; text-align: center; box-shadow: 0 0 0 1px rgba(0,0,0,.1) inset; border-radius: 6px; text-decoration: none; font-weight: bold; padding: 0 24px; }
            .track-btn { display: inline-block; background-color: #d4af37; color: #ffffff !important; padding: 12px 28px; text-decoration: none; border-radius: 30px; font-weight: bold; font-size: 14px; margin: 15px 0; text-align: center; }
            .qr-card { background: #ffffff; border: 2px solid #006aff; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
            .price-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
            .price-table td { padding: 5px 0; font-size: 14px; }
            .price-table tr.total-row { border-top: 2px solid #006aff; font-weight: bold; font-size: 16px; }
            .footer { font-size: 13px; color: #718096; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 15px; text-align: center; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🌸 Petals Paradise Events</h2>
                <div class='status-badge'>💳 Online Payment Option</div>
            </div>
            
            <p>Hi <strong>" . htmlspecialchars($customerName) . "</strong>,</p>
            <p>Here is your secure online payment link for Order <strong>" . htmlspecialchars($orderId) . "</strong>. You can complete your payment using Credit Card, Debit Card, or Apple Pay via Square.</p>
            
            {$notesHtml}

            <!-- SQUARE PAYMENT BUTTON & AMOUNT CALLOUT -->
            <div style='text-align: center; margin: 25px 0;'>
                <a href='{$squarePayUrl}' target='_blank' class='pay-btn'>Pay now (\${$finalTotalFmt})</a>
                <div style='margin: 14px auto 0 auto; max-width: 400px; font-size: 13px; color: #1e293b; font-weight: bold; background: #f0f9ff; border: 1px solid #bae6fd; padding: 10px 16px; border-radius: 8px;'>
                    📌 Amount to Enter on Square: <span style='color: #006aff; font-size: 16px; font-weight: 800;'>\${$finalTotalFmt}</span>
                    <div style='font-weight: normal; font-size: 12px; color: #64748b; margin-top: 3px;'>When Square opens, please enter <strong>\${$finalTotalFmt}</strong> in the 'Enter amount' box.</div>
                </div>
            </div>

            <!-- QR CODE CARD -->
            <div class='qr-card'>
                <h4 style='margin: 0 0 10px 0; color: #1a202c; font-size: 16px;'>📲 Scan QR Code to Pay on Your Mobile Device</h4>
                <img src='{$squareQrUrl}' alt='Scan QR Code to Pay via Square' width='180' height='180' style='border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px; background: #ffffff;' />
                <p style='margin: 12px 0 0 0; font-size: 13px; color: #475569;'>Open your smartphone camera and scan the QR code above to pay securely via Square.</p>
            </div>

            <div class='box'>
                <h3 style='margin-top:0; color:#2d3748; font-size:16px;'>📋 Financial Breakdown & Final Total</h3>
                <table class='price-table'>
                    <tr>
                        <td>Order Subtotal / Base Total:</td>
                        <td style='text-align: right; font-weight: bold;'>\${$baseTotalFmt}</td>
                    </tr>" .
                    ($discountVal > 0 || !empty($promoCode) ? "
                    <tr style='color: #38a169;'>
                        <td>Discount Applied" . (!empty($promoCode) ? " ({$promoCode})" : "") . ":</td>
                        <td style='text-align: right; font-weight: bold;'>-\${$discountFmt}</td>
                    </tr>" : "") . "
                    <tr style='color: #006aff;'>
                        <td>VA Sales Tax (5.9%):</td>
                        <td style='text-align: right; font-weight: bold;'>+\${$onlineTaxFmt}</td>
                    </tr>
                    <tr class='total-row'>
                        <td style='padding-top: 10px; color: #1a202c;'>Final Amount Due:</td>
                        <td style='text-align: right; padding-top: 10px; color: #006aff;'>\${$finalTotalFmt}</td>
                    </tr>
                </table>
            </div>

            <div class='box'>
                <h3 style='margin-top:0; color:#2d3748; font-size:16px;'>🛍️ Order Items</h3>
                <ul style='padding-left: 20px; margin-bottom: 0;'>" . 
                (!empty($itemsHtml) ? $itemsHtml : "<li>No item details listed.</li>") . "
                </ul>
            </div>

            <div style='text-align: center; margin: 25px 0;'>
                <a href='{$trackUrl}' class='track-btn' target='_blank'>📦 Track Your Order Live</a>
            </div>

            <div class='footer'>
                <p><strong>Petals Paradise Events</strong><br>
                Crafting Unforgettable Moments in Loudoun County & DMV<br>
                Phone: +1 848-448-6993 | Email: <a href='mailto:contact@petalsparadiseevents.com' style='color:#d4af37;'>contact@petalsparadiseevents.com</a><br>
                Website: <a href='https://petalsparadiseevents.com' style='color:#d4af37;'>petalsparadiseevents.com</a></p>
            </div>
        </div>
    </body>
    </html>";

    $headers = "MIME-Version: 1.0\r\n"
             . "Content-Type: text/html; charset=UTF-8\r\n"
             . "From: Petals Paradise Events <contact@petalsparadiseevents.com>\r\n"
             . "Reply-To: contact@petalsparadiseevents.com\r\n"
             . "X-Mailer: PHP/" . phpversion();

    // 1. Send to Customer
    $emailSent = @mail($customerEmail, $subject, $message, $headers);

    // 2. Also send copy to Admin Notification Emails
    $notifList = json_decode(NOTIFICATION_EMAILS, true) ?: ['contact@petalsparadiseevents.com', 'biragonimounika@gmail.com'];
    foreach ($notifList as $nEmail) {
        if (!empty($nEmail) && strtolower($nEmail) !== strtolower($customerEmail)) {
            @mail($nEmail, "[Admin Copy] " . $subject, $message, $headers);
        }
    }

    // Update payment_method in database if it was Unpaid
    $updatedStatus = 'Square Pay (Online Link Sent)';
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE `orders` SET `payment_method` = :pm WHERE `id` = :id");
            $stmt->execute([':pm' => $updatedStatus, ':id' => $orderId]);
        } catch (Exception $e) {
            // Ignored
        }
    }

    if (file_exists($ordersFile)) {
        $fileContent = file_get_contents($ordersFile);
        if (!empty($fileContent)) {
            $ordersList = json_decode($fileContent, true) ?: [];
            foreach ($ordersList as &$ord) {
                if (isset($ord['id']) && $ord['id'] === $orderId) {
                    $ord['payment_method'] = $updatedStatus;
                }
            }
            file_put_contents($ordersFile, json_encode($ordersList, JSON_PRETTY_PRINT));
        }
    }

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success'        => true,
        'order_id'       => $orderId,
        'customer_email' => $customerEmail,
        'email_sent'     => $emailSent,
        'payment_url'    => $squarePayUrl,
        'qr_url'         => $squareQrUrl,
        'message'        => "Online payment link & QR Code email sent successfully to {$customerEmail}."
    ]);

} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Server error sending payment link: ' . $e->getMessage()]);
}
