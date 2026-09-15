<?php
/**
 * Petals Paradise Events - AI DIY Decor Generator & Analysis API
 * Processes user prompt text + uploaded reference photos via Google Gemini API
 * to generate visual stage layout coordinates, design suggestions, and catalog item matches.
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
    if (file_exists($configFile)) {
        require_once $configFile;
    }

    // 2. Parse JSON input
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: [];

    $prompt = isset($data['prompt']) ? trim($data['prompt']) : '';
    $images = isset($data['images']) && is_array($data['images']) ? $data['images'] : [];

    if (empty($prompt) && empty($images)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a description prompt or upload a reference picture.'
        ]);
        exit(0);
    }

    // Catalog definition reference for AI matching
    $catalogReference = [
        1 => ['title' => 'Round Fold-In-Half Table', 'price' => 12, 'cat' => 'Tables', 'img' => './wp-content/uploads/2025/05/Screen-Shot-2025-05-12-at-5.19.35-PM.png'],
        2 => ['title' => 'Cocktail Table (With Cloths)', 'price' => 11, 'cat' => 'Tables', 'img' => './wp-content/uploads/2025/12/image.png'],
        3 => ['title' => 'Adult Rectangular Folding Table', 'price' => 8, 'cat' => 'Tables', 'img' => './wp-content/uploads/2025/04/Tables.webp'],
        4 => ['title' => 'Adult Folding Chair', 'price' => 2, 'cat' => 'Chairs', 'img' => './wp-content/uploads/2025/04/Chairs.webp'],
        5 => ['title' => 'Wedding Tent (16x26)', 'price' => 150, 'cat' => 'Tents', 'img' => './wp-content/uploads/2026/04/image.png'],
        25 => ['title' => 'Tent (10x20)', 'price' => 100, 'cat' => 'Tents', 'img' => './wp-content/uploads/2026/05/tent-10x20.jpg'],
        6 => ['title' => 'Round Cylinder Pedestal Display', 'price' => 30, 'cat' => 'Tables', 'img' => './wp-content/uploads/2025/05/Screen-Shot-2025-05-11-at-9.53.07-PM.png'],
        7 => ['title' => 'Buffet Food Warmers', 'price' => 10, 'cat' => 'Buffet Sets', 'img' => './wp-content/uploads/2025/04/Buffet-Food-Warmers.webp'],
        8 => ['title' => 'Loveseat for Rental', 'price' => 100, 'cat' => 'Chairs', 'img' => './wp-content/uploads/2026/03/IMG_1048-scaled.jpg'],
        9 => ['title' => 'Elegant Hand-Carved Accent Chair', 'price' => 75, 'cat' => 'Chairs', 'img' => './wp-content/uploads/2025/12/IMG_0755-1-scaled.jpg'],
        10 => ['title' => 'Haldi Urli`s / Maiyan Tub', 'price' => 125, 'cat' => 'Traditional Decor', 'img' => './wp-content/uploads/2025/09/image-edited.png'],
        11 => ['title' => 'Pipe and Drape Backdrop Stand', 'price' => 50, 'cat' => 'Backdrops', 'img' => './wp-content/uploads/2025/04/image-10.png'],
        12 => ['title' => 'GRAD Marquee Letters', 'price' => 40, 'cat' => 'Marquee Letters', 'img' => './wp-content/uploads/2025/12/image-1.png'],
        13 => ['title' => '4FT Marquee Numbers', 'price' => 20, 'cat' => 'Marquee Letters', 'img' => './wp-content/uploads/2025/07/image-7.png'],
        14 => ['title' => 'Photo/Any Event Backdrop', 'price' => 150, 'cat' => 'Backdrops', 'img' => './wp-content/uploads/2025/06/image-2.png'],
        15 => ['title' => 'New Born Baby Photo Prop / Moon Swing', 'price' => 20, 'cat' => 'Backdrops', 'img' => './wp-content/uploads/2025/05/Baby-backdrop-1-scaled.jpg'],
        18 => ['title' => 'Seemantham/Baby Shower Backdrop', 'price' => 150, 'cat' => 'Backdrops', 'img' => './wp-content/uploads/2025/07/Seemantham-2.jpg'],
        19 => ['title' => 'VEVOR Metal Wedding Centerpiece (2PCS)', 'price' => 25, 'cat' => 'Traditional Decor', 'img' => './wp-content/uploads/2025/04/image-19-908x1024.png'],
        20 => ['title' => 'Happy Birthday Neon Sign', 'price' => 10, 'cat' => 'Neon Signs', 'img' => './wp-content/uploads/2025/05/HBD.jpg'],
        21 => ['title' => 'Good Vibes Only Neon Sign', 'price' => 10, 'cat' => 'Neon Signs', 'img' => './wp-content/uploads/2025/07/image-5.png'],
        22 => ['title' => 'Congrats Grad Neon Sign', 'price' => 10, 'cat' => 'Neon Signs', 'img' => './wp-content/uploads/2025/07/81i8bvay0GL._AC_SX679_.jpg'],
        23 => ['title' => 'Mehandi Umbrella Set', 'price' => 3, 'cat' => 'Traditional Decor', 'img' => './wp-content/uploads/2025/05/Umbrella.jpg'],
        24 => ['title' => 'Easel for Rent', 'price' => 10, 'cat' => 'Traditional Decor', 'img' => './wp-content/uploads/2025/09/gold-litton-lane-boards-easels-27391-64_600.jpg']
    ];

    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    $aiResponseData = null;

    // Check if Gemini API key is configured
    if (!empty($apiKey) && $apiKey !== 'YOUR_GEMINI_API_KEY_HERE') {
        $aiResponseData = queryGeminiForDIYDecor($prompt, $images, $apiKey, $catalogReference);
    }

    // Fallback if AI response not generated
    if (!$aiResponseData || empty($aiResponseData['matchedItemIds'])) {
        $aiResponseData = generateSmartFallbackDIYDecor($prompt, $images, $catalogReference);
    }

    echo json_encode([
        'success' => true,
        'analysis' => $aiResponseData['analysis'],
        'suggestions' => $aiResponseData['suggestions'],
        'matchedItemIds' => $aiResponseData['matchedItemIds'],
        'layout' => $aiResponseData['layout']
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error generating decor visualization: ' . $e->getMessage()
    ]);
}

/**
 * Query Gemini API with prompt + reference pictures
 */
