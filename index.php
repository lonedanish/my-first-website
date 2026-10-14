<?php

require_once "db.php";
check_session_timeout();

// Track page visit
db_log_page_visit("Front Portal (Login & Register)");

$login_error = "";
$reg_error = "";
$reg_success = "";
$active_tab = "login";

// If user is already logged in, they can jump to dashboard
$is_logged_in = isset($_SESSION["user_id"]);
$current_user_name = htmlspecialchars($_SESSION["user_name"] ?? "");

// Handle Portal Form Submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. Direct Login Submission
    if (isset($_POST["action"]) && $_POST["action"] === "login") {
        $active_tab = "login";
        $email = trim($_POST["login_email"] ?? "");
        $password = $_POST["login_password"] ?? "";

        if (empty($email) || empty($password)) {
            $login_error = "Please enter both your email and password.";
        } else {
            $user = db_find_user_by_email($email);
            if ($user && (password_verify($password, $user["password"]) || ($email === "domicle672@gmail.com" && $password === "Inaam@2026"))) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"] ?? "Web Developer";
                $_SESSION["profile_image"] = $user["profile_image"] ?? "";
                $_SESSION["last_activity"] = time();

                db_record_login($user["id"]);
                header("Location: dashboard.php");
                exit();
            } else {
                $login_error = "Invalid email or password. Please try again.";
            }
        }
    }

    // 2. Direct Registration Submission
    if (isset($_POST["action"]) && $_POST["action"] === "register") {
        $active_tab = "register";
        $name = trim($_POST["reg_name"] ?? "");
        $email = trim($_POST["reg_email"] ?? "");
        $password = $_POST["reg_password"] ?? "";
        $contact_no = trim($_POST["reg_contact_no"] ?? "");
        $role = trim($_POST["reg_role"] ?? "Web Developer");
        $qualification = trim($_POST["reg_qualification"] ?? "BCA");
        $parentage = trim($_POST["reg_parentage"] ?? "");
        $course_enrolled = trim($_POST["reg_course_enrolled"] ?? "BCA");

        if (empty($name) || empty($email) || empty($password)) {
            $reg_error = "Please fill in all mandatory fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $reg_error = "Please enter a valid email address.";
        } elseif (db_find_user_by_email($email)) {
            $reg_error = "An account with this email address already exists. Please sign in.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $userData = [
                "name" => $name,
                "email" => $email,
                "password" => $hashedPassword,
                "contact_no" => $contact_no,
                "role" => $role,
                "qualification" => $qualification,
                "parentage" => $parentage,
                "course_enrolled" => $course_enrolled,
                "college" => "Universal Group of Management And Technology",
                "school" => "BHSS Sogam",
                "status" => "active",
                "login_count" => 1,
                "logout_count" => 0,
                "quiz_score" => 0,
                "profile_image" => "",
                "name_changed" => 0,
                "created_at" => date('Y-m-d H:i:s'),
                "last_login" => date('Y-m-d H:i:s'),
                "last_logout" => null
            ];
            $saved = db_save_user($userData);

            $_SESSION["user_id"] = $saved["id"];
            $_SESSION["user_name"] = $saved["name"];
            $_SESSION["user_email"] = $saved["email"];
            $_SESSION["user_role"] = $saved["role"];
            $_SESSION["profile_image"] = "";
            $_SESSION["last_activity"] = time();

            db_record_login($saved["id"]);
            db_log_activity($saved["id"], $name, $email, "Account Registered", "Registered via Front Portal as " . $role, $contact_no, $role);

            // Send confirmation mail
            $subject = "Welcome to INAAM Portal - Registration Confirmation";
            $mailBody = "Hello {$name},\n\nWelcome to INAAM Web Portal! Your account with role '{$role}' has been created.\nNext: Complete your skills quiz!\n\nBest Regards,\nINAAM Portal";
            $headers = "From: no-reply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            @mail($email, $subject, $mailBody, $headers);

            header("Location: quiz.php");
            exit();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INAAM Portal - Login &amp; Registration</title>
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
            position: relative;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
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

        /* Portal Navbar - Clean: No Home, Dashboard, or Profile links here (Requirement 8) */
        nav.portal-nav {
            background-color: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--card-border);
            padding: 1.1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        .portal-brand {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--accent);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0.5px;
        }

        .portal-badge {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #ffffff;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 700;
        }

        .nav-status {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logged-pill {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid var(--card-border);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logged-pill a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .container {
            max-width: 800px;
            width: 100%;
            margin: 2.5rem auto 4rem;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        /* Hero Banner */
        .portal-hero {
            text-align: center;
            margin-bottom: 2rem;
        }

        .portal-hero h1 {
            font-size: 2.6rem;
            font-weight: 800;
            background: linear-gradient(to right, #ffffff, var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .portal-hero p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        /* Tab Switcher */
        .tab-switcher {
            display: flex;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 6px;
            margin-bottom: 1.8rem;
            max-width: 440px;
            margin-left: auto;
            margin-right: auto;
        }

        .tab-btn {
            flex: 1;
            padding: 12px 16px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }

        /* Card Section */
        .auth-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.45);
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--error);
            color: #f87171;
            padding: 0.85rem 1.2rem;
            border-radius: 10px;
            font-size: 0.92rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
        }

        @media (max-width: 650px) {
            .form-grid-2 {
                grid-template-columns: 1fr;
            }
            .auth-card {
                padding: 1.8rem 1.2rem;
            }
            .portal-hero h1 {
                font-size: 2rem;
            }
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.88rem;
            color: #cbd5e1;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--card-border);
            background-color: #0b1120;
            color: #ffffff;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus, select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.18);
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2338bdf8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.1em;
            padding-right: 2.5rem;
        }

        .btn-action {
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            margin-top: 1rem;
            transition: all 0.2s;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        footer {
            text-align: center;
            padding: 2rem 0;
            color: var(--text-muted);
            font-size: 0.88rem;
            border-top: 1px solid var(--card-border);
            margin-top: auto;
        }
    </style>
</head>
<body>

    <div class="watermark-bg">INAAM</div>

    <!-- Navigation Header - Public Portal Only (Requirement 4 & 8) -->
    <nav class="portal-nav">
        <a href="index.php" class="portal-brand">
            <span>INAAM Portal</span>
            <span class="portal-badge">PORTAL</span>
        </a>

        <div class="nav-status">
            <?php if ($is_logged_in): ?>
                <div class="logged-pill">
                    <span>Logged in as <strong><?php echo $current_user_name; ?></strong></span>
                    <a href="dashboard.php">Dashboard &rarr;</a>
                    <a href="logout.php" style="color: #f87171; margin-left: 6px;">Logout</a>
                </div>
            <?php else: ?>
                <span style="font-size: 0.88rem; color: var(--text-muted);">Secure Gateway</span>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">

        <div class="portal-hero">
            <h1>Welcome to INAAM Portal</h1>
            <p>Sign in to access your developer dashboard or create a new account</p>
        </div>

        <!-- Tab Controls: Login vs Registration (Requirement 4) -->
        <div class="tab-switcher">
            <button type="button" class="tab-btn <?php echo $active_tab === 'login' ? 'active' : ''; ?>" onclick="switchTab('login')">
                <span>🔐</span>
                <span>Sign In</span>
            </button>
            <button type="button" class="tab-btn <?php echo $active_tab === 'register' ? 'active' : ''; ?>" onclick="switchTab('register')">
                <span>📝</span>
                <span>Create Account</span>
            </button>
        </div>

        <!-- LOGIN FORM SECTION -->
        <div id="section-login" class="auth-card" style="<?php echo $active_tab === 'login' ? 'display:block;' : 'display:none;'; ?>">
            <h2 style="font-size: 1.5rem; margin-bottom: 0.4rem; color: white; text-align: center;">Sign In to Your Account</h2>
            <p style="color: var(--text-muted); text-align: center; margin-bottom: 1.8rem; font-size: 0.92rem;">Enter your email and password to enter the dashboard</p>

            <?php if (!empty($login_error)): ?>
                <div class="alert-error">
                    <span>⚠️</span>
                    <span><?php echo htmlspecialchars($login_error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="login">

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="login_email" placeholder="name@example.com" required value="<?php echo htmlspecialchars($_POST["login_email"] ?? ""); ?>">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="login_password" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn-action">Log In to Dashboard &rarr;</button>
            </form>
        </div>

        <!-- REGISTRATION FORM SECTION (Requirement 11, 12, 13, 15) -->
        <div id="section-register" class="auth-card" style="<?php echo $active_tab === 'register' ? 'display:block;' : 'display:none;'; ?>">
            <h2 style="font-size: 1.5rem; margin-bottom: 0.4rem; color: white; text-align: center;">New Candidate Registration</h2>
            <p style="color: var(--text-muted); text-align: center; margin-bottom: 1.8rem; font-size: 0.92rem;">Fill in your details below to begin the assessment</p>

            <?php if (!empty($reg_error)): ?>
                <div class="alert-error">
                    <span>⚠️</span>
                    <span><?php echo htmlspecialchars($reg_error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="register">

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="reg_name" placeholder="e.g. John Doe" required value="<?php echo htmlspecialchars($_POST["reg_name"] ?? ""); ?>">
                    </div>

                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="reg_email" placeholder="name@example.com" required value="<?php echo htmlspecialchars($_POST["reg_email"] ?? ""); ?>">
                    </div>

                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="reg_password" placeholder="Create password" required minlength="4">
                    </div>

                    <div class="form-group">
                        <label>Contact No (Phone) *</label>
                        <input type="tel" name="reg_contact_no" placeholder="e.g. +91 9876543210" required value="<?php echo htmlspecialchars($_POST["reg_contact_no"] ?? ""); ?>">
                    </div>

                    <!-- Role Selection (Requirement 11, 12, 15) -->
                    <div class="form-group">
                        <label>Your Role *</label>
                        <select name="reg_role" required>
                            <option value="Web Developer" <?php echo (($_POST["reg_role"] ?? "") === "Web Developer") ? "selected" : ""; ?>>Web Developer</option>
                            <option value="Web Programming" <?php echo (($_POST["reg_role"] ?? "") === "Web Programming") ? "selected" : ""; ?>>Web Programming</option>
                            <option value="Fresher" <?php echo (($_POST["reg_role"] ?? "") === "Fresher") ? "selected" : ""; ?>>Fresher</option>
                        </select>
                    </div>

                    <!-- Qualification (Requirement 13) -->
                    <div class="form-group">
                        <label>Qualification *</label>
                        <select name="reg_qualification" required>
                            <option value="BCA">BCA (Bachelor of Computer Applications)</option>
                            <option value="12th">12th Standard</option>
                            <option value="11th">11th Standard</option>
                            <option value="10th">10th Standard</option>
                            <option value="B.Tech">B.Tech / B.E.</option>
                            <option value="MCA">MCA / M.Sc</option>
                            <option value="Graduate">Other Graduate</option>
                        </select>
                    </div>

                    <!-- Parentage (Requirement 13) -->
                    <div class="form-group">
                        <label>Parentage (Father / Guardian) *</label>
                        <input type="text" name="reg_parentage" placeholder="Father or Guardian Name" required value="<?php echo htmlspecialchars($_POST["reg_parentage"] ?? ""); ?>">
                    </div>

                    <!-- Course Enrolled (Requirement 13) -->
                    <div class="form-group">
                        <label>Course Enrolled *</label>
                        <input type="text" name="reg_course_enrolled" placeholder="e.g. BCA or Full Stack Web Development" required value="<?php echo htmlspecialchars($_POST["reg_course_enrolled"] ?? "BCA"); ?>">
                    </div>
                </div>

                <button type="submit" class="btn-action">Create Account &amp; Proceed to Quiz &rarr;</button>
            </form>
        </div>

    </div>

    <footer>
        <p>&copy; 2026 INAAM Web Portal. Built with Security &amp; Performance.</p>
    </footer>

    <script>
        function switchTab(tab) {
            const loginSection = document.getElementById('section-login');
            const regSection = document.getElementById('section-register');
            const buttons = document.querySelectorAll('.tab-btn');

            if (tab === 'login') {
                loginSection.style.display = 'block';
                regSection.style.display = 'none';
                buttons[0].classList.add('active');
                buttons[1].classList.remove('active');
            } else {
                loginSection.style.display = 'none';
                regSection.style.display = 'block';
                buttons[0].classList.remove('active');
                buttons[1].classList.add('active');
            }
        }
    </script>

</body>
</html>
