<?php
session_start();
/**
 * Leads & Orders Admin Dashboard Portal
 * Petals Paradise Events
 */

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';

// ═══════════════════════════════════════════════════════════
// 1. AUTHENTICATION (Session, Cookie, or URL Key Bypass)
// ═══════════════════════════════════════════════════════════
$adminUser   = ADMIN_USER;
$adminPass   = ADMIN_PASS;
$adminSecret = ADMIN_SECRET;
$cookieHash  = md5($adminUser . $adminPass . $adminSecret);

// Handle Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    setcookie('ppe_auth', '', time() - 3600, '/');
    header('Location: leads_export.php');
    exit;
}

// Handle Login Form Submission
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    if ($_POST['username'] === $adminUser && $_POST['password'] === $adminPass) {
        $_SESSION['admin_logged_in'] = true;
        setcookie('ppe_auth', $cookieHash, time() + (86400 * 30), '/'); // 30 days
        header('Location: leads_export.php' . (!empty($_GET['key']) ? '?key=' . urlencode($_GET['key']) : ''));
        exit;
    } else {
        $loginError = 'Invalid admin username or password.';
    }
}

// Verify Authentication
$providedKey = isset($_GET['key']) ? $_GET['key'] : '';
$isBypassed = (!empty($adminSecret) && $providedKey === $adminSecret);
$isCookieValid = (isset($_COOKIE['ppe_auth']) && $_COOKIE['ppe_auth'] === $cookieHash);
$isAuthenticated = $isBypassed || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || $isCookieValid;