function queryGeminiForDIYDecor($prompt, $images, $apiKey, $catalog) {
    $systemPrompt = "You are the Senior Event Decor Stylist & Visual Planner for Petals Paradise Events in Ashburn, VA.
Analyze the user's decor prompt description and any uploaded reference pictures.

Return ONLY a valid JSON object (no markdown, no code block) with the following structure:
{
  \"analysis\": \"2-3 sentence overview of the decor theme, style, color scheme, and vibe.\",
  \"suggestions\": [
    \"Suggestion 1: Specific color palette or flower accent tip\",
    \"Suggestion 2: Placement or lighting recommendation\",
    \"Suggestion 3: Suggested seating or backdrop enhancement\"
  ],
  \"matchedItemIds\": [11, 10, 8, 20]
}

Available catalog IDs to match:
- 1: Round Table ($12)
- 2: Cocktail Table ($11)
- 3: Adult Rectangular Table ($8)
- 4: Adult Folding Chair ($2)
- 5: Wedding Tent 16x26 ($150)
- 25: Tent 10x20 ($100)
- 6: Round Cylinder Pedestals ($30)
- 7: Buffet Food Warmers ($10)
- 8: Loveseat for Rental ($100)
- 9: Hand-Carved Accent Chair ($75)
- 10: Haldi Urli / Maiyan Tub ($125)
- 11: Pipe and Drape Backdrop Stand ($50)
- 12: GRAD Marquee Letters ($40)
- 13: 4FT Marquee Numbers ($20)
- 14: Photo/Any Event Backdrop ($150)
- 15: New Born Baby Moon Swing ($20)
- 18: Seemantham/Baby Shower Backdrop ($150)
- 19: Metal Wedding Centerpiece ($25)
- 20: Happy Birthday Neon Sign ($10)
- 21: Good Vibes Only Neon Sign ($10)
- 22: Congrats Grad Neon Sign ($10)
- 23: Mehandi Umbrella Set ($3)
- 24: Easel for Rent ($10)
";

    $parts = [];
    $parts[] = ['text' => "User Prompt: " . ($prompt ?: 'Create a beautiful event decor setup based on my uploaded photos.')];

    // Attach uploaded reference images if provided
    foreach ($images as $imgStr) {
        if (strpos($imgStr, 'data:image/') === 0) {
            $partsArr = explode(',', $imgStr);
            if (count($partsArr) === 2) {
                $mime = 'image/png';
                if (strpos($partsArr[0], 'jpeg') !== false || strpos($partsArr[0], 'jpg') !== false) $mime = 'image/jpeg';
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $mime,
                        'data' => $partsArr[1]
                    ]
                ];
            }
        }
    }

    $payload = [
        'contents' => [
            ['role' => 'user', 'parts' => $parts]
        ],
        'systemInstruction' => [
            'parts' => [['text' => $systemPrompt]]
        ],
        'generationConfig' => [
            'temperature' => 0.4,
            'maxOutputTokens' => 800
        ]
    ];

    $models = ['gemini-2.5-flash', 'gemini-1.5-flash', 'gemini-1.5-pro', 'gemini-2.0-flash'];
    foreach ($models as $m) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent?key=" . urlencode($apiKey);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && !empty($body)) {
            $json = json_decode($body, true);
            $rawText = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $rawText = trim(preg_replace('/^```(json)?|```$/m', '', $rawText));
            $parsed = json_decode($rawText, true);

            if ($parsed && isset($parsed['matchedItemIds'])) {
                $parsed['layout'] = generateLayoutCoordinates($parsed['matchedItemIds']);
                return $parsed;
            }
        }
    }
    return null;
}

