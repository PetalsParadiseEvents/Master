<?php
/**
 * Loyalty Points & Customer Accounts — shared helpers
 * Petals Paradise Events
 *
 * Program rules (change here only):
 *   - Earn 10 points per $1 of final order total
 *   - 100 points = $1 off  (500 points = $5)
 *   - Minimum 500 points to redeem, in steps of 500
 *   - Points can cover at most 50% of the order subtotal
 *   - Credited when: Pickup order -> "Returned", Delivery order -> "Completed"
 *   - Redeemed points are refunded if the order is "Cancelled"
 */

require_once __DIR__ . '/config.php';

if (!defined('POINTS_PER_DOLLAR'))      define('POINTS_PER_DOLLAR', 10);
if (!defined('POINTS_PER_DOLLAR_OFF'))  define('POINTS_PER_DOLLAR_OFF', 100);
if (!defined('MIN_REDEEM_POINTS'))      define('MIN_REDEEM_POINTS', 500);
if (!defined('REDEEM_STEP_POINTS'))     define('REDEEM_STEP_POINTS', 500);
if (!defined('MAX_REDEEM_PCT'))         define('MAX_REDEEM_PCT', 0.5);
if (!defined('BACKFILL_MONTHS'))        define('BACKFILL_MONTHS', 12);

/** Start a hardened session (shared with admin session; customer uses its own key). */
function ppeStartSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function ensureLoyaltyTables(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `customers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `phone` VARCHAR(64) DEFAULT '',
            `password_hash` VARCHAR(255) NOT NULL,
            `points_balance` INT NOT NULL DEFAULT 0,
            `reset_token` VARCHAR(128) DEFAULT NULL,
            `reset_expires` DATETIME DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `points_ledger` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `customer_id` INT NOT NULL,
            `order_id` VARCHAR(64) DEFAULT NULL,
            `type` VARCHAR(16) NOT NULL,
            `points` INT NOT NULL,
            `note` VARCHAR(255) DEFAULT '',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_order_type` (`order_id`, `type`),
            KEY `idx_customer` (`customer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `ip` VARCHAR(64) NOT NULL,
            `email` VARCHAR(255) DEFAULT '',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_ip_time` (`ip`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $cols = array_map('strtolower', array_column($pdo->query("SHOW COLUMNS FROM `orders`")->fetchAll(), 'Field'));
        if (!in_array('customer_id', $cols))     $pdo->exec("ALTER TABLE `orders` ADD COLUMN `customer_id` INT DEFAULT NULL");
        if (!in_array('points_redeemed', $cols)) $pdo->exec("ALTER TABLE `orders` ADD COLUMN `points_redeemed` INT DEFAULT 0");
        if (!in_array('points_earned', $cols))   $pdo->exec("ALTER TABLE `orders` ADD COLUMN `points_earned` INT DEFAULT 0");
    } catch (Exception $e) {
        // Silently continue — loyalty features degrade gracefully
    }
}

function loyaltyDb(): ?PDO {
    $pdo = getDbConnection();
    if ($pdo) ensureLoyaltyTables($pdo);
    return $pdo;
}

/** Returns logged-in customer row or null. */
function currentCustomer(): ?array {
    ppeStartSession();
    if (empty($_SESSION['customer_id'])) return null;
    $pdo = loyaltyDb();
    if (!$pdo) return null;
    $st = $pdo->prepare("SELECT `id`,`name`,`email`,`phone`,`points_balance`,`created_at` FROM `customers` WHERE `id` = ?");
    $st->execute([(int)$_SESSION['customer_id']]);
    $row = $st->fetch();
    return $row ?: null;
}

/** Add a ledger row + adjust balance atomically. Returns false if duplicate (already applied). */
function addPoints(PDO $pdo, int $customerId, int $points, string $type, ?string $orderId, string $note = ''): bool {
    $ownTx = !$pdo->inTransaction();
    if ($ownTx) $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare("INSERT INTO `points_ledger` (`customer_id`,`order_id`,`type`,`points`,`note`) VALUES (?,?,?,?,?)");
        $ins->execute([$customerId, $orderId, $type, $points, $note]);
        $upd = $pdo->prepare("UPDATE `customers` SET `points_balance` = GREATEST(0, `points_balance` + ?) WHERE `id` = ?");
        $upd->execute([$points, $customerId]);
        if ($ownTx) $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($ownTx && $pdo->inTransaction()) $pdo->rollBack();
        if ($e->getCode() === '23000') return false; // duplicate (order_id,type) => already applied
        throw $e;
    }
}

function pointsForTotal(float $total): int {
    return max(0, (int)floor($total * POINTS_PER_DOLLAR));
}

function pointsToDollars(int $points): float {
    return round($points / POINTS_PER_DOLLAR_OFF, 2);
}

/**
 * Validate a redemption request. Returns [points, dollarsOff] or throws InvalidArgumentException.
 */