if (!$isAuthenticated) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Petals Paradise Events</title>
        <style>
            :root {
                --bg: #0f172a;
                --surface: #1e293b;
                --primary: #d4af37;
                --text-primary: #f8fafc;
                --text-muted: #94a3b8;
                --border-color: #334155;
            }
            body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text-primary); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            .login-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 400px; padding: 2.5rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); }
            .logo-area { text-align: center; margin-bottom: 2rem; }
            .logo-area h1 { font-family: Georgia, serif; color: var(--primary); font-size: 1.8rem; margin-bottom: 0.25rem; }
            .logo-area p { color: var(--text-muted); font-size: 0.85rem; }
            .form-group { margin-bottom: 1.25rem; }
            .form-label { display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem; font-weight: 500; }
            .form-control { width: 100%; padding: 0.75rem 1rem; background: var(--bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-primary); font-size: 0.95rem; box-sizing: border-box; }
            .btn { width: 100%; padding: 0.75rem 1.25rem; background: var(--primary); color: #000; font-weight: 700; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; margin-top: 1rem; }
            .error-message { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 0.75rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.25rem; text-align: center; }
        </style>
    </head>
    <body>
        <div class="login-card">
            <div class="logo-area">
                <h1>🌸 Petals Paradise</h1>
                <p>Admin Dashboard Portal Access</p>
            </div>
            
            <?php if (!empty($loginError)): ?>
                <div class="error-message"><?php echo htmlspecialchars($loginError); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Username / Email</label>
                    <input type="email" name="username" class="form-control" placeholder="admin@example.com" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn">Sign In</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ═══════════════════════════════════════════════════════════
// 2. LOAD DATA (From MySQL Database or fallback to JSON files)
// ═══════════════════════════════════════════════════════════
$leads = [];
$orders = [];
$pdo = getDbConnection();
$dbConnected = false;

if ($pdo) {
    $dbConnected = true;
    ensureOrderColumnsExist($pdo);
    
    // Load Leads
    try {
        $stmt = $pdo->query("SELECT * FROM `leads` ORDER BY `date_added` DESC");
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        $dbConnected = false;
    }

    // Load Orders
    if ($dbConnected) {
        try {
            $stmt = $pdo->query("SELECT * FROM `orders` ORDER BY `date_added` DESC");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            $dbConnected = false;
        }
    }
}

// Fallback to JSON backup files if database connection unavailable
if (!$dbConnected) {
    $leadsFile = __DIR__ . '/leads.json';
    if (file_exists($leadsFile)) {
        $fileContent = file_get_contents($leadsFile);
        $leads = json_decode($fileContent, true) ?: [];
    }
    
    $ordersFile = __DIR__ . '/orders.json';
    if (file_exists($ordersFile)) {
        $fileContent = file_get_contents($ordersFile);
        $orders = json_decode($fileContent, true) ?: [];
    }
}

// Split orders into Active and Completed arrays
$activeOrders = [];
$completedOrders = [];

foreach ($orders as $ord) {
    $st = $ord['status'] ?? 'Pending';
    if ($st === 'Completed') {
        $completedOrders[] = $ord;
    } else {
        $activeOrders[] = $ord;
    }
}

// Sort Function: Chronologically by Event Date (YYYY-MM-DD), fallback to date_added DESC
$sortByEventDate = function($a, $b) {
    $dateA = !empty($a['event_date']) ? strtotime($a['event_date']) : 0;
    $dateB = !empty($b['event_date']) ? strtotime($b['event_date']) : 0;
    if ($dateA == $dateB) {
        return strtotime($b['date_added'] ?? 0) - strtotime($a['date_added'] ?? 0);
    }
    return $dateA - $dateB;
};

usort($activeOrders, $sortByEventDate);
usort($completedOrders, $sortByEventDate);

// Extract distinct Months for Event Month Filter (e.g., "August 2026", "September 2026")
$eventMonths = [];
foreach ($orders as $ord) {
    if (!empty($ord['event_date'])) {
        $time = strtotime($ord['event_date']);
        if ($time) {
            $monthVal = date('Y-m', $time);
            $monthLabel = date('F Y', $time);
            $eventMonths[$monthVal] = $monthLabel;
        }
    }
}
ksort($eventMonths);

// CSV Export Handler
$format = isset($_GET['format']) ? strtolower($_GET['format']) : '';
$type = isset($_GET['type']) ? strtolower($_GET['type']) : 'leads';

if ($format === 'csv') {
    if ($type === 'orders') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Petals_Paradise_Orders_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Order ID', 'Date Added', 'Name', 'Email', 'Phone', 'Event Date', 'Venue Location', 'Fulfillment Method', 'Delivery Address', 'Payment Method', 'Items', 'Subtotal', 'Discount', 'Delivery Fee', 'Setup Fee', 'Total', 'Status', 'Admin Notes']);
        foreach ($orders as $order) {
            $itemList = "";
            $itemsArr = is_string($order['items']) ? json_decode($order['items'], true) : ($order['items'] ?? []);
            if (is_array($itemsArr)) {
                foreach ($itemsArr as $it) {
                    $itemList .= ($it['quantity'] ?? 1) . "x " . ($it['title'] ?? 'Item') . "; ";
                }
            }
            fputcsv($output, [
                $order['id'] ?? '',
                $order['date_added'] ?? '',
                $order['name'] ?? '',
                $order['email'] ?? '',
                $order['phone'] ?? '',
                $order['event_date'] ?? '',
                $order['venue_location'] ?? '',
                $order['fulfillment_method'] ?? '',
                $order['delivery_address'] ?? '',
                $order['payment_method'] ?? 'Unpaid',
                $itemList,
                $order['subtotal'] ?? 0.00,
                $order['discount'] ?? 0.00,
                $order['delivery_fee'] ?? 0.00,
                $order['setup_fee'] ?? 0.00,
                $order['total'] ?? 0.00,
                $order['status'] ?? 'Pending',
                $order['admin_notes'] ?? ''
            ]);
        }
        fclose($output);
        exit;
    } else {
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
}

if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode(['leads' => $leads, 'orders' => $orders], JSON_PRETTY_PRINT);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petals Paradise Events - Admin Portal</title>
    <style>
        :root {
            --bg: #0b0f17;
            --surface: #151c28;
            --surface-card: #1e2736;
            --primary: #d4af37;
            --primary-hover: #f1c40f;
            --text-primary: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.12);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text-primary); padding: 2rem 1.5rem; min-height: 100vh; }
        .header { max-width: 1550px; margin: 0 auto 2rem auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem; }
        .title-area h1 { font-family: Georgia, serif; color: var(--primary); font-size: 1.8rem; }
        .title-area p { color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem; }
        .action-btns { display: flex; gap: 0.75rem; flex-wrap: wrap; }
        .btn { padding: 0.65rem 1.25rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.88rem; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer; }
        .btn-primary { background: var(--primary); color: #0d0f12; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); }
        .btn-outline:hover { background: rgba(255,255,255,0.08); }
        
        .container { max-width: 1550px; margin: 0 auto; }
        
        .tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); }
        .tab-btn { background: none; border: none; color: var(--text-muted); font-size: 1.05rem; font-weight: 600; padding: 0.75rem 1.5rem; cursor: pointer; transition: all 0.2s; border-bottom: 3px solid transparent; }
        .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .stats-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem; }
        .stat-value { font-size: 1.8rem; font-weight: 700; color: var(--primary); }
        .stat-label { font-size: 0.82rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

        .toolbar { display: flex; gap: 1rem; margin-bottom: 1.2rem; flex-wrap: wrap; align-items: center; }
        .search-input { flex: 1; min-width: 280px; padding: 0.75rem 1rem; background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-primary); font-size: 0.92rem; }
        .filter-select { padding: 0.75rem 1rem; background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; color: var(--primary); font-weight: 600; font-size: 0.9rem; cursor: pointer; }

        .table-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem; }
        th { background: rgba(212,175,55,0.1); color: var(--primary); padding: 0.9rem 1rem; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); }
        td { padding: 0.9rem 1rem; border-bottom: 1px solid var(--border-color); vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: rgba(255,255,255,0.02); }
        
        .badge { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: rgba(212,175,55,0.15); color: #f1c40f; border: 1px solid rgba(212,175,55,0.3); }
        .badge-pickup { background: rgba(52, 152, 219, 0.15); color: #3498db; border-color: rgba(52, 152, 219, 0.3); }
        .badge-delivery { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border-color: rgba(46, 204, 113, 0.3); }
        
        .contact-info a { color: var(--primary); text-decoration: none; }
        .contact-info a:hover { text-decoration: underline; }
        .empty-state { padding: 4rem 2rem; text-align: center; color: var(--text-muted); }
    </style>
</head>
<body>

    <div class="header">
        <div class="title-area">
            <h1>🌸 Petals Paradise Portal</h1>
            <p>Leads, Active Orders &amp; Completed Events Admin Control Center</p>
            <p style="font-size: 0.8rem; margin-top: 0.4rem;">
                <span class="badge" style="background: <?php echo $dbConnected ? 'rgba(46, 204, 113, 0.15)' : 'rgba(230, 126, 34, 0.15)'; ?>; color: <?php echo $dbConnected ? '#2ecc71' : '#e67e22'; ?>; border-color: <?php echo $dbConnected ? 'rgba(46, 204, 113, 0.3)' : 'rgba(230, 126, 34, 0.3)'; ?>;">
                    ● Connection: <?php echo $dbConnected ? 'MySQL Database (Live &amp; Synced)' : 'JSON Backup Files (Offline Fallback)'; ?>
                </span>
            </p>
        </div>
        <div class="action-btns">
            <a href="?format=csv&type=leads<?php echo !empty($providedKey) ? '&key=' . urlencode($providedKey) : ''; ?>" id="downloadLeadsCsvBtn" class="btn btn-primary">
                📥 Download Leads CSV
            </a>
            <a href="?format=csv&type=orders<?php echo !empty($providedKey) ? '&key=' . urlencode($providedKey) : ''; ?>" id="downloadOrdersCsvBtn" class="btn btn-primary" style="display:none;">
                📥 Download Orders CSV
            </a>
            <a href="?format=json<?php echo !empty($providedKey) ? '&key=' . urlencode($providedKey) : ''; ?>" class="btn btn-outline" target="_blank">
                JSON API
            </a>
            <a href="?logout=1" class="btn btn-outline" style="border-color: #ef4444; color: #ef4444;" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='transparent'">
                🚪 Sign Out
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Tab Navigation Bar -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('leads')">📋 Leads / Inquiries (<?php echo count($leads); ?>)</button>
            <button class="tab-btn" onclick="switchTab('orders')">📦 Active Orders (<?php echo count($activeOrders); ?>)</button>
            <button class="tab-btn" onclick="switchTab('completed')">✅ Completed Orders (<?php echo count($completedOrders); ?>)</button>
        </div>

        <!-- ──────────────── 1. LEADS TAB ──────────────── -->
        <div id="leads-tab" class="tab-content active">
            <div class="stats-bar">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($leads); ?></div>
                    <div class="stat-label">Total Leads Received</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $latestLead = !empty($leads) ? date('M d, Y', strtotime($leads[0]['date_added'] ?? 'now')) : 'N/A';
                        echo $latestLead;
                        ?>
                    </div>
                    <div class="stat-label">Latest Inquiry</div>
                </div>
            </div>

            <div class="toolbar">
                <input type="text" id="leadSearch" class="search-input" placeholder="🔍 Search leads by name, email, phone, location..." onkeyup="filterLeads()">
            </div>

            <div class="table-card">
                <div class="table-wrapper">
                    <table id="leadsTable">
                        <thead>
                            <tr>
                                <th>Date Received</th>
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
                                    <td colspan="7" class="empty-state">No customer inquiries submitted yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($leads as $lead): ?>
                                    <tr>
                                        <td style="white-space: nowrap; color: var(--text-muted); font-size: 0.82rem;">
                                            <?php echo htmlspecialchars(formatDateToEST($lead['date_added'] ?? '')); ?>
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
                                        <td style="max-width: 250px; font-size: 0.85rem; color: var(--text-muted); white-space: pre-wrap;"><?php echo htmlspecialchars($lead['notes'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ──────────────── 2. ACTIVE ORDERS TAB ──────────────── -->
        <div id="orders-tab" class="tab-content">
            <div class="stats-bar">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($activeOrders); ?></div>
                    <div class="stat-label">Active Event Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        $<?php 
                        $sum = 0;
                        foreach ($activeOrders as $o) { $sum += (float)($o['total'] ?? 0); }
                        echo number_format($sum, 2);
                        ?>
                    </div>
                    <div class="stat-label">Active Orders Est. Value</div>
                </div>
            </div>

            <div class="toolbar">
                <input type="text" id="orderSearch" class="search-input" placeholder="🔍 Search active orders by Order ID, name, email, phone, items..." onkeyup="filterOrders()">
                
                <select id="monthFilter" class="filter-select" onchange="filterOrders()">
                    <option value="all">📅 All Event Months</option>
                    <?php foreach ($eventMonths as $val => $label): ?>
                        <option value="<?php echo htmlspecialchars($val); ?>"><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php renderOrdersTable($activeOrders, 'ordersTable'); ?>
        </div>

        <!-- ──────────────── 3. COMPLETED ORDERS TAB ──────────────── -->
        <div id="completed-tab" class="tab-content">
            <div class="stats-bar">
                <div class="stat-card">
                    <div class="stat-value" style="color: #10b981;"><?php echo count($completedOrders); ?></div>
                    <div class="stat-label">Completed Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #10b981;">
                        $<?php 
                        $sumComp = 0;
                        foreach ($completedOrders as $o) { $sumComp += (float)($o['total'] ?? 0); }
                        echo number_format($sumComp, 2);
                        ?>
                    </div>
                    <div class="stat-label">Completed Total Revenue</div>
                </div>
            </div>

            <div class="toolbar">
                <input type="text" id="completedSearch" class="search-input" placeholder="🔍 Search completed orders by Order ID, name, email, phone, items..." onkeyup="filterOrders()">
                
                <select id="completedMonthFilter" class="filter-select" onchange="filterOrders()">
                    <option value="all">📅 All Event Months</option>
                    <?php foreach ($eventMonths as $val => $label): ?>
                        <option value="<?php echo htmlspecialchars($val); ?>"><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php renderOrdersTable($completedOrders, 'completedTable'); ?>
        </div>

    </div>

    <?php
    function renderOrdersTable($ordersList, $tableId) {
        ?>
        <div class="table-card">
            <div class="table-wrapper">
                <table id="<?php echo $tableId; ?>">
                    <thead>
                        <tr>
                            <th>Order ID / Date</th>
                            <th>Customer Details</th>
                            <th>Event Date &amp; Location</th>
                            <th>Fulfillment &amp; Logistics</th>
                            <th>Items Requested</th>
                            <th>Financial Breakdown</th>
                            <th>Payment Method (Admin)</th>
                            <th>Status &amp; Actions</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ordersList)): ?>
                            <tr>
                                <td colspan="9" class="empty-state">No orders in this category.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ordersList as $order): 
                                $eventDateRaw = $order['event_date'] ?? '';
                                $eventMonthVal = !empty($eventDateRaw) ? date('Y-m', strtotime($eventDateRaw)) : '';
                                ?>
                                <tr data-event-date="<?php echo htmlspecialchars($eventMonthVal); ?>">
                                    <td style="white-space: nowrap; font-size: 0.85rem;">
                                        <span style="color: var(--primary); font-weight:700; font-family:monospace;"><?php echo htmlspecialchars($order['id'] ?? 'PPE-N/A'); ?></span>
                                        <div style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem;">
                                            <?php echo htmlspecialchars(formatDateToEST($order['date_added'] ?? '')); ?>
                                        </div>
                                    </td>
                                    <td class="contact-info">
                                        <strong><?php echo htmlspecialchars($order['name'] ?? 'N/A'); ?></strong>
                                        <div>📧 <a href="mailto:<?php echo htmlspecialchars($order['email']); ?>"><?php echo htmlspecialchars($order['email']); ?></a></div>
                                        <div>📞 <a href="tel:<?php echo htmlspecialchars($order['phone']); ?>"><?php echo htmlspecialchars($order['phone']); ?></a></div>
                                    </td>
                                    <td>
                                        🗓️ <strong style="color: var(--primary);"><?php echo htmlspecialchars($order['event_date'] ?? 'N/A'); ?></strong>
                                        <div style="font-size: 0.8rem; margin-top:0.30rem; color:var(--text-muted);">
                                            📍 <?php echo htmlspecialchars($order['venue_location'] ?? 'Not Specified'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $currentFulfillment = $order['fulfillment_method'] ?? 'Pickup';
                                        $isDelivery = ($currentFulfillment === 'Delivery'); 
                                        ?>
                                        <div style="margin-bottom: 0.35rem;">
                                            <select class="form-control" style="font-size: 0.78rem; padding: 0.35rem 0.5rem; width: 100%; border-radius: 6px; cursor: pointer; background: var(--bg); color: <?php echo $isDelivery ? '#2ecc71' : '#3498db'; ?>; font-weight: bold; border: 1px solid var(--border-color);" onchange="changeFulfillmentMethod('<?php echo htmlspecialchars($order['id']); ?>', this.value)">
                                                <option value="Delivery" <?php echo $isDelivery ? 'selected' : ''; ?>>🚚 Delivery</option>
                                                <option value="Pickup" <?php echo !$isDelivery ? 'selected' : ''; ?>>📦 Pickup</option>
                                            </select>
                                        </div>
                                        <div id="fulfill-msg-<?php echo htmlspecialchars($order['id']); ?>" style="font-size: 0.72rem; margin-bottom: 0.3rem; display: none;"></div>
                                        
                                        <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
                                            <?php if ($isDelivery): ?>
                                                <strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address'] ?? 'N/A'); ?><br>
                                                <strong>Del:</strong> <?php echo htmlspecialchars($order['delivery_date'] ?? $order['delivery_date_manual'] ?? ''); ?> <?php echo htmlspecialchars($order['delivery_time'] ?? $order['delivery_time_manual'] ?? ''); ?><br>
                                                <strong>Coll:</strong> <?php echo htmlspecialchars($order['collection_date'] ?? $order['collection_date_manual'] ?? ''); ?> <?php echo htmlspecialchars($order['collection_time'] ?? $order['collection_time_manual'] ?? ''); ?>
                                            <?php else: ?>
                                                <strong>Pick:</strong> <?php echo htmlspecialchars($order['pickup_date'] ?? $order['pickup_date_manual'] ?? ''); ?> <?php echo htmlspecialchars($order['pickup_time'] ?? $order['pickup_time_manual'] ?? ''); ?><br>
                                                <strong>Ret:</strong> <?php echo htmlspecialchars($order['return_date'] ?? $order['dropoff_date_manual'] ?? ''); ?> <?php echo htmlspecialchars($order['return_time'] ?? $order['dropoff_time_manual'] ?? ''); ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="font-size: 0.85rem; line-height: 1.4; min-width: 175px;">
                                        <div id="items-list-<?php echo htmlspecialchars($order['id']); ?>">
                                        <?php 
                                        $itemsArr = is_string($order['items']) ? json_decode($order['items'], true) : ($order['items'] ?? []);
                                        if (is_array($itemsArr)): 
                                            foreach ($itemsArr as $it): 
                                                echo "• " . htmlspecialchars($it['quantity'] ?? 1) . "x " . htmlspecialchars($it['title'] ?? 'Item') . " (@ $" . number_format((float)($it['price'] ?? 0), 2) . ")<br>";
                                            endforeach;
                                        else:
                                            echo "-";
                                        endif;
                                        ?>
                                        </div>
                                        <button onclick="openSubstitutionModal('<?php echo htmlspecialchars($order['id']); ?>')" style="margin-top: 6px; font-size: 0.72rem; padding: 3px 8px; background: rgba(212,175,55,0.15); color: var(--primary); border: 1px solid rgba(212,175,55,0.4); border-radius: 4px; cursor: pointer; font-weight: bold; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                            🔄 Substitute / Edit Items
                                        </button>
                                    </td>
                                    <td style="white-space: nowrap; font-size: 0.85rem; line-height: 1.4; min-width: 170px;">
                                        Sub: $<?php echo htmlspecialchars(number_format((float)($order['subtotal'] ?? 0), 2)); ?><br>
                                        <?php if ((float)($order['discount'] ?? 0) > 0): ?>
                                            <span style="color: #38a169;">Disc: -$<?php echo htmlspecialchars(number_format((float)($order['discount'] ?? 0), 2)); ?></span><br>
                                        <?php endif; ?>
                                        <div style="margin-top: 0.3rem; margin-bottom: 0.2rem; display: flex; align-items: center; gap: 4px;">
                                            <span style="font-size: 0.75rem; color: var(--text-muted); width: 62px;">Del Fee: $</span>
                                            <input type="number" step="0.01" min="0" id="delivery-fee-<?php echo htmlspecialchars($order['id']); ?>" value="<?php echo htmlspecialchars(number_format((float)($order['delivery_fee'] ?? 0), 2)); ?>" oninput="recalcOrderTotal('<?php echo htmlspecialchars($order['id']); ?>', <?php echo (float)($order['subtotal'] ?? 0); ?>, <?php echo (float)($order['discount'] ?? 0); ?>)" style="width: 75px; padding: 2px 4px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: var(--bg); color: #d4af37; font-weight: bold;">
                                        </div>
                                        <div style="margin-bottom: 0.3rem; display: flex; align-items: center; gap: 4px;">
                                            <span style="font-size: 0.75rem; color: var(--text-muted); width: 62px;">Setup Fee: $</span>
                                            <input type="number" step="0.01" min="0" id="setup-fee-<?php echo htmlspecialchars($order['id']); ?>" value="<?php echo htmlspecialchars(number_format((float)($order['setup_fee'] ?? 0), 2)); ?>" oninput="recalcOrderTotal('<?php echo htmlspecialchars($order['id']); ?>', <?php echo (float)($order['subtotal'] ?? 0); ?>, <?php echo (float)($order['discount'] ?? 0); ?>)" style="width: 75px; padding: 2px 4px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: var(--bg); color: #d4af37; font-weight: bold;">
                                        </div>
                                        <strong>Total: <span id="total-display-<?php echo htmlspecialchars($order['id']); ?>" style="color: #d4af37;">$<?php echo htmlspecialchars(number_format((float)($order['total'] ?? 0), 2)); ?></span></strong>
                                        
                                        <div style="margin-top: 0.5rem;">
                                            <textarea id="admin-notes-<?php echo htmlspecialchars($order['id']); ?>" placeholder="Notes / quote message to customer..." style="width: 100%; min-height: 38px; font-size: 0.75rem; padding: 4px; border-radius: 4px; border: 1px solid var(--border-color); background: var(--bg); color: var(--text-primary); margin-bottom: 4px; resize: vertical;"><?php echo htmlspecialchars($order['admin_notes'] ?? ''); ?></textarea>
                                            <button onclick="updateOrderQuote('<?php echo htmlspecialchars($order['id']); ?>')" style="width: 100%; font-size: 0.75rem; padding: 4px 8px; background: #d4af37; color: #000; font-weight: bold; border-radius: 4px; border: none; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">✉️ Update Quote & Email</button>
                                            <div id="quote-msg-<?php echo htmlspecialchars($order['id']); ?>" style="font-size: 0.72rem; margin-top: 0.25rem; display: none;"></div>
                                        </div>
                                    </td>

                                    <!-- Admin Only Payment Method Dropdown -->
                                    <td style="white-space: nowrap; font-size: 0.8rem; width: 140px;">
                                        <?php $currentPayment = $order['payment_method'] ?? 'Unpaid'; ?>
                                        <select class="form-control" style="font-size: 0.78rem; padding: 0.4rem; width: 100%; border-radius: 6px; cursor: pointer; background: var(--bg); color: #38bdf8; font-weight: 600; border: 1px solid var(--border-color);" onchange="updatePaymentMethod('<?php echo htmlspecialchars($order['id']); ?>', this.value)">
                                            <?php
                                            $payMethods = [
                                                'Unpaid'                           => '💳 Unpaid',
                                                'Cash'                             => '💵 Cash',
                                                'Online (Zelle / Venmo / CashApp)' => '📲 Online (Zelle/Venmo)',
                                                'Credit / Debit Card'             => '💳 Credit/Debit Card',
                                                'Partial Deposit Paid'             => '💰 Partial Deposit Paid'
                                            ];
                                            foreach ($payMethods as $val => $lbl):
                                                $sel = ($val === $currentPayment) ? 'selected' : '';
                                                echo "<option value=\"{$val}\" {$sel}>{$lbl}</option>";
                                            endforeach;
                                            ?>
                                        </select>
                                        <div id="pay-msg-<?php echo htmlspecialchars($order['id']); ?>" style="font-size: 0.72rem; margin-top: 0.25rem; color: #38bdf8; display: none;"></div>
                                    </td>

                                    <td style="white-space: nowrap; width: 160px;">
                                        <?php
                                        $currentStatus = $order['status'] ?? 'Pending';
                                        $statusColors = [
                                            'Pending'          => 'background: rgba(245, 158, 11, 0.15); color: #f59e0b; border-color: rgba(245, 158, 11, 0.4);',
                                            'Confirmed'        => 'background: rgba(59, 130, 246, 0.15); color: #3b82f6; border-color: rgba(59, 130, 246, 0.4);',
                                            'Order Picked Up'  => 'background: rgba(139, 92, 246, 0.15); color: #8b5cf6; border-color: rgba(139, 92, 246, 0.4);',
                                            'Out for Delivery' => 'background: rgba(99, 102, 241, 0.15); color: #6366f1; border-color: rgba(99, 102, 241, 0.4);',
                                            'Delivered'        => 'background: rgba(16, 185, 129, 0.15); color: #10b981; border-color: rgba(16, 185, 129, 0.4);',
                                            'Returned'         => 'background: rgba(100, 116, 139, 0.15); color: #94a3b8; border-color: rgba(100, 116, 139, 0.4);',
                                            'Completed'        => 'background: rgba(16, 185, 129, 0.25); color: #34d399; border-color: #10b981;',
                                            'Cancelled'        => 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border-color: rgba(239, 68, 68, 0.4);'
                                        ];
                                        $badgeStyle = $statusColors[$currentStatus] ?? $statusColors['Pending'];
                                        ?>
                                        <div style="margin-bottom: 0.4rem;">
                                            <span class="badge" id="badge-<?php echo htmlspecialchars($order['id']); ?>" style="<?php echo $badgeStyle; ?>">
                                                ● <?php echo htmlspecialchars($currentStatus); ?>
                                            </span>
                                        </div>
                                        
                                        <select class="form-control" style="font-size: 0.8rem; padding: 0.4rem 0.5rem; min-height: auto; width: 100%; border-radius: 6px; cursor: pointer; background: var(--bg); color: var(--text-primary); border: 1px solid var(--border-color);" onchange="changeOrderStatus('<?php echo htmlspecialchars($order['id']); ?>', this.value)">
                                            <?php 
                                            $statuses = ['Pending', 'Confirmed', 'Order Picked Up', 'Out for Delivery', 'Delivered', 'Returned', 'Completed', 'Cancelled'];
                                            foreach ($statuses as $st): 
                                                $selected = ($st === $currentStatus) ? 'selected' : '';
                                                echo "<option value=\"{$st}\" {$selected}>{$st}</option>";
                                            endforeach;
                                            ?>
                                        </select>
                                        <div id="status-msg-<?php echo htmlspecialchars($order['id']); ?>" style="font-size: 0.72rem; margin-top: 0.3rem; color: #10b981; display: none;"></div>
                                    </td>
                                    <td style="max-width: 180px; font-size: 0.8rem; color: var(--text-muted); white-space: pre-wrap;"><?php echo htmlspecialchars($order['special_requests'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php
    }
    ?>

    <script>
        function recalcOrderTotal(orderId, subtotal, discount) {
            const delInput = document.getElementById('delivery-fee-' + orderId);
            const setupInput = document.getElementById('setup-fee-' + orderId);
            const totalDisplay = document.getElementById('total-display-' + orderId);
            if (!totalDisplay) return;

            const delFee = delInput ? (parseFloat(delInput.value) || 0) : 0;
            const setupFee = setupInput ? (parseFloat(setupInput.value) || 0) : 0;
            const calcTotal = Math.max(0, subtotal - discount + delFee + setupFee);

            totalDisplay.innerText = '$' + calcTotal.toFixed(2);
        }

        async function updateOrderQuote(orderId) {
            const delInput = document.getElementById('delivery-fee-' + orderId);
            const setupInput = document.getElementById('setup-fee-' + orderId);
            const notesInput = document.getElementById('admin-notes-' + orderId);
            const msgDiv = document.getElementById('quote-msg-' + orderId);

            if (!delInput) return;
            const delFee = parseFloat(delInput.value) || 0.00;
            const setupFee = setupInput ? (parseFloat(setupInput.value) || 0.00) : 0.00;
            const notes = notesInput ? notesInput.value.trim() : '';

            if (msgDiv) {
                msgDiv.style.display = 'block';
                msgDiv.style.color = '#cbd5e1';
                msgDiv.innerText = 'Sending quote...';
            }

            try {
                const res = await fetch('update_order_quote.php' + (window.location.search || ''), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: orderId,
                        delivery_fee: delFee,
                        setup_fee: setupFee,
                        admin_notes: notes,
                        notify: true
                    })
                });

                const data = await res.json();
                if (data.success) {
                    if (msgDiv) {
                        msgDiv.style.color = '#10b981';
                        msgDiv.innerText = '✅ Quote updated & email sent!';
                        setTimeout(() => { msgDiv.style.display = 'none'; }, 4000);
                    }
                    const totalDisplay = document.getElementById('total-display-' + orderId);
                    if (totalDisplay && data.new_total !== undefined) {
                        totalDisplay.innerText = '$' + parseFloat(data.new_total).toFixed(2);
                    }
                } else {
                    if (msgDiv) {
                        msgDiv.style.color = '#ef4444';
                        msgDiv.innerText = '❌ ' + (data.error || 'Update failed');
                    }
                }
            } catch (err) {
                if (msgDiv) {
                    msgDiv.style.color = '#ef4444';
                    msgDiv.innerText = '❌ Server connection error';
                }
            }
        }

        async function updatePaymentMethod(orderId, paymentMethod) {
            const msgDiv = document.getElementById('pay-msg-' + orderId);
            if (msgDiv) {
                msgDiv.style.display = 'block';
                msgDiv.style.color = '#cbd5e1';
                msgDiv.innerText = 'Saving payment...';
            }

            try {
                const res = await fetch('update_order_payment.php' + (window.location.search || ''), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, payment_method: paymentMethod })
                });
                const data = await res.json();
                if (data.success && msgDiv) {
                    msgDiv.style.color = '#38bdf8';
                    msgDiv.innerText = '💳 Payment status saved!';
                    setTimeout(() => { msgDiv.style.display = 'none'; }, 3000);
                } else if (msgDiv) {
                    msgDiv.style.color = '#ef4444';
                    msgDiv.innerText = '❌ Failed to save payment';
                }
            } catch (err) {
                if (msgDiv) {
                    msgDiv.style.color = '#ef4444';
                    msgDiv.innerText = '❌ Server connection error';
                }
            }
        }

        async function changeFulfillmentMethod(orderId, method) {
            const msgDiv = document.getElementById('fulfill-msg-' + orderId);
            if (msgDiv) {
                msgDiv.style.display = 'block';
                msgDiv.style.color = '#cbd5e1';
                msgDiv.innerText = 'Saving...';
            }

            try {
                const res = await fetch('update_fulfillment_method.php' + (window.location.search || ''), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, fulfillment_method: method })
                });
                const data = await res.json();
                if (data.success && msgDiv) {
                    msgDiv.style.color = '#2ecc71';
                    msgDiv.innerText = (method === 'Delivery') ? '🚚 Saved as Delivery!' : '📦 Saved as Pickup!';
                    setTimeout(() => { window.location.reload(); }, 1000);
                } else if (msgDiv) {
                    msgDiv.style.color = '#ef4444';
                    msgDiv.innerText = '❌ Save error';
                }
            } catch (err) {
                if (msgDiv) {
                    msgDiv.style.color = '#ef4444';
                    msgDiv.innerText = '❌ Server connection error';
                }
            }
        }

        function switchTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            const activeBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => btn.innerText.toLowerCase().includes(tabName));
            if (activeBtn) activeBtn.classList.add('active');

            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            const target = document.getElementById(tabName + '-tab');
            if (target) target.classList.add('active');

            if (tabName === 'leads') {
                document.getElementById('downloadLeadsCsvBtn').style.display = 'inline-flex';
                document.getElementById('downloadOrdersCsvBtn').style.display = 'none';
            } else {
                document.getElementById('downloadLeadsCsvBtn').style.display = 'none';
                document.getElementById('downloadOrdersCsvBtn').style.display = 'inline-flex';
            }
        }

        function filterLeads() {
            const query = document.getElementById('leadSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#leadsTable tbody tr');
            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return;
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function filterOrders() {
            const orderQ = (document.getElementById('orderSearch') ? document.getElementById('orderSearch').value : '').toLowerCase();
            const orderMonth = document.getElementById('monthFilter') ? document.getElementById('monthFilter').value : 'all';

            const compQ = (document.getElementById('completedSearch') ? document.getElementById('completedSearch').value : '').toLowerCase();
            const compMonth = document.getElementById('completedMonthFilter') ? document.getElementById('completedMonthFilter').value : 'all';

            // Active Orders Table
            document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
                if (row.querySelector('.empty-state')) return;
                const text = row.innerText.toLowerCase();
                const eventMonth = row.getAttribute('data-event-date') || '';
                const matchQ = !orderQ || text.includes(orderQ);
                const matchM = orderMonth === 'all' || eventMonth === orderMonth;
                row.style.display = (matchQ && matchM) ? '' : 'none';
            });

            // Completed Orders Table
            document.querySelectorAll('#completedTable tbody tr').forEach(row => {
                if (row.querySelector('.empty-state')) return;
                const text = row.innerText.toLowerCase();
                const eventMonth = row.getAttribute('data-event-date') || '';
                const matchQ = !compQ || text.includes(compQ);
                const matchM = compMonth === 'all' || eventMonth === compMonth;
                row.style.display = (matchQ && matchM) ? '' : 'none';
            });
        }

        async function changeOrderStatus(orderId, newStatus) {
            const msgDiv = document.getElementById('status-msg-' + orderId);
            const badge = document.getElementById('badge-' + orderId);
            
            if (msgDiv) {
                msgDiv.style.display = 'block';
                msgDiv.style.color = '#cbd5e1';
                msgDiv.innerText = 'Updating status...';
            }

            try {
                const res = await fetch('update_order_status.php' + (window.location.search || ''), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, status: newStatus, notify: true })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    if (msgDiv) {
                        msgDiv.style.color = '#10b981';
                        msgDiv.innerText = data.email_sent ? '✅ Status Updated & Email Sent!' : '✅ Status Updated!';
                    }
                    
                    if (badge) {
                        badge.innerText = '● ' + newStatus;
                        const colors = {
                            'Pending':          { bg: 'rgba(245, 158, 11, 0.15)', col: '#f59e0b', bd: 'rgba(245, 158, 11, 0.4)' },
                            'Confirmed':        { bg: 'rgba(59, 130, 246, 0.15)', col: '#3b82f6', bd: 'rgba(59, 130, 246, 0.4)' },
                            'Order Picked Up':  { bg: 'rgba(139, 92, 246, 0.15)', col: '#8b5cf6', bd: 'rgba(139, 92, 246, 0.4)' },
                            'Out for Delivery': { bg: 'rgba(99, 102, 241, 0.15)', col: '#6366f1', bd: 'rgba(99, 102, 241, 0.4)' },
                            'Delivered':        { bg: 'rgba(16, 185, 129, 0.15)', col: '#10b981', bd: 'rgba(16, 185, 129, 0.4)' },
                            'Returned':         { bg: 'rgba(100, 116, 139, 0.15)', col: '#94a3b8', bd: 'rgba(100, 116, 139, 0.4)' },
                            'Completed':        { bg: 'rgba(16, 185, 129, 0.25)', col: '#34d399', bd: '#10b981' },
                            'Cancelled':        { bg: 'rgba(239, 68, 68, 0.15)', col: '#ef4444', bd: 'rgba(239, 68, 68, 0.4)' }
                        };
                        const styleObj = colors[newStatus] || colors['Pending'];
                        badge.style.background = styleObj.bg;
                        badge.style.color = styleObj.col;
                        badge.style.borderColor = styleObj.bd;
                    }

                    // Reload page after short delay if status changed to or from Completed so tab arrays refresh!
                    if (newStatus === 'Completed') {
                        setTimeout(() => { window.location.reload(); }, 1200);
                    } else {
                        setTimeout(() => { if (msgDiv) msgDiv.style.display = 'none'; }, 3500);
                    }
                } else {
                    if (msgDiv) {
                        msgDiv.style.color = '#ef4444';
                        msgDiv.innerText = '❌ ' + (data.error || 'Update failed');
                    }
                }
            } catch (err) {
                if (msgDiv) {
                    msgDiv.style.color = '#ef4444';
                    msgDiv.innerText = '❌ Server connection error';
                }
            }
        }

        // ═══════════════════════════════════════════════════════════
        // ITEM SUBSTITUTION MODAL LOGIC
        // ═══════════════════════════════════════════════════════════
        let currentModalOrderId = null;
        let allOrdersData = <?php echo json_encode(array_values($orders)); ?>;

        function escapeHtml(str) {
            if (typeof str !== 'string') return str;
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function openSubstitutionModal(orderId) {
            currentModalOrderId = orderId;
            document.getElementById('modalOrderId').innerText = orderId;
            document.getElementById('modalSubNote').value = '';
            document.getElementById('modalMsg').style.display = 'none';

            const ord = allOrdersData.find(o => o.id === orderId);
            const tbody = document.getElementById('modalItemsBody');
            tbody.innerHTML = '';

            if (ord && ord.items) {
                let items = (typeof ord.items === 'string') ? JSON.parse(ord.items) : ord.items;
                if (Array.isArray(items)) {
                    items.forEach(it => {
                        appendModalRow(it.title || 'Item', it.quantity || 1, it.price || 0);
                    });
                }
            }

            if (tbody.children.length === 0) {
                appendModalRow('Sample Item', 1, 10.00);
            }

            recalcModalSubtotal();
            document.getElementById('substitutionModal').style.display = 'flex';
        }

        function closeSubstitutionModal() {
            document.getElementById('substitutionModal').style.display = 'none';
        }

        function appendModalRow(title, qty, price) {
            const tbody = document.getElementById('modalItemsBody');
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--border-color)';
            tr.innerHTML = `
                <td style="padding: 0.4rem;"><input type="text" value="${escapeHtml(title)}" class="modal-title-input" style="width: 100%; padding: 5px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: var(--bg); color: var(--text-primary);"></td>
                <td style="padding: 0.4rem;"><input type="number" min="1" value="${qty}" class="modal-qty-input" oninput="recalcModalSubtotal()" style="width: 100%; padding: 5px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: var(--bg); color: var(--text-primary);"></td>
                <td style="padding: 0.4rem;"><input type="number" step="0.01" min="0" value="${parseFloat(price).toFixed(2)}" class="modal-price-input" oninput="recalcModalSubtotal()" style="width: 100%; padding: 5px; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border-color); background: var(--bg); color: #d4af37; font-weight: bold;"></td>
                <td style="padding: 0.4rem; font-weight: bold; color: var(--primary);" class="modal-row-total">$0.00</td>
                <td style="padding: 0.4rem; text-align: center;"><button onclick="this.closest('tr').remove(); recalcModalSubtotal();" style="background: none; border: none; color: #ef4444; font-size: 1rem; cursor: pointer;">🗑️</button></td>
            `;
            tbody.appendChild(tr);
            recalcModalSubtotal();
        }

        function addCatalogItemToModal() {
            const picker = document.getElementById('catalogPicker');
            if (!picker.value) return;
            const parts = picker.value.split('|');
            appendModalRow(parts[0], 1, parseFloat(parts[1]) || 0);
            picker.value = '';
        }

        function addCustomRowToModal() {
            appendModalRow('Custom Item / Prop', 1, 0.00);
        }

        function recalcModalSubtotal() {
            let subtotal = 0;
            document.querySelectorAll('#modalItemsBody tr').forEach(tr => {
                const qtyInput = tr.querySelector('.modal-qty-input');
                const priceInput = tr.querySelector('.modal-price-input');
                if (qtyInput && priceInput) {
                    const qty = parseFloat(qtyInput.value) || 0;
                    const price = parseFloat(priceInput.value) || 0;
                    const total = qty * price;
                    subtotal += total;
                    const totalTd = tr.querySelector('.modal-row-total');
                    if (totalTd) totalTd.innerText = '$' + total.toFixed(2);
                }
            });
            document.getElementById('modalSubtotalDisplay').innerText = '$' + subtotal.toFixed(2);
        }

        async function saveSubstitutedItems(sendEmail) {
            if (!currentModalOrderId) return;
            const msgDiv = document.getElementById('modalMsg');
            const items = [];

            document.querySelectorAll('#modalItemsBody tr').forEach(tr => {
                const titleInput = tr.querySelector('.modal-title-input');
                const qtyInput = tr.querySelector('.modal-qty-input');
                const priceInput = tr.querySelector('.modal-price-input');

                if (titleInput && qtyInput && priceInput) {
                    const title = titleInput.value.trim();
                    const qty = parseInt(qtyInput.value) || 1;
                    const price = parseFloat(priceInput.value) || 0;
                    if (title) {
                        items.push({ title: title, quantity: qty, price: price });
                    }
                }
            });

            if (items.length === 0) {
                alert('Please add at least one item.');
                return;
            }

            const note = document.getElementById('modalSubNote').value.trim();

            if (msgDiv) {
                msgDiv.style.display = 'block';
                msgDiv.style.color = '#cbd5e1';
                msgDiv.innerText = sendEmail ? 'Saving and sending quote email...' : 'Saving item changes...';
            }

            try {
                const res = await fetch('update_order_items.php' + (window.location.search || ''), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: currentModalOrderId,
                        items: items,
                        substitution_note: note,
                        notify: sendEmail
                    })
                });

                const data = await res.json();
                if (data.success) {
                    if (msgDiv) {
                        msgDiv.style.color = '#10b981';
                        msgDiv.innerText = sendEmail ? '✅ Item substitution saved & customer emailed!' : '✅ Item changes saved successfully!';
                    }
                    setTimeout(() => {
                        closeSubstitutionModal();
                        window.location.reload();
                    }, 1200);
                } else {
                    if (msgDiv) {
                        msgDiv.style.color = '#ef4444';
                        msgDiv.innerText = '❌ ' + (data.error || 'Update failed');
                    }
                }
            } catch (err) {
                if (msgDiv) {
                    msgDiv.style.color = '#ef4444';
                    msgDiv.innerText = '❌ Server connection error';
                }
            }
        }
    </script>

    <!-- Item Substitution Modal Container -->
    <div id="substitutionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(6px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
        <div style="background: var(--surface); border: 1px solid var(--primary); border-radius: 16px; width: 100%; max-width: 650px; max-height: 90vh; overflow-y: auto; padding: 1.8rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8); position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.8rem;">
                <h3 style="color: var(--primary); font-family: Georgia, serif; font-size: 1.3rem; margin: 0;">🔄 Substitute &amp; Edit Order Items</h3>
                <button onclick="closeSubstitutionModal()" style="background: none; border: none; color: var(--text-muted); font-size: 1.6rem; cursor: pointer;">&times;</button>
            </div>

            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                Order ID: <strong id="modalOrderId" style="color: var(--primary); font-family: monospace;"></strong>
            </div>

            <!-- Catalog Picker Quick Add -->
            <div style="background: rgba(212,175,55,0.08); border: 1px solid rgba(212,175,55,0.25); border-radius: 8px; padding: 0.8rem; margin-bottom: 1.2rem; display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                <span style="font-size: 0.8rem; font-weight: bold; color: var(--primary);">➕ Add Store Catalog Item:</span>
                <select id="catalogPicker" class="filter-select" style="flex: 1; min-width: 200px; font-size: 0.82rem; padding: 0.4rem; background: var(--bg); color: var(--text-primary); border: 1px solid var(--border-color);">
                    <option value="">-- Select Store Item --</option>
                    <option value="Round Fold-In-Half Table|12.00">Round Fold-In-Half Table ($12.00)</option>
                    <option value="Cocktail Table (With Cloths)|11.00">Cocktail Table ($11.00)</option>
                    <option value="Adult Rectangular Folding Table Rental|8.00">Adult Rectangular Table ($8.00)</option>
                    <option value="Adult Folding Chair|1.50">Adult Folding Chair ($1.50)</option>
                    <option value="Wedding Tent (16x26)|150.00">Wedding Tent (16x26) ($150.00)</option>
                    <option value="Tent (10x20)|100.00">Tent (10x20) ($100.00)</option>
                    <option value="Round Cylinder Pedestal Display|30.00">Round Cylinder Pedestal ($30.00)</option>
                    <option value="Buffet Food Warmers|10.00">Buffet Food Warmer ($10.00)</option>
                    <option value="Loveseat for rental|100.00">Loveseat ($100.00)</option>
                    <option value="Haldi Urli`s|125.00">Haldi Urli`s ($125.00)</option>
                    <option value="Pipe and Drape Backdrop Stand|50.00">Pipe and Drape Backdrop Stand ($50.00)</option>
                    <option value="GRAD Marquee Letters|40.00">GRAD Marquee Letters ($40.00)</option>
                    <option value="4FT Marquee Numbers|20.00">4FT Marquee Numbers ($20.00)</option>
                    <option value="Photo/Any Event Backdrop|150.00">Photo / Event Backdrop ($150.00)</option>
                    <option value="New Born Baby Photo Prop|20.00">New Born Baby Photo Prop ($20.00)</option>
                    <option value="Seemantham/Baby Shower Backdrop|150.00">Seemantham / Baby Shower Backdrop ($150.00)</option>
                    <option value="VEVOR Metal Wedding Centerpiece (2PCS)|25.00">Metal Wedding Centerpiece ($25.00)</option>
                    <option value="Happy Birthday Neon Sign|10.00">Happy Birthday Neon Sign ($10.00)</option>
                    <option value="Good Vibes Only Neon Sign|10.00">Good Vibes Only Neon Sign ($10.00)</option>
                </select>
                <button onclick="addCatalogItemToModal()" style="font-size: 0.8rem; padding: 0.4rem 0.8rem; background: var(--primary); color: #000; font-weight: bold; border-radius: 6px; border: none; cursor: pointer;">Add Item</button>
            </div>

            <!-- Dynamic Items Table -->
            <div style="margin-bottom: 1.2rem;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.05); text-align: left;">
                            <th style="padding: 0.5rem;">Item Description</th>
                            <th style="padding: 0.5rem; width: 70px;">Qty</th>
                            <th style="padding: 0.5rem; width: 90px;">Price ($)</th>
                            <th style="padding: 0.5rem; width: 80px;">Total</th>
                            <th style="padding: 0.5rem; width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="modalItemsBody">
                        <!-- Dynamic Rows -->
                    </tbody>
                </table>
                <button onclick="addCustomRowToModal()" style="margin-top: 0.6rem; font-size: 0.78rem; padding: 0.35rem 0.75rem; background: transparent; color: var(--primary); border: 1px dashed var(--primary); border-radius: 6px; cursor: pointer; font-weight: bold;">➕ Add Custom Item Row</button>
            </div>

            <!-- Live Subtotal Display -->
            <div style="background: var(--bg); border: 1px solid var(--border-color); padding: 0.8rem; border-radius: 8px; margin-bottom: 1.2rem; display: flex; justify-content: space-between; font-weight: bold;">
                <span>New Items Subtotal:</span>
                <span id="modalSubtotalDisplay" style="color: var(--primary); font-size: 1rem;">$0.00</span>
            </div>

            <!-- Optional Explanation Note for Email -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.3rem; font-weight: bold;">💬 Explanation / Substitution Note for Customer Email:</label>
                <textarea id="modalSubNote" placeholder="e.g. 'Haldi Urli`s is out of stock for your event date. We have substituted it with the Pipe &amp; Drape Backdrop Stand set...'" style="width: 100%; min-height: 55px; font-size: 0.8rem; padding: 6px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg); color: var(--text-primary); resize: vertical;"></textarea>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; flex-wrap: wrap;">
                <button onclick="saveSubstitutedItems(false)" style="font-size: 0.85rem; padding: 0.6rem 1.2rem; background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); font-weight: bold; border-radius: 8px; cursor: pointer;">💾 Save Changes quietly</button>
                <button onclick="saveSubstitutedItems(true)" style="font-size: 0.85rem; padding: 0.6rem 1.2rem; background: var(--primary); color: #000; font-weight: bold; border-radius: 8px; border: none; cursor: pointer;">✉️ Save &amp; Send Replacement Quote Email</button>
            </div>
            <div id="modalMsg" style="margin-top: 0.8rem; font-size: 0.8rem; text-align: center; display: none;"></div>
        </div>
    </div>
</body>
</html>
