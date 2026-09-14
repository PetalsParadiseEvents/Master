<?php
/**
 * Create Manual Phone / Direct Quote Order API
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (ob_get_length()) ob_clean();
    exit;
}

try {
    require_once __DIR__ . '/config.php';

    // 1. Admin Authentication
    $adminUser   = ADMIN_USER;
    $adminPass   = ADMIN_PASS;
    $adminSecret = ADMIN_SECRET;
    $cookieHash  = md5($adminUser . $adminPass . $adminSecret);

    $rawInput        = file_get_contents('php://input');
    $data            = json_decode($rawInput, true) ?: [];
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

    // 2. Parse & Validate Customer Details
    $name  = isset($data['name']) ? trim($data['name']) : '';
    $email = isset($data['email']) ? trim($data['email']) : '';
    $phone = isset($data['phone']) ? trim($data['phone']) : '';

    if (empty($name)) {
        if (ob_get_length()) ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Customer Name is required.']);
        exit;
    }

    // 3. Event & Logistics Details
    $eventDate         = isset($data['event_date']) ? trim($data['event_date']) : '';
    $venueLocation     = isset($data['venue_location']) ? trim($data['venue_location']) : '';
    $fulfillmentMethod = (isset($data['fulfillment_method']) && strcasecmp($data['fulfillment_method'], 'Delivery') === 0) ? 'Delivery' : 'Pickup';
    $deliveryAddress   = isset($data['delivery_address']) ? trim($data['delivery_address']) : '';
    $pickupDate        = isset($data['pickup_date']) ? trim($data['pickup_date']) : '';
    $pickupTime        = isset($data['pickup_time']) ? trim($data['pickup_time']) : '';
    $returnDate        = isset($data['return_date']) ? trim($data['return_date']) : '';
    $returnTime        = isset($data['return_time']) ? trim($data['return_time']) : '';
    $deliveryDate      = isset($data['delivery_date']) ? trim($data['delivery_date']) : '';
    $deliveryTime      = isset($data['delivery_time']) ? trim($data['delivery_time']) : '';
    $collectionDate    = isset($data['collection_date']) ? trim($data['collection_date']) : '';
    $collectionTime    = isset($data['collection_time']) ? trim($data['collection_time']) : '';
    $specialRequests   = isset($data['special_requests']) ? trim($data['special_requests']) : '';
    $adminNotes        = isset($data['admin_notes']) ? trim($data['admin_notes']) : '';
    $notify            = isset($data['notify']) ? (bool)$data['notify'] : true;

    // 4. Financials & Items
    $itemsInput = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
    $itemsList  = [];
    $subtotal   = 0.00;

    foreach ($itemsInput as $it) {
        $itemTitle = isset($it['title']) ? trim($it['title']) : '';
        $itemQty   = isset($it['quantity']) ? max(1, (int)$it['quantity']) : 1;
        $itemPrice = isset($it['price']) ? max(0, floatval($it['price'])) : 0.00;
        
        if (!empty($itemTitle)) {
            $itemsList[] = [
                'title'    => $itemTitle,
                'quantity' => $itemQty,
                'price'    => $itemPrice
            ];
            $subtotal += ($itemQty * $itemPrice);
        }
    }

    $discount    = isset($data['discount']) ? max(0, floatval($data['discount'])) : 0.00;
    $promoCode   = isset($data['promo_code']) ? trim(filter_var($data['promo_code'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
    $deliveryFee = isset($data['delivery_fee']) ? max(0, floatval($data['delivery_fee'])) : 0.00;
    $setupFee    = isset($data['setup_fee']) ? max(0, floatval($data['setup_fee'])) : 0.00;

    $baseTotal     = max(0, $subtotal - $discount + $deliveryFee + $setupFee);
    $onlineTaxVal  = round($baseTotal * 0.059, 2);
    $finalTotalVal = round($baseTotal + $onlineTaxVal, 2);

    // Generate Custom Order ID
    $orderId = 'PPE-' . date('Ymd') . '-' . rand(100, 999);

    $orderRecord = [
        'id'                 => $orderId,
        'date_added'         => date('Y-m-d H:i:s'),
        'name'               => $name,
        'email'              => $email,
        'phone'              => $phone,
        'event_date'         => $eventDate,
        'venue_location'     => $venueLocation,
        'fulfillment_method' => $fulfillmentMethod,
        'delivery_address'   => $deliveryAddress,
        'pickup_date'        => $pickupDate,
        'pickup_time'        => $pickupTime,
        'return_date'        => $returnDate,
        'return_time'        => $returnTime,
        'delivery_date'      => $deliveryDate,
        'delivery_time'      => $deliveryTime,
        'collection_date'    => $collectionDate,
        'collection_time'    => $collectionTime,
        'special_requests'   => $specialRequests,
        'items'              => json_encode($itemsList),
        'subtotal'           => $subtotal,
        'discount'           => $discount,
        'promo_code'         => $promoCode,
        'delivery_fee'       => $deliveryFee,
        'setup_fee'          => $setupFee,
        'total'              => $baseTotal,
        'status'             => 'Confirmed',
        'payment_method'     => 'Unpaid',
        'admin_notes'        => $adminNotes
    ];

    // 5. Save to MySQL Database
    $pdo = getDbConnection();
    if ($pdo) {
        ensureOrderColumnsExist($pdo);
        try {
            $stmt = $pdo->prepare("INSERT INTO `orders` (
                `id`, `date_added`, `name`, `email`, `phone`, `event_date`, `venue_location`,
                `fulfillment_method`, `delivery_address`, `pickup_date`, `pickup_time`, `return_date`, `return_time`,
                `delivery_date`, `delivery_time`, `collection_date`, `collection_time`, `special_requests`,
                `items`, `subtotal`, `discount`, `promo_code`, `delivery_fee`, `setup_fee`, `total`, `status`, `payment_method`, `admin_notes`
            ) VALUES (
                :id, :date_added, :name, :email, :phone, :event_date, :venue_location,
                :fulfillment_method, :delivery_address, :pickup_date, :pickup_time, :return_date, :return_time,
                :delivery_date, :delivery_time, :collection_date, :collection_time, :special_requests,
                :items, :subtotal, :discount, :promo_code, :delivery_fee, :setup_fee, :total, :status, :payment_method, :admin_notes
            )");
            $stmt->execute([
                ':id'                 => $orderId,
                ':date_added'         => $orderRecord['date_added'],
                ':name'               => $name,
                ':email'              => $email,
                ':phone'              => $phone,
                ':event_date'         => $eventDate,
                ':venue_location'     => $venueLocation,
                ':fulfillment_method' => $fulfillmentMethod,
                ':delivery_address'   => $deliveryAddress,
                ':pickup_date'        => $pickupDate,
                ':pickup_time'        => $pickupTime,
                ':return_date'        => $returnDate,
                ':return_time'        => $returnTime,
                ':delivery_date'      => $deliveryDate,
                ':delivery_time'      => $deliveryTime,
                ':collection_date'    => $collectionDate,
                ':collection_time'    => $collectionTime,
                ':special_requests'   => $specialRequests,
                ':items'              => $orderRecord['items'],
                ':subtotal'           => $subtotal,
                ':discount'           => $discount,
                ':promo_code'         => $promoCode,
                ':delivery_fee'       => $deliveryFee,
                ':setup_fee'          => $setupFee,
                ':total'              => $baseTotal,
                ':status'             => 'Confirmed',
                ':payment_method'     => 'Unpaid',
                ':admin_notes'        => $adminNotes
            ]);
        } catch (Exception $e) {
            // Ignored, fallback to JSON
        }
    }

    // 6. Save to orders.json backup file
    $ordersFile = __DIR__ . '/orders.json';
    $ordersList = [];
    if (file_exists($ordersFile)) {
        $fileContent = file_get_contents($ordersFile);
        if (!empty($fileContent)) {
            $ordersList = json_decode($fileContent, true) ?: [];
        }
    }
    array_unshift($ordersList, $orderRecord);
    file_put_contents($ordersFile, json_encode($ordersList, JSON_PRETTY_PRINT));

    // 7. Send Quote Email to Customer if requested
    $emailSent = false;
    if ($notify && !empty($email)) {
        $subtotalFmt   = number_format($subtotal, 2);
        $discountFmt   = number_format($discount, 2);
        $deliveryFmt   = number_format($deliveryFee, 2);
        $setupFmt      = number_format($setupFee, 2);
        $baseTotalFmt  = number_format($baseTotal, 2);
        $onlineTaxFmt  = number_format($onlineTaxVal, 2);
        $finalTotalFmt = number_format($finalTotalVal, 2);

        $squarePayUrl = function_exists('generateSquarePaymentUrl') ? generateSquarePaymentUrl($orderId, $finalTotalVal) : ((defined('SQUARE_PAYMENT_URL') ? SQUARE_PAYMENT_URL : 'https://square.link/u/xV2eBBtG') . '?src=embed&amount=' . urlencode($finalTotalFmt) . '&total=' . urlencode($finalTotalFmt) . '&price=' . urlencode($finalTotalFmt));
        $squareQrUrl   = 'https://petalsparadiseevents.com/square-qr-code.png';
        $trackUrl      = "https://petalsparadiseevents.com/#track";

        $itemsHtml = "";
        foreach ($itemsList as $it) {
            $t = htmlspecialchars($it['title']);
            $q = (int)$it['quantity'];
            $p = number_format((float)$it['price'], 2);
            $itemsHtml .= "<li style='margin-bottom: 4px;'><strong>{$q}x</strong> {$t} (@ \${$p} each)</li>";
        }

        $notesHtml = "";
        if (!empty($adminNotes)) {
            $notesHtml = "
            <div style='background: #fff8e6; border: 1px solid #ffe58f; padding: 15px; border-radius: 8px; margin: 18px 0;'>
                <h4 style='margin-top: 0; color: #d48806; font-size: 15px;'>💬 Note from Petals Paradise Events:</h4>
                <p style='margin: 0; color: #595959; font-size: 14px; white-space: pre-wrap;'>" . htmlspecialchars($adminNotes) . "</p>
            </div>";
        }

        $subject = "🌸 Rental Quote & Order Confirmation - Order {$orderId} - Petals Paradise Events";

        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Rental Quote & Order Confirmation</title>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333333; line-height: 1.6; background-color: #f9f9f9; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; }
                .header { text-align: center; border-bottom: 2px solid #d4af37; padding-bottom: 15px; margin-bottom: 20px; }
                .header h2 { color: #1a202c; margin: 0; font-size: 22px; }
                .box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 18px; border-radius: 8px; margin: 18px 0; }
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
                    <p style='margin:5px 0 0 0; color:#718096; font-size:14px;'>Rental Order &amp; Financial Quote</p>
                </div>
                
                <p>Hi <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <p>Thank you for contacting Petals Paradise Events! Here is your custom rental quote and order summary for Order <strong>" . htmlspecialchars($orderId) . "</strong>.</p>
                
                {$notesHtml}

                <div class='box'>
                    <h3 style='margin-top:0; color:#2d3748; font-size:16px;'>📋 Financial Breakdown &amp; Order Quote</h3>
                    <table class='price-table'>
                        <tr>
                            <td>Items Subtotal:</td>
                            <td style='text-align: right; font-weight: bold;'>\${$subtotalFmt}</td>
                        </tr>" .
                        ($discount > 0 || !empty($promoCode) ? "
                        <tr style='color: #38a169;'>
                            <td>Discount Applied" . (!empty($promoCode) ? " (" . htmlspecialchars($promoCode) . ")" : "") . ":</td>
                            <td style='text-align: right; font-weight: bold;'>-\${$discountFmt}</td>
                        </tr>" : "") . "
                        <tr>
                            <td>Delivery Fee:</td>
                            <td style='text-align: right; font-weight: bold; color: #d4af37;'>\${$deliveryFmt}</td>
                        </tr>
                        <tr>
                            <td>Setup &amp; Installation Fee:</td>
                            <td style='text-align: right; font-weight: bold; color: #d4af37;'>\${$setupFmt}</td>
                        </tr>
                        <tr style='border-top: 1px solid #cbd5e1;'>
                            <td>Quote Base Total:</td>
                            <td style='text-align: right; font-weight: bold;'>\${$baseTotalFmt}</td>
                        </tr>
                        <tr style='color: #006aff;'>
                            <td>VA Sales Tax (5.9%):</td>
                            <td style='text-align: right; font-weight: bold;'>+\${$onlineTaxFmt}</td>
                        </tr>
                        <tr class='total-row'>
                            <td style='padding-top: 10px; color: #1a202c;'>Final Amount Due (Online):</td>
                            <td style='text-align: right; padding-top: 10px; color: #006aff;'>\${$finalTotalFmt}</td>
                        </tr>
                    </table>
                </div>

                <!-- SQUARE ONLINE PAYMENT & QR CODE -->
                <div style='background: #f8fafc; border: 2px solid #006aff; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center;'>
                    <h3 style='margin-top:0; color:#006aff; font-size:17px;'>💳 Pay Online via Square</h3>
                    <p style='margin: 6px 0 15px 0; font-size: 14px; color: #475569;'>Click below or scan the QR code with your smartphone camera to pay securely using Credit Card, Debit Card, or Apple Pay.</p>
                    <div style='margin-bottom: 12px;'>
                        <a href='{$squarePayUrl}' target='_blank' style='display: inline-block; font-size: 18px; line-height: 48px; height: 48px; color: #ffffff !important; min-width: 212px; background-color: #006aff; text-align: center; box-shadow: 0 0 0 1px rgba(0,0,0,.1) inset; border-radius: 6px; text-decoration: none; font-weight: bold; padding: 0 24px;'>Pay now (\${$finalTotalFmt})</a>
                    </div>
                    <div style='margin: 0 auto 16px auto; max-width: 380px; font-size: 13px; color: #1e293b; font-weight: bold; background: #ffffff; border: 1px solid #bae6fd; padding: 10px 16px; border-radius: 8px;'>
                        📌 Amount to Enter on Square: <span style='color: #006aff; font-size: 15px; font-weight: 800;'>\${$finalTotalFmt}</span>
                        <div style='font-weight: normal; font-size: 12px; color: #64748b; margin-top: 2px;'>When Square opens, please enter <strong>\${$finalTotalFmt}</strong> in the 'Enter amount' box.</div>
                    </div>
                    <div style='display: inline-block; background: #ffffff; padding: 10px; border-radius: 10px; border: 1px solid #cbd5e1;'>
                        <img src='{$squareQrUrl}' alt='Scan to Pay via Square' width='160' height='160' style='display: block; border-radius: 6px;' />
                        <span style='font-size: 11px; color: #64748b; margin-top: 4px; display: block;'>Scan with Phone Camera</span>
                    </div>
                </div>

                <div class='box'>
                    <h3 style='margin-top:0; color:#2d3748; font-size:16px;'>🛍️ Rental Items Requested</h3>
                    <ul style='padding-left: 20px; margin-bottom: 0;'>
                        {$itemsHtml}
                    </ul>
                </div>

                <div style='text-align: center; margin: 25px 0;'>
                    <a href='{$trackUrl}' style='display: inline-block; background-color: #d4af37; color: #ffffff !important; padding: 12px 28px; text-decoration: none; border-radius: 30px; font-weight: bold; font-size: 14px;' target='_blank'>📦 Track Your Order Live</a>
                </div>

                <div class='footer'>
                    <p><strong>Petals Paradise Events</strong><br>
                    Crafting Unforgettable Moments in Loudoun County &amp; DMV<br>
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

        $emailSent = @mail($email, $subject, $message, $headers);

        // Send admin copy
        $notifList = json_decode(NOTIFICATION_EMAILS, true) ?: ['contact@petalsparadiseevents.com', 'biragonimounika@gmail.com'];
        foreach ($notifList as $nEmail) {
            if (!empty($nEmail) && strtolower($nEmail) !== strtolower($email)) {
                @mail($nEmail, "[Admin Copy] " . $subject, $message, $headers);
            }
        }
    }

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success'        => true,
        'order_id'       => $orderId,
        'customer_name'  => $name,
        'customer_email' => $email,
        'base_total'     => $baseTotal,
        'final_total'    => $finalTotalVal,
        'email_sent'     => $emailSent,
        'message'        => "Order {$orderId} created successfully" . ($emailSent ? " and quote email sent to {$email}." : ".")
    ]);

} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error creating order: ' . $e->getMessage()]);
}
