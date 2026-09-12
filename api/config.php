<?php
date_default_timezone_set('America/New_York');

/**
 * Petals Paradise Events API & Portal Config
 *
 * Credentials, Database, and API keys can be defined here or overridden via:
 * 1. api/secrets.php (gitignored local server file)
 * 2. Server Environment / Apache SetEnv / GitHub Secrets
 */

// 1. Default Configurations
$adminUser   = 'admin@petalsparadiseevents.com'; // Override this in api/secrets.php on your server
$adminPass   = 'ReplaceWithSecurePassword123!';  // Override this in api/secrets.php on your server
$adminSecret = 'ppe_admin_2026';
$notificationEmails = [
    'contact@petalsparadiseevents.com',
    'biragonimounika@gmail.com'
];
$apiKey = '';

// Database Defaults (Hostinger phpMyAdmin)
$dbHost  = 'localhost';
$dbName  = 'u704222898_ParadiseDB';
$dbUser  = '';
$dbPass  = '';
$dbTable = 'leads';

// 2. Load from gitignored secrets.php if present on server
$secretsFile = __DIR__ . '/secrets.php';
if (file_exists($secretsFile)) {
    $secrets = require $secretsFile;
    if (is_array($secrets)) {
        if (!empty($secrets['ADMIN_USER']))          $adminUser = $secrets['ADMIN_USER'];
        if (!empty($secrets['ADMIN_PASS']))          $adminPass = $secrets['ADMIN_PASS'];
        if (!empty($secrets['ADMIN_SECRET']))        $adminSecret = $secrets['ADMIN_SECRET'];
        if (!empty($secrets['NOTIFICATION_EMAILS']))  $notificationEmails = (array)$secrets['NOTIFICATION_EMAILS'];
        if (!empty($secrets['GEMINI_API_KEY']))      $apiKey = $secrets['GEMINI_API_KEY'];
        
        // Database secrets
        if (!empty($secrets['DB_HOST']))  $dbHost = $secrets['DB_HOST'];
        if (!empty($secrets['DB_NAME']))  $dbName = $secrets['DB_NAME'];
        if (!empty($secrets['DB_USER']))  $dbUser = $secrets['DB_USER'];
        if (!empty($secrets['DB_PASS']))  $dbPass = $secrets['DB_PASS'];
        if (!empty($secrets['DB_TABLE'])) $dbTable = $secrets['DB_TABLE'];
    }
}

// 3. Fallback check for Environment Variables
if (!empty($_SERVER['ADMIN_USER']))   $adminUser = $_SERVER['ADMIN_USER'];
if (!empty($_SERVER['ADMIN_PASS']))   $adminPass = $_SERVER['ADMIN_PASS'];
if (!empty($_SERVER['DB_USER']))     $dbUser = $_SERVER['DB_USER'];
if (!empty($_SERVER['DB_PASS']))     $dbPass = $_SERVER['DB_PASS'];
if (!empty($_SERVER['DB_NAME']))     $dbName = $_SERVER['DB_NAME'];

if (empty($apiKey)) {
    if (!empty($_SERVER['GEMINI_API_KEY'])) {
        $apiKey = $_SERVER['GEMINI_API_KEY'];
    } elseif (!empty($_ENV['GEMINI_API_KEY'])) {
        $apiKey = $_ENV['GEMINI_API_KEY'];
    } elseif (getenv('GEMINI_API_KEY')) {
        $apiKey = getenv('GEMINI_API_KEY');
    }
}

// 4. Global Constants
if (!defined('ADMIN_USER'))          define('ADMIN_USER', $adminUser);
if (!defined('ADMIN_PASS'))          define('ADMIN_PASS', $adminPass);
if (!defined('ADMIN_SECRET'))        define('ADMIN_SECRET', $adminSecret);
if (!defined('NOTIFICATION_EMAILS')) define('NOTIFICATION_EMAILS', json_encode($notificationEmails));
if (!defined('GEMINI_API_KEY'))      define('GEMINI_API_KEY', $apiKey);

if (!defined('DB_HOST'))  define('DB_HOST', $dbHost);
if (!defined('DB_NAME'))  define('DB_NAME', $dbName);
if (!defined('DB_USER'))  define('DB_USER', $dbUser);
if (!defined('DB_PASS'))  define('DB_PASS', $dbPass);
if (!defined('DB_TABLE')) define('DB_TABLE', $dbTable);

// Square Online Payment Constants
$squareToken = '';
$squareLocId = 'LV04RNB7PJKCA';

