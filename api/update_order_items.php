<?php
session_start();
/**
 * Order Item Substitution & Management API
 * Petals Paradise Events
 */

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // 1. Admin Authentication
    $adminUser   = ADMIN_USER;
    $adminPass   = ADMIN_PASS;
    $adminSecret = ADMIN_SECRET;
    $cookieHash  = md5($adminUser . $adminPass . $adminSecret);

    $providedKey     = isset($_GET['key']) ? $_GET['key'] : '';
    $isBypassed      = (!empty($adminSecret) && $providedKey === $adminSecret);
    $isCookieValid   = (isset($_COOKIE['ppe_auth']) && $_COOKIE['ppe_auth'] === $cookieHash);
    $isAuthenticated = $isBypassed || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || $isCookieValid;

    if (!$isAuthenticated) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized admin access.']);
        exit(0);
    }

    // 2. Parse Input Data
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $orderId          = isset($data['order_id']) ? trim($data['order_id']) : '';
    $newItems         = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
    $substitutionNote = isset($data['substitution_note']) ? trim($data['substitution_note']) : '';
    $notify           = isset($data['notify']) ? (bool)$data['notify'] : true;

    if (empty($orderId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID is required.']);
        exit(0);
    }

    // Clean and validate items array
    $cleanedItems = [];
    $newSubtotal  = 0.00;
    foreach ($newItems as $it) {
        $title = isset($it['title']) ? trim(filter_var($it['title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : 'Rental Item';
        $qty   = isset($it['quantity']) ? max(1, (int)$it['quantity']) : 1;
        $price = isset($it['price']) ? max(0, (float)$it['price']) : 0.00;
        $id    = isset($it['id']) ? $it['id'] : mt_rand(100, 999);

        $itemTotal = $price * $qty;
        $newSubtotal += $itemTotal;

        $cleanedItems[] = [
            'id'       => $id,
            'title'    => $title,
            'price'    => $price,
            'quantity' => $qty
        ];
    }

    $pdo = getDbConnection();
    $orderRecord = null;

    // 3. Update Database Record
    if ($pdo) {
        ensureOrderColumnsExist($pdo);
        try {
            $stmt = $pdo->prepare("SELECT * FROM `orders` WHERE `id` = :id LIMIT 1");
            $stmt->execute([':id' => $orderId]);
            $orderRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($orderRecord) {
                $discount    = floatval($orderRecord['discount'] ?? 0);
                $deliveryFee = floatval($orderRecord['delivery_fee'] ?? 0);
                $setupFee    = floatval($orderRecord['setup_fee'] ?? 0);
                $newTotal    = max(0, $newSubtotal - $discount + $deliveryFee + $setupFee);
                $itemsJson   = json_encode($cleanedItems, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                $adminNotes = $orderRecord['admin_notes'] ?? '';
                if (!empty($substitutionNote)) {
                    $adminNotes = !empty($adminNotes) ? $adminNotes . "\n[Item Substitution Note]: " . $substitutionNote : "[Item Substitution Note]: " . $substitutionNote;
                }

                $updateStmt = $pdo->prepare("UPDATE `orders` SET `items` = :items, `subtotal` = :subtotal, `total` = :total, `admin_notes` = :notes WHERE `id` = :id");
                $updateStmt->execute([
                    ':items'    => $itemsJson,
                    ':subtotal' => $newSubtotal,
                    ':total'    => $newTotal,
                    ':notes'    => $adminNotes,
                    ':id'       => $orderId
                ]);

                $orderRecord['items']       = $cleanedItems;
                $orderRecord['subtotal']    = $newSubtotal;
                $orderRecord['total']       = $newTotal;
                $orderRecord['admin_notes'] = $adminNotes;
            }
        } catch (Exception $e) {
            // Fall through to JSON update
        }
    }

    // Update in orders.json backup file
    $ordersFile = __DIR__ . '/orders.json';
    if (file_exists($ordersFile)) {
        $fileContent = file_get_contents($ordersFile);
        if (!empty($fileContent)) {
            $ordersList = json_decode($fileContent, true) ?: [];
            foreach ($ordersList as &$ord) {
                if (isset($ord['id']) && $ord['id'] === $orderId) {
                    $discount    = floatval($ord['discount'] ?? 0);
                    $deliveryFee = floatval($ord['delivery_fee'] ?? 0);
                    $setupFee    = floatval($ord['setup_fee'] ?? 0);
                    $newTotal    = max(0, $newSubtotal - $discount + $deliveryFee + $setupFee);

                    $adminNotes = $ord['admin_notes'] ?? '';
                    if (!empty($substitutionNote)) {
                        $adminNotes = !empty($adminNotes) ? $adminNotes . "\n[Item Substitution Note]: " . $substitutionNote : "[Item Substitution Note]: " . $substitutionNote;
                    }

                    $ord['items']       = $cleanedItems;
                    $ord['subtotal']    = $newSubtotal;
                    $ord['total']       = $newTotal;
                    $ord['admin_notes'] = $adminNotes;

                    if (!$orderRecord) {
                        $orderRecord = $ord;
                    }
                }
            }
            file_put_contents($ordersFile, json_encode($ordersList, JSON_PRETTY_PRINT));
        }
    }

    if (!$orderRecord) {
        http_response_code(404);
        echo json_encode(['error' => "Order {$orderId} not found."]);
        exit(0);
    }

    // 4. Send Customer Substitution & Replacement Quote Email
    $emailSent = false;
    $customerEmail = $orderRecord['email'] ?? '';
    $customerName  = $orderRecord['name'] ?? 'Valued Customer';
    
    $subtotalFmt = number_format(floatval($orderRecord['subtotal'] ?? 0), 2);
    $discountFmt = number_format(floatval($orderRecord['discount'] ?? 0), 2);
    $deliveryFmt = number_format(floatval($orderRecord['delivery_fee'] ?? 0), 2);
    $setupFmt    = number_format(floatval($orderRecord['setup_fee'] ?? 0), 2);
    $totalFmt    = number_format(floatval($orderRecord['total'] ?? 0), 2);

    $itemsHtml = "";
    if (is_array($cleanedItems) && !empty($cleanedItems)) {
        foreach ($cleanedItems as $it) {
            $title = htmlspecialchars($it['title'] ?? 'Rental Item');
            $qty   = (int)($it['quantity'] ?? 1);
            $price = number_format((float)($it['price'] ?? 0), 2);
            $itemsHtml .= "<li style='margin-bottom: 6px;'><strong>{$qty}x</strong> {$title} (@ \${$price} each)</li>";
        }
    }

    if ($notify && !empty($customerEmail)) {
        $trackUrl = "https://petalsparadiseevents.com/#track";
        $subject  = "🌸 Important: Item Substitution & Updated Quote for Order {$orderId}";

        $subsitutionNoticeHtml = "";
        if (!empty($substitutionNote)) {
            $subsitutionNoticeHtml = "
            <div style='background: #fff8e6; border: 1px solid #ffe58f; padding: 18px; border-radius: 10px; margin: 20px 0;'>
                <h4 style='margin-top: 0; color: #d48806; font-size: 16px;'>🔄 Item Substitution & Replacement Note:</h4>
                <p style='margin: 0; color: #595959; font-size: 14px; white-space: pre-wrap;'>" . htmlspecialchars($substitutionNote) . "</p>
            </div>";
        }

        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Item Substitution Notification</title>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333333; line-height: 1.6; background-color: #f9f9f9; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; }
                .header { text-align: center; border-bottom: 2px solid #d4af37; padding-bottom: 15px; margin-bottom: 20px; }
                .header h2 { color: #1a202c; margin: 0; font-size: 22px; }
                .status-badge { display: inline-block; background: rgba(212, 175, 55, 0.15); color: #d4af37; border: 1px solid #d4af37; font-weight: bold; padding: 6px 18px; border-radius: 20px; margin-top: 10px; font-size: 14px; }
                .box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 18px; border-radius: 8px; margin: 18px 0; }
                .track-btn { display: inline-block; background-color: #d4af37; color: #ffffff !important; padding: 14px 32px; text-decoration: none; border-radius: 30px; font-weight: bold; font-size: 16px; margin: 15px 0; text-align: center; }
                .price-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                .price-table td { padding: 6px 0; }
                .price-table tr.total-row { border-top: 2px dashed #d4af37; font-size: 16px; font-weight: bold; }
                .footer { font-size: 13px; color: #718096; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 15px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🌸 Petals Paradise Events</h2>
                    <div class='status-badge'>Order ID: " . htmlspecialchars($orderId) . "</div>
                </div>
                
                <p>Hi <strong>" . htmlspecialchars($customerName) . "</strong>,</p>
                <p>Thank you for choosing Petals Paradise Events! We are reviewing your rental order items. Please review the item substitution details and updated rental quote below.</p>
                
                {$subsitutionNoticeHtml}

                <div class='box'>
                    <h3 style='margin-top:0; color:#2d3748; font-size:16px;'>🛍️ Updated Requested Items Lineup</h3>
                    <ul style='padding-left: 20px; margin-bottom: 0;'>" . 
                    (!empty($itemsHtml) ? $itemsHtml : "<li>No items listed.</li>") . "
                    </ul>
                </div>

                <div class='box'>
                    <h3 style='margin-top:0; color:#2d3748; font-size:16px;'>💰 Updated Quote Summary</h3>
                    <table class='price-table'>
                        <tr>
                            <td>Items Subtotal:</td>
                            <td style='text-align: right; font-weight: bold;'>\${$subtotalFmt}</td>
                        </tr>" .
                        (floatval($orderRecord['discount'] ?? 0) > 0 ? "
                        <tr style='color: #38a169;'>
                            <td>Discount Applied:</td>
                            <td style='text-align: right; font-weight: bold;'>-\${$discountFmt}</td>
                        </tr>" : "") . "
                        <tr>
                            <td>Delivery Fee:</td>
                            <td style='text-align: right; font-weight: bold;'>\${$deliveryFmt}</td>
                        </tr>
                        <tr>
                            <td>Setup & Installation Fee:</td>
                            <td style='text-align: right; font-weight: bold;'>\${$setupFmt}</td>
                        </tr>
                        <tr class='total-row'>
                            <td style='padding-top: 10px;'>Final Total Estimate:</td>
                            <td style='text-align: right; padding-top: 10px; color: #d4af37;'>\${$totalFmt}</td>
                        </tr>
                    </table>
                </div>

                <div style='background: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px; border-radius: 8px; margin: 20px 0;'>
                    <h4 style='margin-top: 0; color: #166534; font-size: 15px;'>📩 Please Confirm Your Replacement Items</h4>
                    <p style='margin: 0; color: #15803d; font-size: 14px;'>
                        Please review these suggested replacement items. If these replacements work for your event, simply reply to confirm or let us know if you prefer a different item! You can also call us anytime at <strong>+1 848-448-6993</strong>.
                    </p>
                </div>

                <div style='text-align: center; margin: 25px 0;'>
                    <a href='{$trackUrl}' class='track-btn' target='_blank'>📦 View & Confirm Replacement Quote Live</a>
                </div>

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
        'success'      => true,
        'order_id'     => $orderId,
        'items'        => $cleanedItems,
        'new_subtotal' => $newSubtotal,
        'new_total'    => floatval($orderRecord['total']),
        'email_sent'   => $emailSent
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server diagnostic error: ' . $e->getMessage()]);
}
