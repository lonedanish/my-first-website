<?php

require_once "db.php";
check_session_timeout();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user = db_find_user_by_id($user_id);

if (!$user) {
    header("Location: logout.php");
    exit();
}

$user_name = htmlspecialchars($user["name"] ?? "User");
$user_email = htmlspecialchars($user["email"] ?? "");
$is_admin = is_owner_or_admin();

// Track visit
db_log_page_visit("Activity Hub", $user_name, $user_email);

// Fetch logs
$all_activities = db_get_activities(150);
$all_visits = $is_admin ? db_get_page_visits(150) : [];
$all_users = $is_admin ? db_get_users() : [];

// If regular user, filter to only show their own activities (Requirement 7)
if (!$is_admin) {
    $all_activities = array_filter($all_activities, function($act) use ($user_id, $user_email) {
        return ((int)($act["user_id"] ?? 0) === (int)$user_id) || (strtolower($act["user_email"] ?? "") === strtolower($user_email));
    });
}

// User-specific lookup map for login/logout counters
$userMetrics = [];
$usersList = db_get_users();
foreach ($usersList as $u) {
    $uid = $u['id'];
    $userMetrics[$uid] = [
        'login_count' => (int)($u['login_count'] ?? 0),
        'logout_count' => (int)($u['logout_count'] ?? 0),
        'contact_no' => $u['contact_no'] ?? 'N/A',
        'role' => $u['role'] ?? 'Web Developer'
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_admin ? "Owner Audit Hub &amp; Visitor Log" : "My Account Activity"; ?> - INAAM Portal</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-color: #0b1120;
            --card-bg: #151f32;
            --card-border: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #38bdf8;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            line-height: 1.6;
            position: relative;
            overflow-x: hidden;
        }

        /* Watermark INAAM (Requirement 9) */
        .watermark-bg {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 16vw;
            font-weight: 900;
            color: rgba(255, 255, 255, 0.025);
            letter-spacing: 20px;
            pointer-events: none;
            z-index: 0;
            user-select: none;
            white-space: nowrap;
            text-transform: uppercase;
        }

        nav.navbar {
            background-color: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--card-border);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            max-width: 1280px;
            margin: 0 auto;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--accent);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            padding: 0.5rem 0.9rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-links a:hover, .nav-links a.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.08);
        }

        .btn-logout {
            background-color: rgba(239, 68, 68, 0.15);
            color: #f87171 !important;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .container {
            max-width: 1280px;
            margin: 2.5rem auto 4rem;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .header-title h1 {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(to right, #ffffff, var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.3rem;
        }

        .header-title p {
            color: var(--text-muted);
            font-size: 1rem;
        }

        /* Tabs for Admin */
        .tab-controls {
            display: flex;
            gap: 10px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            padding: 6px;
            border-radius: 12px;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 10px 18px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        }

        .count-pill {
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        /* Search Filter Box */
        .filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-input {
            max-width: 380px;
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--card-border);
            background: #0b1120;
            color: white;
            font-size: 0.9rem;
            outline: none;
        }

        .search-input:focus {
            border-color: var(--accent);
        }

        /* Table Card */
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 1.8rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            overflow: hidden;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--card-border);
        }

        table.audit-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            background: #0f172a;
        }

        table.audit-table th {
            background: #090e1a;
            color: var(--accent);
            padding: 14px 16px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--card-border);
            white-space: nowrap;
        }

        table.audit-table td {
            padding: 14px 16px;
            color: #cbd5e1;
            font-size: 0.92rem;
            border-bottom: 1px solid rgba(51, 65, 85, 0.5);
        }

        table.audit-table tr:last-child td {
            border-bottom: none;
        }

        table.audit-table tr:hover td {
            background: rgba(56, 189, 248, 0.04);
        }

        .badge-action {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-login {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-logout {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .badge-quiz {
            background: rgba(56, 189, 248, 0.15);
            color: #7dd3fc;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }

        .badge-register {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .stat-tag {
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.82rem;
        }
    </style>
</head>
<body>

    <div class="watermark-bg">INAAM</div>

    <!-- Top Navigation -->
    <nav class="navbar">
        <a href="dashboard.php" class="brand">
            <span>INAAM Audit Portal</span>
            <span style="font-size: 0.75rem; background: rgba(56, 189, 248, 0.15); border: 1px solid var(--accent); color: var(--accent); padding: 2px 8px; border-radius: 6px;">
                <?php echo $is_admin ? "OWNER AUDIT" : "MY LOGS"; ?>
            </span>
        </a>

        <div class="nav-links">
            <a href="dashboard.php">&larr; Return to Dashboard</a>
            <a href="profile.php">Profile</a>
            <a href="activity.php" class="active">Activity</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">

        <div class="header-section">
            <div class="header-title">
                <h1><?php echo $is_admin ? "👑 Real-Time User Audit &amp; Visitor Tracker" : "Recent Account Activity"; ?></h1>
                <p>
                    <?php if ($is_admin): ?>
                        Exclusive Owner Control Center: Monitor user sessions, contact numbers, logins/logouts, and page visitors in real-time.
                    <?php else: ?>
                        Audit log of your account sessions, authentication, and actions.
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <a href="dashboard.php" style="color: var(--accent); text-decoration: none; font-size: 0.95rem; font-weight: 600;">
                    &larr; Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($is_admin): ?>
            <!-- Admin Tabs (Requirement 1, 2, 7, 18) -->
            <div class="tab-controls">
                <button type="button" class="tab-btn active" onclick="showTab('auditTab', this)">
                    <span>⚡</span>
                    <span>Recent User Activities &amp; Actions</span>
                    <span class="count-pill"><?php echo count($all_activities); ?></span>
                </button>

                <button type="button" class="tab-btn" onclick="showTab('visitorsTab', this)">
                    <span>🌐</span>
                    <span>Who Visited My Page</span>
                    <span class="count-pill"><?php echo count($all_visits); ?></span>
                </button>

                <button type="button" class="tab-btn" onclick="showTab('usersTab', this)">
                    <span>👥</span>
                    <span>Registered Users &amp; Login/Logout Counts</span>
                    <span class="count-pill"><?php echo count($all_users); ?></span>
                </button>
            </div>
        <?php endif; ?>

        <!-- ======================================================== -->
        <!-- TAB 1: AUDIT LOG OF ACCOUNT SESSIONS & ACTIONS           -->
        <!-- (Requirement 18: user name, email, contact no, logins)    -->
        <!-- ======================================================== -->
        <div id="auditTab" class="card tab-pane">
            
            <div class="filter-bar">
                <h2 style="font-size: 1.3rem; color: white;">⚡ Real-Time Audit Stream</h2>
                <input type="text" class="search-input" id="auditSearchInput" placeholder="Filter by User Name, Email, or Action..." onkeyup="filterAuditTable()">
            </div>

            <div class="table-responsive">
                <table class="audit-table" id="auditTable">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Email Address</th>
                            <th>Contact No</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Logins / Logouts</th>
                            <th>Action Details</th>
                            <th>IP &amp; Device</th>
                            <th>Date &amp; Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_activities)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    No activity records found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_activities as $row): 
                                $uid = (int)($row['user_id'] ?? 0);
                                $metric = $userMetrics[$uid] ?? ['login_count' => 0, 'logout_count' => 0];
                                $cNo = !empty($row['contact_no']) ? $row['contact_no'] : ($metric['contact_no'] ?? 'N/A');
                                $rRole = !empty($row['role']) ? $row['role'] : ($metric['role'] ?? 'Web Developer');
                                $actionName = strtolower($row['action'] ?? '');
                            ?>
                                <tr>
                                    <td style="font-weight: 700; color: white;">
                                        <?php echo htmlspecialchars($row['user_name'] ?? 'User'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['user_email'] ?? 'N/A'); ?></td>
                                    <td><code><?php echo htmlspecialchars($cNo); ?></code></td>
                                    <td style="color: var(--accent);"><?php echo htmlspecialchars($rRole); ?></td>
                                    <td>
                                        <span class="badge-action <?php 
                                            if (str_contains($actionName, 'login')) echo 'badge-login';
                                            elseif (str_contains($actionName, 'logout') || str_contains($actionName, 'timeout')) echo 'badge-logout';
                                            elseif (str_contains($actionName, 'quiz')) echo 'badge-quiz';
                                            elseif (str_contains($actionName, 'register')) echo 'badge-register';
                                            else echo 'badge-login';
                                        ?>">
                                            <?php echo htmlspecialchars($row['action']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="stat-tag" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                                            <?php echo $metric['login_count']; ?> In
                                        </span>
                                        /
                                        <span class="stat-tag" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">
                                            <?php echo $metric['logout_count']; ?> Out
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['details'] ?? ''); ?></td>
                                    <td>
                                        <div style="font-size: 0.85rem; font-family: monospace; color: #94a3b8;">
                                            <?php echo htmlspecialchars($row['ip_address'] ?? '127.0.0.1'); ?>
                                        </div>
                                    </td>
                                    <td style="font-size: 0.85rem; color: #94a3b8; white-space: nowrap;">
                                        <?php echo htmlspecialchars($row['created_at']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <?php if ($is_admin): ?>
            <!-- ======================================================== -->
            <!-- TAB 2: WHO VISITED MY PAGE (Requirement 1)               -->
            <!-- ======================================================== -->
            <div id="visitorsTab" class="card tab-pane" style="display: none;">
                
                <div class="filter-bar">
                    <h2 style="font-size: 1.3rem; color: var(--accent);">🌐 Visitor Tracking Log (Who Visited Your Pages)</h2>
                    <input type="text" class="search-input" id="visitorSearchInput" placeholder="Filter by Page or IP Address..." onkeyup="filterVisitorTable()">
                </div>

                <div class="table-responsive">
                    <table class="audit-table" id="visitorTable">
                        <thead>
                            <tr>
                                <th>Page Visited</th>
                                <th>Visitor Status / Name</th>
                                <th>Email</th>
                                <th>IP Address</th>
                                <th>Device / User Agent</th>
                                <th>Visited At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_visits)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        No page visits logged yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_visits as $v): ?>
                                    <tr>
                                        <td style="font-weight: 700; color: white;">
                                            <?php echo htmlspecialchars($v['page']); ?>
                                        </td>
                                        <td>
                                            <span style="font-weight: 600; color: <?php echo ($v['user_name'] === 'Guest Visitor') ? '#94a3b8' : 'var(--accent)'; ?>">
                                                <?php echo htmlspecialchars($v['user_name'] ?? 'Guest Visitor'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($v['user_email'] ?? 'N/A'); ?></td>
                                        <td><code><?php echo htmlspecialchars($v['ip_address']); ?></code></td>
                                        <td style="font-size: 0.82rem; color: #94a3b8; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($v['user_agent'] ?? 'Browser'); ?>
                                        </td>
                                        <td style="font-size: 0.85rem; color: #94a3b8; white-space: nowrap;">
                                            <?php echo htmlspecialchars($v['created_at']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- ======================================================== -->
            <!-- TAB 3: REGISTERED USERS & HOW MANY TIMES LOGGED IN/OUT   -->
            <!-- (Requirement 2 & 18)                                     -->
            <!-- ======================================================== -->
            <div id="usersTab" class="card tab-pane" style="display: none;">
                
                <div class="filter-bar">
                    <h2 style="font-size: 1.3rem; color: #fbbf24;">👥 All Users Directory &amp; Login/Logout Counts</h2>
                    <input type="text" class="search-input" id="userSearchInput" placeholder="Filter by Name, Role, or Contact No..." onkeyup="filterUserTable()">
                </div>

                <div class="table-responsive">
                    <table class="audit-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Contact No</th>
                                <th>Role</th>
                                <th>Qualification</th>
                                <th>Course Enrolled</th>
                                <th>Logins</th>
                                <th>Logouts</th>
                                <th>Quiz Score</th>
                                <th>Last Login</th>
                                <th>Last Logout</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_users)): ?>
                                <tr>
                                    <td colspan="12" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        No registered users found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_users as $u): ?>
                                    <tr>
                                        <td>#<?php echo (int)$u['id']; ?></td>
                                        <td style="font-weight: 700; color: white;">
                                            <?php echo htmlspecialchars($u['name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><code><?php echo htmlspecialchars($u['contact_no'] ?? 'N/A'); ?></code></td>
                                        <td style="color: var(--accent); font-weight: 600;"><?php echo htmlspecialchars($u['role'] ?? 'Web Developer'); ?></td>
                                        <td><?php echo htmlspecialchars($u['qualification'] ?? 'BCA'); ?></td>
                                        <td><?php echo htmlspecialchars($u['course_enrolled'] ?? 'BCA'); ?></td>
                                        <td style="color: #34d399; font-weight: 800; font-size: 1rem;">
                                            <?php echo (int)($u['login_count'] ?? 0); ?> times
                                        </td>
                                        <td style="color: #f87171; font-weight: 800; font-size: 1rem;">
                                            <?php echo (int)($u['logout_count'] ?? 0); ?> times
                                        </td>
                                        <td style="font-weight: 700; color: #fbbf24;">
                                            <?php echo (int)($u['quiz_score'] ?? 0); ?> / 20
                                        </td>
                                        <td style="font-size: 0.82rem; color: #94a3b8; white-space: nowrap;">
                                            <?php echo htmlspecialchars($u['last_login'] ?? 'Never'); ?>
                                        </td>
                                        <td style="font-size: 0.82rem; color: #94a3b8; white-space: nowrap;">
                                            <?php echo htmlspecialchars($u['last_logout'] ?? 'Never'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        <?php endif; ?>

    </div>

    <script>
        function showTab(tabId, btn) {
            document.querySelectorAll('.tab-pane').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

            document.getElementById(tabId).style.display = 'block';
            btn.classList.add('active');
        }

        function filterAuditTable() {
            const query = document.getElementById('auditSearchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#auditTable tbody tr');
            rows.forEach(r => {
                const text = r.textContent.toLowerCase();
                r.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function filterVisitorTable() {
            const query = document.getElementById('visitorSearchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#visitorTable tbody tr');
            rows.forEach(r => {
                const text = r.textContent.toLowerCase();
                r.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function filterUserTable() {
            const query = document.getElementById('userSearchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            rows.forEach(r => {
                const text = r.textContent.toLowerCase();
                r.style.display = text.includes(query) ? '' : 'none';
            });
        }

        // 10-Minute session timeout (Requirement 19)
        (function() {
            const TIMEOUT_MS = 10 * 60 * 1000;
            let idleTimer = setTimeout(() => { window.location.href = 'login.php?timeout=1'; }, TIMEOUT_MS);
            ['mousemove', 'keydown', 'click'].forEach(evt => {
                window.addEventListener(evt, () => {
                    clearTimeout(idleTimer);
                    idleTimer = setTimeout(() => { window.location.href = 'login.php?timeout=1'; }, TIMEOUT_MS);
                }, { passive: true });
            });
        })();
    </script>

</body>
</html>
