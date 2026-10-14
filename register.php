<?php

require_once "db.php";
check_session_timeout();

$message = "";
$message_type = "";
$is_success = false;
$registered_user = null;

// Track page visit
db_log_page_visit("Registration Page");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $contact_no = trim($_POST["contact_no"] ?? "");
    $role = trim($_POST["role"] ?? "Web Developer");
    $qualification = trim($_POST["qualification"] ?? "BCA");
    $parentage = trim($_POST["parentage"] ?? "");
    $course_enrolled = trim($_POST["course_enrolled"] ?? "BCA");
    $college = trim($_POST["college"] ?? "Universal Group of Management And Technology");
    $school = trim($_POST["school"] ?? "BHSS Sogam");

    if (empty($name) || empty($email) || empty($password)) {
        $message = "Please complete all required fields (Name, Email, Password).";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please provide a valid email address.";
        $message_type = "error";
    } elseif (db_find_user_by_email($email)) {
        $message = "An account with this email address already exists. Please login instead.";
        $message_type = "error";
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
            "college" => $college,
            "school" => $school,
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

        // Auto authenticate newly registered user
        $_SESSION["user_id"] = $saved["id"];
        $_SESSION["user_name"] = $saved["name"];
        $_SESSION["user_email"] = $saved["email"];
        $_SESSION["user_role"] = $saved["role"];
        $_SESSION["profile_image"] = "";
        $_SESSION["last_activity"] = time();

        // Log registration & initial login
        db_log_activity(
            $saved["id"],
            $name,
            $email,
            "Account Registered",
            "New account created with role '{$role}' and course '{$course_enrolled}'",
            $contact_no,
            $role
        );
        db_record_login($saved["id"]);

        // Send confirmation email to user's registered mail (Requirement 16)
        $subject = "Welcome to INAAM Portal - Registration Confirmation";
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir = dirname($_SERVER['PHP_SELF']);
        $portalUrl = $protocol . $host . rtrim($dir, '/\\') . "/dashboard.php";

        $mailBody = "Hello " . $name . ",\n\n"
                  . "Congratulations! Your account has been successfully created on INAAM's Web Portal.\n\n"
                  . "--- Your Registration Details ---\n"
                  . "Full Name: " . $name . "\n"
                  . "Email Address: " . $email . "\n"
                  . "Contact No: " . $contact_no . "\n"
                  . "Selected Role: " . $role . "\n"
                  . "Qualification: " . $qualification . "\n"
                  . "Parentage: " . $parentage . "\n"
                  . "Course Enrolled: " . $course_enrolled . "\n"
                  . "College: " . $college . "\n"
                  . "School: " . $school . "\n"
                  . "Account Status: Active\n\n"
                  . "Access your dashboard here: " . $portalUrl . "\n\n"
                  . "Next Step: Complete your 4 AI Questions & 20-Question Technical Quiz!\n\n"
                  . "Best Regards,\n"
                  . "INAAM Web Portal Team";

        $headers = "From: no-reply@" . $host . "\r\n"
                 . "Reply-To: domicle672@gmail.com\r\n"
                 . "X-Mailer: PHP/" . phpversion();

        // Send mail via PHP mailer
        @mail($email, $subject, $mailBody, $headers);

        // Record mail in JSON mail logs for audit
        $mailLogs = read_json_data('email_logs');
        array_unshift($mailLogs, [
            "to" => $email,
            "name" => $name,
            "subject" => $subject,
            "body" => $mailBody,
            "sent_at" => date('Y-m-d H:i:s'),
            "status" => "dispatched"
        ]);
        write_json_data('email_logs', array_slice($mailLogs, 0, 200));

        $registered_user = $saved;
        $is_success = true;
        $message = "Registration successful! Confirmation email dispatched to " . htmlspecialchars($email) . ".";
        $message_type = "success";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account &amp; Register - INAAM Portal</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2.5rem 1.5rem;
            position: relative;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Watermark INAAM */
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

        .register-container {
            width: 100%;
            max-width: 640px;
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 1;
        }

        .header-title {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header-title h1 {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(to right, #ffffff, var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.4rem;
        }

        .header-title p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .alert-message {
            padding: 0.9rem 1.2rem;
            border-radius: 10px;
            font-size: 0.92rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.15);
            border: 1px solid var(--success);
            color: #34d399;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--error);
            color: #f87171;
        }

        /* Post-Registration Action Card */
        .success-box {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(56, 189, 248, 0.1));
            border: 1px solid var(--success);
            border-radius: 14px;
            padding: 2rem;
            text-align: center;
        }

        .success-box h2 {
            font-size: 1.6rem;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .success-box p {
            color: #cbd5e1;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .email-pill {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--card-border);
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.9rem;
            color: var(--accent);
            margin-bottom: 1.5rem;
            word-break: break-all;
        }

        .quiz-launch-btn {
            display: block;
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #ffffff;
            font-weight: 700;
            font-size: 1.05rem;
            text-decoration: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            margin-bottom: 0.8rem;
            transition: all 0.2s;
        }

        .quiz-launch-btn:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        .dashboard-direct-btn {
            display: inline-block;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            transition: color 0.2s;
        }

        .dashboard-direct-btn:hover {
            color: white;
            text-decoration: underline;
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .register-container {
                padding: 1.8rem 1.2rem;
            }
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.88rem;
            color: #cbd5e1;
            font-weight: 500;
        }

        label .required-dot {
            color: #f87171;
            margin-left: 2px;
        }

        input, select {
            width: 100%;
            padding: 0.8rem 1rem;
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

        .btn-submit {
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            margin-top: 1rem;
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        .bottom-links {
            text-align: center;
            margin-top: 1.8rem;
            padding-top: 1.2rem;
            border-top: 1px solid var(--card-border);
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .bottom-links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .bottom-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="watermark-bg">INAAM</div>

    <div class="register-container">
        
        <div class="header-title">
            <h1>Create Account</h1>
            <p>Join INAAM Web Portal &bull; Set up your developer profile</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-message <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <span><?php echo $message_type === 'success' ? '✓' : '⚠️'; ?></span>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($is_success && $registered_user): ?>
            <!-- Success Screen: Direct to AI Assessment & 20-Question Quiz -->
            <div class="success-box">
                <h2>Welcome, <?php echo htmlspecialchars($registered_user["name"]); ?>!</h2>
                <p>Your account has been registered with role <strong><?php echo htmlspecialchars($registered_user["role"]); ?></strong>.</p>
                <div class="email-pill">
                    ✉️ Confirmation dispatched to: <strong><?php echo htmlspecialchars($registered_user["email"]); ?></strong>
                </div>

                <a href="quiz.php" class="quiz-launch-btn">
                    🚀 Start 4 AI Questions &amp; 20 Quiz Assessment &rarr;
                </a>

                <a href="dashboard.php" class="dashboard-direct-btn">
                    Or proceed directly to Dashboard &rarr;
                </a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-grid">
                    
                    <!-- 1. Full Name -->
                    <div class="form-group">
                        <label>Full Name <span class="required-dot">*</span></label>
                        <input type="text" name="name" placeholder="e.g. John Doe" required value="<?php echo htmlspecialchars($_POST["name"] ?? ""); ?>">
                    </div>

                    <!-- 2. Email Address -->
                    <div class="form-group">
                        <label>Email Address <span class="required-dot">*</span></label>
                        <input type="email" name="email" placeholder="name@example.com" required value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>">
                    </div>

                    <!-- 3. Password -->
                    <div class="form-group">
                        <label>Password <span class="required-dot">*</span></label>
                        <input type="password" name="password" placeholder="Create strong password" required minlength="4">
                    </div>

                    <!-- 4. Contact Number (Requirement 13) -->
                    <div class="form-group">
                        <label>Contact No (Phone) <span class="required-dot">*</span></label>
                        <input type="tel" name="contact_no" placeholder="e.g. +91 9876543210" required value="<?php echo htmlspecialchars($_POST["contact_no"] ?? ""); ?>">
                    </div>

                    <!-- 5. Role Selection (Requirement 11, 12, 13, 15) -->
                    <div class="form-group">
                        <label>Your Role <span class="required-dot">*</span></label>
                        <select name="role" required>
                            <option value="Web Developer" <?php echo (($_POST["role"] ?? "") === "Web Developer") ? "selected" : ""; ?>>Web Developer</option>
                            <option value="Web Programming" <?php echo (($_POST["role"] ?? "") === "Web Programming") ? "selected" : ""; ?>>Web Programming</option>
                            <option value="Fresher" <?php echo (($_POST["role"] ?? "") === "Fresher") ? "selected" : ""; ?>>Fresher</option>
                        </select>
                    </div>

                    <!-- 6. Qualification (Requirement 13) -->
                    <div class="form-group">
                        <label>Qualification <span class="required-dot">*</span></label>
                        <select name="qualification" required>
                            <option value="BCA" <?php echo (($_POST["qualification"] ?? "") === "BCA") ? "selected" : ""; ?>>BCA (Bachelor of Computer Applications)</option>
                            <option value="12th" <?php echo (($_POST["qualification"] ?? "") === "12th") ? "selected" : ""; ?>>12th Standard</option>
                            <option value="11th" <?php echo (($_POST["qualification"] ?? "") === "11th") ? "selected" : ""; ?>>11th Standard</option>
                            <option value="10th" <?php echo (($_POST["qualification"] ?? "") === "10th") ? "selected" : ""; ?>>10th Standard</option>
                            <option value="B.Tech" <?php echo (($_POST["qualification"] ?? "") === "B.Tech") ? "selected" : ""; ?>>B.Tech / B.E.</option>
                            <option value="MCA" <?php echo (($_POST["qualification"] ?? "") === "MCA") ? "selected" : ""; ?>>MCA / M.Sc</option>
                            <option value="Graduate" <?php echo (($_POST["qualification"] ?? "") === "Graduate") ? "selected" : ""; ?>>Other Graduate</option>
                        </select>
                    </div>

                    <!-- 7. Parentage (Requirement 13) -->
                    <div class="form-group">
                        <label>Parentage (Father / Guardian Name) <span class="required-dot">*</span></label>
                        <input type="text" name="parentage" placeholder="Father or Guardian Name" required value="<?php echo htmlspecialchars($_POST["parentage"] ?? ""); ?>">
                    </div>

                    <!-- 8. Course Enrolled (Requirement 13) -->
                    <div class="form-group">
                        <label>Course Enrolled <span class="required-dot">*</span></label>
                        <input type="text" name="course_enrolled" placeholder="e.g. BCA or Full Stack Web Development" required value="<?php echo htmlspecialchars($_POST["course_enrolled"] ?? "BCA"); ?>">
                    </div>

                    <!-- 9. College -->
                    <div class="form-group">
                        <label>College / Institute</label>
                        <input type="text" name="college" placeholder="College Name" value="<?php echo htmlspecialchars($_POST["college"] ?? "Universal Group of Management And Technology"); ?>">
                    </div>

                    <!-- 10. School -->
                    <div class="form-group">
                        <label>School</label>
                        <input type="text" name="school" placeholder="School Name" value="<?php echo htmlspecialchars($_POST["school"] ?? "BHSS Sogam"); ?>">
                    </div>

                </div>

                <button type="submit" class="btn-submit">Complete Registration &rarr;</button>
            </form>
        <?php endif; ?>

        <div class="bottom-links">
            <p>Already have an account? <a href="login.php">Login here</a></p>
            <p style="margin-top: 0.5rem;"><a href="index.php">&larr; Back to Front Portal</a></p>
        </div>

    </div>

</body>
</html>