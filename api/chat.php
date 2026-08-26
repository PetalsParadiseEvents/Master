<?php
/**
 * Secure proxy endpoint for Petals Paradise Events AI Assistant.
 * Routes client queries to the Google Gemini 1.5 Flash API.
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // 1. Load config
    $configFile = __DIR__ . '/config.php';
    if (!file_exists($configFile)) {
        echo json_encode([
            'error' => 'Configuration file missing.',
            'response' => '🌸 Configuration file api/config.php not found.'
        ]);
        exit(0);
    }
    require_once $configFile;

    // 2. Validate API Key
    if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE' || empty(GEMINI_API_KEY)) {
        echo json_encode([
            'error' => 'API Key not configured.',
            'response' => '🌸 Welcome to Petals Paradise Events! My Gemini API Key is not yet configured. Please add your key in `api/config.php` or `api/secrets.php` to enable live AI responses.'
        ]);
        exit(0);
    }

    // 3. Parse JSON request body
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $userMessage = isset($data['message']) ? trim($data['message']) : '';
    $history = isset($data['history']) ? $data['history'] : [];

    if (empty($userMessage)) {
        echo json_encode([
            'error' => 'Empty message.',
            'response' => 'Please enter a question or event planning detail!'
        ]);
        exit(0);
    }

    // 4. System Instruction
    $systemInstruction = "You are the premium AI Event Decor Assistant & Order Agent for Petals Paradise Events, a luxury party rental and event decor boutique serving the DMV (DC, Maryland, Virginia) area. Your home base is Ashburn, VA.

Your tone should be warm, creative, helpful, and highly professional. You assist clients in planning events (weddings, graduations, baby showers, birthdays, South Asian traditional Haldi/Mehandi, housewarmings), recommending inventory, AND placing orders directly on their behalf!

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
3. If they rent 30 or more chairs, calculate the bulk rate of $1.50/chair instead of $2.
4. ORDER PLACEMENT PROTOCOL:
   - If the user asks you to 'place order', 'book', 'submit quote', or finalizes their event package:
     a) Confirm the exact list of items and quantities requested.
     b) Collect the REQUIRED customer details:
        1. Full Name
        2. Email Address
        3. Phone Number
        4. Event Date (e.g., YYYY-MM-DD or Month Day Year)
        5. Fulfillment Choice ('Delivery' or 'Pickup')
        6. Delivery Address (if Delivery is chosen)
     c) Once ALL required details are gathered, ALWAYS output the single structured payload tag at the VERY END of your response:
        [PLACE_ORDER:{\"name\":\"Customer Name\",\"email\":\"customer@email.com\",\"phone\":\"848-000-0000\",\"event_date\":\"2026-09-15\",\"fulfillment_method\":\"Delivery\",\"delivery_address\":\"123 Main St, Ashburn VA\",\"special_requests\":\"Notes here\",\"items\":[{\"id\":4,\"title\":\"Adult Folding Chair\",\"price\":1.50,\"quantity\":50},{\"id\":1,\"title\":\"Round Fold-In-Half Table\",\"price\":12,\"quantity\":6}]}]
5. Never invent or guess customer name, email, or phone. Politely ask the user to provide them if missing before outputting the [PLACE_ORDER] tag.
6. Emphasize that deliveries are available throughout Ashburn, Aldie, Sterling, Leesburg, Chantilly, Fairfax, Great Falls, Loudoun County, and the DMV.
7. Keep your replies warm, helpful, structured, and easy to read using markdown formatting.";

    // 5. Structure payload for Gemini API
    $contents = [];
    foreach ($history as $msg) {
        $role = (isset($msg['role']) && $msg['role'] === 'user') ? 'user' : 'model';
        $text = isset($msg['text']) ? $msg['text'] : '';
        if (!empty($text)) {
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $text]]
            ];
        }
    }

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
            'maxOutputTokens' => 1000
        ]
    ];

    // 6. Execute Dual Engine HTTP Request with automatic model fallback
    $modelsToTry = ['gemini-3.6-flash', 'gemini-3.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash-latest', 'gemini-2.5-flash', 'gemini-pro'];
    $apiResult = null;
    
    foreach ($modelsToTry as $modelName) {
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent";
        $apiResult = makeGeminiRequest($apiUrl, $payload, GEMINI_API_KEY);
        if ($apiResult['status'] === 200) {
            break;
        }
    }

    $httpStatus = $apiResult['status'];
    $rawResponseBody = $apiResult['body'];

    if (empty($rawResponseBody)) {
        echo json_encode([
            'error' => 'Empty response from Gemini API',
            'response' => '🌸 Sorry, I am having trouble contacting my AI brain right now. Please try again in a moment!'
        ]);
        exit(0);
    }

    $responseDecoded = json_decode($rawResponseBody, true);

    if ($httpStatus !== 200) {
        $errorMessage = isset($responseDecoded['error']['message']) ? $responseDecoded['error']['message'] : 'API Error ' . $httpStatus;
        echo json_encode([
            'error' => "Gemini API Status $httpStatus",
            'debug' => $errorMessage,
            'response' => "⚠️ AI API Notice ($httpStatus): $errorMessage. Please try again or call us at 848-448-6993."
        ]);
        exit(0);
    }

    if (isset($responseDecoded['candidates'][0]['content']['parts'][0]['text'])) {
        $botResponse = $responseDecoded['candidates'][0]['content']['parts'][0]['text'];
        echo json_encode(['response' => $botResponse]);
    } else {
        echo json_encode([
            'error' => 'Unexpected JSON structure',
            'response' => '🌸 I received an empty response. Please ask me again.'
        ]);
    }

} catch (Throwable $e) {
    echo json_encode([
        'error' => 'Fatal Server Error',
        'debug' => $e->getMessage() . ' on line ' . $e->getLine(),
        'response' => '🌸 Server Notice: ' . $e->getMessage()
    ]);
}

/**
 * Dual Engine HTTP Requester
 * Primary: cURL
 * Fallback: file_get_contents with stream_context
 */
function makeGeminiRequest($url, $payload, $apiKey) {
    $payloadJson = json_encode($payload);
    
    // Engine 1: cURL (if function is available)
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if (!$err && !empty($body)) {
            return ['status' => $status, 'body' => $body];
        }
    }

    // Engine 2: file_get_contents stream context fallback
    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n" .
                         "x-goog-api-key: " . $apiKey . "\r\n",
            'content' => $payloadJson,
            'timeout' => 15,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    
    $context = stream_context_create($opts);
    $body = @file_get_contents($url, false, $context);
    
    return ['status' => 200, 'body' => $body];
}
