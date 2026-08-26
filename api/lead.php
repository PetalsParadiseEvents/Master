<?php
/**
 * Confidential Customer Lead Capture API
 * Petals Paradise Events
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 1. Read input data (JSON or POST)
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

if (!$inputData) {
    $inputData = $_POST;
}

// 2. Sanitize fields
$name        = isset($inputData['name']) ? trim(filter_var($inputData['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$email       = isset($inputData['email']) ? trim(filter_var($inputData['email'], FILTER_SANITIZE_EMAIL)) : '';
$phone       = isset($inputData['phone']) ? trim(filter_var($inputData['phone'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$eventType   = isset($inputData['event_type']) ? trim(filter_var($inputData['event_type'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : 'General Inquiry';
$serviceTier = isset($inputData['service_tier']) ? trim(filter_var($inputData['service_tier'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$guestCount  = isset($inputData['guest_count']) ? trim(filter_var($inputData['guest_count'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$budget      = isset($inputData['budget']) ? trim(filter_var($inputData['budget'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$eventDate   = isset($inputData['event_date']) ? trim(filter_var($inputData['event_date'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$location    = isset($inputData['location']) ? trim(filter_var($inputData['location'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
$source      = isset($inputData['source']) ? trim(filter_var($inputData['source'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : 'Website Contact';
$notes       = isset($inputData['notes']) ? trim(filter_var($inputData['notes'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';

if (empty($name) && empty($email) && empty($phone)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please provide at least a name, email, or phone number.']);
    exit;
}

// 3. Prepare Lead Record
$leadRecord = [
    'id'           => uniqid('lead_', true),
    'date_added'   => date('Y-m-d H:i:s'),
    'name'         => $name,
    'email'        => $email,
    'phone'        => $phone,
    'event_type'   => $eventType,
    'service_tier' => $serviceTier,
    'guest_count'  => $guestCount,
    'budget'       => $budget,
    'event_date'   => $eventDate,
    'location'     => $location,
    'source'       => $source,
    'notes'        => $notes
];


// 4. Save to Confidential leads.json
$leadsFile = __DIR__ . '/leads.json';
$existingLeads = [];

if (file_exists($leadsFile)) {
    $fileContent = file_get_contents($leadsFile);
    if (!empty($fileContent)) {
        $existingLeads = json_decode($fileContent, true) ?: [];
    }
}

array_unshift($existingLeads, $leadRecord);

// Save back atomically
file_put_contents($leadsFile, json_encode($existingLeads, JSON_PRETTY_PRINT));

// 5. Send Notification Email to Owners / Sales Team
// Add any additional notification email addresses to this array:
$notificationEmails = [
    'contact@petalsparadiseevents.com',
    // 'sales@petalsparadiseevents.com', // Uncomment or add more emails here
];

$subject = "🌸 New Lead Received: {$name} ({$eventType})";
$message = "You received a new customer lead on Petals Paradise Events!\n\n"
         . "----------------------------------------\n"
         . "Name: {$name}\n"
         . "Email: {$email}\n"
         . "Phone: {$phone}\n"
         . "Event Type: {$eventType}\n"
         . "Event Date: {$eventDate}\n"
         . "Location: {$location}\n"
         . "Source: {$source}\n"
         . "Notes / Items: {$notes}\n"
         . "Date Received: " . date('Y-m-d H:i:s') . "\n"
         . "----------------------------------------\n\n"
         . "This lead has been saved confidentially to your website lead database.\n"
         . "View and export all leads at: https://petalsparadiseevents.com/api/leads_export.php\n";

$headers = "From: Petals Paradise Website <contact@petalsparadiseevents.com>\r\n"
         . "Reply-To: " . (!empty($email) ? $email : 'contact@petalsparadiseevents.com') . "\r\n"
         . "X-Mailer: PHP/" . phpversion();

// Send email to all configured notification recipient addresses
foreach ($notificationEmails as $recipientEmail) {
    if (!empty($recipientEmail)) {
        @mail($recipientEmail, $subject, $message, $headers);
    }
}

// 6. Return Response
echo json_encode([
    'success' => true,
    'message' => 'Thank you! Your inquiry has been received confidentially.'
]);
