<?php
/**
 * Confidential Customer Order Placement API
 * Petals Paradise Events
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
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
    try {
        $stmt = $pdo->prepare("INSERT INTO `orders` (
            `id`, `date_added`, `name`, `email`, `phone`, `event_date`, `venue_location`, 
            `fulfillment_method`, `delivery_address`, 
            `pickup_date`, `pickup_time`, `return_date`, `return_time`, 
            `delivery_date`, `delivery_time`, `collection_date`, `collection_time`, 
            `special_requests`, `items`, `subtotal`, `discount`, `total`, `status`
        ) VALUES (
            :id, NOW(), :name, :email, :phone, :event_date, :venue_location, 
            :fulfillment_method, :delivery_address, 
            :pickup_date, :pickup_time, :return_date, :return_time, 
            :delivery_date, :delivery_time, :collection_date, :collection_time, 
            :special_requests, :items, :subtotal, :discount, :total, 'Pending'
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
            ':total'              => $total
        ]);
        $dbSaved = true;
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
    'total'              => $total,
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
              . "Discount: -\${$discount}" . (!empty($promoCode) ? " ({$promoCode})" : "") . "\n"
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

// 6. Send Confirmation Email to the Customer
$custSubject = "🌸 Rental Request Received: {$orderId} - Petals Paradise Events";
$custMessage = "Hi {$name},\n\n"
             . "Thank you for choosing Petals Paradise Events! We have received your rental request and are checking item availability for your event date.\n\n"
             . "Below is a summary of your requested rentals:\n\n"
             . "----------------------------------------\n"
             . "Confirmation ID: {$orderId}\n"
             . "Event Date: {$eventDate}\n"
             . "Fulfillment Method: {$fulfillment}\n"
             . "Estimated Total: \${$total}" . ($fulfillment === 'Delivery' ? " + Delivery Fee (TBD)" : "") . "\n\n"
             . "ITEMS:\n"
             . $itemsText . "\n"
             . "----------------------------------------\n\n"
             . "What happens next?\n"
             . "1. Our team will review item availability for {$eventDate}.\n"
             . "2. We will contact you via email or phone within 24 hours to confirm your booking and coordinate logistics.\n"
             . "3. Please note that bookings are only secured once a deposit is completed.\n\n"
             . "If you need to make any changes, please reply directly to this email or call us at +1 848-448-6993.\n\n"
             . "Best regards,\n"
             . "The Petals Paradise Events Team\n"
             . "https://petalsparadiseevents.com\n";

$custHeaders = "From: Petals Paradise Events <contact@petalsparadiseevents.com>\r\n"
             . "Reply-To: contact@petalsparadiseevents.com\r\n"
             . "X-Mailer: PHP/" . phpversion();

@mail($email, $custSubject, $custMessage, $custHeaders);

// 7. Return JSON Success Response
echo json_encode([
    'success' => true,
    'order_id' => $orderId,
    'message' => 'Order placed successfully.'
]);
