<?php
/**
 * Confidential Customer Lead Export API
 * Petals Paradise Events
 * 
 * Usage: https://petalsparadiseevents.com/api/leads_export.php?key=ppe_admin_2026
 */

$adminSecret = 'ppe_admin_2026';
$providedKey = isset($_GET['key']) ? $_GET['key'] : '';

if ($providedKey !== $adminSecret) {
    http_response_code(403);
    die('Unauthorized access. Invalid admin key.');
}

$leadsFile = __DIR__ . '/leads.json';
$leads = [];

if (file_exists($leadsFile)) {
    $fileContent = file_get_contents($leadsFile);
    $leads = json_decode($fileContent, true) ?: [];
}

// Optional test lead generator: ?key=ppe_admin_2026&test=1
if (isset($_GET['test']) && $_GET['test'] == '1') {
    $testLead = [
        'id'         => 'lead_sample_001',
        'date_added' => date('Y-m-d H:i:s'),
        'name'       => 'Sample Customer',
        'email'      => 'sample@example.com',
        'phone'      => '+1 848-448-6993',
        'event_type' => 'Graduation 2026',
        'event_date' => date('Y-m-d', strtotime('+30 days')),
        'location'   => 'Ashburn, VA',
        'source'     => 'FB Marketplace / Website',
        'notes'      => 'Sample lead generated for testing CSV download.'
    ];
    if (empty($leads)) {
        $leads = [$testLead];
        file_put_contents($leadsFile, json_encode($leads, JSON_PRETTY_PRINT));
    }
}

$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';

if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode($leads, JSON_PRETTY_PRINT);
    exit;
}

// Default CSV export for easy import into HubSpot / Brevo / Mailchimp / Excel
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Petals_Paradise_Leads_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// CSV Header
fputcsv($output, ['ID', 'Date Added', 'Name', 'Email', 'Phone', 'Event Type', 'Event Date', 'Location', 'Source', 'Notes']);

foreach ($leads as $lead) {
    fputcsv($output, [
        $lead['id'] ?? '',
        $lead['date_added'] ?? '',
        $lead['name'] ?? '',
        $lead['email'] ?? '',
        $lead['phone'] ?? '',
        $lead['event_type'] ?? '',
        $lead['event_date'] ?? '',
        $lead['location'] ?? '',
        $lead['source'] ?? '',
        $lead['notes'] ?? ''
    ]);
}

fclose($output);
exit;
