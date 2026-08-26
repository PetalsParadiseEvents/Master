<?php
/**
 * Confidential Customer Lead Portal & Export API
 * Petals Paradise Events
 * 
 * Access via: https://petalsparadiseevents.com/api/leads_export.php
 * Requires Username & Password for authentication.
 */

// ═══════════════════════════════════════════════════════════
// 1. SECURITY & AUTHENTICATION CONFIGURATION
// ═══════════════════════════════════════════════════════════
$adminUser   = 'admin';               // Change username if desired
$adminPass   = 'ppe_password_2026';     // Change password if desired
$adminSecret = 'ppe_admin_2026';        // Optional API key parameter for automated scripts (?key=ppe_admin_2026)

// Extract credentials from standard PHP Auth or CGI environment header fallback
$authUser = $_SERVER['PHP_AUTH_USER'] ?? '';
$authPass = $_SERVER['PHP_AUTH_PW'] ?? '';

if (empty($authUser) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $decoded = base64_decode($matches[1]);
        if (strpos($decoded, ':') !== false) {
            list($authUser, $authPass) = explode(':', $decoded, 2);
        }
    }
}

// Check key bypass parameter for API calls, or enforce Username & Password HTTP Basic Auth
$providedKey = $_GET['key'] ?? '';
$isAuthenticated = ($providedKey === $adminSecret) || ($authUser === $adminUser && $authPass === $adminPass);

