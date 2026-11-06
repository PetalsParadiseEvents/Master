<?php
/**
 * Customer Accounts API — sign up, sign in, sign out, profile, password reset
 * Petals Paradise Events
 *
 * POST JSON { action: "signup"|"login"|"logout"|"forgot"|"reset", ... }
 * GET  ?action=me
 */

require_once __DIR__ . '/loyalty.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://petalsparadiseevents.com');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

ppeStartSession();

function out($data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$pdo = loyaltyDb();
if (!$pdo) out(['error' => 'Accounts are temporarily unavailable. Please try again later.'], 503);

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_GET['action'] ?? ($input['action'] ?? '');

function profilePayload(PDO $pdo, int $cid): array {
    $st = $pdo->prepare("SELECT `id`,`name`,`email`,`phone`,`points_balance`,`created_at` FROM `customers` WHERE `id` = ?");
    $st->execute([$cid]);
    $c = $st->fetch();
    if (!$c) return [];

    $l = $pdo->prepare("SELECT `order_id`,`type`,`points`,`note`,`created_at` FROM `points_ledger` WHERE `customer_id` = ? ORDER BY `id` DESC LIMIT 50");
    $l->execute([$cid]);

    $o = $pdo->prepare("SELECT `id`,`date_added`,`event_date`,`fulfillment_method`,`status`,`total`,`points_redeemed`,`points_earned`
                        FROM `orders` WHERE `customer_id` = ? ORDER BY `date_added` DESC LIMIT 50");
    $o->execute([$cid]);

    $balance = (int)$c['points_balance'];
    return [
        'customer' => [
            'name'  => $c['name'],
            'email' => $c['email'],
            'phone' => $c['phone'],
            'member_since' => $c['created_at'],
        ],
        'points' => [
            'balance'        => $balance,
            'redeemable'     => (int)(floor($balance / REDEEM_STEP_POINTS) * REDEEM_STEP_POINTS),
            'value_dollars'  => pointsToDollars((int)(floor($balance / REDEEM_STEP_POINTS) * REDEEM_STEP_POINTS)),
            'to_next_reward' => $balance >= MIN_REDEEM_POINTS ? (REDEEM_STEP_POINTS - ($balance % REDEEM_STEP_POINTS)) % REDEEM_STEP_POINTS : MIN_REDEEM_POINTS - $balance,
        ],
        'rules' => [
            'points_per_dollar'     => POINTS_PER_DOLLAR,
            'points_per_dollar_off' => POINTS_PER_DOLLAR_OFF,
            'min_redeem'            => MIN_REDEEM_POINTS,
            'redeem_step'           => REDEEM_STEP_POINTS,
            'max_redeem_pct'        => MAX_REDEEM_PCT,
        ],
        'history' => $l->fetchAll(),
        'orders'  => $o->fetchAll(),
    ];
}

switch ($action) {
    case 'signup': {
        if (tooManyAttempts($pdo, 20)) out(['error' => 'Too many attempts. Please wait 15 minutes.'], 429);
        recordAttempt($pdo, '');
        $name  = trim(strip_tags($input['name'] ?? ''));
        $email = strtolower(trim($input['email'] ?? ''));
        $phone = trim(strip_tags($input['phone'] ?? ''));
        $pass  = (string)($input['password'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) out(['error' => 'Please enter your name and a valid email.'], 400);
        if (strlen($pass) < 8) out(['error' => 'Password must be at least 8 characters.'], 400);

        $ex = $pdo->prepare("SELECT `id` FROM `customers` WHERE `email` = ?");
        $ex->execute([$email]);
        if ($ex->fetch()) out(['error' => 'An account with this email already exists. Please sign in.'], 409);

        $pdo->prepare("INSERT INTO `customers` (`name`,`email`,`phone`,`password_hash`) VALUES (?,?,?,?)")
            ->execute([$name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT)]);
        $cid = (int)$pdo->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['customer_id'] = $cid;

        $backfilled = 0;
        try { $backfilled = backfillCustomerOrders($cid, $email); } catch (Exception $e) {}
        out(['success' => true, 'backfilled_points' => $backfilled] + profilePayload($pdo, $cid));
    }

    case 'login': {
        if (tooManyAttempts($pdo)) out(['error' => 'Too many sign-in attempts. Please wait 15 minutes.'], 429);
        $email = strtolower(trim($input['email'] ?? ''));
        $pass  = (string)($input['password'] ?? '');
        $st = $pdo->prepare("SELECT `id`,`password_hash` FROM `customers` WHERE `email` = ?");
        $st->execute([$email]);
        $row = $st->fetch();
        if (!$row || !password_verify($pass, $row['password_hash'])) {
            recordAttempt($pdo, $email);
            out(['error' => 'Incorrect email or password.'], 401);
        }
        session_regenerate_id(true);
        $_SESSION['customer_id'] = (int)$row['id'];
        out(['success' => true] + profilePayload($pdo, (int)$row['id']));
    }

    case 'logout': {
        unset($_SESSION['customer_id']);
        out(['success' => true]);
    }

    case 'me': {
        if (empty($_SESSION['customer_id'])) out(['logged_in' => false]);
        $p = profilePayload($pdo, (int)$_SESSION['customer_id']);
        if (!$p) { unset($_SESSION['customer_id']); out(['logged_in' => false]); }
        out(['logged_in' => true] + $p);
    }

    case 'forgot': {
        if (tooManyAttempts($pdo, 10)) out(['error' => 'Too many requests. Please wait 15 minutes.'], 429);
        recordAttempt($pdo, '');
        $email = strtolower(trim($input['email'] ?? ''));
        $st = $pdo->prepare("SELECT `id`,`name` FROM `customers` WHERE `email` = ?");
        $st->execute([$email]);
        if ($c = $st->fetch()) {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare("UPDATE `customers` SET `reset_token` = ?, `reset_expires` = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE `id` = ?")
                ->execute([hash('sha256', $token), $c['id']]);
            $link = "https://petalsparadiseevents.com/#reset?token={$token}";
            $msg  = "<p>Hi " . htmlspecialchars($c['name']) . ",</p><p>Click below to reset your Petals Paradise Events password. This link expires in 1 hour.</p>"
                  . "<p><a href='{$link}' style='background:#d4af37;color:#fff;padding:12px 28px;border-radius:30px;text-decoration:none;font-weight:bold;'>Reset Password</a></p>"
                  . "<p>If you didn't request this, you can ignore this email.</p>";
            $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: Petals Paradise Events <contact@petalsparadiseevents.com>\r\n";
            @mail($email, '🌸 Reset your Petals Paradise password', $msg, $headers);
        }
        out(['success' => true, 'message' => 'If an account exists for that email, a reset link has been sent.']);
    }

    case 'reset': {
        $token = (string)($input['token'] ?? '');
        $pass  = (string)($input['password'] ?? '');
        if (strlen($pass) < 8) out(['error' => 'Password must be at least 8 characters.'], 400);
        $st = $pdo->prepare("SELECT `id` FROM `customers` WHERE `reset_token` = ? AND `reset_expires` > NOW()");
        $st->execute([hash('sha256', $token)]);
        $cid = (int)($st->fetchColumn() ?: 0);
        if (!$cid) out(['error' => 'This reset link is invalid or has expired.'], 400);
        $pdo->prepare("UPDATE `customers` SET `password_hash` = ?, `reset_token` = NULL, `reset_expires` = NULL WHERE `id` = ?")
            ->execute([password_hash($pass, PASSWORD_DEFAULT), $cid]);
        session_regenerate_id(true);
        $_SESSION['customer_id'] = $cid;
        out(['success' => true] + profilePayload($pdo, $cid));
    }

    default:
        out(['error' => 'Unknown action.'], 400);
}