function validateRedemption(int $requested, int $balance, float $subtotal): array {
    if ($requested <= 0) return [0, 0.0];
    if ($requested < MIN_REDEEM_POINTS) throw new InvalidArgumentException('A minimum of ' . MIN_REDEEM_POINTS . ' points is required to redeem.');
    if ($requested % REDEEM_STEP_POINTS !== 0) throw new InvalidArgumentException('Points can be redeemed in steps of ' . REDEEM_STEP_POINTS . '.');
    if ($requested > $balance) throw new InvalidArgumentException('You do not have enough points.');
    $maxDollars = $subtotal * MAX_REDEEM_PCT;
    $dollars = pointsToDollars($requested);
    if ($dollars > $maxDollars + 0.001) throw new InvalidArgumentException('Points can cover at most ' . (int)(MAX_REDEEM_PCT * 100) . '% of your order.');
    return [$requested, $dollars];
}

/** Is this order eligible to earn points given its status + fulfillment? */
function orderEarnsAtStatus(string $fulfillment, string $status): bool {
    $isDelivery = stripos($fulfillment, 'deliver') !== false;
    return $isDelivery ? $status === 'Completed' : $status === 'Returned';
}

/**
 * Credit points for an order (idempotent). Links order to a customer by email if not linked.
 * Returns ['earned'=>int,'balance'=>int] or null if nothing credited.
 */
function creditOrderPoints(string $orderId): ?array {
    $pdo = loyaltyDb();
    if (!$pdo) return null;
    $st = $pdo->prepare("SELECT `id`,`email`,`total`,`customer_id`,`status` FROM `orders` WHERE `id` = ?");
    $st->execute([$orderId]);
    $order = $st->fetch();
    if (!$order) return null;

    $customerId = (int)($order['customer_id'] ?? 0);
    if (!$customerId && !empty($order['email'])) {
        $c = $pdo->prepare("SELECT `id` FROM `customers` WHERE LOWER(`email`) = LOWER(?)");
        $c->execute([$order['email']]);
        $customerId = (int)($c->fetchColumn() ?: 0);
        if ($customerId) $pdo->prepare("UPDATE `orders` SET `customer_id` = ? WHERE `id` = ?")->execute([$customerId, $orderId]);
    }
    if (!$customerId) return null;

    $earned = pointsForTotal((float)$order['total']);
    if ($earned <= 0) return null;
    if (!addPoints($pdo, $customerId, $earned, 'earn', $orderId, "Earned on order {$orderId}")) return null;
    $pdo->prepare("UPDATE `orders` SET `points_earned` = ? WHERE `id` = ?")->execute([$earned, $orderId]);

    $b = $pdo->prepare("SELECT `points_balance` FROM `customers` WHERE `id` = ?");
    $b->execute([$customerId]);
    return ['earned' => $earned, 'balance' => (int)$b->fetchColumn()];
}

/** Refund points redeemed on an order (idempotent). Returns refunded points or 0. */
function refundRedeemedPoints(string $orderId): int {
    $pdo = loyaltyDb();
    if (!$pdo) return 0;
    $st = $pdo->prepare("SELECT `customer_id`,`points_redeemed` FROM `orders` WHERE `id` = ?");
    $st->execute([$orderId]);
    $o = $st->fetch();
    $pts = (int)($o['points_redeemed'] ?? 0);
    $cid = (int)($o['customer_id'] ?? 0);
    if (!$o || $pts <= 0 || !$cid) return 0;
    return addPoints($pdo, $cid, $pts, 'refund', $orderId, "Refund for cancelled order {$orderId}") ? $pts : 0;
}

/** On signup: link past orders by email and credit eligible completed ones from the last N months. */
function backfillCustomerOrders(int $customerId, string $email): int {
    $pdo = loyaltyDb();
    if (!$pdo) return 0;
    $pdo->prepare("UPDATE `orders` SET `customer_id` = ? WHERE LOWER(`email`) = LOWER(?) AND (`customer_id` IS NULL OR `customer_id` = 0)")
        ->execute([$customerId, $email]);
    $st = $pdo->prepare("SELECT `id`,`fulfillment_method`,`status` FROM `orders`
                         WHERE `customer_id` = ? AND `date_added` >= DATE_SUB(NOW(), INTERVAL " . (int)BACKFILL_MONTHS . " MONTH)");
    $st->execute([$customerId]);
    $total = 0;
    foreach ($st->fetchAll() as $o) {
        if (orderEarnsAtStatus($o['fulfillment_method'] ?? '', $o['status'] ?? '')) {
            $r = creditOrderPoints($o['id']);
            if ($r) $total += $r['earned'];
        }
    }
    return $total;
}

/** Simple rate limit: max $max attempts per IP per 15 minutes. */
function tooManyAttempts(PDO $pdo, int $max = 10): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $st = $pdo->prepare("SELECT COUNT(*) FROM `login_attempts` WHERE `ip` = ? AND `created_at` > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $st->execute([$ip]);
    return (int)$st->fetchColumn() >= $max;
}

function recordAttempt(PDO $pdo, string $email): void {
    $pdo->prepare("INSERT INTO `login_attempts` (`ip`,`email`) VALUES (?,?)")->execute([$_SERVER['REMOTE_ADDR'] ?? 'unknown', $email]);
}
