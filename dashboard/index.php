<?php
session_start();
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php'; 

// --- 1. CONFIGURATION ---
$forms = [
    'custom' => ['table' => 'submissions', 'label' => 'Build Requests', 'icon' => 'fa-hammer', 'path' => '../buildcustomhome/builduploads/', 'date_col' => 'created_at', 'email_col' => 'email', 'name_col' => 'first_name'],
    'agent'  => ['table' => 'agentregister', 'label' => 'Agent Registrations', 'icon' => 'fa-user-tie', 'path' => '../agentregister/uploads/', 'date_col' => 'created_at', 'email_col' => 'email', 'name_col' => 'first_name'],
    'buyer'  => ['table' => 'property_requests', 'label' => 'Dream Home Req', 'icon' => 'fa-house-user', 'path' => '', 'date_col' => 'created_at', 'email_col' => 'email', 'name_col' => 'first_name'], 
    'pro'    => ['table' => 'professional_registrations', 'label' => 'Professionals', 'icon' => 'fa-briefcase', 'path' => '', 'date_col' => 'created_at', 'email_col' => 'email', 'name_col' => 'first_name'],
    'seller' => ['table' => 'sell_requests', 'label' => 'Sell Requests', 'icon' => 'fa-sign-hanging', 'path' => '../sellhomeform/sellfolder/', 'date_col' => 'created_at', 'email_col' => 'contact_email', 'name_col' => 'contact_name'],
];

// --- 2. AUTHENTICATION ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_attempt'])) {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($email === 'nexushomepro@gmail.com' && $pass === 'Nexus@12345') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $login_error = "Invalid email or password.";
    }
}

// --- 3. EXPORT LOGIC ---
if (isset($_GET['export']) && isset($_SESSION['admin_logged_in'])) {
    $page_key = $_GET['page'] ?? 'custom';
    $isAll = ($page_key === 'all_requests');
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Export_'.date('Y-m-d').'.csv"');
    $output = fopen('php://output', 'w');
    $rowsToExport = [];
    $formsLoop = $isAll ? $forms : [$page_key => $forms[$page_key]];

    foreach($formsLoop as $key => $conf) {
        $table = $conf['table'];
        $dateCol = $conf['date_col'] ?? 'created_at';
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $filterStatus = $_GET['status'] ?? ''; 
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        
        $sql = "SELECT *, '$key' as form_type, '{$conf['label']}' as form_label FROM $table WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $term = "%$search%";
            $sql .= " AND (id LIKE ? OR status LIKE ?)"; 
            $params = array_merge($params, [$term, $term]);
        }
        if (!empty($filterStatus) && $filterStatus !== 'All') {
            $sql .= " AND status = ?";
            $params[] = $filterStatus;
        }
        if (!empty($dateFrom)) { $sql .= " AND DATE($dateCol) >= ?"; $params[] = $dateFrom; }
        if (!empty($dateTo)) { $sql .= " AND DATE($dateCol) <= ?"; $params[] = $dateTo; }
        
        $sql .= " ORDER BY $dateCol DESC, id DESC";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $fetched = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rowsToExport = array_merge($rowsToExport, $fetched);
        } catch (Exception $e) { }
    }

    if (!empty($rowsToExport)) {
        fputcsv($output, array_keys($rowsToExport[0]));
        foreach ($rowsToExport as $row) fputcsv($output, $row);
    } else { fputcsv($output, ['No records found']); }
    fclose($output);
    exit;
}