if (file_exists($secretsFile)) {
    $sec = require $secretsFile;
    if (is_array($sec)) {
        if (!empty($sec['SQUARE_ACCESS_TOKEN'])) $squareToken = $sec['SQUARE_ACCESS_TOKEN'];
        if (!empty($sec['SQUARE_LOCATION_ID']))  $squareLocId = $sec['SQUARE_LOCATION_ID'];
    }
}
if (!empty($_SERVER['SQUARE_ACCESS_TOKEN'])) $squareToken = $_SERVER['SQUARE_ACCESS_TOKEN'];
if (!empty($_SERVER['SQUARE_LOCATION_ID']))  $squareLocId = $_SERVER['SQUARE_LOCATION_ID'];

if (!defined('SQUARE_ACCESS_TOKEN'))     define('SQUARE_ACCESS_TOKEN',     $squareToken);
if (!defined('SQUARE_LOCATION_ID'))        define('SQUARE_LOCATION_ID',        $squareLocId);
if (!defined('SQUARE_PAYMENT_LINK'))       define('SQUARE_PAYMENT_LINK',       'https://square.link/u/xV2eBBtG?src=embed');
if (!defined('SQUARE_PAYMENT_URL'))        define('SQUARE_PAYMENT_URL',        'https://square.link/u/xV2eBBtG');
if (!defined('SQUARE_QR_CODE_DATA_URI'))   define('SQUARE_QR_CODE_DATA_URI',   'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHgAAAB4CAYAAAA5ZDbSAAAQAElEQVR4AezdBZzmVdUH8HseW1DHxh4be1VU7LHBxMYeFcUWGxvs7lYUsQtbLHAxsRu7u7Fb3ud7H3/P/PeZmRVmFl/ZXT+ePf8T99y+99xz7zyMjh7/74gjjjh6YWHh6B133PHo1tqqcPrTn/7oW9/61kf/+Mc/Hqea/P8rX/lK1x+m/d3vfteFO++8c5dd4hKX6Piggw7q/PzznOc8p/NveMMbdtbHP/7xTp/lLGfptH9Snp122qnLLnShC3W86667dswGvYA8pLna1a7WWSnfKU5xik6v9A9daa5whSt0m49//OO7Gtv4yevSl7505x+Tf9RJ2itf+crd5oMf/OBNkh166KGdf65znavjldpvmECba3t9wO5qwI6+1KfSj8aN2sYVaxs3bmx/+MMfxulW//8vfvGL9spXvrKNK9zGnbi64nbJFm0Bba3Ntb0+2Jxxfagv9am+HY1HVvvHP/7RznzmM7c3velN7Tvf+c6K8LWvfa099alPbSc84Qnb97///fbsZz+751NV7axnPWtPn7SXvexl2znPec52wAEHdFtnP/vZu85rX/vazr/RjW7U8bvf/e7O//3vf9/pBz7wgZ0+zWlO02k22AbjkdtlyiAftuAf/vCHXTc2P/nJT/Y8X/WqV/Xynfvc5+70Jz7xia7H5h//+Mcuyz9zc3Pd9m1uc5uue9e73rWLTnSiE3X+eJZ1/lve8pbO/9nPfja11Rnjf1Lnz3/+82OqtfGK0dOe5CQn6fikJz1p5x988ME97Ytf/OJu87DDDutY+dQTVFXXzT/aWptre/XXF+q+EuhDfalP9e1IgzA0Xo6aRpqfn2/zK8D5zne+dt/73rfd+c53pt6SbrwMNI38k5/8ZJpOYb773e82HcWWDqRjJOL/5je/aXD4Rh36l7/8ZbdllKKBdAAPPvWpT93zOcc5ztFx0sbmb3/7284/4xnP2Mupk5RhvOz3PNn817/+1WX556ijjur5nuxkJ+tpT3WqU3XR3//+987/85//3PlnOtOZOl/jsQM6Y/zPD37wg27/r3/965hqLXVDK/df/vKXzje4pFMf5Uo9dCw9oE278r//SVtre32gL6RdCfShvpRUupEGQlzykpeE2ste9rJ2latcZRO45jWv2T71qU91+aUudamOk66q2oYNG9oFL3jBaZo3v/nN7QMf+EB79KMf3Xl77bVXp8f7Z9cd70udfsxjHtOxmcPGeJ/t8vHe3fls4IMXvehFnQcr3+1vf/tu22wiH++PXW7UKuBnPvOZLr/JTW7S8c1vfvMuZ/PkJz85lSmYFfjqOWWOP/bYY4+eZryfdhuZ2VWTOsv3Kv9uKysD+gQnOME4Zet1ZxMPfPvb3+42DjnkkF5HM1ralA+mB6qq28g/aeu0vb5QVumHoO+kSV9KN8IAKdgLX/jCvh9bxwPve9/7mk6jNxpNkyCb0fa5z32uffnLX56m09gLCwtNI7NxhjOcoaHNBLoqh7ZPwJYU/J/+9KcN/sY3vtH1ydBg7Kh13he/+MXG5le/+tWOf/7zn/c0yr8wztPgUDCzkt5nP/vZBn/4wx/u6RfGOnTpBC52sYt1WWZ9+GY9fcstG+pDljp/blxvfPDNb36zl+Of//wnlWYwS4umZyDSM9PRVjx0yvfRj360pydjvxuZ+Sdtry/0ifRD0HeSDOu3aW+NpasZX41v2TrwwAPbc5/73AYDHTg21cbeaOcZyWacfYr8Ote5DvEUTnziE3dHz16t043iCOkDSzPeIx7xiG5TR9K90pWu1GmzXx72NnoXuMAFOt8qIv2znvWsRg4sdfC97nWvzht72pKsCinfec973q6jLGwCZQD77bdfz88ey3YGw9jz7fzLXOYyvY4p1y1vectOX/jCF+7yZzzjGZ1mq2eymX9W64uV+Ms6eDN2VxTZr253u9u12972tg0G9j3Kljj0xz72sV6JHXbYoetc5CIXIZ7C3/72t2aG2bthozhC6UGWVYMDba+la9ah//SnP/U88KQ18PA1JHzjG9+4y3XKa17zmv79zne+s2OzSZrVIOWzstBRFjaB/MB1r3vdHjeDWR7qQlfH0pubm+t1tCejL37xi3f661//ek93s5vdrNNsSbelYN0dnIJwIoxc4Bv/IQ95SJ8hRryRmZn9xje+sfO5/fQsvxrFzKI3nMHkgHPB9n3uc5+elndK9wtf+EKnzTI2rBL0HvCAB3R+Zql09MEuu+zSZ4sZieadyiMwWz626dGnw6GTB8AH+++/f8/PwEK/5z3v6TSfgN63vvWtnqclG/2GN7yh05ZyNoF0wPeWgmUdnE6YzWA1fvR4nBoY+Ma3V6A5GEZmOt7yhe+cRs/SbFQ769EbzmBy8PrXv77PNnuPtN8de+l0HRnQPHA2rBJoxw84s/R1r3vddIZwUqQ1I2FesTwCs+WbncFZLdiXHrz97W/v5bMXo9WBPKtX+N/73ve6nmMRPb5L8kWD0Kvh1fpiJf60gzlADD7taU9r9pMhPPaxj2177703cYteJ8b/aFijVzpOBfCNd/7zn787L7vttlu3edGLXnScorVx5KjT1772tTvNYaL/wQ9+sPPN1i4Y/IOnTPYweViaYUexYOoqiY6DZDuQ7kEPelC3Pfwmozs7g2OTc8im/V66O93pTsh2ylOecmoLH1gp4NnyWZ3w1V1epzvd6XqbOOrgLy4uNnV/5jOfObVpdeoZzfyTttcX+kT6IWh3SaLne6ShfLztbW+DGhf7kY98ZBuCpXZWL7QOlslTnvKUtnHjxg6+9xs7HfF4dSh7vFWZhN59992RTQfTt2zR05ldMPgHj8zetnHjxhav9Ne//nXPE6ZulSD/0Y9+1PlmuHQPe9jDpnXKNxnd2RnMFj4PnU0dzIbjHppXjR6CpRg9Wz6BFnxLN5vaC3YOxr/BDW7Q9hu31ZOe9KRp+WwJ8gmkrdNHaH0i/RD0nTRDvdHVr351vGaE3+1ud2uWlZWAC86ReNe73tX1k66q+l7ijLa4uNgWx+DbXnKNa1yj05bol7/85c0+JLElEJ0lWoXoZ4+zBJID+kDno5375CFAII2ZgDYzyAUS0Jwb8uxxtg1y4JtNMjqZwZZHcrbYONvZztbQHCf6OgetLGiABsqMVgc2DWY2eLbkHDN08jznONJHv2rSfo6WaCAKJw1HEp221vb6QF+s1Ed4+lBfJt3oyU9+crMHGsXPf/7zu2PACZiFu9zlLs1+JqGl1X7nWwU0jH3NQRv4xttnn30aWlhR5Zz1pOHEoONkze5xnBhyQB9wktAiNWwmjYYMTW7PQwscKEP2ODObHPhmk4yOuqMdscjZZsN+jhYqJDdQ0cqCBmigzOjs67uNtyU2rBLkzvjoy1/+8t0XEGakn/azJ6OBgIo08e61tTYn0wf6YrZ/QutD9dGn+nZk6bCUOi9e//rXbwsLC6vCLW5xi/bSl760cShy6K6aRHWc5xQA+OYJs2s5ssQtjO1aWsgtceQO5OTjm5KGFvBA84zpA/rATKUjDZottFEujU5DCzeiOWFocWT6Zil7wDceGR260kiLZgsttIqWF315o89znvM0coAGVZPokz13YVxXoUpy+zg6M5YttDYhd0xCX/GKV5RFh7Rf1cSmttbm2l4f0F8N9KG+1Pb6tjtZnAb70lvf+tZmOVoNXv3qV/cZLsNekvE/RiCP8Utf+tKYmvzfN96+++7bw3NmHZvCazRUnhxYcl/xilf0KI7Rj+bM0Af0gYag75iBtgej3/ve9/Y8Dj/88G7DrGRD4IXcrKOv89gDvvHI6NCVRlo0W2hHHbS86MsbbVaSAzTQDnTMIHk4QZBz2NDqRK4N0NqE3HKKNjPJQdovNvG0uVmqD+ivBvpQX+rTns4/Q7AsGNHfG7vzMKcBzhJkj0On0lXVb0uMFnzgW/Dc+Rc2y/BjSwPPz883cvi0pz1tg90YwdLQBymb/ZAsHqZVgB6MH1vBGpacbXZSn+E3GR26bFiS0fKA0fjk0mkD/JSTDA0swXQs78ps5IrH3q2DezZ5Lx98iGk/Th5bKbdgtka6rM11BvWtc/gJeXWRJ8sJ3vuuWe/1hKhQlvP6QnFoW91q1shm1HmBsQyiw9842kQ2KUCvjSwSJc9yCiH3/GOd/QrM6MS7cqLHuiZjP+xt5LZc8fk9LbGXoevXDAPEubVytuAZceyB4N8k9GhK43lEy3aBFvC8Tk20t3vfvfrt0u8bHxAD0THbFa+F7zgBb1O2g9tZrORNhCalP79738/8SaQcpmx0rjsgJOWrWECqxi5fRoWT4DBsg4eJtz+ffxvgWkHW2aqqlmCVcveYHZyBODHPe5x2M3VHto5rKqa0YU2a7vC+B/feI5F8Kc//ek+082IsbjZ26qq3fOe90Q2F+lV1cysqmq77LJL5w//SfmG3uZQ/oQnPKFVVeOsVFUTqiR3BFMGZUIPwb5HdtWrXrWzHU3QVhKYI1RV7YlPfGKXW7LxHe+qqufXBeN/nBzIctyxx1ZVv4QZixt/hFw4Fm11q6omMILml1RNbDrD01V2Mqsn+kMf+lBvR7bw+Q5V1Q466KDO54Thcwbpg2kHE2yHra8FVu1go7ZqaQSm6jzeqqUZwqmoqv5kJzrBRmdVNXHh8I4JHo7AqsmoNmuMyMwQey767ne/ezdpVKMzQzhbaPt81ablM5vJrD5VS+Vz3qyq7pVXVUtAg9NDXxlkxjFCD0FgpapaVpiUT5SuasmmBwtspHy5quSMxZ5vOmR4Vs+qpdWJ911VjW1yedHXNmg+ARqs2sGE2+H43wLTDj7iiCO612d02u/MDF6e0cAbsw+orr0F31mLntGNdnaDwY477ki1CfnRcfHPhqMVWuiN3v7jKzaKzoZooTiYV00foIH7XLTRC8drtf+wwbPE9w6JvnAfftXkGCeIgg9cKdJVTuVxxqRrj0WnfOLE6MwoOqsBD5xubEXPkRBfnvJWLnifcZRPGeRFl++DBr7xAkkTzBabzvH0RbboevCHJkeDaQdL4Fx21FFH9aOAyqO963HG4hRIoBHwXfTr/F/96lf9DCs2jA+qimoTu6XjWMGGgqMtwfScLymK06KF12DnTPoADaRDC9LD8oU5J2w4G6Jh+hocP0uWowc+yHlSudjNI7zZ8nFyyJMHe6uBwUs3tqLnZgcfLe/UUZ2Vl0NFJh0a+MYLqMswLT6bykvfeRnP8Q7tWRAaTDv4pje9ad977A0bNmxo7k/NFgVHZxQ7c+ELeOObwWgvLRgEvvHs4zxSAXi6Zj/aKwt6vGd6znuwMBzs6o0eoAfsh2jnS3jnnXfu4c2PfOQjvdxGrzwEL+i70GBL2I6+vRgNPBCky/MmO/jgg7sNgxmfLXoGADkbbAYMXHIQXspnj8VPndJ+Zhv+He5wh56XfZNtbYQvdi5vkL7AI3NygbW9/ETFpBVfgPkS5PJGKws9MO1ggQRHIiPD2FnncgAAEABJREFUYd1IQBsd6IxiBcZ3gY0vaoLmwjMIfOOJH4uZahC6lmy0UUzP6KXHWYDFT2HODD1AD1h20G5qYDOVTQ6TNCJJaDObvpUIX0fT54CgQUKSuQ9O/qkrW/TQ0roBYjNgppCD8FK+1GHWZtpNOFY6cra1EdpFjPIDgxYvD/KOPPLIhmZDfpbhhXG8O21h8pHLG19Z6IFpBxs5EvBCDzzwwGaPgF0/wUaTBJe73OUa2jkTNrrh4aM233huZ4xkqwLbLuPZcJuEb6bgW5JhXrd0RiY5oA/cB6Pd5MDCidLEtjdO0qocuZGMtv9LD+gDUSQyqw++B/foe9zjHr1ubnPQHiywRQ4/6lGPot7Pv+yAzhj/k/KxiW//ZsMgQqd8/A20crOpjeg508LAYKRj5YOtePiWXmmc38dZTv9vK6FHf8r898e0g40cV2fCXI4L3hHDHoPBbkmkcYGNFsqEZQ5zghQC+MbjNKEtT2y7p93f/wFAAP//WCM+VwAAAAZJREFUAwAP2A1wiDAQwQAAAABJRU5ErkJggg==');
if (!defined('SQUARE_QR_CODE_URL'))       define('SQUARE_QR_CODE_URL',       'https://petalsparadiseevents.com/square-qr-code.png');

