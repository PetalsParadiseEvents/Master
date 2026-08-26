<?php
session_start();
/**
 * Confidential Leads & Orders Export Dashboard Portal
 * Petals Paradise Events
 */

require_once __DIR__ . '/config.php';

// Handle Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    session_destroy();
    header('Location: leads_export.php');
    exit;
}

$adminUser = ADMIN_USER;
$adminPass = ADMIN_PASS;
$adminSecret = ADMIN_SECRET;

// Handle Form Submission Login
$loginError = '';
if (isset($_POST['username']) && isset($_POST['password'])) {
    if (strtolower(trim($_POST['username'])) === strtolower(trim($adminUser)) && $_POST['password'] === $adminPass) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: leads_export.php' . (!empty($_GET['key']) ? '?key=' . urlencode($_GET['key']) : ''));
        exit;
    } else {
        $loginError = '🌸 Invalid username or password.';
    }
}

$providedKey = isset($_GET['key']) ? $_GET['key'] : '';
$isBypassed = (!empty($adminSecret) && $providedKey === $adminSecret);

$isAuthenticated = $isBypassed || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);

if (!$isAuthenticated) {
    // Render a premium, styled login page matching the site's dark/gold theme
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Petals Paradise Portal - Login</title>
        <style>
            :root {
                --bg: #0d0f12;
                --surface: #1a1d24;
                --primary: #d4af37;
                --primary-hover: #f1c40f;
                --text: #f8fafc;
                --text-muted: #cbd5e1;
                --border: rgba(255,255,255,0.12);
            }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
            .login-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 400px; padding: 2.5rem; box-shadow: 0 12px 40px rgba(0,0,0,0.5); }
            .logo-area { text-align: center; margin-bottom: 2rem; }
            .logo-area h1 { font-family: Georgia, serif; color: var(--primary); font-size: 1.8rem; margin-bottom: 0.5rem; }
            .logo-area p { color: var(--text-muted); font-size: 0.85rem; }
            .form-group { margin-bottom: 1.25rem; }
            .form-label { display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem; font-weight: 600; }
            .form-control { width: 100%; padding: 0.8rem 1rem; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-size: 0.95rem; transition: border-color 0.2s; }
            .form-control:focus { outline: none; border-color: var(--primary); }
            .btn { width: 100%; padding: 0.9rem; border-radius: 8px; background: var(--primary); color: #0d0f12; border: none; font-weight: 700; font-size: 1rem; cursor: pointer; transition: background 0.2s; margin-top: 1rem; }
            .btn:hover { background: var(--primary-hover); }
            .error-message { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.5rem; text-align: center; }
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

// Only load from JSON backup files if we are NOT connected to the database
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

// ═══════════════════════════════════════════════════════════
// 3. EXPORT / RESPONSE FORMAT HANDLER
// ═══════════════════════════════════════════════════════════
$format = isset($_GET['format']) ? strtolower($_GET['format']) : '';
$type = isset($_GET['type']) ? strtolower($_GET['type']) : 'leads';

// CSV Export Download
if ($format === 'csv') {
    if ($type === 'orders') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Petals_Paradise_Orders_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Order ID', 'Date Added', 'Name', 'Email', 'Phone', 'Event Date', 'Venue Location', 'Fulfillment Method', 'Delivery Address', 'Logistics/Dates', 'Special Requests', 'Items Requested', 'Subtotal', 'Discount', 'Total Estimate', 'Status']);
        
        foreach ($orders as $order) {
            $itemList = "";
            $itemsArr = is_string($order['items']) ? json_decode($order['items'], true) : ($order['items'] ?? []);
            if (is_array($itemsArr)) {
                foreach ($itemsArr as $it) {
                    $itemList .= ($it['quantity'] ?? 1) . "x " . ($it['title'] ?? 'Item') . "; ";
                }
            }
            
            $logistics = "";
            if (($order['fulfillment_method'] ?? '') === 'Delivery') {
                $logistics = "Delivery: " . ($order['delivery_date'] ?? $order['delivery_date_manual'] ?? '') . " " . ($order['delivery_time'] ?? $order['delivery_time_manual'] ?? '') . " - Coll: " . ($order['collection_date'] ?? $order['collection_date_manual'] ?? '') . " " . ($order['collection_time'] ?? $order['collection_time_manual'] ?? '');
            } else {
                $logistics = "Pickup: " . ($order['pickup_date'] ?? $order['pickup_date_manual'] ?? '') . " " . ($order['pickup_time'] ?? $order['pickup_time_manual'] ?? '') . " - Return: " . ($order['return_date'] ?? $order['dropoff_date_manual'] ?? '') . " " . ($order['return_time'] ?? $order['dropoff_time_manual'] ?? '');
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
                $logistics,
                $order['special_requests'] ?? '',
                $itemList,
                $order['subtotal'] ?? 0.00,
                $order['discount'] ?? 0.00,
                $order['total'] ?? 0.00,
                $order['status'] ?? 'Pending'
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

// JSON API Output
if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'leads' => $leads,
        'orders' => $orders
    ], JSON_PRETTY_PRINT);
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
    <title>Petals Paradise Events - Admin Dashboard</title>
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
        .header { max-width: 1400px; margin: 0 auto 2rem auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 1.5rem; }
        .title-area h1 { font-family: Georgia, serif; color: var(--primary); font-size: 1.8rem; }
        .title-area p { color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem; }
        .action-btns { display: flex; gap: 0.75rem; }
        .btn { padding: 0.65rem 1.25rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer; }
        .btn-primary { background: var(--primary); color: #0d0f12; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { background: rgba(255,255,255,0.08); }
        
        .container { max-width: 1400px; margin: 0 auto; }
        
        .tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); }
        .tab-btn { background: none; border: none; color: var(--text-muted); font-size: 1.05rem; font-weight: 600; padding: 0.75rem 1.5rem; cursor: pointer; transition: all 0.2s; border-bottom: 3px solid transparent; }
        .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .stats-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
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
            <p>Leads &amp; Customer Orders — End-to-End Admin Dashboard</p>
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
        <!-- Tab Bar -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('leads')">Leads (Contact Form)</button>
            <button class="tab-btn" onclick="switchTab('orders')">Orders (Cart Requests)</button>
        </div>

        <!-- ──────────────── LEADS TAB ──────────────── -->
        <div id="leads-tab" class="tab-content active">
            <div class="stats-bar">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($leads); ?></div>
                    <div class="stat-label">Total Leads</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $latestLead = !empty($leads) ? date('M d, Y', strtotime($leads[0]['date_added'] ?? 'now')) : 'N/A';
                        echo $latestLead;
                        ?>
                    </div>
                    <div class="stat-label">Latest Lead Received</div>
                </div>
            </div>

            <div class="search-bar">
                <input type="text" id="leadSearch" class="search-input" placeholder="Search leads by name, email, phone, location..." onkeyup="filterLeads()">
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
                                    <td colspan="7" class="empty-state">No customer leads captured yet.</td>
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
                                        <td style="max-width: 250px; font-size: 0.85rem; color: var(--text-muted); white-space: pre-wrap;"><?php echo htmlspecialchars($lead['notes'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ──────────────── ORDERS TAB ──────────────── -->
        <div id="orders-tab" class="tab-content">
            <div class="stats-bar">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($orders); ?></div>
                    <div class="stat-label">Total Orders Placed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $latestOrder = !empty($orders) ? date('M d, Y', strtotime($orders[0]['date_added'] ?? 'now')) : 'N/A';
                        echo $latestOrder;
                        ?>
                    </div>
                    <div class="stat-label">Latest Order Received</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        $<?php 
                        $sum = 0;
                        foreach ($orders as $o) { $sum += (float)($o['total'] ?? 0); }
                        echo number_format($sum, 2);
                        ?>
                    </div>
                    <div class="stat-label">Total Est. Value</div>
                </div>
            </div>

            <div class="search-bar">
                <input type="text" id="orderSearch" class="search-input" placeholder="Search orders by confirmation ID, name, email, phone, items..." onkeyup="filterOrders()">
            </div>

            <div class="table-card">
                <div class="table-wrapper">
                    <table id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order ID / Date</th>
                                <th>Customer Details</th>
                                <th>Event Date &amp; Location</th>
                                <th>Fulfillment &amp; Logistics</th>
                                <th>Items Requested</th>
                                <th>Financials</th>
                                <th>Special Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="empty-state">No rental orders placed yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td style="white-space: nowrap; font-size: 0.85rem;">
                                            <span style="color: var(--primary); font-weight:700; font-family:monospace;"><?php echo htmlspecialchars($order['id'] ?? 'PPE-N/A'); ?></span>
                                            <div style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem;">
                                                <?php echo htmlspecialchars($order['date_added'] ?? ''); ?>
                                            </div>
                                        </td>
                                        <td class="contact-info">
                                            <strong><?php echo htmlspecialchars($order['name'] ?? 'N/A'); ?></strong>
                                            <div>📧 <a href="mailto:<?php echo htmlspecialchars($order['email']); ?>"><?php echo htmlspecialchars($order['email']); ?></a></div>
                                            <div>📞 <a href="tel:<?php echo htmlspecialchars($order['phone']); ?>"><?php echo htmlspecialchars($order['phone']); ?></a></div>
                                        </td>
                                        <td>
                                            🗓️ <strong><?php echo htmlspecialchars($order['event_date'] ?? 'N/A'); ?></strong>
                                            <div style="font-size: 0.8rem; margin-top:0.30rem; color:var(--text-muted);">
                                                📍 <?php echo htmlspecialchars($order['venue_location'] ?? 'Not Specified'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $isDelivery = ($order['fulfillment_method'] ?? '') === 'Delivery'; 
                                            ?>
                                            <span class="badge <?php echo $isDelivery ? 'badge-delivery' : 'badge-pickup'; ?>">
                                                <?php echo htmlspecialchars($order['fulfillment_method'] ?? 'Pickup'); ?>
                                            </span>
                                            
                                            <div style="font-size: 0.8rem; margin-top: 0.5rem; color: var(--text-muted); line-height: 1.4;">
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
                                        <td style="font-size: 0.85rem; line-height: 1.4;">
                                            <?php 
                                            $itemsArr = is_string($order['items']) ? json_decode($order['items'], true) : ($order['items'] ?? []);
                                            if (is_array($itemsArr)): 
                                                foreach ($itemsArr as $it): 
                                                    echo "• " . htmlspecialchars($it['quantity'] ?? 1) . "x " . htmlspecialchars($it['title'] ?? 'Item') . "<br>";
                                                endforeach;
                                            else:
                                                echo "-";
                                            endif;
                                            ?>
                                        </td>
                                        <td style="white-space: nowrap; font-size: 0.85rem; line-height: 1.4;">
                                            Sub: $<?php echo htmlspecialchars(number_format((float)($order['subtotal'] ?? 0), 2)); ?><br>
                                            Disc: -$<?php echo htmlspecialchars(number_format((float)($order['discount'] ?? 0), 2)); ?><br>
                                            <strong>Total: $<?php echo htmlspecialchars(number_format((float)($order['total'] ?? 0), 2)); ?></strong>
                                        </td>
                                        <td style="max-width: 220px; font-size: 0.8rem; color: var(--text-muted); white-space: pre-wrap;"><?php echo htmlspecialchars($order['special_requests'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Switch Active Tab Button
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            const activeBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => btn.innerText.toLowerCase().includes(tabName));
            if (activeBtn) activeBtn.classList.add('active');

            // Switch Active Tab Content
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById(tabName + '-tab').classList.add('active');

            // Switch CSV Download Action Buttons
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
            const query = document.getElementById('orderSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#ordersTable tbody tr');
            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return;
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
