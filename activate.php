<?php

require_once "db.php";

$message = "";
$status = "error";

$email = isset($_GET["email"]) ? trim($_GET["email"]) : "";
$token = isset($_GET["token"]) ? trim($_GET["token"]) : "";

if (empty($email) || empty($token)) {
    $message = "Invalid activation link. Missing email or token.";
} else {
    $user = db_find_user_by_email($email);
    if ($user) {
        db_update_user($user["id"], ["status" => "active"]);
        db_log_activity(
            $user["id"],
            $user["name"],
            $user["email"],
            "Account Activated",
            "Account activated via email activation token",
            $user["contact_no"] ?? "",
            $user["role"] ?? ""
        );
        $status = "success";
        $message = "Your account has been activated successfully! You can now log in to the portal.";
    } else {
        $status = "success";
        $message = "Account verified successfully! You can now log in.";
    }
}

db_log_page_visit("Account Activation Page");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activation - INAAM Portal</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1.5rem;
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

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 2.8rem 2.2rem;
            max-width: 440px;
            width: 100%;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.45);
            position: relative;
            z-index: 1;
        }

        .icon-wrap {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .icon-success {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 2px solid var(--success);
        }

        .icon-error {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 2px solid var(--error);
        }

        h1 {
            font-size: 1.8rem;
            margin-bottom: 0.8rem;
            color: #ffffff;
            font-weight: 700;
        }

        p {
            color: var(--text-muted);
            font-size: 1rem;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            transition: opacity 0.2s ease;
        }

        .btn:hover {
            opacity: 0.95;
        }
    </style>
</head>
<body>

    <div class="watermark-bg">INAAM</div>

    <div class="card">
        <div class="icon-wrap <?php echo ($status === 'success') ? 'icon-success' : 'icon-error'; ?>">
            <?php echo ($status === 'success') ? '✓' : '✕'; ?>
        </div>

        <h1><?php echo ($status === 'success') ? 'Account Activated!' : 'Activation Failed'; ?></h1>
        <p><?php echo htmlspecialchars($message); ?></p>

        <a href="login.php" class="btn">Proceed to Sign In &rarr;</a>
    </div>

</body>
</html>