/**
 * Helper: Generate Square Payment URL
 * (Uses Square API for exact prefilled payment link if token exists, or falls back to prefilled params)
 */
function generateSquarePaymentUrl($orderId, $finalTotalVal) {
    $token = defined('SQUARE_ACCESS_TOKEN') ? SQUARE_ACCESS_TOKEN : '';
    $locId = defined('SQUARE_LOCATION_ID') ? SQUARE_LOCATION_ID : 'LV04RNB7PJKCA';
    
    $formattedTotal = number_format((float)$finalTotalVal, 2, '.', '');
    $fallbackBase   = defined('SQUARE_PAYMENT_URL') ? SQUARE_PAYMENT_URL : 'https://square.link/u/xV2eBBtG';
    $fallbackUrl    = $fallbackBase . '?src=embed&amount=' . urlencode($formattedTotal) . '&total=' . urlencode($formattedTotal) . '&price=' . urlencode($formattedTotal);

    if (empty($token)) {
        return $fallbackUrl;
    }

    $amountCents = (int)round((float)$finalTotalVal * 100);
    $payload = [
        'idempotency_key' => 'ppe_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $orderId) . '_' . time(),
        'quick_pay' => [
            'name' => 'Petals Paradise Events - Order #' . $orderId,
            'price_money' => [
                'amount' => $amountCents,
                'currency' => 'USD'
            ],
            'location_id' => $locId
        ],
        'checkout_options' => [
            'redirect_url' => 'https://petalsparadiseevents.com/#confirmation'
        ]
    ];

    $ch = curl_init('https://connect.squareup.com/v2/online-checkout/payment-links');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Square-Version: 2026-08-01',
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && !empty($resp)) {
        $json = json_decode($resp, true);
        if (!empty($json['payment_link']['long_url'])) {
            return $json['payment_link']['long_url'];
        } elseif (!empty($json['payment_link']['url'])) {
            return $json['payment_link']['url'];
        }
    }

    return $fallbackUrl;
}

