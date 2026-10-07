<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_name = htmlspecialchars($_SESSION["user_name"]);
$user_email = htmlspecialchars($_SESSION["user_email"]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Log - <?php echo $user_name; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --card-border: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #38bdf8;
            --success: #10b981;
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
        }

        nav {
            background-color: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--card-border);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .brand {
            color: var(--accent);
            text-decoration: none;
            font-size: 1.25rem;
            font-weight: 700;
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
            transition: all 0.2s ease;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: white;
            background: rgba(255, 255, 255, 0.08);
        }

        .container {
            max-width: 900px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
        }

        .header-section {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-section h1 {
            font-size: 2rem;
            background: linear-gradient(to right, #ffffff, var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: #ffffff;
        }

        .header-section p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .activity-card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .activity-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .activity-table th {
            background-color: #0b1120;
            color: var(--accent);
            padding: 14px 18px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--card-border);
        }

        .activity-table td {
            padding: 14px 18px;
            color: #e2e8f0;
            border-bottom: 1px solid rgba(51, 65, 85, 0.6);
            font-size: 0.95rem;
        }

        .activity-table tr:last-child td {
            border-bottom: none;
        }

        .activity-table tr:hover td {
            background-color: rgba(56, 189, 248, 0.04);
        }

        .badge-tag {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-login {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-profile {
            background: rgba(56, 189, 248, 0.15);
            color: #7dd3fc;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }

        .back-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.4rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
        }

        .btn-outline {
            border: 1px solid var(--card-border);
            color: var(--accent);
        }
    </style>
</head>
<body>

    <nav>
        <a href="dashboard.php" class="brand">User Activity Hub</a>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">Profile</a>
            <a href="activity.php" class="active">Activity</a>
            <a href="logout.php" style="color: #f87171;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="header-section">
            <div>
                <h1>Recent User Activity</h1>
                <p>Activity log for <strong><?php echo $user_name; ?></strong> (<?php echo $user_email; ?>)</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline">&larr; Back to Dashboard</a>
        </div>

        <div class="activity-card">
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>IP / Device</th>
                        <th>Date &amp; Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge-tag badge-login">Login</span> Authenticated Session</td>
                        <td><?php echo $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; ?></td>
                        <td><?php echo date('Y-m-d H:i:s'); ?></td>
                        <td style="color: #34d399;">Success ✓</td>
                    </tr>
                    <tr>
                        <td><span class="badge-tag badge-profile">Profile</span> Loaded Profile View</td>
                        <td><?php echo $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; ?></td>
                        <td><?php echo date('Y-m-d H:i:s', time() - 120); ?></td>
                        <td style="color: #34d399;">Success ✓</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="back-actions">
            <a href="dashboard.php" class="btn btn-primary">&larr; Return to Dashboard</a>
            <a href="profile.php" class="btn btn-outline">Go to Profile</a>
        </div>
    </div>

</body>
</html>
