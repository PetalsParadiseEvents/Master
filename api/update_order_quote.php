<?php
session_start();
/**
 * Order Quote & Delivery Fee Update API
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
    $adminUser = ADMIN_USER;
    $adminPass = ADMIN_PASS;
    $adminSecret = ADMIN_SECRET;
    $cookieHash = md5($adminUser . $adminPass . $adminSecret);

    $providedKey = isset($_GET['key']) ? $_GET['key'] : '';
    $isBypassed = (!empty($adminSecret) && $providedKey === $adminSecret);
    $isCookieValid = (isset($_COOKIE['ppe_auth']) && $_COOKIE['ppe_auth'] === $cookieHash);
    $isAuthenticated = $isBypassed || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || $isCookieValid;

    if (!$isAuthenticated) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized admin access.']);
        exit(0);
    }

    // 2. Parse Input Data
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $orderId     = isset($data['order_id']) ? trim($data['order_id']) : '';
    $deliveryFee = isset($data['delivery_fee']) ? floatval($data['delivery_fee']) : 0.00;
    $setupFee    = isset($data['setup_fee']) ? floatval($data['setup_fee']) : 0.00;
    $adminNotes  = isset($data['admin_notes']) ? trim($data['admin_notes']) : '';
    $notify      = isset($data['notify']) ? (bool)$data['notify'] : true;

    $hasDiscountInput = isset($data['discount']);
    $hasPromoInput    = isset($data['promo_code']);

    if (empty($orderId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID is required.']);
        exit(0);
    }

    $pdo = getDbConnection();
    $orderRecord = null;

    // 3. Update Database
    if ($pdo) {
        ensureOrderColumnsExist($pdo);
        try {
            $stmt = $pdo->prepare("SELECT * FROM `orders` WHERE `id` = :id LIMIT 1");
            $stmt->execute([':id' => $orderId]);
            $orderRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($orderRecord) {
                $subtotal  = floatval($orderRecord['subtotal'] ?? 0);
                $discount  = $hasDiscountInput ? max(0, floatval($data['discount'])) : floatval($orderRecord['discount'] ?? 0);
                $promoCode = $hasPromoInput ? trim(filter_var($data['promo_code'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : ($orderRecord['promo_code'] ?? '');
                $newTotal  = max(0, $subtotal - $discount + $deliveryFee + $setupFee);

                $updateStmt = $pdo->prepare("UPDATE `orders` SET `discount` = :discount, `promo_code` = :promo, `delivery_fee` = :fee, `setup_fee` = :setup, `admin_notes` = :notes, `total` = :total WHERE `id` = :id");
                $updateStmt->execute([
                    ':discount' => $discount,
                    ':promo'    => $promoCode,
                    ':fee'      => $deliveryFee,
                    ':setup'    => $setupFee,
                    ':notes'    => $adminNotes,
                    ':total'    => $newTotal,
                    ':id'       => $orderId
                ]);

                $orderRecord['discount']     = $discount;
                $orderRecord['promo_code']   = $promoCode;
                $orderRecord['delivery_fee'] = $deliveryFee;
                $orderRecord['setup_fee']    = $setupFee;
                $orderRecord['admin_notes']  = $adminNotes;
                $orderRecord['total']        = $newTotal;
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
                    $subtotal  = floatval($ord['subtotal'] ?? 0);
                    $discount  = $hasDiscountInput ? max(0, floatval($data['discount'])) : floatval($ord['discount'] ?? 0);
                    $promoCode = $hasPromoInput ? trim(filter_var($data['promo_code'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : ($ord['promo_code'] ?? '');
                    $newTotal  = max(0, $subtotal - $discount + $deliveryFee + $setupFee);

                    $ord['discount']     = $discount;
                    $ord['promo_code']   = $promoCode;
                    $ord['delivery_fee'] = $deliveryFee;
                    $ord['setup_fee']    = $setupFee;
                    $ord['admin_notes']  = $adminNotes;
                    $ord['total']        = $newTotal;

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

    // 4. Send Branded Updated Quote Email to Customer
    $emailSent = false;
    $customerEmail = $orderRecord['email'] ?? '';
    $customerName  = $orderRecord['name'] ?? 'Valued Customer';
    $eventDate     = $orderRecord['event_date'] ?? '';
    $fulfillment   = $orderRecord['fulfillment_method'] ?? 'Pickup';
    $status        = $orderRecord['status'] ?? 'Pending';
    
    $baseTotalVal  = floatval($orderRecord['total'] ?? 0);
    $onlineTaxVal  = round($baseTotalVal * 0.059, 2);
    $finalTotalVal = round($baseTotalVal + $onlineTaxVal, 2);

    $subtotalFmt   = number_format(floatval($orderRecord['subtotal'] ?? 0), 2);
    $discountFmt   = number_format(floatval($orderRecord['discount'] ?? 0), 2);
    $deliveryFmt   = number_format($deliveryFee, 2);
    $setupFmt      = number_format($setupFee, 2);
    $baseTotalFmt  = number_format($baseTotalVal, 2);
    $onlineTaxFmt  = number_format($onlineTaxVal, 2);
    $finalTotalFmt = number_format($finalTotalVal, 2);

    $squarePayUrl = function_exists('generateSquarePaymentUrl') ? generateSquarePaymentUrl($orderId, $finalTotalVal) : ((defined('SQUARE_PAYMENT_URL') ? SQUARE_PAYMENT_URL : 'https://square.link/u/xV2eBBtG') . '?src=embed&amount=' . urlencode($finalTotalFmt) . '&total=' . urlencode($finalTotalFmt) . '&price=' . urlencode($finalTotalFmt));

    $rawItems = $orderRecord['items'] ?? [];
    $itemsArr = is_string($rawItems) ? json_decode($rawItems, true) : (is_array($rawItems) ? $rawItems : []);
    $itemsHtml = "";
    if (is_array($itemsArr) && !empty($itemsArr)) {
        foreach ($itemsArr as $it) {
            $title = htmlspecialchars($it['title'] ?? 'Rental Item');
            $qty   = (int)($it['quantity'] ?? 1);
            $price = isset($it['price']) ? number_format((float)$it['price'], 2) : '0.00';
            $itemsHtml .= "<li style='margin-bottom: 4px;'><strong>{$qty}x</strong> {$title} (@ \${$price} each)</li>";
        }
    }

    if ($notify && !empty($customerEmail)) {
        $trackUrl = "https://petalsparadiseevents.com/#track";
        $subject  = "🌸 Updated Rental Quote for Order {$orderId}";

        $notesHtml = "";
        if (!empty($adminNotes)) {
            $notesHtml = "
            <div style='background: #fff8e6; border: 1px solid #ffe58f; padding: 15px; border-radius: 8px; margin: 18px 0;'>
                <h4 style='margin-top: 0; color: #d48806; font-size: 15px;'>💬 Note from Petals Paradise Events:</h4>
                <p style='margin: 0; color: #595959; font-size: 14px; white-space: pre-wrap;'>" . htmlspecialchars($adminNotes) . "</p>
            </div>";
        }

        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Updated Rental Quote</title>
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
                    <p style='margin:5px 0 0 0; color:#718096; font-size:14px;'>Updated Quote Notice</p>
                </div>
                
                <p>Hi <strong>" . htmlspecialchars($customerName) . "</strong>,</p>
                <p>Thank you for submitting your rental request! We have updated the delivery and setup quote for your event as detailed below.</p>
                
                {$notesHtml}

                <div class='box'>
                    <h3 style='margin-top:0; color:#2d3748; font-size:16px;'>💰 Updated Quote & Financial Breakdown</h3>
                    <table class='price-table'>
                        <tr>
                            <td>Items Subtotal:</td>
                            <td style='text-align: right; font-weight: bold;'>\${$subtotalFmt}</td>
                        </tr>" .
                        (floatval($orderRecord['discount'] ?? 0) > 0 || !empty($orderRecord['promo_code']) ? "
                        <tr style='color: #38a169;'>
                            <td>Coupon / Discount Applied" . (!empty($orderRecord['promo_code']) ? " (" . htmlspecialchars($orderRecord['promo_code']) . ")" : "") . ":</td>
                            <td style='text-align: right; font-weight: bold;'>-\${$discountFmt}</td>
                        </tr>" : "") . "
                        <tr>
                            <td>Delivery Fee:</td>
                            <td style='text-align: right; font-weight: bold; color: #d4af37;'>\${$deliveryFmt}</td>
                        </tr>
                        <tr>
                            <td>Setup & Installation Fee:</td>
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
                            <td style='padding-top: 10px;'>Final Amount Due (Online):</td>
                            <td style='text-align: right; padding-top: 10px; color: #006aff;'>\${$finalTotalFmt}</td>
                        </tr>
                    </table>
                </div>

                <!-- SQUARE ONLINE PAYMENT & QR CODE -->
                <div style='background: #f8fafc; border: 2px solid #006aff; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center;'>
                    <h3 style='margin-top:0; color:#006aff; font-size:17px;'>💳 Pay Online via Square</h3>
                    <p style='margin: 6px 0 15px 0; font-size: 14px; color: #475569;'>Click below or scan the QR code with your phone to pay securely using Credit Card, Debit Card, or Apple Pay.</p>
                    <div style='margin-bottom: 12px;'>
                        <a href='{$squarePayUrl}' target='_blank' style='display: inline-block; font-size: 18px; line-height: 48px; height: 48px; color: #ffffff !important; min-width: 212px; background-color: #006aff; text-align: center; box-shadow: 0 0 0 1px rgba(0,0,0,.1) inset; border-radius: 6px; text-decoration: none; font-weight: bold; padding: 0 24px;'>Pay now (\${$finalTotalFmt})</a>
                    </div>
                    <div style='margin: 0 auto 16px auto; max-width: 380px; font-size: 13px; color: #1e293b; font-weight: bold; background: #ffffff; border: 1px solid #bae6fd; padding: 10px 16px; border-radius: 8px;'>
                        📌 Amount to Enter on Square: <span style='color: #006aff; font-size: 15px; font-weight: 800;'>\${$finalTotalFmt}</span>
                        <div style='font-weight: normal; font-size: 12px; color: #64748b; margin-top: 2px;'>When Square opens, please enter <strong>\${$finalTotalFmt}</strong> in the \"Enter amount\" box.</div>
                    </div>
                    <div style='display: inline-block; background: #ffffff; padding: 10px; border-radius: 10px; border: 1px solid #cbd5e1;'>
                        <img src='" . SQUARE_QR_CODE_URL . "' alt='Scan to Pay via Square' width='160' height='160' style='display: block; border-radius: 6px;' />
                        <span style='font-size: 11px; color: #64748b; margin-top: 4px; display: block;'>Scan with Phone Camera</span>
                    </div>
                </div>

                <div class='box'>
                    <h3 style='margin-top:0; color:#2d3748; font-size:16px;'>🛍️ Requested Items</h3>
                    <ul style='padding-left: 20px; margin-bottom: 0;'>" . 
                    (!empty($itemsHtml) ? $itemsHtml : "<li>No item details listed.</li>") . "
                    </ul>
                </div>

                <div style='background: #f0fdf4; border: 1px solid #bbf7d0; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h4 style='margin-top: 0; color: #166534; font-size: 15px;'>📩 Questions or Concerns?</h4>
                    <p style='margin: 0; color: #15803d; font-size: 14px;'>
                        Please review your quote above. If you have any questions or concerns regarding the delivery or setup fees, simply reply directly to this email or call us at <strong>+1 848-448-6993</strong>.
                    </p>
                </div>

                <div style='text-align: center; margin: 25px 0;'>
                    <a href='{$trackUrl}' class='track-btn' target='_blank'>📦 Track Your Order Live</a>
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
        'delivery_fee' => $deliveryFee,
        'setup_fee'    => $setupFee,
        'admin_notes'  => $adminNotes,
        'new_total'    => floatval($orderRecord['total']),
        'email_sent'   => $emailSent
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server diagnostic error: ' . $e->getMessage()]);
}