/**
 * PDO Database Helper Function
 * Connects to Hostinger MySQL Database and auto-creates the clean `leads` table.
 */
function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    
    $host   = DB_HOST;
    $dbname = DB_NAME;
    $user   = DB_USER;
    $pass   = DB_PASS;
    
    if (empty($dbname) || empty($user) || empty($pass)) return null;
    
    try {
        $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Synchronize MySQL session timezone to US Eastern Time
        @$pdo->exec("SET time_zone = '" . date('P') . "';");
        
        initLeadsTable($pdo);
        initOrdersTable($pdo);
        return $pdo;
    } catch (Exception $e) {
        return null; // Gracefully fall back to leads.json if DB connection isn't configured yet
    }
}

/**
 * Initializes the clean `leads` table with Primary Keys
 */
function initLeadsTable($pdo) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `leads` (
            `id` VARCHAR(64) PRIMARY KEY,
            `date_added` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) DEFAULT '',
            `phone` VARCHAR(64) DEFAULT '',
            `event_type` VARCHAR(128) DEFAULT '',
            `service_tier` VARCHAR(128) DEFAULT '',
            `guest_count` VARCHAR(64) DEFAULT '',
            `budget` VARCHAR(64) DEFAULT '',
            `event_date` VARCHAR(64) DEFAULT '',
            `location` TEXT,
            `source` VARCHAR(128) DEFAULT '',
            `notes` TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql);
    } catch (Exception $e) {
        // Silently continue
    }
}

