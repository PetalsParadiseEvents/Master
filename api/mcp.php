<?php
/**
 * Minimal read-only MCP server (Streamable HTTP, JSON-RPC 2.0).
 * Exposes public business info only — no customer data, no writes.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Mcp-Session-Id, MCP-Protocol-Version');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Use POST with JSON-RPC 2.0']);
    exit;
}

$req = json_decode(file_get_contents('php://input'), true);
$id = $req['id'] ?? null;
$method = $req['method'] ?? '';

function reply($id, $result) { echo json_encode(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]); exit; }
function fail($id, $code, $msg) { echo json_encode(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $msg]]); exit; }

$business = [
    'name' => 'Petals Paradise Events',
    'description' => 'Party rentals and event decor in Ashburn, VA serving Loudoun County, Fairfax County and the DMV.',
    'phone' => '+1-848-448-6993',
    'email' => 'contact@petalsparadiseevents.com',
    'hours' => 'Monday–Sunday, 9 AM – 9 PM',
    'location' => 'Ashburn, VA 20148',
    'booking_url' => 'https://petalsparadiseevents.com/#rentals',
    'contact_url' => 'https://petalsparadiseevents.com/contact/',
];

$tools = [
    ['name' => 'get_business_info', 'description' => 'Contact details, hours, service area and booking links for Petals Paradise Events.',
     'inputSchema' => ['type' => 'object', 'properties' => new stdClass()]],
    ['name' => 'get_catalog_summary', 'description' => 'Markdown summary of services, rental inventory and pricing (llms.txt).',
     'inputSchema' => ['type' => 'object', 'properties' => new stdClass()]],
];

if ($id === null && str_starts_with($method, 'notifications/')) { http_response_code(202); exit; }

switch ($method) {
    case 'initialize':
        reply($id, [
            'protocolVersion' => $req['params']['protocolVersion'] ?? '2025-06-18',
            'capabilities' => ['tools' => ['listChanged' => false]],
            'serverInfo' => ['name' => 'petals-paradise-events', 'version' => '1.0.0'],
        ]);
    case 'ping':
        reply($id, new stdClass());
    case 'tools/list':
        reply($id, ['tools' => $tools]);
    case 'tools/call':
        $name = $req['params']['name'] ?? '';
        if ($name === 'get_business_info') {
            reply($id, ['content' => [['type' => 'text', 'text' => json_encode($business, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]]]);
        }
        if ($name === 'get_catalog_summary') {
            $md = @file_get_contents(__DIR__ . '/../llms.txt') ?: 'Unavailable';
            reply($id, ['content' => [['type' => 'text', 'text' => $md]]]);
        }
        fail($id, -32602, 'Unknown tool');
    default:
        fail($id, -32601, 'Method not found');
}
