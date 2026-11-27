<?php
/**
 * Confidential Customer Order Placement API
 * Petals Paradise Events
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/loyalty.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://petalsparadiseevents.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 1. Read input data
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

if (!$inputData) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order details.']);
    exit;
}

// 2. Extract and Sanitize fields (supports web forms & AI chatbot payload aliases)
$name         = isset($inputData['name']) ? trim(filter_var($inputData['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$email        = isset($inputData['email']) ? trim(filter_var($inputData['email'], FILTER_SANITIZE_EMAIL)) : '';
$phone        = isset($inputData['phone']) ? trim(filter_var($inputData['phone'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';

$eventDateRaw = isset($inputData['event_date']) ? $inputData['event_date'] : (isset($inputData['date']) ? $inputData['date'] : '');
$eventDate    = trim(filter_var($eventDateRaw, FILTER_SANITIZE_FULL_SPECIAL_CHARS));

$venueRaw     = isset($inputData['delivery_address']) ? $inputData['delivery_address'] : (isset($inputData['location']) ? $inputData['location'] : (isset($inputData['venue_location']) ? $inputData['venue_location'] : ''));
$venue        = trim(filter_var($venueRaw, FILTER_SANITIZE_FULL_SPECIAL_CHARS));

$fulfillRaw   = isset($inputData['fulfillment_method']) ? $inputData['fulfillment_method'] : (isset($inputData['fulfillment']) ? $inputData['fulfillment'] : 'Pickup');
$fulfillment  = trim(filter_var($fulfillRaw, FILTER_SANITIZE_FULL_SPECIAL_CHARS));

$deliveryAddr = isset($inputData['delivery_address']) ? trim(filter_var($inputData['delivery_address'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : $venue;
$specialReqs  = isset($inputData['special_requests']) ? trim(filter_var($inputData['special_requests'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';

// Logistics details
$pickupDate     = isset($inputData['pickup_date_manual']) ? trim(filter_var($inputData['pickup_date_manual'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$pickupTime     = isset($inputData['pickup_time_manual']) ? trim(filter_var($inputData['pickup_time_manual'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$returnDate     = isset($inputData['dropoff_date_manual']) ? trim(filter_var($inputData['dropoff_date_manual'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$returnTime     = isset($inputData['dropoff_time_manual']) ? trim(filter_var($inputData['dropoff_time_manual'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';

$deliveryDate   = isset($inputData['delivery_date_manual']) ? trim(filter_var($inputData['delivery_date_manual'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$deliveryTime   = isset($inputData['delivery_time_manual']) ? trim(filter_var($inputData['delivery_time_manual'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$collectionDate = isset($inputData['collection_date_manual']) ? trim(filter_var($inputData['collection_date_manual'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$collectionTime = isset($inputData['collection_time_manual']) ? trim(filter_var($inputData['collection_time_manual'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';

// Financials
$subtotal     = isset($inputData['subtotal']) ? (float)$inputData['subtotal'] : 0.00;
$discount     = isset($inputData['discount']) ? (float)$inputData['discount'] : 0.00;
$total        = isset($inputData['total']) ? (float)$inputData['total'] : 0.00;
$promoCode    = isset($inputData['promo_code']) ? trim(filter_var($inputData['promo_code'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';

// Order Items List
$items = isset($inputData['items']) ? $inputData['items'] : [];

if (empty($name) || empty($email) || empty($phone) || empty($items)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please fill in all required customer details and select items.']);
    exit;
}

// Loyalty: link signed-in customer and validate points redemption (server-side)
$customer        = currentCustomer();
$customerId      = $customer ? (int)$customer['id'] : null;
$pointsRequested = isset($inputData['points_to_redeem']) ? (int)$inputData['points_to_redeem'] : 0;
$pointsRedeemed  = 0;
$pointsDollars   = 0.00;
if ($pointsRequested > 0) {
    if (!$customer) {
        http_response_code(401);
        echo json_encode(['error' => 'Please sign in to redeem loyalty points.']);
        exit;
    }
    // Use the item list (not only the client-sent subtotal) as the basis for the 50% cap
    $itemsSum = 0.0;
    foreach ($items as $it) { $itemsSum += (float)($it['price'] ?? 0) * (int)($it['quantity'] ?? 1); }
    $capBase = $itemsSum > 0 ? min($itemsSum, $subtotal > 0 ? $subtotal : $itemsSum) : $subtotal;
    try {
        [$pointsRedeemed, $pointsDollars] = validateRedemption($pointsRequested, (int)$customer['points_balance'], $capBase);
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
    $discount  = round($discount + $pointsDollars, 2);
    $total     = max(0, round($total - $pointsDollars, 2));
    $promoCode = trim($promoCode . ($promoCode !== '' ? ' + ' : '') . "{$pointsRedeemed} pts");
}

// Generate a professional human-readable Order Confirmation ID
$orderId = 'PPE-' . date('Ymd') . '-' . str_pad(mt_rand(100, 999), 3, '0', STR_PAD_LEFT);

// Format items as a clean JSON string for the DB and clean text for email
$itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$itemsText = "";
foreach ($items as $item) {
    $itemTitle = $item['title'] ?? 'Item';
    $itemQty   = $item['quantity'] ?? 1;
    $itemPrice = $item['price'] ?? 0.00;
    $itemsText .= "- {$itemQty}x {$itemTitle} (@ \${$itemPrice} each)\n";
}

// 3. Save Order to MySQL Database (Hostinger phpMyAdmin)
$dbSaved = false;
$pdo = getDbConnection();
if ($pdo) {
    ensureOrderColumnsExist($pdo);
    try {
        $stmt = $pdo->prepare("INSERT INTO `orders` (
            `id`, `date_added`, `name`, `email`, `phone`, `event_date`, `venue_location`, 
            `fulfillment_method`, `delivery_address`, 
            `pickup_date`, `pickup_time`, `return_date`, `return_time`, 
            `delivery_date`, `delivery_time`, `collection_date`, `collection_time`, 
            `special_requests`, `items`, `subtotal`, `discount`, `promo_code`, `total`, `status`
        ) VALUES (
            :id, NOW(), :name, :email, :phone, :event_date, :venue_location, 
            :fulfillment_method, :delivery_address, 
            :pickup_date, :pickup_time, :return_date, :return_time, 
            :delivery_date, :delivery_time, :collection_date, :collection_time, 
            :special_requests, :items, :subtotal, :discount, :promo_code, :total, 'Pending'
        )");
        
        $stmt->execute([
            ':id'                 => $orderId,
            ':name'               => $name,
            ':email'              => $email,
            ':phone'              => $phone,
            ':event_date'         => $eventDate,
            ':venue_location'     => $venue,
            ':fulfillment_method' => $fulfillment,
            ':delivery_address'   => $deliveryAddr,
            ':pickup_date'        => $pickupDate,
            ':pickup_time'        => $pickupTime,
            ':return_date'        => $returnDate,
            ':return_time'        => $returnTime,
            ':delivery_date'      => $deliveryDate,
            ':delivery_time'      => $deliveryTime,
            ':collection_date'    => $collectionDate,
            ':collection_time'    => $collectionTime,
            ':special_requests'   => $specialReqs,
            ':items'              => $itemsJson,
            ':subtotal'           => $subtotal,
            ':discount'           => $discount,
            ':promo_code'         => $promoCode,
            ':total'              => $total
        ]);
        $dbSaved = true;

        // Loyalty: attach customer + deduct redeemed points
        if ($customerId) {
            ensureLoyaltyTables($pdo);
            $pdo->prepare("UPDATE `orders` SET `customer_id` = ?, `points_redeemed` = ? WHERE `id` = ?")
                ->execute([$customerId, $pointsRedeemed, $orderId]);
            if ($pointsRedeemed > 0) {
                addPoints($pdo, $customerId, -$pointsRedeemed, 'redeem', $orderId, "Redeemed on order {$orderId} (-\$" . number_format($pointsDollars, 2) . ")");
            }
        }
    } catch (Exception $e) {
        // Fallback to JSON file continues if DB fails
    }
}

// 4. Save to orders.json file backup (Always save as redundancy)
$ordersFile = __DIR__ . '/orders.json';
$existingOrders = [];
if (file_exists($ordersFile)) {
    $fileContent = file_get_contents($ordersFile);
    if (!empty($fileContent)) {
        $existingOrders = json_decode($fileContent, true) ?: [];
    }
}
$orderRecord = [
    'id'                 => $orderId,
    'date_added'         => date('Y-m-d H:i:s'),
    'name'               => $name,
    'email'              => $email,
    'phone'              => $phone,
    'event_date'         => $eventDate,
    'venue_location'     => $venue,
    'fulfillment_method' => $fulfillment,
    'delivery_address'   => $deliveryAddr,
    'pickup_details'     => "{$pickupDate} {$pickupTime} to {$returnDate} {$returnTime}",
    'delivery_details'   => "{$deliveryDate} {$deliveryTime} to {$collectionDate} {$collectionTime}",
    'special_requests'   => $specialReqs,
    'items'              => $items,
    'subtotal'           => $subtotal,
    'discount'           => $discount,
    'promo_code'         => $promoCode,
    'total'              => $total,
    'customer_id'        => $customerId,
    'points_redeemed'    => $pointsRedeemed,
    'status'             => 'Pending'
];
array_unshift($existingOrders, $orderRecord);
file_put_contents($ordersFile, json_encode($existingOrders, JSON_PRETTY_PRINT));

// 5. Send Notification Email to Admins / Owners
$adminSubject = "🌸 New Order Placed: {$orderId} - {$name}";
$adminMessage = "A new customer rental request has been placed on Petals Paradise Events!\n\n"
              . "ORDER SUMMARY\n"
              . "----------------------------------------\n"
              . "Confirmation ID: {$orderId}\n"
              . "Customer Name: {$name}\n"
              . "Customer Email: {$email}\n"
              . "Customer Phone: {$phone}\n\n"
              . "LOGISTICS & EVENT DETAILS\n"
              . "----------------------------------------\n"
              . "Event Date: {$eventDate}\n"
              . "Fulfillment: {$fulfillment}\n";

if ($fulfillment === 'Delivery') {
    $adminMessage .= "Delivery Address: {$deliveryAddr}\n"
                  . "Delivery Date: {$deliveryDate} at {$deliveryTime}\n"
                  . "Collection Date: {$collectionDate} at {$collectionTime}\n";
} else {
    $adminMessage .= "Venue Location: {$venue}\n"
                  . "Pickup Date: {$pickupDate} at {$pickupTime}\n"
                  . "Return Date: {$returnDate} at {$returnTime}\n";
}

if (!empty($specialReqs)) {
    $adminMessage .= "Special Requests: {$specialReqs}\n";
}

$adminMessage .= "\nITEMS REQUESTED\n"
              . "----------------------------------------\n"
              . $itemsText . "\n"
              . "Subtotal: \${$subtotal}\n"
              . ((float)$discount > 0 ? "Discount: -\${$discount}" . (!empty($promoCode) ? " ({$promoCode})" : "") . "\n" : "")
              . "Total Estimate: \${$total}" . ($fulfillment === 'Delivery' ? " + Delivery Fee (TBD)" : "") . "\n\n"
              . "----------------------------------------\n"
              . "This order has been saved directly to your phpMyAdmin database and leads dashboard.\n"
              . "View portal: https://petalsparadiseevents.com/api/leads_export.php\n";

$adminHeaders = "From: Petals Paradise Website <contact@petalsparadiseevents.com>\r\n"
              . "Reply-To: {$email}\r\n"
              . "X-Mailer: PHP/" . phpversion();

// Send to all notification emails
foreach ($notificationEmails as $recipientEmail) {
    if (!empty($recipientEmail)) {
        @mail($recipientEmail, $adminSubject, $adminMessage, $adminHeaders);
    }
}

// Send push notification alert to owner's phone (ntfy app)
$smsTotal = number_format((float)$total, 2);
smsOwner("PPE NEW ORDER! ID:{$orderId} | {$name} | {$phone} | {$fulfillment} | Event:{$eventDate} | Total:\${$smsTotal}");

// 6. Send Confirmation Email to the Customer (Fancy Luxury HTML Email)
$pointsToEarn = $customerId
    ? pointsForTotal(pointsEligibleAmount([
        'subtotal' => $subtotal,
        'discount' => $discount,
        'points_redeemed' => $pointsRedeemed,
        'total' => $total
    ]))
    : 0;

$esc = function ($val) {
    return htmlspecialchars(html_entity_decode((string)$val, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
};

$safeName        = $esc($name);
$safeOrderId     = $esc($orderId);
$safeEventDate   = $esc($eventDate);
$safeFulfillment = $esc($fulfillment);
$safeVenue       = $esc($venue);
$safeDeliveryAdr = $esc($deliveryAddr);
$safePromoCode   = $esc($promoCode);
$safeSpecialReqs = $esc($specialReqs);

$subtotalFmt     = number_format((float)$subtotal, 2);
$discountFmt     = number_format((float)$discount, 2);
$totalFmt        = number_format((float)$total, 2);
$trackUrl        = "https://petalsparadiseevents.com/#track";

// Build styled HTML item rows
$itemsHtmlRows = '';
foreach ($items as $item) {
    $iTitle = $esc($item['title'] ?? 'Rental Item');
    $iQty   = (int)($item['quantity'] ?? 1);
    $iPrice = (float)($item['price'] ?? 0.00);
    $iLine  = $iQty * $iPrice;
    $priceCell = $iPrice > 0
        ? '$' . number_format($iLine, 2) . ($iQty > 1 ? "<div style='font-size:11px; color:#8c7b6b; font-weight:normal;'>\$" . number_format($iPrice, 2) . " ea</div>" : '')
        : "<span style='color:#9a7b2c; font-style:italic; font-size:12px;'>Quote TBD</span>";

    $itemsHtmlRows .= "
        <tr>
            <td style='padding: 12px 10px; border-bottom: 1px solid #f0e6d2; color: #2c241e; font-size: 14px; font-family: \"Lato\", \"Helvetica Neue\", Helvetica, Arial, sans-serif;'>
                {$iTitle}
            </td>
            <td style='padding: 12px 8px; border-bottom: 1px solid #f0e6d2; color: #6b5b4d; font-size: 14px; text-align: center; font-family: \"Lato\", \"Helvetica Neue\", Helvetica, Arial, sans-serif;'>
                &times;{$iQty}
            </td>
            <td style='padding: 12px 10px; border-bottom: 1px solid #f0e6d2; color: #2c241e; font-size: 14px; font-weight: bold; text-align: right; white-space: nowrap; font-family: \"Lato\", \"Helvetica Neue\", Helvetica, Arial, sans-serif;'>
                {$priceCell}
            </td>
        </tr>";
}

// Build logistics details rows
$logisticsRows = "";
if (!empty($eventDate)) {
    $logisticsRows .= "<tr><td style='padding: 6px 0; color: #7a6a58; font-size: 13px; width: 42%;'>Event Date</td><td style='padding: 6px 0; color: #2c241e; font-size: 14px; font-weight: bold; text-align: right;'>{$safeEventDate}</td></tr>";
}
$logisticsRows .= "<tr><td style='padding: 6px 0; color: #7a6a58; font-size: 13px;'>Fulfillment Method</td><td style='padding: 6px 0; color: #2c241e; font-size: 14px; font-weight: bold; text-align: right;'>{$safeFulfillment}</td></tr>";

if ($fulfillment === 'Delivery') {
    if (!empty($deliveryAddr)) {
        $logisticsRows .= "<tr><td style='padding: 6px 0; color: #7a6a58; font-size: 13px; vertical-align: top;'>Delivery Address</td><td style='padding: 6px 0; color: #2c241e; font-size: 14px; font-weight: bold; text-align: right;'>{$safeDeliveryAdr}</td></tr>";
    }
    if (!empty($deliveryDate) || !empty($deliveryTime)) {
        $delivStr = trim($esc($deliveryDate) . (!empty($deliveryTime) ? ' at ' . $esc($deliveryTime) : ''));
        $logisticsRows .= "<tr><td style='padding: 6px 0; color: #7a6a58; font-size: 13px;'>Delivery Window</td><td style='padding: 6px 0; color: #2c241e; font-size: 14px; font-weight: bold; text-align: right;'>{$delivStr}</td></tr>";
    }
    if (!empty($collectionDate) || !empty($collectionTime)) {
        $collStr = trim($esc($collectionDate) . (!empty($collectionTime) ? ' at ' . $esc($collectionTime) : ''));
        $logisticsRows .= "<tr><td style='padding: 6px 0; color: #7a6a58; font-size: 13px;'>Collection Window</td><td style='padding: 6px 0; color: #2c241e; font-size: 14px; font-weight: bold; text-align: right;'>{$collStr}</td></tr>";
    }
} else {
    if (!empty($venue)) {
        $logisticsRows .= "<tr><td style='padding: 6px 0; color: #7a6a58; font-size: 13px; vertical-align: top;'>Venue / Location</td><td style='padding: 6px 0; color: #2c241e; font-size: 14px; font-weight: bold; text-align: right;'>{$safeVenue}</td></tr>";
    }
    if (!empty($pickupDate) || !empty($pickupTime)) {
        $pickStr = trim($esc($pickupDate) . (!empty($pickupTime) ? ' at ' . $esc($pickupTime) : ''));
        $logisticsRows .= "<tr><td style='padding: 6px 0; color: #7a6a58; font-size: 13px;'>Pickup Window</td><td style='padding: 6px 0; color: #2c241e; font-size: 14px; font-weight: bold; text-align: right;'>{$pickStr}</td></tr>";
    }
    if (!empty($returnDate) || !empty($returnTime)) {
        $retStr = trim($esc($returnDate) . (!empty($returnTime) ? ' at ' . $esc($returnTime) : ''));
        $logisticsRows .= "<tr><td style='padding: 6px 0; color: #7a6a58; font-size: 13px;'>Return Window</td><td style='padding: 6px 0; color: #2c241e; font-size: 14px; font-weight: bold; text-align: right;'>{$retStr}</td></tr>";
    }
}

$specialReqsBlock = !empty($specialReqs)
    ? "<div style='background: #fffdf8; border-left: 3px solid #d4af37; padding: 12px 16px; border-radius: 0 8px 8px 0; margin: 16px 0 0 0; font-size: 13px; color: #4a3f35;'>
           <strong style='color: #8a6d1c; text-transform: uppercase; font-size: 11px; letter-spacing: 0.8px; display: block; margin-bottom: 4px;'>Special Requests / Notes</strong>
           " . nl2br($safeSpecialReqs) . "
       </div>"
    : "";

$loyaltyBanner = "";
if ($customerId && $pointsToEarn > 0) {
    $ptsFmt = number_format($pointsToEarn);
    $loyaltyBanner = "
        <div style='background: linear-gradient(135deg, #fffaf0 0%, #fdf3d7 100%); border: 1px solid #d4af37; border-radius: 10px; padding: 14px 18px; margin: 22px 0; text-align: center;'>
            <div style='font-family: \"Playfair Display\", Georgia, serif; font-size: 15px; color: #6e5210; font-weight: bold;'>&#10024; Petals Rewards</div>
            <div style='font-size: 13px; color: #4a3f35; margin-top: 4px;'>You are estimated to earn <strong>{$ptsFmt} points</strong> once this order is completed!</div>
        </div>";
} elseif (!$customerId) {
    $loyaltyBanner = "
        <div style='background: #fffcf5; border: 1px dashed #d4af37; border-radius: 10px; padding: 14px 18px; margin: 22px 0; text-align: center;'>
            <div style='font-family: \"Playfair Display\", Georgia, serif; font-size: 15px; color: #6e5210; font-weight: bold;'>&#127800; Join Petals Rewards &mdash; It&rsquo;s Free!</div>
            <div style='font-size: 13px; color: #4a3f35; margin-top: 4px;'>Create an account with this email at <a href='https://petalsparadiseevents.com/#signup' style='color: #b88a1e; font-weight: bold; text-decoration: underline;'>petalsparadiseevents.com</a> to earn points on every rental.</div>
        </div>";
}

$discountRow = ((float)$discount > 0)
    ? "<tr>
           <td style='padding: 6px 0; color: #2f855a; font-size: 14px;'>Discount Applied" . (!empty($safePromoCode) ? " ({$safePromoCode})" : "") . "</td>
           <td style='padding: 6px 0; color: #2f855a; font-size: 14px; font-weight: bold; text-align: right;'>-\${$discountFmt}</td>
       </tr>"
    : "";

$deliveryNote = ($fulfillment === 'Delivery')
    ? "<div style='font-size: 12px; color: #8c7b6b; font-weight: normal; margin-top: 2px;'>+ Delivery Fee (TBD)</div>"
    : "";

$custSubject = "🌸 Rental Request Received: {$orderId} - Petals Paradise Events";
$custMessage = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Rental Request Received - Petals Paradise Events</title>
    <link href='https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Lato:wght@400;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Lato', 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2c241e; line-height: 1.65; background-color: #f7f3ec; margin: 0; padding: 24px 12px; }
        h1, h2, h3, .serif { font-family: 'Playfair Display', Georgia, 'Times New Roman', serif; }
        @media only screen and (max-width: 600px) {
            .email-body { padding: 22px 18px !important; }
            .email-header { padding: 28px 18px !important; }
        }
    </style>
</head>
<body style='margin: 0; padding: 24px 12px; background-color: #f7f3ec; font-family: \"Lato\", \"Helvetica Neue\", Helvetica, Arial, sans-serif; color: #2c241e; line-height: 1.65;'>
    <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #f7f3ec;'>
        <tr>
            <td align='center'>
                <table role='presentation' width='600' cellpadding='0' cellspacing='0' border='0' style='max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e6d7bd; box-shadow: 0 8px 30px rgba(44, 36, 30, 0.08);'>
                    <!-- LUXURY HEADER BANNER -->
                    <tr>
                        <td class='email-header' style='background: linear-gradient(135deg, #1d1916 0%, #2d241c 100%); background-color: #1d1916; padding: 36px 32px; text-align: center; border-bottom: 3px solid #d4af37;'>
                            <div style='font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: #d4af37; margin-bottom: 8px; font-weight: bold;'>&#10022; Luxury Event Decor &amp; Rentals &#10022;</div>
                            <h1 style='font-family: \"Playfair Display\", Georgia, \"Times New Roman\", serif; font-size: 28px; font-weight: 700; color: #f5df9d; margin: 0; letter-spacing: 0.5px;'>&#127800; Petals Paradise Events</h1>
                            <div style='margin-top: 16px;'>
                                <span style='display: inline-block; background-color: rgba(212, 175, 55, 0.16); border: 1px solid #d4af37; color: #f5df9d; font-size: 13px; font-weight: bold; letter-spacing: 0.6px; padding: 6px 20px; border-radius: 30px;'>&#10024; Rental Request Received</span>
                            </div>
                        </td>
                    </tr>

                    <!-- MAIN CONTENT -->
                    <tr>
                        <td class='email-body' style='padding: 34px 36px;'>
                            <p style='font-family: \"Playfair Display\", Georgia, \"Times New Roman\", serif; font-size: 20px; color: #1d1916; margin: 0 0 12px 0;'>Dear <strong>{$safeName}</strong>,</p>
                            <p style='font-size: 15px; color: #4a3f35; margin: 0 0 24px 0;'>
                                Thank you for choosing <strong style='color: #1d1916;'>Petals Paradise Events</strong>! We are delighted to have received your rental request and our team is currently checking item availability for your special occasion.
                            </p>

                            <!-- CONFIRMATION ID HIGHLIGHT -->
                            <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #fdf8ec; border: 1px solid #d4af37; border-radius: 12px; margin-bottom: 24px;'>
                                <tr>
                                    <td style='padding: 18px 20px; text-align: center;'>
                                        <div style='font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #8a6d1c; font-weight: bold; margin-bottom: 6px;'>Order Confirmation ID</div>
                                        <div style='font-family: \"Courier New\", Courier, monospace; font-size: 22px; font-weight: bold; color: #b5891d; letter-spacing: 1px;'>{$safeOrderId}</div>
                                    </td>
                                </tr>
                            </table>

                            <!-- EVENT & LOGISTICS SUMMARY -->
                            <div style='background-color: #faf8f4; border: 1px solid #ebe2d3; border-radius: 12px; padding: 20px 22px; margin-bottom: 24px;'>
                                <h3 style='font-family: \"Playfair Display\", Georgia, \"Times New Roman\", serif; font-size: 17px; color: #1d1916; margin: 0 0 12px 0; padding-bottom: 8px; border-bottom: 1px solid #e6d7bd;'>&#128197; Event &amp; Logistics Details</h3>
                                <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>
                                    {$logisticsRows}
                                </table>
                                {$specialReqsBlock}
                            </div>

                            <!-- REQUESTED ITEMS TABLE -->
                            <div style='background-color: #ffffff; border: 1px solid #ebe2d3; border-radius: 12px; padding: 20px 22px; margin-bottom: 24px;'>
                                <h3 style='font-family: \"Playfair Display\", Georgia, \"Times New Roman\", serif; font-size: 17px; color: #1d1916; margin: 0 0 14px 0; padding-bottom: 8px; border-bottom: 1px solid #e6d7bd;'>&#128717;&#65039; Requested Rental Items</h3>
                                <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='border-collapse: collapse;'>
                                    <thead>
                                        <tr style='background-color: #faf6ee;'>
                                            <th align='left' style='padding: 9px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #8a6d1c; border-bottom: 1px solid #e6d7bd;'>Item</th>
                                            <th align='center' style='padding: 9px 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #8a6d1c; border-bottom: 1px solid #e6d7bd;'>Qty</th>
                                            <th align='right' style='padding: 9px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #8a6d1c; border-bottom: 1px solid #e6d7bd;'>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {$itemsHtmlRows}
                                    </tbody>
                                </table>

                                <!-- TOTALS -->
                                <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='margin-top: 14px;'>
                                    <tr>
                                        <td style='padding: 6px 0; color: #7a6a58; font-size: 14px;'>Items Subtotal</td>
                                        <td style='padding: 6px 0; color: #2c241e; font-size: 14px; font-weight: bold; text-align: right;'>\${$subtotalFmt}</td>
                                    </tr>
                                    {$discountRow}
                                    <tr>
                                        <td colspan='2' style='padding: 6px 0;'><div style='border-top: 2px solid #d4af37;'></div></td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 4px 0; font-family: \"Playfair Display\", Georgia, \"Times New Roman\", serif; font-size: 17px; font-weight: bold; color: #1d1916;'>Estimated Total</td>
                                        <td style='padding: 4px 0; font-size: 18px; font-weight: bold; color: #b5891d; text-align: right;'>\${$totalFmt}{$deliveryNote}</td>
                                    </tr>
                                </table>
                            </div>

                            {$loyaltyBanner}

                            <!-- WHAT HAPPENS NEXT -->
                            <div style='background-color: #faf8f4; border: 1px solid #ebe2d3; border-radius: 12px; padding: 20px 22px; margin-bottom: 26px;'>
                                <h3 style='font-family: \"Playfair Display\", Georgia, \"Times New Roman\", serif; font-size: 17px; color: #1d1916; margin: 0 0 14px 0;'>&#10024; What Happens Next?</h3>
                                <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>
                                    <tr>
                                        <td width='34' valign='top' style='padding: 0 0 12px 0;'>
                                            <span style='display: inline-block; width: 24px; height: 24px; line-height: 24px; text-align: center; background-color: #d4af37; color: #ffffff; border-radius: 50%; font-size: 12px; font-weight: bold;'>1</span>
                                        </td>
                                        <td valign='top' style='padding: 0 0 12px 0; font-size: 14px; color: #4a3f35;'>
                                            <strong style='color: #1d1916;'>Availability Review:</strong> Our team will verify availability of all requested pieces for <strong>{$safeEventDate}</strong>.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width='34' valign='top' style='padding: 0 0 12px 0;'>
                                            <span style='display: inline-block; width: 24px; height: 24px; line-height: 24px; text-align: center; background-color: #d4af37; color: #ffffff; border-radius: 50%; font-size: 12px; font-weight: bold;'>2</span>
                                        </td>
                                        <td valign='top' style='padding: 0 0 12px 0; font-size: 14px; color: #4a3f35;'>
                                            <strong style='color: #1d1916;'>Personal Confirmation:</strong> We will reach out via email or phone within <strong>24 hours</strong> to confirm your booking and coordinate logistics.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width='34' valign='top'>
                                            <span style='display: inline-block; width: 24px; height: 24px; line-height: 24px; text-align: center; background-color: #d4af37; color: #ffffff; border-radius: 50%; font-size: 12px; font-weight: bold;'>3</span>
                                        </td>
                                        <td valign='top' style='font-size: 14px; color: #4a3f35;'>
                                            <strong style='color: #1d1916;'>Secure Your Date:</strong> Please note that reservations are officially secured once a deposit is completed.
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- TRACK ORDER CTA -->
                            <div style='text-align: center; margin: 26px 0 22px 0;'>
                                <a href='{$trackUrl}' target='_blank' style='display: inline-block; background-color: #d4af37; color: #ffffff !important; padding: 14px 34px; text-decoration: none; border-radius: 30px; font-weight: bold; font-size: 15px; letter-spacing: 0.4px; box-shadow: 0 4px 12px rgba(212, 175, 55, 0.35);'>&#128230; Track Your Order Live</a>
                            </div>

                            <p style='font-size: 14px; color: #6b5b4d; text-align: center; margin: 0 0 24px 0;'>
                                Need to make changes or add items? Simply reply directly to this email or call/text us at
                                <a href='tel:+18484486993' style='color: #b5891d; font-weight: bold; text-decoration: none;'>+1 848-448-6993</a>.
                            </p>

                            <p style='font-family: \"Playfair Display\", Georgia, \"Times New Roman\", serif; font-style: italic; font-size: 16px; color: #2c241e; margin: 0;'>
                                Warm regards,<br>
                                <strong style='font-style: normal; color: #8a6d1c;'>The Petals Paradise Events Team</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- LUXURY FOOTER -->
                    <tr>
                        <td style='background-color: #faf6ee; border-top: 1px solid #e6d7bd; padding: 22px 28px; text-align: center; font-size: 13px; color: #7a6a58;'>
                            <div style='font-family: \"Playfair Display\", Georgia, \"Times New Roman\", serif; font-size: 15px; font-weight: bold; color: #1d1916; margin-bottom: 4px;'>Petals Paradise Events</div>
                            <div style='margin-bottom: 6px;'>Crafting Unforgettable Moments in Loudoun County &amp; the DMV</div>
                            <div>
                                Phone: <a href='tel:+18484486993' style='color: #b5891d; text-decoration: none;'>+1 848-448-6993</a> &nbsp;|&nbsp;
                                Email: <a href='mailto:contact@petalsparadiseevents.com' style='color: #b5891d; text-decoration: none;'>contact@petalsparadiseevents.com</a><br>
                                Website: <a href='https://petalsparadiseevents.com' style='color: #b5891d; text-decoration: none; font-weight: bold;'>petalsparadiseevents.com</a>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";

$custHeaders = "MIME-Version: 1.0\r\n"
             . "Content-Type: text/html; charset=UTF-8\r\n"
             . "From: Petals Paradise Events <contact@petalsparadiseevents.com>\r\n"
             . "Reply-To: contact@petalsparadiseevents.com\r\n"
             . "X-Mailer: PHP/" . phpversion();

@mail($email, $custSubject, $custMessage, $custHeaders);

// 7. Return JSON Success Response
echo json_encode([
    'success' => true,
    'order_id' => $orderId,
    'points_redeemed' => $pointsRedeemed,
    'points_discount' => $pointsDollars,
    'total' => $total,
    'points_to_earn' => $pointsToEarn,
    'message' => 'Order placed successfully.'
]);
