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

// User details
$user_name = htmlspecialchars($user["name"] ?? "User");
$user_email = htmlspecialchars($user["email"] ?? "");
$user_role = htmlspecialchars($user["role"] ?? "Web Developer");
$contact_no = htmlspecialchars($user["contact_no"] ?? "N/A");
$qualification = htmlspecialchars($user["qualification"] ?? "BCA");
$parentage = htmlspecialchars($user["parentage"] ?? "N/A");
$course_enrolled = htmlspecialchars($user["course_enrolled"] ?? "BCA");
$college = htmlspecialchars($user["college"] ?? "Universal Group of Management And Technology");
$school = htmlspecialchars($user["school"] ?? "BHSS Sogam");
$account_status = htmlspecialchars($user["status"] ?? "Active");
$login_count = (int)($user["login_count"] ?? 1);
$logout_count = (int)($user["logout_count"] ?? 0);
$quiz_score = (int)($user["quiz_score"] ?? 0);
$profile_image = $user["profile_image"] ?? "";

// Check if owner/admin
$is_admin = is_owner_or_admin();

// Track dashboard visit
db_log_page_visit("Dashboard", $user_name, $user_email);

// Activity logs
$recent_activities = db_get_activities(20);
$page_visits = $is_admin ? db_get_page_visits(15) : [];
$all_users = $is_admin ? db_get_users() : [];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo $user_name; ?> | INAAM Portal</title>

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

        /* Top Navbar */
        .navbar {
            background-color: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--card-border);
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            max-width: 1280px;
            margin: 0 auto;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
            color: var(--accent);
            font-weight: 800;
            font-size: 1.3rem;
        }

        .brand-badge {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1rem;
            list-style: none;
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

        .nav-links a:hover,
        .nav-links a.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.08);
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid var(--card-border);
            padding: 0.4rem 0.9rem;
            border-radius: 30px;
        }

        .avatar-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
            color: white;
            overflow: hidden;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn-logout {
            background-color: rgba(239, 68, 68, 0.15);
            color: #f87171 !important;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-logout:hover {
            background-color: #ef4444 !important;
            color: white !important;
        }

        /* Container Layout */
        .container {
            max-width: 1280px;
            margin: 2rem auto;
            padding: 0 1.5rem 4rem;
            position: relative;
            z-index: 1;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.25), rgba(56, 189, 248, 0.12));
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 2.2rem 2.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .welcome-text h1 {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(to right, #ffffff, var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.4rem;
        }

        .welcome-text p {
            color: var(--text-muted);
            font-size: 1.05rem;
        }

        .badge-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid var(--success);
            color: #34d399;
            padding: 0.5rem 1.1rem;
            border-radius: 30px;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .admin-badge {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid var(--warning);
            color: #fbbf24;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--success);
            box-shadow: 0 0 8px var(--success);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.6rem;
            transition: transform 0.2s ease, border-color 0.2s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            border-color: rgba(56, 189, 248, 0.4);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.8rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            font-size: 1.4rem;
        }

        .stat-value {
            font-size: 1.9rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.3rem;
        }

        .stat-subtext {
            color: var(--text-muted);
            font-size: 0.88rem;
        }

        /* 2-Column Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 900px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        .panel {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 2rem;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.25);
        }

        .panel h2 {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 1.3rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 0.8rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Profile Details List (Requirement 17) */
        .info-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 0.7rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            font-size: 0.95rem;
        }

        .info-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .info-value {
            color: #ffffff;
            font-weight: 600;
            text-align: right;
        }

        /* Education Table (Requirement 3 & 5) */
        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--card-border);
        }

        table.custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            background: #0f172a;
        }

        table.custom-table th {
            background: #090e1a;
            color: var(--accent);
            padding: 12px 16px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--card-border);
        }

        table.custom-table td {
            padding: 12px 16px;
            color: #cbd5e1;
            font-size: 0.92rem;
            border-bottom: 1px solid rgba(51, 65, 85, 0.5);
        }

        table.custom-table tr:last-child td {
            border-bottom: none;
        }

        table.custom-table tr:hover td {
            background: rgba(56, 189, 248, 0.04);
        }

        .badge-year {
            display: inline-block;
            background: rgba(56, 189, 248, 0.15);
            color: var(--accent);
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.82rem;
        }

        /* Activity Items */
        .activity-stream {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-row {
            display: flex;
            align-items: flex-start;
            gap: 0.9rem;
            padding: 0.7rem;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(56, 189, 248, 0.15);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .activity-body h4 {
            font-size: 0.92rem;
            color: #ffffff;
            font-weight: 600;
        }

        .activity-body p {
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        .activity-meta {
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 2px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.8rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--card-border);
            color: var(--accent);
        }

        .btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: var(--accent);
        }

        /* Owner / Admin Control Hub (Requirement 1, 2, 7, 18) */
        .admin-hub {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95));
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: 18px;
            padding: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.4);
        }

        .admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .admin-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-title h2 {
            font-size: 1.45rem;
            color: #fbbf24;
            font-weight: 800;
        }
    </style>