/**
 * Initializes the clean `orders` table with Primary Keys
 */
function initOrdersTable($pdo) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `orders` (
            `id` VARCHAR(64) PRIMARY KEY,
            `date_added` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(64) NOT NULL,
            `event_date` VARCHAR(64) NOT NULL,
            `venue_location` VARCHAR(255) DEFAULT '',
            `fulfillment_method` VARCHAR(64) NOT NULL,
            `delivery_address` TEXT,
            `pickup_date` VARCHAR(64) DEFAULT '',
            `pickup_time` VARCHAR(64) DEFAULT '',
            `return_date` VARCHAR(64) DEFAULT '',
            `return_time` VARCHAR(64) DEFAULT '',
            `delivery_date` VARCHAR(64) DEFAULT '',
            `delivery_time` VARCHAR(64) DEFAULT '',
            `collection_date` VARCHAR(64) DEFAULT '',
            `collection_time` VARCHAR(64) DEFAULT '',
            `special_requests` TEXT,
            `items` TEXT NOT NULL,
            `subtotal` DECIMAL(10,2) NOT NULL,
            `discount` DECIMAL(10,2) DEFAULT 0.00,
            `delivery_fee` DECIMAL(10,2) DEFAULT 0.00,
            `setup_fee` DECIMAL(10,2) DEFAULT 0.00,
            `total` DECIMAL(10,2) NOT NULL,
            `status` VARCHAR(64) DEFAULT 'Pending',
            `payment_method` VARCHAR(128) DEFAULT 'Unpaid',
            `admin_notes` TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql);

        ensureOrderColumnsExist($pdo);
    } catch (Exception $e) {
        // Silently continue
    }
}