// --- 4. LOGIN PAGE VIEW ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - NexusHome</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body.login-body {
            background: linear-gradient(rgba(15, 23, 42, 0.65), rgba(15, 23, 42, 0.65)), 
                        url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .login-wrapper { width: 100%; max-width: 400px; padding: 20px; }
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            text-align: center;
        }
        .login-header h2 { margin: 0 0 5px; color: #1e293b; font-size: 24px; font-weight: 700; }
        .login-header p { margin: 0 0 25px; color: #64748b; font-size: 14px; }
    </style>
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header"><h2>NexusHome Admin</h2><p>Secure Portal Access</p></div>
            <?php if($login_error): ?><div class="error-msg"><?= htmlspecialchars($login_error) ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="login_attempt" value="1">
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                <button type="submit" class="btn-login">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php exit; }

// --- 5. ACTION HANDLER (WITH EMAIL NOTIFICATION) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $page_key = $_POST['form_type'];
    $redirect_page = $_POST['redirect_page'] ?? $page_key;

    if (isset($forms[$page_key])) {
        $conf = $forms[$page_key];
        $table = $conf['table'];
        $id = (int)$_POST['record_id'];

        if ($_POST['action_type'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($_POST['action_type'] === 'update') {
            $new_status = $_POST['new_status'];
            $admin_comment = $_POST['admin_comment'] ?? '';

            // Update Database
            $sql = "UPDATE $table SET status = ?";
            $params = [$new_status];
            if($admin_comment !== null) { $sql .= ", admin_comments = ?"; $params[] = $admin_comment; }
            $sql .= " WHERE id = ?";
            $params[] = $id;

            try { 
                $stmt = $pdo->prepare($sql); 
                $stmt->execute($params); 

                // --- EMAIL NOTIFICATION LOGIC ---
                // 1. Fetch User Email & Name from DB using config columns
                $emailCol = $conf['email_col'] ?? 'email';
                $nameCol = $conf['name_col'] ?? 'first_name';
                
                $fetchStmt = $pdo->prepare("SELECT $emailCol, $nameCol FROM $table WHERE id = ?");
                $fetchStmt->execute([$id]);
                $userData = $fetchStmt->fetch(PDO::FETCH_ASSOC);

                if ($userData && !empty($userData[$emailCol])) {
                    $to = $userData[$emailCol];
                    $name = $userData[$nameCol] ?? 'Valued Customer';
                    $subject = "Update on your Request - NexusHome";
                    
                    // Determine Color based on status
                    $color = ($new_status === 'Approved') ? '#10b981' : (($new_status === 'Rejected') ? '#ef4444' : '#f59e0b');

                    $message = "
                    <html>
                    <head>
                      <title>Request Status Update</title>
                    </head>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                      <div style='max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 8px; overflow: hidden;'>
                        <div style='background-color: #2563eb; color: #fff; padding: 20px; text-align: center;'>
                          <h2 style='margin:0;'>NexusHome Update</h2>
                        </div>
                        <div style='padding: 20px;'>
                          <p>Hello <strong>$name</strong>,</p>
                          <p>The status of your request has been updated.</p>
                          <div style='background-color: #f9fafb; border-left: 4px solid $color; padding: 15px; margin: 20px 0;'>
                             <p style='margin:0;'><strong>New Status:</strong> <span style='color: $color; font-weight: bold;'>$new_status</span></p>
                             " . (!empty($admin_comment) ? "<p style='margin:10px 0 0;'><strong>Admin Comments:</strong><br>".nl2br(htmlspecialchars($admin_comment))."</p>" : "") . "
                          </div>
                          <p>If you have any questions, please reply to this email.</p>
                          <p>Best regards,<br>The NexusHome Team</p>
                        </div>
                        <div style='background-color: #f1f5f9; color: #64748b; padding: 10px; text-align: center; font-size: 12px;'>
                          &copy; " . date("Y") . " NexusHome. All rights reserved.
                        </div>
                      </div>
                    </body>
                    </html>
                    ";

                    // Headers
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= 'From: NexusHome <noreply@nexushomepro.com>' . "\r\n";

                    // Send Email
                    mail($to, $subject, $message, $headers);
                }
                // --- END EMAIL LOGIC ---

            } catch (Exception $e) { }
        }
    }
    $statusRedirect = isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '';
    header("Location: ?page=$redirect_page" . $statusRedirect);
    exit;
}

$page = $_GET['page'] ?? 'home';
$table_data = [];
$stats = ['total'=>0, 'pending'=>0, 'approved'=>0, 'rejected'=>0, 'today'=>0];
$recent_all = [];
$total_records = 0;

$chartLabels = [];
$chartDataTotal = [];
$chartDataApproved = [];

for ($i = 5; $i >= 0; $i--) {
    $monthKey = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M', strtotime("-$i months"));
    $chartLabels[] = $monthLabel;
    $chartDataTotal[$monthKey] = 0; 
    $chartDataApproved[$monthKey] = 0; 
}

// --- 6. DATA FETCHING ---

// A. HOME PAGE
if ($page === 'home') {
    $todayDate = date('Y-m-d');

    foreach($forms as $key => $conf) {
        $t = $conf['table'];
        $dCol = $conf['date_col'] ?? 'created_at';
        try {
            $stats['total'] += $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            $stats['pending'] += $pdo->query("SELECT COUNT(*) FROM $t WHERE status = 'Pending' OR status IS NULL")->fetchColumn();
            $stats['approved'] += $pdo->query("SELECT COUNT(*) FROM $t WHERE status = 'Approved'")->fetchColumn();
            $stats['rejected'] += $pdo->query("SELECT COUNT(*) FROM $t WHERE status = 'Rejected'")->fetchColumn();
            
            try {
                $todayCount = $pdo->query("SELECT COUNT(*) FROM $t WHERE DATE($dCol) = '$todayDate'")->fetchColumn();
                $stats['today'] += $todayCount;
            } catch (Exception $e) { }

            try {
                $chartSql = "SELECT DATE_FORMAT($dCol, '%Y-%m') as m, COUNT(*) as total, SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved FROM $t WHERE $dCol >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY m";
                $cStmt = $pdo->query($chartSql);
                if($cStmt) {
                    while($cRow = $cStmt->fetch(PDO::FETCH_ASSOC)) {
                        if(isset($chartDataTotal[$cRow['m']])) {
                            $chartDataTotal[$cRow['m']] += $cRow['total'];
                            $chartDataApproved[$cRow['m']] += $cRow['approved'];
                        }
                    }
                }
            } catch (Exception $e) { }

            $trackCol = ($key=='agent' || $key=='buyer') ? 'tracking_id' : 'tracking_code';
            $nameExpr = "''";
            if($key == 'seller') $nameExpr = "contact_name";
            elseif($key == 'pro') $nameExpr = "company_name"; 
            else $nameExpr = "CONCAT(first_name, ' ', last_name)";

            $sql = "SELECT id, status, $dCol as created_at, 
                    $trackCol as track_ref, 
                    $nameExpr as name_ref,
                    '$conf[label]' as source_label
                    FROM $t ORDER BY id DESC LIMIT 3";
            
            $stmt = $pdo->query($sql);
            if($stmt) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $recent_all = array_merge($recent_all, $rows);
            }

        } catch(Exception $e) {}
    }
    usort($recent_all, function($a, $b) { return $b['id'] - $a['id']; });
    $recent_all = array_slice($recent_all, 0, 5);
}

// B. TABLE PAGES
if ($page !== 'home') {
    $tablesToQuery = [];
    if ($page === 'all_requests') {
        $tablesToQuery = $forms;
    } elseif (isset($forms[$page])) {
        $tablesToQuery = [$page => $forms[$page]];
    }

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filterStatus = $_GET['status'] ?? ''; 
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    
    $limit = 10; 
    $curr_page_num = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
    $allRows = [];

    foreach ($tablesToQuery as $key => $conf) {
        $tbl = $conf['table'];
        $dCol = $conf['date_col'] ?? 'created_at';
        
        $where = " WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $term = "%$search%";
            $orParts = [];
            $orParts[] = "id LIKE ?"; $params[] = $term;
            $orParts[] = "status LIKE ?"; $params[] = $term;

            if ($key === 'custom') {
                $orParts[] = "tracking_code LIKE ?"; $params[] = $term;
                $orParts[] = "first_name LIKE ?"; $params[] = $term;
                $orParts[] = "last_name LIKE ?"; $params[] = $term;
                $orParts[] = "email LIKE ?"; $params[] = $term;
                $orParts[] = "phone LIKE ?"; $params[] = $term;
            } elseif ($key === 'agent') {
                $orParts[] = "tracking_id LIKE ?"; $params[] = $term;
                $orParts[] = "first_name LIKE ?"; $params[] = $term;
                $orParts[] = "last_name LIKE ?"; $params[] = $term;
                $orParts[] = "email LIKE ?"; $params[] = $term;
                $orParts[] = "phone_primary LIKE ?"; $params[] = $term;
            } elseif ($key === 'buyer') {
                $orParts[] = "tracking_id LIKE ?"; $params[] = $term;
                $orParts[] = "first_name LIKE ?"; $params[] = $term;
                $orParts[] = "last_name LIKE ?"; $params[] = $term;
                $orParts[] = "email LIKE ?"; $params[] = $term;
                $orParts[] = "phone LIKE ?"; $params[] = $term;
            } elseif ($key === 'pro') {
                $orParts[] = "tracking_code LIKE ?"; $params[] = $term;
                $orParts[] = "company_name LIKE ?"; $params[] = $term;
                $orParts[] = "first_name LIKE ?"; $params[] = $term;
                $orParts[] = "last_name LIKE ?"; $params[] = $term;
                $orParts[] = "email LIKE ?"; $params[] = $term;
                $orParts[] = "phone LIKE ?"; $params[] = $term;
            } elseif ($key === 'seller') {
                $orParts[] = "tracking_code LIKE ?"; $params[] = $term;
                $orParts[] = "contact_name LIKE ?"; $params[] = $term;
                $orParts[] = "contact_email LIKE ?"; $params[] = $term;
                $orParts[] = "contact_phone LIKE ?"; $params[] = $term;
            }
            $where .= " AND (" . implode(" OR ", $orParts) . ")";
        }

        if (!empty($filterStatus) && $filterStatus !== 'All') {
            $where .= " AND status = ?";
            $params[] = $filterStatus;
        }

        if (!empty($dateFrom)) { $where .= " AND DATE($dCol) >= ?"; $params[] = $dateFrom; }
        if (!empty($dateTo)) { $where .= " AND DATE($dCol) <= ?"; $params[] = $dateTo; }

        $sql = "SELECT *, '$key' as origin_key, '{$conf['label']}' as origin_label FROM $tbl $where"; 
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $fetched = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $allRows = array_merge($allRows, $fetched);
        } catch (Exception $e) { }
    }

    usort($allRows, function($a, $b) {
        $tA = strtotime($a['created_at'] ?? '');
        $tB = strtotime($b['created_at'] ?? '');
        if ($tA && $tB) { return $tB - $tA; } 
        return $b['id'] - $a['id']; 
    });

    $total_records = count($allRows);
    $total_pages = ceil($total_records / $limit);
    $offset = ($curr_page_num - 1) * $limit;
    $table_data = array_slice($allRows, $offset, $limit);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusHome Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .filter-group { display: flex; align-items: center; gap: 8px; margin-right: 10px; }
        .date-input { padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; color: #475569; }
        .btn-export { background: #10b981; color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-export:hover { background: #059669; }
        
        .pagination-container { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top:15px; border-top:1px solid #eee; }
        .pagination-info { font-size: 13px; color: #64748b; font-weight: 500; }
        .pagination { display: flex; gap: 5px; }
        .page-link { padding: 6px 12px; border: 1px solid #e2e8f0; background: white; color: #64748b; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 500; }
        .page-link.active { background: #2563eb; color: white; border-color: #2563eb; }
        .page-link:hover:not(.active) { background: #f1f5f9; }

        .btn-delete { background: #fee2e2; color: #991b1b; }
        .btn-delete:hover { background: #dc2626; color: white; }

        .recent-section { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; margin-top: 25px; }
        .recent-header { font-size: 1.1rem; font-weight: 700; margin-bottom: 15px; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }
        .recent-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f8fafc; }
        .recent-item:last-child { border-bottom: none; }
        .recent-id { font-family: monospace; font-weight: 600; color: #2563eb; font-size: 14px; }
        .recent-meta { font-size: 12px; color: #64748b; }

        .status-tabs { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
        .status-tab { 
            padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid #e2e8f0; background: white; color: #64748b; 
        }
        .status-tab:hover { background: #f8fafc; }
        .status-tab.active { background: #eff6ff; color: #2563eb; border-color: #2563eb; }
        .status-tab.active-approved { background: #ecfdf5; color: #059669; border-color: #059669; }
        .status-tab.active-rejected { background: #fef2f2; color: #dc2626; border-color: #dc2626; }
        .status-tab.active-pending { background: #fffbeb; color: #d97706; border-color: #d97706; }
        
        .stat-card-link { text-decoration: none; color: inherit; display: block; height: 100%; }
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="dashboard-body">

<div class="sidebar">
    <div class="brand"><i class="fa-solid fa-layer-group" style="color:#2563eb;"></i> NexusHome</div>
    <div class="menu">
        <a href="?page=home" class="menu-item <?= $page=='home'?'active':'' ?>">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
        </a>
        <div class="menu-label">MANAGEMENT</div>
        <?php foreach($forms as $key => $conf): ?>
            <a href="?page=<?= $key ?>" class="menu-item <?= $page==$key?'active':'' ?>">
                <i class="fa-solid <?= $conf['icon'] ?>"></i> <?= $conf['label'] ?>
            </a>
        <?php endforeach; ?>
        <a href="?action=logout" class="menu-item logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</div>

<div class="main-content">
    <header class="top-header">
        <h2 style="font-size:1.1rem; font-weight:700;">
            <?= $page=='home' ? 'Overview' : ($page=='all_requests' ? 'All Submissions' : $forms[$page]['label']) ?>
        </h2>
        <div class="user-menu">
            <span class="user-role">nexushomepro@gmail.com</span>
            <div class="avatar"><i class="fa-regular fa-user"></i></div>
        </div>
    </header>

    <div class="content-scroll">
        <?php if($page === 'home'): ?>
            <div class="stats-grid">
                <a href="?page=all_requests" class="stat-card-link">
                    <div class="stat-card">
                        <div class="label">Total Submissions</div>
                        <h3><?= $stats['total'] ?> 
                        <?php if($stats['today'] > 0): ?><span style="font-size: 13px; color: #10b981; font-weight: 600; margin-left: 8px;">(+<?= $stats['today'] ?> today)</span><?php endif; ?>
                        </h3>
                    </div>
                </a>
                <a href="?page=all_requests&status=Pending" class="stat-card-link">
                    <div class="stat-card"><div class="label">Pending Review</div><h3 style="color:#f59e0b"><?= $stats['pending'] ?></h3></div>
                </a>
                <a href="?page=all_requests&status=Approved" class="stat-card-link">
                    <div class="stat-card"><div class="label">Approved</div><h3 style="color:#10b981"><?= $stats['approved'] ?></h3></div>
                </a>
                <a href="?page=all_requests&status=Rejected" class="stat-card-link">
                    <div class="stat-card"><div class="label">Rejected</div><h3 style="color:#ef4444"><?= $stats['rejected'] ?></h3></div>
                </a>
            </div>

            <div class="charts-section">
                <div class="chart-container"><div class="chart-header">Monthly Activity</div><div style="height:320px"><canvas id="lineChart"></canvas></div></div>
                <div class="chart-container"><div class="chart-header">Status Distribution</div><div style="height:320px"><canvas id="donutChart"></canvas></div></div>
            </div>

            <div class="recent-section">
                <div class="recent-header">Recent Requests (Last 5)</div>
                <?php if(empty($recent_all)): ?>
                    <div style="color:#94a3b8; font-size:13px; font-style:italic;">No recent activity found.</div>
                <?php else: ?>
                    <?php foreach($recent_all as $r): 
                        $statusColor = ($r['status']=='Approved') ? '#10b981' : (($r['status']=='Rejected')?'#ef4444':'#f59e0b');
                    ?>
                    <div class="recent-item">
                        <div class="recent-info">
                            <span class="recent-id"><?= htmlspecialchars($r['track_ref'] ?? 'ID-'.$r['id']) ?></span>
                            <span class="recent-meta"><?= htmlspecialchars($r['name_ref']) ?> &bull; <?= $r['source_label'] ?> &bull; <?= htmlspecialchars($r['created_at']) ?></span>
                        </div>
                        <div><span style="font-size:11px; font-weight:700; color:<?= $statusColor ?>; background:<?= $statusColor ?>15; padding:4px 8px; border-radius:4px; text-transform:uppercase;"><?= htmlspecialchars($r['status'] ?? 'Pending') ?></span></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <script>
                new Chart(document.getElementById('donutChart'), { type: 'doughnut', data: { labels: ['Pending', 'Approved', 'Rejected'], datasets: [{ data: [<?= $stats['pending'] ?>, <?= $stats['approved'] ?>, <?= $stats['rejected'] ?>], backgroundColor: ['#fbbf24', '#10b981', '#ef4444'], borderWidth: 0 }] }, options: { maintainAspectRatio:false, cutout:'70%', plugins:{ legend:{position:'bottom'} } } });
                
                // Line Chart (Two Lines)
                new Chart(document.getElementById('lineChart'), { 
                    type: 'line', 
                    data: { 
                        labels: <?= json_encode($chartLabels) ?>, 
                        datasets: [
                            { 
                                label: 'Total', 
                                data: <?= json_encode(array_values($chartDataTotal)) ?>, 
                                borderColor: '#3b82f6', tension: 0.4, borderWidth: 2, pointRadius: 4, pointBackgroundColor: 'white' 
                            },
                            { 
                                label: 'Approved', 
                                data: <?= json_encode(array_values($chartDataApproved)) ?>, 
                                borderColor: '#10b981', tension: 0.4, borderWidth: 2, pointRadius: 4, pointBackgroundColor: 'white' 
                            }
                        ] 
                    }, 
                    options: { maintainAspectRatio:false, plugins: { legend: { display:true } }, scales:{ y:{grid:{borderDash:[5,5]}, beginAtZero: true}, x:{grid:{display:false}} } } 
                });
            </script>

        <?php else: ?>
            
            <?php $st = $_GET['status'] ?? 'All'; ?>
            <div class="status-tabs">
                <a href="?page=<?= $page ?>&status=All" class="status-tab <?= $st=='All' || $st=='' ? 'active' : '' ?>">All</a>
                <a href="?page=<?= $page ?>&status=Pending" class="status-tab <?= $st=='Pending' ? 'active-pending' : '' ?>">Pending</a>
                <a href="?page=<?= $page ?>&status=Approved" class="status-tab <?= $st=='Approved' ? 'active-approved' : '' ?>">Approved</a>
                <a href="?page=<?= $page ?>&status=Rejected" class="status-tab <?= $st=='Rejected' ? 'active-rejected' : '' ?>">Rejected</a>
            </div>

            <div class="filter-bar">
                <form method="GET" style="display:flex; width:100%; align-items:center; flex-wrap:wrap; gap:10px;">
                    <input type="hidden" name="page" value="<?= $page ?>">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($_GET['status'] ?? '') ?>">
                    
                    <div class="search-input-group" style="width:250px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" placeholder="Search ID, Name, Email..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>

                    <div class="filter-group">
                        <input type="date" name="date_from" class="date-input" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                        <span style="color:#94a3b8;">-</span>
                        <input type="date" name="date_to" class="date-input" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn-search">Filter</button>
                    <a href="?page=<?= $page ?>&status=<?= htmlspecialchars($st) ?>" style="color:#64748b; text-decoration:none;"><i class="fa-solid fa-rotate-right"></i> Reset</a>

                    <a href="?page=<?= $page ?>&export=1&status=<?= urlencode($st) ?>&search=<?= urlencode($_GET['search']??'') ?>&date_from=<?= urlencode($_GET['date_from']??'') ?>&date_to=<?= urlencode($_GET['date_to']??'') ?>" 
                       class="btn-export" style="margin-left:auto;">
                        <i class="fa-solid fa-file-csv"></i> Export CSV
                    </a>
                </form>
            </div>

            <div class="table-section">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Tracking ID</th>
                                <th>Details</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($table_data)): ?>
                                <tr><td colspan="4" style="text-align:center; padding:40px; color:#94a3b8;">No records found.</td></tr>
                            <?php else: ?>
                                <?php foreach($table_data as $row): 
                                    $originKey = $row['origin_key'] ?? $page;
                                    $originLabel = $row['origin_label'] ?? '';

                                    $trackID = $row['tracking_code'] ?? $row['tracking_id'] ?? 'ID-'.$row['id'];
                                    $fName = $row['first_name'] ?? '';
                                    $lName = $row['last_name'] ?? '';
                                    $fullName = trim("$fName $lName");
                                    if(empty($fullName)) $fullName = $row['contact_name'] ?? $row['company_name'] ?? 'User';
                                    $email = $row['email'] ?? $row['contact_email'] ?? 'No Email';
                                    
                                    $detailText = '';
                                    if($originKey === 'custom') $detailText = $row['home_type'];
                                    if($originKey === 'agent') $detailText = $row['license_number'];
                                    if($originKey === 'buyer') $detailText = $row['property_type'];
                                    if($originKey === 'pro') $detailText = $row['professional_type'];
                                    if($originKey === 'seller') $detailText = $row['property_type'];
                                    
                                    if($page === 'all_requests') $detailText = "<strong>$originLabel</strong> - " . $detailText;

                                    $status = $row['status'] ?? 'Pending';
                                    $adminComment = $row['admin_comments'] ?? '';

                                    $images = [];
                                    $currentUploadPath = $forms[$originKey]['path'];
                                    $imgCols = ['file_site_photos', 'file_reference_design', 'profile_photo', 'additional_photos', 'photo_path', 'property_photos'];
                                    foreach($imgCols as $col) {
                                        if (!empty($row[$col])) {
                                            $parts = explode(',', $row[$col]);
                                            foreach($parts as $p) {
                                                $clean = trim($p);
                                                if(!empty($clean)) $images[] = $currentUploadPath . rawurlencode($clean);
                                            }
                                        }
                                    }
                                    $images = array_unique($images); 

                                    $badgeClass = 'badge-pending';
                                    if(strtolower($status) === 'approved') $badgeClass = 'badge-approved';
                                    if(strtolower($status) === 'rejected') $badgeClass = 'badge-rejected';
                                ?>
                                <tr>
                                    <td><button onclick="openModal('modal-<?= $row['id'].$originKey ?>')" class="track-id-btn"><?= htmlspecialchars($trackID) ?></button></td>
                                    <td><div class="client-name"><?= htmlspecialchars($fullName) ?></div><div class="client-meta"><?= htmlspecialchars($email) ?> <br> <?= $detailText ?></div></td>
                                    <td><span class="badge-status <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span></td>
                                    <td>
                                        <div style="display:flex;">
                                            <form method="POST">
                                                <input type="hidden" name="action_type" value="update">
                                                <input type="hidden" name="form_type" value="<?= $originKey ?>">
                                                <input type="hidden" name="redirect_page" value="<?= $page ?>">
                                                <input type="hidden" name="record_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="new_status" value="Approved">
                                                <button type="submit" class="action-btn btn-approve" title="Approve"><i class="fa-solid fa-check"></i></button>
                                            </form>
                                            <form method="POST">
                                                <input type="hidden" name="action_type" value="update">
                                                <input type="hidden" name="form_type" value="<?= $originKey ?>">
                                                <input type="hidden" name="redirect_page" value="<?= $page ?>">
                                                <input type="hidden" name="record_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="new_status" value="Rejected">
                                                <button type="submit" class="action-btn btn-reject" title="Reject"><i class="fa-solid fa-xmark"></i></button>
                                            </form>
                                            <button onclick="openModal('modal-<?= $row['id'].$originKey ?>')" class="action-btn" style="background:#eff6ff; color:#2563eb;" title="View"><i class="fa-solid fa-eye"></i></button>
                                            <form method="POST" onsubmit="return confirmDelete(event)">
                                                <input type="hidden" name="action_type" value="delete">
                                                <input type="hidden" name="form_type" value="<?= $originKey ?>">
                                                <input type="hidden" name="redirect_page" value="<?= $page ?>">
                                                <input type="hidden" name="record_id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="action-btn btn-delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <div id="modal-<?= $row['id'].$originKey ?>" class="custom-modal">
                                    <div class="modal-overlay" onclick="closeModal('modal-<?= $row['id'].$originKey ?>')"></div>
                                    <div class="modal-content">
                                        <div class="modal-header"><h3><?= $trackID ?></h3><button class="close-modal" onclick="closeModal('modal-<?= $row['id'].$originKey ?>')">&times;</button></div>
                                        <div class="modal-body">
                                            <div class="modal-grid">
                                                <?php 
                                                $exclude = ['id','tracking_code','tracking_id','status','admin_comments','file_site_photos','file_reference_design','profile_photo','additional_photos','photo_path','property_photos','last_completed_step','origin_key','origin_label'];
                                                foreach($row as $key => $val): if(in_array($key, $exclude) || empty($val)) continue;
                                                ?>
                                                <div class="modal-group <?= (strlen($val)>50)?'full-width':'' ?>"><label><?= ucwords(str_replace('_',' ',$key)) ?></label><p><?= nl2br(htmlspecialchars($val)) ?></p></div>
                                                <?php endforeach; ?>
                                                <?php if(!empty($images)): ?><div class="modal-group full-width"><label>Photos</label><div class="image-gallery"><?php foreach($images as $imgUrl): ?><a href="<?= $imgUrl ?>" target="_blank"><img src="<?= $imgUrl ?>" onerror="this.onerror=null; this.src='https://via.placeholder.com/100?text=No+Img';"></a><?php endforeach; ?></div></div><?php endif; ?>
                                            </div>
                                            <div class="admin-action-area">
                                                <h4>Admin Actions</h4>
                                                <form method="POST">
                                                    <input type="hidden" name="action_type" value="update">
                                                    <input type="hidden" name="form_type" value="<?= $originKey ?>">
                                                    <input type="hidden" name="redirect_page" value="<?= $page ?>">
                                                    <input type="hidden" name="record_id" value="<?= $row['id'] ?>">
                                                    <textarea name="admin_comment" class="form-control" rows="2" placeholder="Notes..."><?= htmlspecialchars($adminComment) ?></textarea>
                                                    <div class="action-buttons">
                                                        <button type="submit" name="new_status" value="Approved" class="btn-approve-big">Approve</button>
                                                        <button type="submit" name="new_status" value="Rejected" class="btn-reject-big">Reject</button>
                                                        <button type="submit" name="new_status" value="<?= $status ?>" class="btn-save-comment">Save Note</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-container">
                    <div class="pagination-info">
                        Showing <?= ($total_records > 0) ? ($offset + 1) : 0 ?> to <?= min($total_records, $offset + $limit) ?> of <?= $total_records ?> entries
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if($curr_page_num > 1): ?>
                            <a href="?page=<?= $page ?>&p=<?= $curr_page_num - 1 ?>&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="page-link">&laquo; Prev</a>
                        <?php endif; ?>
                        
                        <?php for($i=1; $i<=$total_pages; $i++): ?>
                            <a href="?page=<?= $page ?>&p=<?= $i ?>&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="page-link <?= $i==$curr_page_num?'active':'' ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if($curr_page_num < $total_pages): ?>
                            <a href="?page=<?= $page ?>&p=<?= $curr_page_num + 1 ?>&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="page-link">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; document.body.style.overflow = 'auto'; }
    function confirmDelete(e) { e.preventDefault(); if(confirm("Are you sure?")) { if(confirm("Really delete? Cannot be undone.")) { e.target.submit(); } } return false; }
</script>

</body>
</html>