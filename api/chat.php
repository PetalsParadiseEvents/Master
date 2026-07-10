<?php
/**
 * Secure proxy endpoint for Petals Paradise Events AI Assistant.
 * Routes client queries to the Google Gemini 1.5 Flash API.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 1. Load config
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    echo json_encode(['error' => 'Configuration file not found. please create api/config.php.']);
    exit(1);
}
require_once $configFile;

// 2. Validate API Key
if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE' || empty(GEMINI_API_KEY)) {
    echo json_encode([
        'error' => 'API Key not configured.',
        'response' => '🌸 Welcome to Petals Paradise Events! I am ready to assist you. However, my Gemini API Key is not yet configured. Please add your key in `api/config.php` to enable live AI responses.'
    ]);
    exit(0);
}

// 3. Parse JSON request body
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

$userMessage = isset($data['message']) ? trim($data['message']) : '';
$history = isset($data['history']) ? $data['history'] : [];

if (empty($userMessage)) {
    echo json_encode(['error' => 'Empty message.']);
    exit(0);
}

// 4. Define the System Instruction
$systemInstruction = "You are the premium AI Event Decor Assistant for Petals Paradise Events, a luxury party rental and event decor boutique serving the DMV (DC, Maryland, Virginia) area. Your home base is Ashburn, VA.

Your tone should be warm, creative, helpful, and highly professional. You assist clients in planning events (weddings, graduations, baby showers, birthdays, South Asian traditional Haldi/Mehandi, housewarmings) and recommending inventory.

Here is the exact catalog of rental items. ALWAYS suggest items from this list when a user asks for recommendations:
- Round Fold-In-Half Table ($12 each, 60\" x 29.8\"): ID 1
- Cocktail Table with Cloths ($11 each, black/white cloths): ID 2
- Adult Rectangular Folding Table ($8 each): ID 3
- Adult Folding Chair ($2 each. Bulk discount: $1.50 each when renting 30 or more): ID 4
- Wedding Tent (16x26, $150): ID 5
- Tent (10x20, $100): ID 25
- Round Cylinder Pedestal Display (Set of 5, gold/white covers, $30): ID 6
- Buffet Food Warmers ($10 each): ID 7
- Loveseat for Rental ($100): ID 8
- Elegant Hand-Carved Accent Chair ($75): ID 9
- Haldi Urli / Maiyan Tub ($125, traditional setups): ID 10
- Pipe and Drape Backdrop Stand ($50, heavy duty): ID 11
- GRAD Marquee Letters ($40 total for letters G-R-A-D): ID 12
- 4FT Marquee Numbers ($20 per digit): ID 13
- Photo/Any Event Backdrop ($150): ID 14
- New Born Baby Photo Prop / Moon Swing ($20): ID 15
- Custom Graduation Setup (Varies, call/inquire): ID 16
- Premium GRAD Decor (Varies, call/inquire): ID 17
- Seemantham / Baby Shower Backdrop ($150, traditional South Asian): ID 18
- VEVOR Metal Wedding Centerpiece (2PCS, $25): ID 19
- Happy Birthday Neon Sign ($10): ID 20
- Good Vibes Only Neon Sign ($10): ID 21
- Congrats Grad Neon Sign ($10): ID 22
- Mehandi Umbrella Set ($3 each): ID 23
- Easel for Rent ($10): ID 24

CORE RULES:
1. When recommending any item from the catalog, you MUST append '[ADD_TO_CART:id]' immediately after the item name so the user can add it to their inquiry cart with a click. Example: 'I recommend renting our Adult Folding Chairs [ADD_TO_CART:4] and Round Fold-In-Half Tables [ADD_TO_CART:1].'
2. Always calculate realistic counts based on guest numbers. (e.g. 50 guests = 50 chairs, and about 6 to 8 rectangular/round tables).
3. If they rent 30 or more chairs, mention they automatically get the bulk rate of $1.50/chair instead of $2.
4. If a guest asks for something not in inventory, guide them politely and let them know we do custom orders or they can write us in the contact form.
5. Emphasize that deliveries are available throughout Ashburn, Aldie, Sterling, Leesburg, Chantilly, Fairfax, Loudoun County, and the DMV.
6. Provide helpful event advice and package estimations. Keep your replies concise and easy to read using markdown bullet points.";

// 5. Structure payload for Gemini API
$contents = [];

// Convert input history to Gemini API format (role must be 'user' or 'model')
foreach ($history as $msg) {
    $role = ($msg['role'] === 'user') ? 'user' : 'model';
    $contents[] = [
        'role' => $role,
        'parts' => [['text' => $msg['text']]]
    ];
}

// Add the current message
$contents[] = [
    'role' => 'user',
    'parts' => [['text' => $userMessage]]
];

$payload = [
    'contents' => $contents,
    'systemInstruction' => [
        'parts' => [['text' => $systemInstruction]]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 800
    ]
];

// 6. Execute cURL request to Gemini API (using gemini-2.0-flash)
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent";
$headers = [
    'Content-Type: application/json',
    'x-goog-api-key: ' . GEMINI_API_KEY
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 7. Process API response
if ($curlError) {
    echo json_encode([
        'error' => 'Network request failed',
        'response' => 'Sorry, I am having trouble connecting to my brain right now. Please try again in a moment!'
    ]);
    exit(0);
}

$responseDecoded = json_decode($response, true);

if ($httpStatus !== 200) {
    $errorMessage = isset($responseDecoded['error']['message']) ? $responseDecoded['error']['message'] : 'Unknown API Error';
    // Diagnostic: show key prefix (first 6 chars) + length to help debug
    $keyLen = strlen(GEMINI_API_KEY);
    $keyPreview = $keyLen > 6 ? substr(GEMINI_API_KEY, 0, 6) . '...' : '(empty)';
    echo json_encode([
        'error' => "Gemini API returned status $httpStatus",
        'debug' => $errorMessage,
        'response' => "⚠️ API Error ($httpStatus): $errorMessage | Key loaded: {$keyPreview} (length: {$keyLen})"
    ]);
    exit(0);
}

// Extract candidate text
if (isset($responseDecoded['candidates'][0]['content']['parts'][0]['text'])) {
    $botResponse = $responseDecoded['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode(['response' => $botResponse]);
} else {
    echo json_encode([
        'error' => 'Unexpected response structure',
        'debug' => $response,
        'response' => 'I received an empty response. Please ask me again.'
    ]);
}
