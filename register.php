<?php

include "db.php";

$message = "";
$is_success = false;
$activation_link = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($name == "" || $email == "" || $password == "") {
        $message = "Please fill all fields.";
    } else {
        $token = bin2hex(random_bytes(16));
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir = dirname($_SERVER['PHP_SELF']);
        $activation_link = $protocol . $host . rtrim($dir, '/\\') . "/activate.php?email=" . urlencode($email) . "&token=" . $token;

        if (isset($conn) && $conn && !$conn->connect_error) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("sss", $name, $email, $hashedPassword);
                if ($stmt->execute()) {
                    $message = "Registration successful! Click the activation link below to activate your account.";
                    $is_success = true;
                } else {
                    $message = "Email already exists or registration failed.";
                }
                $stmt->close();
            } else {
                $message = "Database table 'users' not found. Please create the users table.";
            }
        } else {
            // Local demo without active MySQL database
            $message = "Registration successful! Click the activation link below to activate your account.";
            $is_success = true;
        }

        // Try sending mail if SMTP is configured
        if ($is_success) {
            $subject = "Activate Your Account";
            $mail_body = "Hello $name,\n\nPlease click the link below to activate your account:\n$activation_link\n\nThank you!";
            $headers = "From: no-reply@" . $host;
            @mail($email, $subject, $mail_body, $headers);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - My Website</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1.5rem;
        }

        .register-box {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            width: 100%;
            max-width: 440px;
            padding: 2.5rem 2rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        h1 {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, #ffffff, var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: #ffffff;
        }

        .subtitle {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .alert-message {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 1.2rem;
            text-align: center;
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

        .activation-box {
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid var(--accent);
            border-radius: 12px;
            padding: 1.2rem;
            margin-top: 1.2rem;
            text-align: center;
        }

        .activation-box h3 {
            color: var(--accent);
            font-size: 1.05rem;
            margin-bottom: 0.5rem;
        }

        .activation-box p {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 0.8rem;
        }

        .activation-btn {
            display: block;
            width: 100%;
            padding: 0.75rem;
            background: var(--accent);
            color: #0f172a;
            font-weight: 700;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            transition: opacity 0.2s ease;
        }

        .activation-btn:hover {
            opacity: 0.9;
        }

        .link-input {
            width: 100%;
            padding: 0.5rem;
            background: #0f172a;
            border: 1px solid var(--card-border);
            color: #94a3b8;
            font-size: 0.8rem;
            border-radius: 6px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
            color: #cbd5e1;
        }

        input {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--card-border);
            background-color: #0f172a;
            color: #ffffff;
            font-size: 0.95rem;
            outline: none;
        }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
        }

        .bottom-links {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.2rem;
            border-top: 1px solid var(--card-border);
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .bottom-links a {
            color: var(--accent);
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="register-box">
        <h1>Create Account</h1>
        <p class="subtitle">Join today and build your profile</p>

        <?php if (!empty($message)): ?>
            <div class="alert-message <?php echo $is_success ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($is_success && !empty($activation_link)): ?>
            <div class="activation-box">
                <h3>🔗 Account Activation Link</h3>
                <p>Click below or copy the link to activate your account:</p>
                <a href="<?php echo htmlspecialchars($activation_link); ?>" class="activation-btn">Activate Account Now &rarr;</a>
                <input type="text" readonly value="<?php echo htmlspecialchars($activation_link); ?>" class="link-input" onclick="this.select();">
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="e.g. INAAM" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="name@example.com" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Create a password" required minlength="4">
                </div>

                <button type="submit" class="btn-submit">Register</button>
            </form>
        <?php endif; ?>

        <div class="bottom-links">
            <p>Already have an account? <a href="login.php">Login here</a></p>
            <p style="margin-top: 0.5rem;"><a href="index.html">&larr; Back to Home</a></p>
        </div>
    </div>

</body>
</html>