function ensureOrderColumnsExist($pdo) {
    if (!$pdo) return;
    try {
        $columns = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM `orders`");
        if ($stmt) {
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $c) {
                $columns[] = strtolower($c['Field']);
            }
        }
        if (!in_array('delivery_fee', $columns)) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `delivery_fee` DECIMAL(10,2) DEFAULT 0.00");
        }
        if (!in_array('setup_fee', $columns)) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `setup_fee` DECIMAL(10,2) DEFAULT 0.00");
        }
        if (!in_array('payment_method', $columns)) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `payment_method` VARCHAR(128) DEFAULT 'Unpaid'");
        }
        if (!in_array('admin_notes', $columns)) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `admin_notes` TEXT");
        }
        if (!in_array('promo_code', $columns)) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `promo_code` VARCHAR(128) DEFAULT ''");
        }
    } catch (Exception $e) {
        // Ignored
    }
}

/**
 * Formats any raw datetime string into US Eastern Time (America/New_York)
 */
function formatDateToEST($dateStr, $format = 'M d, Y g:i A') {
    if (empty($dateStr)) return '';
    try {
        if (strpos($dateStr, 'EDT') !== false || strpos($dateStr, 'EST') !== false) {
            return $dateStr;
        }
        $dt = new DateTime($dateStr, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('America/New_York'));
        return $dt->format($format) . ($format === 'M d, Y' ? '' : ' EDT');
    } catch (Exception $e) {
        return $dateStr;
    }
}