</head>

<body>

    <div class="watermark-bg">INAAM</div>

    <!-- Navigation Bar -->
    <header class="navbar">
        <a href="dashboard.php" class="brand">
            <span>INAAM Dashboard</span>
            <span class="brand-badge"><?php echo $is_admin ? "OWNER / ADMIN" : "PRO"; ?></span>
        </a>

        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="profile.php">Profile</a>
            <a href="activity.php">
                <?php echo $is_admin ? "👑 Audit &amp; Visitors" : "My Activity"; ?>
            </a>
            <a href="quiz.php">AI Quiz (<?php echo $quiz_score; ?>/20)</a>
            <a href="settings.php">Settings</a>

            <div class="user-pill">
                <div class="avatar-circle">
                    <?php if (!empty($profile_image) && file_exists(__DIR__ . "/" . $profile_image)): ?>
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Avatar">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <span style="font-size: 0.9rem; font-weight: 600;"><?php echo $user_name; ?></span>
            </div>

            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <main class="container">

        <!-- Welcome Banner -->
        <section class="welcome-banner">
            <div class="welcome-text">
                <h1>Welcome back, <?php echo $user_name; ?> 👋</h1>
                <p>Welcome to your personal control center. Here are your account metrics and system status.</p>
            </div>
            <div class="badge-group">
                <div class="status-badge">
                    <div class="status-dot"></div>
                    <span>Account Active</span>
                </div>
                <?php if ($is_admin): ?>
                    <div class="status-badge admin-badge">
                        <span>👑 Site Owner (INAAM)</span>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Stats Grid (Requirement 11, 15, 18) -->
        <section class="stats-grid">
            
            <!-- Current Role (Requirement 11 & 15) -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Current Role</span>
                    <span class="stat-icon">💻</span>
                </div>
                <div class="stat-value" style="color: var(--accent); font-size: 1.7rem;"><?php echo $user_role; ?></div>
                <p class="stat-subtext">Submitted during registration</p>
            </div>

            <!-- Course Enrolled -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Course Enrolled</span>
                    <span class="stat-icon">🎓</span>
                </div>
                <div class="stat-value"><?php echo $course_enrolled; ?></div>
                <p class="stat-subtext"><?php echo $college; ?></p>
            </div>

            <!-- Sessions & Logins (Requirement 2 & 18) -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Account Sessions</span>
                    <span class="stat-icon">🔄</span>
                </div>
                <div class="stat-value" style="font-size: 1.6rem;">
                    <?php echo $login_count; ?> Logins &bull; <?php echo $logout_count; ?> Logouts
                </div>
                <p class="stat-subtext">Tracked real-time sessions</p>
            </div>

            <!-- Quiz Assessment -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">AI &amp; Quiz Score</span>
                    <span class="stat-icon">🏆</span>
                </div>
                <div class="stat-value" style="color: #34d399;"><?php echo $quiz_score; ?> / 20</div>
                <p class="stat-subtext">
                    <a href="quiz.php" style="color: var(--accent); text-decoration: none;">Retake Assessment &rarr;</a>
                </p>
            </div>

        </section>

        <!-- ======================================================== -->
        <!-- 👑 OWNER / ADMIN ONLY HUB (Requirement 1, 2, 7, 18)        -->
        <!-- "make it only for me not for user"                       -->
        <!-- ======================================================== -->
        <?php if ($is_admin): ?>
            <section class="admin-hub">
                <div class="admin-header">
                    <div class="admin-title">
                        <span style="font-size: 1.8rem;">👑</span>
                        <div>
                            <h2>Owner &amp; Admin Control Hub (Exclusive for INAAM)</h2>
                            <p style="color: var(--text-muted); font-size: 0.92rem;">
                                Real-time monitoring of all users who visit your page, logins, and logouts. Hidden from regular users.
                            </p>
                        </div>
                    </div>
                    <div>
                        <a href="activity.php" class="btn btn-primary" style="background: linear-gradient(135deg, #d97706, #f59e0b);">
                            Open Full Audit Hub &rarr;
                        </a>
                    </div>
                </div>

                <div class="content-grid" style="margin-bottom: 0;">
                    
                    <!-- Who Visited My Page (Requirement 1) -->
                    <div style="background: rgba(11, 17, 32, 0.7); border: 1px solid var(--card-border); border-radius: 14px; padding: 1.5rem;">
                        <h3 style="color: var(--accent); font-size: 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
                            <span>🌐 Who Visited Your Page (Real-Time)</span>
                            <span style="font-size: 0.8rem; background: rgba(56, 189, 248, 0.2); color: var(--accent); padding: 2px 8px; border-radius: 10px;">
                                Total: <?php echo count($page_visits); ?>
                            </span>
                        </h3>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Page</th>
                                        <th>Visitor</th>
                                        <th>IP Address</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($page_visits)): ?>
                                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">No visits recorded yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach (array_slice($page_visits, 0, 5) as $v): ?>
                                            <tr>
                                                <td style="font-weight: 600; color: white;"><?php echo htmlspecialchars($v['page']); ?></td>
                                                <td><?php echo htmlspecialchars($v['user_name'] ?? 'Guest'); ?></td>
                                                <td><code><?php echo htmlspecialchars($v['ip_address']); ?></code></td>
                                                <td style="font-size: 0.82rem; color: #94a3b8;"><?php echo htmlspecialchars($v['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- User Sessions & Activity (Requirement 2 & 18) -->
                    <div style="background: rgba(11, 17, 32, 0.7); border: 1px solid var(--card-border); border-radius: 14px; padding: 1.5rem;">
                        <h3 style="color: #fbbf24; font-size: 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
                            <span>⚡ User Logins &amp; Logouts Tracker</span>
                            <span style="font-size: 0.8rem; background: rgba(245, 158, 11, 0.2); color: #fbbf24; padding: 2px 8px; border-radius: 10px;">
                                Registered Users: <?php echo count($all_users); ?>
                            </span>
                        </h3>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>User Name</th>
                                        <th>Contact No</th>
                                        <th>Logins</th>
                                        <th>Logouts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($all_users)): ?>
                                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">No registered users.</td></tr>
                                    <?php else: ?>
                                        <?php foreach (array_slice($all_users, 0, 5) as $u): ?>
                                            <tr>
                                                <td style="font-weight: 600; color: white;"><?php echo htmlspecialchars($u['name']); ?></td>
                                                <td><?php echo htmlspecialchars($u['contact_no'] ?? 'N/A'); ?></td>
                                                <td style="color: #34d399; font-weight: 700;"><?php echo (int)($u['login_count'] ?? 0); ?> times</td>
                                                <td style="color: #f87171; font-weight: 700;"><?php echo (int)($u['logout_count'] ?? 0); ?> times</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </section>
        <?php endif; ?>

        <!-- Content Grid Row 1: Profile Details & Recent Activity -->
        <div class="content-grid">

            <!-- Panel 1: Profile Details (Requirement 17) -->
            <section class="panel">
                <h2>
                    <span>👤 Profile Details</span>
                    <a href="profile.php" style="font-size: 0.85rem; color: var(--accent); text-decoration: none;">Edit Profile &rarr;</a>
                </h2>
                <ul class="info-list">
                    <li class="info-item">
                        <span class="info-label">Full Name</span>
                        <span class="info-value"><?php echo $user_name; ?></span>
                    </li>
                    <li class="info-item">
                        <span class="info-label">Email Address</span>
                        <span class="info-value"><?php echo $user_email; ?></span>
                    </li>
                    <li class="info-item">
                        <span class="info-label">Contact Number</span>
                        <span class="info-value"><?php echo $contact_no; ?></span>
                    </li>
                    <li class="info-item">
                        <span class="info-label">Role</span>
                        <span class="info-value" style="color: var(--accent);"><?php echo $user_role; ?></span>
                    </li>
                    <li class="info-item">
                        <span class="info-label">Qualification</span>
                        <span class="info-value"><?php echo $qualification; ?></span>
                    </li>
                    <li class="info-item">
                        <span class="info-label">Parentage</span>
                        <span class="info-value"><?php echo $parentage; ?></span>
                    </li>
                    <li class="info-item">
                        <span class="info-label">Course Enrolled</span>
                        <span class="info-value"><?php echo $course_enrolled; ?></span>
                    </li>
                    <li class="info-item">
                        <span class="info-label">College</span>
                        <span class="info-value"><?php echo $college; ?></span>
                    </li>
                    <li class="info-item">
                        <span class="info-label">School</span>
                        <span class="info-value"><?php echo $school; ?></span>
                    </li>
                    <li class="info-item">
                        <span class="info-label">Account Status</span>
                        <span class="info-value" style="color: #34d399;"><?php echo $account_status; ?> ✓</span>
                    </li>
                </ul>

                <div class="action-buttons">
                    <a href="profile.php" class="btn btn-primary">Edit / Crop Profile Photo</a>
                    <a href="quiz.php" class="btn btn-outline">Technical Quiz</a>
                </div>
            </section>

            <!-- Panel 2: Recent Activity Timeline -->
            <section class="panel">
                <h2>
                    <span>⚡ Recent Account Actions</span>
                    <a href="activity.php" style="font-size: 0.85rem; color: var(--accent); text-decoration: none;">View All &rarr;</a>
                </h2>

                <ul class="activity-stream">
                    <?php if (empty($recent_activities)): ?>
                        <li style="color: var(--text-muted); text-align: center; padding: 2rem 0;">No activities logged yet.</li>
                    <?php else: ?>
                        <?php foreach (array_slice($recent_activities, 0, 5) as $act): ?>
                            <li class="activity-row">
                                <div class="activity-icon">
                                    <?php 
                                        if (str_contains(strtolower($act['action']), 'login')) echo '🔐';
                                        elseif (str_contains(strtolower($act['action']), 'logout')) echo '🚪';
                                        elseif (str_contains(strtolower($act['action']), 'quiz')) echo '🏆';
                                        elseif (str_contains(strtolower($act['action']), 'register')) echo '📝';
                                        else echo '⚡';
                                    ?>
                                </div>
                                <div class="activity-body">
                                    <h4><?php echo htmlspecialchars($act['action']); ?></h4>
                                    <p><?php echo htmlspecialchars($act['details'] ?? ''); ?></p>
                                    <div class="activity-meta">
                                        <?php echo htmlspecialchars($act['user_name'] ?? ''); ?> &bull; 
                                        <?php echo htmlspecialchars($act['created_at']); ?> &bull; 
                                        IP: <?php echo htmlspecialchars($act['ip_address'] ?? '127.0.0.1'); ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>

                <div class="action-buttons">
                    <a href="activity.php" class="btn btn-outline">Real-Time Audit Log</a>
                    <a href="logout.php" class="btn btn-outline" style="border-color: #ef4444; color: #f87171;">Logout</a>
                </div>
            </section>

        </div>

        <!-- Content Grid Row 2: Transferred Sections (Requirement 3 & 5) -->
        <div class="content-grid">

            <!-- Section: About This Person (Transferred into Dashboard) -->
            <section class="panel">
                <h2>
                    <span>ℹ️ About This Person</span>
                    <span style="font-size: 0.8rem; color: var(--accent); background: rgba(56, 189, 248, 0.1); padding: 2px 8px; border-radius: 6px;">
                        Transferred to Dashboard
                    </span>
                </h2>
                
                <div style="color: #cbd5e1; font-size: 1rem; line-height: 1.8;">
                    <p style="margin-bottom: 0.8rem;">Hello,</p>
                    <p style="margin-bottom: 0.8rem;">
                        My name is <strong style="color: var(--accent);"><?php echo $user_name; ?></strong>, and I am actively working in 
                        <strong><?php echo $user_role; ?></strong>.
                    </p>
                    <p style="margin-bottom: 0.8rem;">
                        In my free time, I enjoy coding, reading, and exploring cutting-edge web technologies, architecture, and responsive application design.
                    </p>
                    <p>
                        Feel free to reach out for collaboration or inquiries!
                    </p>
                </div>
            </section>

            <!-- Section: Education & Academic Qualifications (Transferred into Dashboard) -->
            <section class="panel">
                <h2>
                    <span>🎓 Education &amp; Qualifications</span>
                    <span style="font-size: 0.8rem; color: var(--accent); background: rgba(56, 189, 248, 0.1); padding: 2px 8px; border-radius: 6px;">
                        Transferred to Dashboard
                    </span>
                </h2>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>College / Institute</th>
                                <th>Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="font-weight: 700; color: white;">BCA</td>
                                <td>Universal Group of Management And Technology</td>
                                <td><span class="badge-year">2028</span></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 700; color: white;">12th</td>
                                <td>BHSS Sogam</td>
                                <td><span class="badge-year">2024</span></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 700; color: white;">11th</td>
                                <td>BHSS Sogam</td>
                                <td><span class="badge-year">2023</span></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 700; color: white;">10th</td>
                                <td>BHSS Sogam</td>
                                <td><span class="badge-year">2022</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>

    </main>

    <!-- 10-Minute Session Timeout Script (Requirement 19) -->
    <script>
        (function() {
            const TIMEOUT_MS = 10 * 60 * 1000; // 10 minutes
            const WARNING_MS = 9 * 60 * 1000;  // 9 minutes (warning at 60s remaining)
            let idleTimer = null;
            let warningTimer = null;
            let warningToast = null;

            function resetTimer() {
                if (warningToast) {
                    warningToast.remove();
                    warningToast = null;
                }
                clearTimeout(idleTimer);
                clearTimeout(warningTimer);

                warningTimer = setTimeout(showWarning, WARNING_MS);
                idleTimer = setTimeout(() => {
                    window.location.href = 'login.php?timeout=1';
                }, TIMEOUT_MS);
            }

            function showWarning() {
                if (document.getElementById('session-warning-toast')) return;
                warningToast = document.createElement('div');
                warningToast.id = 'session-warning-toast';
                warningToast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:14px 20px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.5);font-family:inherit;font-size:0.92rem;display:flex;align-items:center;gap:12px;';
                warningToast.innerHTML = '<span style="font-size:1.4rem;">⏰</span><div><strong>Session Timeout Warning</strong><div style="font-size:0.82rem;opacity:0.95;">Your session will automatically expire in 60 seconds due to inactivity. Move your cursor to keep session active.</div></div>';
                document.body.appendChild(warningToast);
            }

            ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach(evt => {
                window.addEventListener(evt, resetTimer, { passive: true });
            });

            resetTimer();
        })();
    </script>

</body>

</html>