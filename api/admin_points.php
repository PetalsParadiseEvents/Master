<?php
/**
 * Admin: manually adjust a customer's loyalty points
 * POST JSON { customer_id, points (+/-), note }
 */
session_start();
require_once __DIR__ . '/loyalty.php';

header('Content-Type: application/json');

$cookieHash = md5(ADMIN_USER . ADMIN_PASS . ADMIN_SECRET);
$isAuthed = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)
         || (isset($_COOKIE['ppe_auth']) && $_COOKIE['ppe_auth'] === $cookieHash);
if (!$isAuthed) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized admin access.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$cid    = (int)($data['customer_id'] ?? 0);
$points = (int)($data['points'] ?? 0);
$note   = trim(strip_tags($data['note'] ?? 'Manual adjustment by admin'));

$pdo = loyaltyDb();
if (!$pdo || !$cid || $points === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Customer and a non-zero points value are required.']);
    exit;
}

addPoints($pdo, $cid, $points, 'adjust', null, $note ?: 'Manual adjustment by admin');
$b = $pdo->prepare("SELECT `points_balance` FROM `customers` WHERE `id` = ?");
$b->execute([$cid]);
echo json_encode(['success' => true, 'balance' => (int)$b->fetchColumn()]);