if (!$isAuthenticated) {
    header('WWW-Authenticate: Basic realm="Petals Paradise Admin Lead Portal"');
    header('HTTP/1.0 401 Unauthorized');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>401 Unauthorized - Lead Portal</title>
        <style>
            body { font-family: system-ui, -apple-system, sans-serif; background: #0d0f12; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1rem; }
            .auth-card { background: #1a1d24; border: 1px solid rgba(255,255,255,0.12); padding: 2.5rem; border-radius: 16px; max-width: 420px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
            h1 { color: #d4af37; font-size: 1.5rem; margin-top: 0; }
            p { color: #cbd5e1; font-size: 0.95rem; line-height: 1.6; }
            .btn { display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: #d4af37; color: #0d0f12; border-radius: 8px; font-weight: bold; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="auth-card">
            <h1>🔒 Restricted Lead Portal</h1>
            <p>Authentication required. Please enter valid admin username and password credentials to access customer leads.</p>
            <a href="javascript:location.reload()" class="btn">Login Again</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ═══════════════════════════════════════════════════════════
// 2. LOAD LEADS DATA
// ═══════════════════════════════════════════════════════════
$leadsFile = __DIR__ . '/leads.json';
$leads = [];

if (file_exists($leadsFile)) {
    $fileContent = file_get_contents($leadsFile);
    $leads = json_decode($fileContent, true) ?: [];
}

// Optional test lead generator: ?test=1
if (isset($_GET['test']) && $_GET['test'] == '1') {
    $testLead = [
        'id'           => 'lead_sample_' . time(),
        'date_added'   => date('Y-m-d H:i:s'),
        'name'         => 'Sample Customer',
        'email'        => 'sample@example.com',
        'phone'        => '+1 848-448-6993',
        'event_type'   => 'Graduation 2026',
        'event_date'   => date('Y-m-d', strtotime('+30 days')),
        'location'     => 'Ashburn, VA',
        'source'       => 'Website Contact',
        'notes'        => 'Sample lead generated for portal validation.'
    ];
    array_unshift($leads, $testLead);
    file_put_contents($leadsFile, json_encode($leads, JSON_PRETTY_PRINT));
}

// ═══════════════════════════════════════════════════════════
// 3. EXPORT / RESPONSE FORMAT HANDLER
// ═══════════════════════════════════════════════════════════
$format = isset($_GET['format']) ? strtolower($_GET['format']) : '';

// CSV Export Download
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Petals_Paradise_Leads_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Date Added', 'Name', 'Email', 'Phone', 'Event Type', 'Service Tier', 'Guest Count', 'Budget', 'Event Date', 'Location', 'Source', 'Notes']);
    
    foreach ($leads as $lead) {
        fputcsv($output, [
            $lead['id'] ?? '',
            $lead['date_added'] ?? '',
            $lead['name'] ?? '',
            $lead['email'] ?? '',
            $lead['phone'] ?? '',
            $lead['event_type'] ?? '',
            $lead['service_tier'] ?? '',
            $lead['guest_count'] ?? '',
            $lead['budget'] ?? '',
            $lead['event_date'] ?? '',
            $lead['location'] ?? '',
            $lead['source'] ?? '',
            $lead['notes'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// JSON API Output
if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode($leads, JSON_PRETTY_PRINT);
    exit;
}

// ═══════════════════════════════════════════════════════════
// 4. HTML ADMIN PORTAL DASHBOARD (Default Web Access)
// ═══════════════════════════════════════════════════════════
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petals Paradise Events - Lead Portal Admin</title>
    <style>
        :root {
            --bg: #0d0f12;
            --surface: #1a1d24;
            --surface-card: #222630;
            --primary: #d4af37;
            --primary-hover: #f1c40f;
            --text: #f8fafc;
            --text-muted: #cbd5e1;
            --border: rgba(255,255,255,0.12);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); padding: 2rem 1.5rem; min-height: 100vh; }
        .header { max-width: 1200px; margin: 0 auto 2rem auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 1.5rem; }
        .title-area h1 { font-family: Georgia, serif; color: var(--primary); font-size: 1.8rem; }
        .title-area p { color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem; }
        .action-btns { display: flex; gap: 0.75rem; }
        .btn { padding: 0.65rem 1.25rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer; }
        .btn-primary { background: var(--primary); color: #0d0f12; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { background: rgba(255,255,255,0.08); }
        
        .container { max-width: 1200px; margin: 0 auto; }
        .stats-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem; }
        .stat-value { font-size: 2rem; font-weight: 700; color: var(--primary); }
        .stat-label { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

        .search-bar { margin-bottom: 1rem; }
        .search-input { width: 100%; padding: 0.75rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-size: 0.95rem; }

        .table-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        th { background: rgba(212,175,55,0.1); color: var(--primary); padding: 1rem; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); }
        td { padding: 1rem; border-bottom: 1px solid var(--border); vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: rgba(255,255,255,0.02); }
        
        .badge { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: rgba(212,175,55,0.15); color: #f1c40f; border: 1px solid rgba(212,175,55,0.3); }
        .contact-info a { color: var(--primary); text-decoration: none; }
        .contact-info a:hover { text-decoration: underline; }
        .empty-state { padding: 4rem 2rem; text-align: center; color: var(--text-muted); }
    </style>
</head>
<body>

    <div class="header">
        <div class="title-area">
            <h1>🌸 Customer Lead Portal</h1>
            <p>Petals Paradise Events — Confidential Admin Database</p>
        </div>
        <div class="action-btns">
            <a href="?format=csv<?php echo $providedKey ? '&key='.$providedKey : ''; ?>" class="btn btn-primary">
                📥 Download CSV
            </a>
            <a href="?format=json<?php echo $providedKey ? '&key='.$providedKey : ''; ?>" class="btn btn-outline" target="_blank">
                JSON API
            </a>
        </div>
    </div>

    <div class="container">
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($leads); ?></div>
                <div class="stat-label">Total Received Leads</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php 
                    $latestDate = !empty($leads) ? date('M d, Y', strtotime($leads[0]['date_added'] ?? 'now')) : 'N/A';
                    echo $latestDate;
                    ?>
                </div>
                <div class="stat-label">Latest Lead Received</div>
            </div>
        </div>

        <div class="search-bar">
            <input type="text" id="leadSearch" class="search-input" placeholder="Search by name, email, phone, location, or event..." onkeyup="filterLeads()">
        </div>

        <div class="table-card">
            <div class="table-wrapper">
                <table id="leadsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Contact Info</th>
                            <th>Event Details</th>
                            <th>Location</th>
                            <th>Source</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leads)): ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    No customer leads captured yet. Leads submitted through your website forms will appear here automatically.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leads as $lead): ?>
                                <tr>
                                    <td style="white-space: nowrap; color: var(--text-muted); font-size: 0.85rem;">
                                        <?php echo htmlspecialchars($lead['date_added'] ?? ''); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($lead['name'] ?? 'N/A'); ?></strong>
                                    </td>
                                    <td class="contact-info">
                                        <?php if (!empty($lead['email'])): ?>
                                            <div>📧 <a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>"><?php echo htmlspecialchars($lead['email']); ?></a></div>
                                        <?php endif; ?>
                                        <?php if (!empty($lead['phone'])): ?>
                                            <div>📞 <a href="tel:<?php echo htmlspecialchars($lead['phone']); ?>"><?php echo htmlspecialchars($lead['phone']); ?></a></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge"><?php echo htmlspecialchars($lead['event_type'] ?? 'General'); ?></span>
                                        <?php if (!empty($lead['event_date'])): ?>
                                            <div style="font-size: 0.8rem; margin-top: 0.3rem; color: var(--text-muted);">🗓️ <?php echo htmlspecialchars($lead['event_date']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($lead['location'] ?? 'DMV Area'); ?></td>
                                    <td><span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($lead['source'] ?? 'Website'); ?></span></td>
                                    <td style="max-width: 250px; font-size: 0.85rem; color: var(--text-muted);"><?php echo nl2br(htmlspecialchars($lead['notes'] ?? '-')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function filterLeads() {
            const query = document.getElementById('leadSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#leadsTable tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
