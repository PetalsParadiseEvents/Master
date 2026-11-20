<?php
/**
 * Admin: manually adjust a customer's loyalty points
 * POST JSON { customer_id, points (+/-), note }
 */
session_start();
require_once __DIR__ . '/loyalty.php';

header('Content-Type: application/json');

ppeRequireAdminApiAuth();

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
