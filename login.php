<?php

session_start();

include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($conn) || !$conn || $conn->connect_error) {
        $message = "Database connection error. Please verify your MySQL password in db.php.";
    } else {
        $email = trim($_POST["email"]);
        $password = $_POST["password"];

        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user["password"])) {
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["user_name"] = $user["name"];
                    $_SESSION["user_email"] = $user["email"];

                    header("Location: dashboard.php");
                    exit();
                } else {
                    $message = "Incorrect password.";
                }
            } else {
                $message = "Account not found with that email.";
            }

            $stmt->close();
        } else {
            $message = "Database table 'users' not found.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - My Website</title>
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

        .login-box {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            width: 100%;
            max-width: 400px;
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

        .alert-error {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--error);
            color: #f87171;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 1.2rem;
            text-align: center;
            font-weight: 500;
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

    <div class="login-box">
        <h1>Welcome Back</h1>
        <p class="subtitle">Enter your credentials to access your dashboard</p>

        <?php if (!empty($message)): ?>
            <div class="alert-error">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="name@example.com" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn-submit">Login to Dashboard</button>
        </form>

        <div class="bottom-links">
            <p>Don't have an account? <a href="register.php">Create an account</a></p>
            <p style="margin-top: 0.5rem;"><a href="index.html">&larr; Back to Home</a></p>
        </div>
    </div>

</body>
</html>