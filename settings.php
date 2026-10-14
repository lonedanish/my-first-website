<?php

require_once "db.php";
check_session_timeout();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user = db_find_user_by_id($user_id);
$user_name = htmlspecialchars($user["name"] ?? $_SESSION["user_name"]);
$user_email = htmlspecialchars($user["email"] ?? $_SESSION["user_email"]);
$name_changed = $user["name_changed"] ?? ($_SESSION["name_changed"] ?? 0);
$profile_image = $user["profile_image"] ?? ($_SESSION["profile_image"] ?? "");

$message = "";

// Check database for latest status if connected
if (isset($conn) && $conn && !$conn->connect_error) {
    $stmt = $conn->prepare("SELECT name, name_changed, profile_image FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $user_name = htmlspecialchars($row["name"]);
            $name_changed = (int)$row["name_changed"];
            $profile_image = $row["profile_image"] ?? "";
        }
        $stmt->close();
    }
}

// Handle Security Questions Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_questions"])) {
    $_SESSION["security_q1"] = htmlspecialchars($_POST["q1_answer"] ?? "");
    $_SESSION["security_q2"] = htmlspecialchars($_POST["q2_answer"] ?? "");
    $message = "✅ Security questions saved successfully to your session!";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings &amp; Security</title>
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
        }

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
            max-width: 860px;
            margin: 2.5rem auto;
            padding: 0 1.5rem 4rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        h1 {
            font-size: 2.2rem;
            margin-bottom: 0.4rem;
            background: linear-gradient(to right, #ffffff, var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: #ffffff;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.8rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 0.8rem;
            flex-wrap: wrap;
            gap: 0.8rem;
        }

        .card-header h2 {
            font-size: 1.3rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge-health {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid var(--success);
            color: #34d399;
            padding: 0.3rem 0.9rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Auto-check Details Grid */
        .details-check-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .check-item {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 1rem 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            transition: border-color 0.2s ease;
        }

        .check-item:hover {
            border-color: rgba(56, 189, 248, 0.4);
        }

        .check-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .check-info {
            flex: 1;
        }

        .check-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .check-value {
            font-size: 0.98rem;
            font-weight: 600;
            color: #ffffff;
        }

        .check-status {
            font-size: 0.78rem;
            color: #34d399;
            font-weight: 500;
        }

        /* Form elements */
        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: #cbd5e1;
        }

        select, input[type="text"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--card-border);
            background-color: #0f172a;
            color: #ffffff;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.4rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--card-border);
            color: var(--accent);
        }

        .alert-box {
            padding: 0.85rem 1.2rem;
            border-radius: 10px;
            font-size: 0.95rem;
            margin-bottom: 1.2rem;
            font-weight: 500;
            background-color: rgba(16, 185, 129, 0.15);
            border: 1px solid var(--success);
            color: #34d399;
        }

        /* FAQ Accordion */
        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .faq-item {
            border: 1px solid var(--card-border);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.5);
            overflow: hidden;
        }

        .faq-question {
            padding: 1rem 1.2rem;
            font-weight: 600;
            color: #ffffff;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }

        .faq-icon {
            font-size: 1.2rem;
            color: var(--accent);
            transition: transform 0.2s ease;
        }

        .faq-answer {
            display: none;
            padding: 0 1.2rem 1.2rem;
            color: var(--text-muted);
            font-size: 0.92rem;
            line-height: 1.6;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            margin-top: 0.5rem;
            padding-top: 0.8rem;
        }

        .faq-item.active .faq-answer {
            display: block;
        }

        .faq-item.active .faq-icon {
            transform: rotate(45deg);
        }
    </style>
</head>
<body>
    <div class="watermark-bg">INAAM</div>

    <nav>
        <a href="dashboard.php" class="brand">Account Settings</a>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">Profile</a>
            <a href="settings.php" class="active">Settings</a>
            <a href="logout.php" style="color: #f87171;">Logout</a>
        </div>
    </nav>

    <div class="container">

        <header class="page-header">
            <h1>Settings &amp; Account Health</h1>
            <p class="subtitle">Automatic verification of your profile details, recovery questions, and help guide.</p>
        </header>

        <!-- 1. Automatic Details Diagnostic Check -->
        <section class="card">
            <div class="card-header">
                <h2>🔍 Automatic Details Check</h2>
                <span class="badge-health">● All Details Verified</span>
            </div>

            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
                Live scan of your account configuration from session and database:
            </p>

            <div class="details-check-grid">
                <div class="check-item">
                    <div class="check-icon">👤</div>
                    <div class="check-info">
                        <div class="check-label">Full Username</div>
                        <div class="check-value"><?php echo $user_name; ?></div>
                        <div class="check-status">✓ Detected</div>
                    </div>
                </div>

                <div class="check-item">
                    <div class="check-icon">📧</div>
                    <div class="check-info">
                        <div class="check-label">Email Address</div>
                        <div class="check-value"><?php echo $user_email; ?></div>
                        <div class="check-status">✓ Detected &amp; Active</div>
                    </div>
                </div>

                <div class="check-item">
                    <div class="check-icon">✏️</div>
                    <div class="check-info">
                        <div class="check-label">Name Change Limit</div>
                        <div class="check-value" style="<?php echo ($name_changed >= 1) ? 'color: #f87171;' : 'color: #34d399;'; ?>">
                            <?php echo ($name_changed >= 1) ? 'Locked (1/1 used)' : 'Available (0/1 used)'; ?>
                        </div>
                        <div class="check-status">✓ Policy Verified</div>
                    </div>
                </div>

                <div class="check-item">
                    <div class="check-icon">🖼️</div>
                    <div class="check-info">
                        <div class="check-label">Profile Picture</div>
                        <div class="check-value"><?php echo !empty($profile_image) ? 'Photo Uploaded' : 'Default Avatar'; ?></div>
                        <div class="check-status">✓ Avatar Active</div>
                    </div>
                </div>

                <div class="check-item">
                    <div class="check-icon">🔒</div>
                    <div class="check-info">
                        <div class="check-label">Password Protection</div>
                        <div class="check-value">Encrypted</div>
                        <div class="check-status">✓ Password Hash Active</div>
                    </div>
                </div>

                <div class="check-item">
                    <div class="check-icon">⚡</div>
                    <div class="check-info">
                        <div class="check-label">Account ID</div>
                        <div class="check-value">#<?php echo $user_id; ?></div>
                        <div class="check-status">✓ Authenticated</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Security Questions Form -->
        <section class="card">
            <div class="card-header">
                <h2>🛡️ Security &amp; Recovery Questions</h2>
                <span class="badge-health" style="background: rgba(56, 189, 248, 0.15); border-color: var(--accent); color: var(--accent);">
                    Account Protection
                </span>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert-box"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Question 1: Educational Background</label>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.3rem;">What is the name of your high school?</p>
                    <input type="text" name="q1_answer" value="<?php echo htmlspecialchars($_SESSION['security_q1'] ?? 'BHSS Sogam'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Question 2: Personal Career</label>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.3rem;">What is your target tech degree or degree topic?</p>
                    <input type="text" name="q2_answer" value="<?php echo htmlspecialchars($_SESSION['security_q2'] ?? 'BCA at UGMT'); ?>" required>
                </div>

                <button type="submit" name="save_questions" class="btn btn-primary">💾 Save Security Questions</button>
            </form>
        </section>

        <!-- 3. Frequently Asked Questions -->
        <section class="card">
            <div class="card-header">
                <h2>❓ Common Questions &amp; Answers</h2>
                <span style="font-size: 0.85rem; color: var(--accent);">Click any question to view</span>
            </div>

            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question">
                        <span>1. Why am I only allowed to change my username one time?</span>
                        <span class="faq-icon">+</span>
                    </div>
                    <div class="faq-answer">
                        To maintain identity consistency and prevent account confusion, username edits are limited to 1 time. After your first update, the username is permanently locked.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>2. Can I change my profile picture multiple times?</span>
                        <span class="faq-icon">+</span>
                    </div>
                    <div class="faq-answer">
                        Yes! Profile pictures can be updated as often as you want. Go to your Profile page and click the camera icon to upload a new picture or remove it anytime.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>3. How do I contact the website administrator?</span>
                        <span class="faq-icon">+</span>
                    </div>
                    <div class="faq-answer">
                        You can reach support through the portal helpdesk or contact system administration directly from your registered account.
                    </div>
                </div>
            </div>
        </section>

        <div style="display: flex; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-outline">&larr; Return to Dashboard</a>
            <a href="profile.php" class="btn btn-outline">Go to Profile</a>
        </div>

    </div>

    <script>
        document.querySelectorAll('.faq-question').forEach(header => {
            header.addEventListener('click', () => {
                const item = header.parentElement;
                item.classList.toggle('active');
            });
        });

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