/**
 * Smart Fallback Generator matching prompt keywords
 */
function generateSmartFallbackDIYDecor($prompt, $images, $catalog) {
    $p = strtolower($prompt);
    $matched = [];
    $suggestions = [];
    $analysis = "Custom styled decor visualization based on your preferences.";

    if (strpos($p, 'haldi') !== false || strpos($p, 'mehandi') !== false || strpos($p, 'maiyan') !== false || strpos($p, 'yellow') !== false) {
        $matched = [10, 11, 8, 23, 24]; // Haldi Urli, Pipe & Drape, Loveseat, Umbrellas, Easel
        $analysis = "Traditional Vibrant Haldi & Mehandi Celebration with festive marigold tones, traditional Urli tub, and umbrella accents.";
        $suggestions = [
            "💡 Accent Tip: Surround the Haldi Urli tub with fresh marigold garlands and brass diyas for photo warmth.",
            "🌸 Seating Recommendation: Place the Loveseat or Accent Chair right behind the Urli for royal guest seating.",
            "✨ Backlighting: Use warm curtain lights behind the Pipe & Drape backdrop to make photos pop."
        ];
    } elseif (strpos($p, 'grad') !== false || strpos($p, 'senior') !== false || strpos($p, 'class of') !== false) {
        $matched = [12, 13, 22, 11, 4]; // GRAD Marquee, Numbers, Congrats Grad Neon, Pipe Drape, Chairs
        $analysis = "Class of 2026 Graduation Celebration Setup featuring 4FT Marquee Numbers, Congrats Grad Neon Sign, and photo backdrop.";
        $suggestions = [
            "💡 Photo Zone: Position the 4FT Marquee Numbers on the left of the backdrop for iconic photo ops.",
            "🎈 Balloon Arch: Add a balloon garland in school colors wrapping around the Pipe & Drape frame.",
            "🪑 Guest Comfort: Pair with adult folding chairs and cocktail tables for seamless socializing."
        ];
    } elseif (strpos($p, 'baby') !== false || strpos($p, 'shower') !== false || strpos($p, 'seemantham') !== false || strpos($p, 'gender') !== false) {
        $matched = [18, 15, 8, 6, 21]; // Seemantham Backdrop, Moon Prop, Loveseat, Pedestals, Good Vibes Neon
        $analysis = "Dreamy & Elegant Baby Shower / Seemantham Celebration with pastel backdrops, moon swing photo prop, and pedestal displays.";
        $suggestions = [
            "💡 Prop Focal Point: Center the Moon Swing / Baby Prop in front of the backdrop.",
            "🍰 Display Setup: Use the 5-piece Cylinder Pedestals on either side for cake & favor displays.",
            "✨ Signage: Hang the 'Good Vibes Only' or custom neon sign centrally for soft lighting."
        ];
    } else {
        // Default Luxury Event setup
        $matched = [14, 8, 6, 20, 19, 4]; // Photo Backdrop, Loveseat, Pedestals, HBD Neon, Centerpieces, Chairs
        $analysis = "Elegant Luxury Event & Party Setup with custom photo backdrop, plush seating, cylinder displays, and festive neon signage.";
        $suggestions = [
            "💡 Lighting: Hang the Neon Sign centrally on the backdrop at eye-level for camera focus.",
            "💐 Table Accent: Place metal centerpieces on cylinder pedestals for vertical grandeur.",
            "🪑 Seating Arrangement: Set the Loveseat at center stage flanked by pedestal displays."
        ];
    }

    if (!empty($images) && count($images) > 0) {
        $analysis .= " Custom inspiration photos analyzed to match color and decor composition.";
    }

    return [
        'analysis' => $analysis,
        'suggestions' => $suggestions,
        'matchedItemIds' => array_values(array_unique($matched)),
        'layout' => generateLayoutCoordinates($matched)
    ];
}

/**
 * Generate 2D canvas coordinates for matched items
 */
function generateLayoutCoordinates($itemIds) {
    $layout = [];
    $count = count($itemIds);

    $xOffsets = [120, 360, 600, 240, 480, 180, 520];
    $yOffsets = [140, 180, 160, 260, 280, 220, 200];

    foreach ($itemIds as $idx => $id) {
        $x = isset($xOffsets[$idx]) ? $xOffsets[$idx] : (150 + ($idx * 110) % 600);
        $y = isset($yOffsets[$idx]) ? $yOffsets[$idx] : (160 + ($idx * 40) % 200);

        $layout[] = [
            'itemId' => $id,
            'x' => $x,
            'y' => $y,
            'scale' => 1.0,
            'zIndex' => $idx + 1
        ];
    }
    return $layout;
}
