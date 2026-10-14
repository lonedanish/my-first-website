<?php

require_once "db.php";
check_session_timeout();

// Ensure user is logged in to take quiz
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user_name = htmlspecialchars($_SESSION["user_name"] ?? "Developer");
$user_email = htmlspecialchars($_SESSION["user_email"] ?? "");
$user_role = htmlspecialchars($_SESSION["user_role"] ?? "Web Developer");

$user = db_find_user_by_id($user_id);
if ($user && !empty($user["role"])) {
    $user_role = htmlspecialchars($user["role"]);
}

$quiz_saved = false;
$saved_score = $user["quiz_score"] ?? 0;

// Handle Quiz Submission
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && isset($_POST["submit_quiz"])) {
    $score = isset($_POST["score"]) ? (int)$_POST["score"] : 0;
    if ($score < 0) $score = 0;
    if ($score > 20) $score = 20;

    db_update_user($user_id, ["quiz_score" => $score]);
    db_log_activity(
        $user_id,
        $user_name,
        $user_email,
        "Quiz Completed",
        "Completed AI Assessment & 20-Question Quiz with score: {$score}/20",
        $user["contact_no"] ?? "",
        $user_role
    );
    $quiz_saved = true;
    $saved_score = $score;
}

db_log_page_visit("AI Assessment & Quiz", $user_name, $user_email);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Readiness Assessment &amp; 20-Question Quiz - INAAM Portal</title>
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

        header.quiz-header {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--card-border);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-logo {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--accent);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-badge {
            background: rgba(56, 189, 248, 0.15);
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: var(--accent);
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-skip {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid var(--card-border);
            transition: all 0.2s;
        }

        .btn-skip:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }

        .container {
            max-width: 860px;
            margin: 2rem auto 4rem;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }

        .welcome-card {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.2), rgba(56, 189, 248, 0.1));
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .welcome-card h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, #ffffff, var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-card p {
            color: var(--text-muted);
            font-size: 1.05rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .role-tag {
            display: inline-block;
            margin-top: 1rem;
            background: rgba(56, 189, 248, 0.15);
            border: 1px solid var(--accent);
            color: #7dd3fc;
            padding: 4px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Section Cards */
        .stage-box {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        .stage-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.35rem;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .stage-pill {
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .stage-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        /* Questions */
        .question-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.2rem;
            transition: border-color 0.2s;
        }

        .question-card:hover {
            border-color: rgba(56, 189, 248, 0.3);
        }

        .question-title {
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 1rem;
            display: flex;
            gap: 8px;
        }

        .q-num {
            color: var(--accent);
            font-weight: 700;
        }

        .options-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .option-label {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--card-border);
            padding: 10px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            color: #cbd5e1;
            transition: all 0.2s;
        }

        .option-label:hover {
            background: rgba(56, 189, 248, 0.1);
            border-color: var(--accent);
            color: white;
        }

        .option-label input[type="radio"] {
            accent-color: var(--accent);
        }

        /* Submit Bar */
        .submit-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1.5rem 2rem;
            margin-top: 2rem;
        }

        .btn-submit-quiz {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            padding: 12px 28px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            transition: all 0.2s;
        }

        .btn-submit-quiz:hover {
            opacity: 0.95;
            transform: translateY(-2px);
        }

        /* Results Modal / Box */
        .results-box {
            text-align: center;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(56, 189, 248, 0.15));
            border: 1px solid var(--success);
            border-radius: 16px;
            padding: 2.5rem 2rem;
            margin-bottom: 2rem;
        }

        .results-box h2 {
            font-size: 2rem;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .score-display {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--accent);
            margin: 1rem 0;
        }

        .btn-continue {
            display: inline-block;
            background: var(--success);
            color: #0b1120;
            font-weight: 700;
            font-size: 1rem;
            padding: 12px 28px;
            border-radius: 10px;
            text-decoration: none;
            margin-top: 1rem;
            transition: opacity 0.2s;
        }

        .btn-continue:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

    <div class="watermark-bg">INAAM</div>

    <header class="quiz-header">
        <a href="dashboard.php" class="brand-logo">
            <span>INAAM Assessment Hub</span>
            <span class="brand-badge">AI Powered</span>
        </a>
        <div class="header-actions">
            <span style="font-size: 0.9rem; color: var(--text-muted);">Candidate: <strong><?php echo $user_name; ?></strong></span>
            <a href="dashboard.php" class="btn-skip">Skip to Dashboard &rarr;</a>
        </div>
    </header>

    <main class="container">

        <?php if ($quiz_saved): ?>
            <div class="results-box">
                <h2>🎉 Assessment &amp; Quiz Completed!</h2>
                <p style="color: #cbd5e1; font-size: 1.1rem;">Your assessment score has been recorded directly into your dashboard profile.</p>
                <div class="score-display"><?php echo $saved_score; ?> / 20</div>
                <p style="color: #34d399; font-weight: 600; font-size: 1.15rem;">
                    <?php 
                        if ($saved_score >= 18) echo "🌟 Outstanding Performance! Grade A+";
                        elseif ($saved_score >= 14) echo "👍 Good Job! Grade A";
                        elseif ($saved_score >= 10) echo "👌 Passed! Ready for advanced modules.";
                        else echo "Keep practicing! You can retake anytime.";
                    ?>
                </p>
                <a href="dashboard.php" class="btn-continue">Go to Your Dashboard &rarr;</a>
            </div>
        <?php endif; ?>

        <div class="welcome-card">
            <h1>Technical Skills Evaluation</h1>
            <p>Welcome, <?php echo $user_name; ?>! Answer the 4 AI-customized questions for your role followed by the 20-question comprehensive quiz.</p>
            <div class="role-tag">Target Role: <?php echo $user_role; ?></div>
        </div>

        <form method="POST" id="quizForm" onsubmit="calculateScore(event)">

            <!-- Stage 1: 4 AI Generated Questions -->
            <section class="stage-box">
                <div class="stage-title">
                    <span>🤖 Part 1: AI Generated Assessment</span>
                    <span class="stage-pill">4 Questions</span>
                </div>
                <p class="stage-desc">Tailored dynamic queries based on your chosen career path: <strong><?php echo $user_role; ?></strong>.</p>

                <?php if ($user_role === "Web Programming"): ?>
                    <!-- Web Programming AI Questions -->
                    <div class="question-card">
                        <div class="question-title"><span class="q-num">AI-1.</span> Which programming paradigm do you predominantly structure backend code with?</div>
                        <div class="options-grid">
                            <label class="option-label"><input type="radio" name="ai_1" value="a" required> Object-Oriented Programming (Classes, Interfaces &amp; Inheritance)</label>
                            <label class="option-label"><input type="radio" name="ai_1" value="b"> Procedural scripts with reusable modular functions</label>
                            <label class="option-label"><input type="radio" name="ai_1" value="c"> Functional programming using pure pipelines &amp; immutable data</label>
                        </div>
                    </div>

                    <div class="question-card">
                        <div class="question-title"><span class="q-num">AI-2.</span> What is the industry standard practice for preventing SQL Injection attacks?</div>
                        <div class="options-grid">
                            <label class="option-label"><input type="radio" name="ai_2" value="a" required> Prepared Statements with Parameterized Queries (PDO/mysqli)</label>
                            <label class="option-label"><input type="radio" name="ai_2" value="b"> Concatenating user inputs directly into query strings</label>
                            <label class="option-label"><input type="radio" name="ai_2" value="c"> Relying solely on client-side HTML pattern validation</label>
                        </div>
                    </div>

                    <div class="question-card">
                        <div class="question-title"><span class="q-num">AI-3.</span> How do you effectively handle runtime exceptions in production web systems?</div>
                        <div class="options-grid">
                            <label class="option-label"><input type="radio" name="ai_3" value="a" required> Structured Try-Catch blocks with centralized error logging</label>
                            <label class="option-label"><input type="radio" name="ai_3" value="b"> Suppress errors with the '@' operator silently</label>
                            <label class="option-label"><input type="radio" name="ai_3" value="c"> Display raw database stack traces directly to visitors</label>
                        </div>
                    </div>

                    <div class="question-card">
                        <div class="question-title"><span class="q-num">AI-4.</span> What is the primary role of indexing database columns in MySQL?</div>
                        <div class="options-grid">
                            <label class="option-label"><input type="radio" name="ai_4" value="a" required> Significantly accelerate data retrieval speeds during search queries</label>
                            <label class="option-label"><input type="radio" name="ai_4" value="b"> Encrypt column values for security compliance</label>
                            <label class="option-label"><input type="radio" name="ai_4" value="c"> Automatically format timestamps to local user timezones</label>
                        </div>
                    </div>

                <?php elseif ($user_role === "Fresher"): ?>
                    <!-- Fresher AI Questions -->
                    <div class="question-card">
                        <div class="question-title"><span class="q-num">AI-1.</span> Which core languages form the fundamental building blocks of the web?</div>
                        <div class="options-grid">
                            <label class="option-label"><input type="radio" name="ai_1" value="a" required> HTML for structure, CSS for styling, and JavaScript for interactivity</label>
                            <label class="option-label"><input type="radio" name="ai_1" value="b"> C++, Java, and Python exclusively</label>
                            <label class="option-label"><input type="radio" name="ai_1" value="c"> SQL for markup and PHP for browser rendering</label>
                        </div>
                    </div>

                    <div class="question-card">
                        <div class="question-title"><span class="q-num">AI-2.</span> What is your primary learning goal in web development for the next 6 months?</div>
                        <div class="options-grid">
                            <label class="option-label"><input type="radio" name="ai_2" value="a" required> Building clean, responsive web projects and mastering fundamentals</label>
                            <label class="option-label"><input type="radio" name="ai_2" value="b"> Mastering full-stack web applications with authentication &amp; databases</label>
                            <label class="option-label"><input type="radio" name="ai_2" value="c"> Exploring modern JavaScript frameworks and UI/UX design</label>
                        </div>
                    </div>

                    <div class="question-card">
                        <div class="question-title"><span class="q-num">AI-3.</span> How do you test and inspect your web page code while coding?</div>
                        <div class="options-grid">
                            <label class="option-label"><input type="radio" name="ai_3" value="a" required> Using Browser DevTools (Inspect Element, Console, Network Tab)</label>
                            <label class="option-label"><input type="radio" name="ai_3" value="b"> Printing out the code on paper to read line by line</label>
                            <label class="option-label"><input type="radio" name="ai_3" value="c"> Guessing errors without testing in a browser</label>
                        </div>
                    </div>

                    <div class="question-card">
                        <div class="question-title"><span class="q-num">AI-4.</span> What does responsive design mean for a modern website?</div>
                        <div class="options-grid">
                            <label class="option-label"><input type="radio" name="ai_4" value="a" required> The website automatically adapts and looks great on mobiles, tablets, and desktops</label>
                            <label class="option-label"><input type="radio" name="ai_4" value="b"> The website only opens on laptop screens</label>
                            <label class="option-label"><input type="radio" name="ai_4" value="c"> The website sends automatic email replies to visitors</label>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Web Developer AI Questions (Default) -->
                    <div class="question-card">
                        <div class="question-title"><span class="q-num">AI-1.</span> What CSS layout system do you favor for multi-dimensional grid structures?</div>
                        <div class="options-grid">
                            <label class="option-label"><input type="radio" name="ai_1" value="a" required> CSS Grid for 2D page architecture and Flexbox for 1D component rows</label>
                            <label class="option-label"><input type="radio" name="ai_1" value="b"> HTML `<table>` elements nested for full site layouts</label>
                            <label class="option-label"><input type="radio" name="ai_1" value="c"> Absolute positioning with hardcoded pixel coordinates</label>
                        </div>
                    </div>

                    <div class="question-card">
                        <div class="question-title"><span class="q-num">AI-2.</span> How do you ensure high performance and fast load times on frontend assets?</div>
                        <div class="options-grid">
                            <label class="option-label"><input type="radio" name="ai_2" value="a" required> Modern image formats (WebP), asset minification &amp; lazy loading</label>
                            <label class="option-label"><input type="radio" name="ai_2" value="b"> Loading uncompressed 10MB PNG images on the homepage</label>
                            <label class="option-label"><input type="radio" name="ai_2" value="c"> Linking 50 external CSS files in `<head>` without bundling</label>
                        </div>
                    </div>

                    <div class="question-card">
                        <div class="question-title"><span class="q-num">AI-3.</span> How do you securely handle user authentication state in a web application?</div>
                        <div class="options-grid">
                            <label class="option-label"><input type="radio" name="ai_3" value="a" required> Server-managed Sessions with HttpOnly, Secure session cookies</label>
                            <label class="option-label"><input type="radio" name="ai_3" value="b"> Storing unencrypted plaintext passwords in localStorage</label>
                            <label class="option-label"><input type="radio" name="ai_3" value="c"> Passing the password in URL query parameters on every page</label>
                        </div>
                    </div>

                    <div class="question-card">
                        <div class="question-title"><span class="q-num">AI-4.</span> When interacting with asynchronous backend APIs, which modern syntax is preferred?</div>
                        <div class="options-grid">
                            <label class="option-label"><input type="radio" name="ai_4" value="a" required> `async/await` with `fetch()` and `try...catch` handling</label>
                            <label class="option-label"><input type="radio" name="ai_4" value="b"> Synchronous blocking XMLHttpRequest</label>
                            <label class="option-label"><input type="radio" name="ai_4" value="c"> Infinite polling with zero delay setInterval</label>
                        </div>
                    </div>
                <?php endif; ?>

            </section>

            <!-- Stage 2: 20 Question Comprehensive Quiz -->
            <section class="stage-box">
                <div class="stage-title">
                    <span>📝 Part 2: 20-Question Web Tech Quiz</span>
                    <span class="stage-pill">20 Questions</span>
                </div>
                <p class="stage-desc">Test your real-world knowledge of HTML5, CSS3, JavaScript, Web Security, Databases, and Dev tools.</p>

                <!-- Q1 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">1.</span> Which HTML5 element represents the primary navigation links of a website?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q1" value="nav" data-correct="true"> &lt;nav&gt;</label>
                        <label class="option-label"><input type="radio" name="q1" value="menu"> &lt;menu&gt;</label>
                        <label class="option-label"><input type="radio" name="q1" value="links"> &lt;links&gt;</label>
                    </div>
                </div>

                <!-- Q2 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">2.</span> Which HTML attribute enforces that an input must be filled before form submission?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q2" value="required" data-correct="true"> required</label>
                        <label class="option-label"><input type="radio" name="q2" value="validate"> validate</label>
                        <label class="option-label"><input type="radio" name="q2" value="mandatory"> mandatory</label>
                    </div>
                </div>

                <!-- Q3 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">3.</span> What does the viewport meta tag &lt;meta name="viewport" content="width=device-width, initial-scale=1.0"&gt; accomplish?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q3" value="responsive" data-correct="true"> Configures the page width and scaling for mobile screen sizes</label>
                        <label class="option-label"><input type="radio" name="q3" value="speed"> Speeds up image downloading</label>
                        <label class="option-label"><input type="radio" name="q3" value="color"> Sets the browser theme color</label>
                    </div>
                </div>

                <!-- Q4 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">4.</span> In CSS Flexbox, which property aligns items along the cross-axis?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q4" value="align-items" data-correct="true"> align-items</label>
                        <label class="option-label"><input type="radio" name="q4" value="justify-content"> justify-content</label>
                        <label class="option-label"><input type="radio" name="q4" value="flex-direction"> flex-direction</label>
                    </div>
                </div>

                <!-- Q5 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">5.</span> What is the difference between "display: none" and "visibility: hidden"?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q5" value="a" data-correct="true"> display:none removes the element from layout; visibility:hidden hides it but preserves its space</label>
                        <label class="option-label"><input type="radio" name="q5" value="b"> They are 100% identical in behavior</label>
                        <label class="option-label"><input type="radio" name="q5" value="c"> display:none only works on images</label>
                    </div>
                </div>

                <!-- Q6 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">6.</span> Which CSS unit is relative to the font-size of the root &lt;html&gt; element?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q6" value="rem" data-correct="true"> rem</label>
                        <label class="option-label"><input type="radio" name="q6" value="em"> em</label>
                        <label class="option-label"><input type="radio" name="q6" value="px"> px</label>
                    </div>
                </div>

                <!-- Q7 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">7.</span> What is the primary difference between CSS Grid and Flexbox?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q7" value="a" data-correct="true"> CSS Grid is two-dimensional (rows &amp; columns); Flexbox is one-dimensional (row OR column)</label>
                        <label class="option-label"><input type="radio" name="q7" value="b"> CSS Grid cannot handle responsive designs</label>
                        <label class="option-label"><input type="radio" name="q7" value="c"> Flexbox only works on text</label>
                    </div>
                </div>

                <!-- Q8 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">8.</span> What is the difference between "==" and "===" in JavaScript?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q8" value="strict" data-correct="true"> "===" performs strict comparison checking both value and type without coercion</label>
                        <label class="option-label"><input type="radio" name="q8" value="same"> There is no difference</label>
                        <label class="option-label"><input type="radio" name="q8" value="assign"> "===" is used to assign variables</label>
                    </div>
                </div>

                <!-- Q9 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">9.</span> Which JavaScript array method returns a new array containing items that pass a truth test?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q9" value="filter" data-correct="true"> filter()</label>
                        <label class="option-label"><input type="radio" name="q9" value="map"> map()</label>
                        <label class="option-label"><input type="radio" name="q9" value="forEach"> forEach()</label>
                    </div>
                </div>

                <!-- Q10 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">10.</span> What does an async function in JavaScript always return?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q10" value="promise" data-correct="true"> A Promise</label>
                        <label class="option-label"><input type="radio" name="q10" value="boolean"> A Boolean</label>
                        <label class="option-label"><input type="radio" name="q10" value="undefined"> undefined</label>
                    </div>
                </div>

                <!-- Q11 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">11.</span> How do you prevent a form from performing a page refresh on submit in JavaScript?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q11" value="prevent" data-correct="true"> event.preventDefault()</label>
                        <label class="option-label"><input type="radio" name="q11" value="stop"> event.stopPropagation()</label>
                        <label class="option-label"><input type="radio" name="q11" value="halt"> window.stop()</label>
                    </div>
                </div>

                <!-- Q12 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">12.</span> What is the key difference between localStorage and sessionStorage?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q12" value="persist" data-correct="true"> localStorage persists until explicitly cleared; sessionStorage clears when the tab/window closes</label>
                        <label class="option-label"><input type="radio" name="q12" value="server"> sessionStorage is stored on the remote server</label>
                        <label class="option-label"><input type="radio" name="q12" value="size"> localStorage can only store 10 characters</label>
                    </div>
                </div>

                <!-- Q13 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">13.</span> Why should passwords be hashed using bcrypt/argon2 instead of MD5/SHA1?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q13" value="salt" data-correct="true"> MD5 is fast and vulnerable to rainbow tables; bcrypt uses computational cost and automatic salt</label>
                        <label class="option-label"><input type="radio" name="q13" value="short"> MD5 hashes are too long for database columns</label>
                        <label class="option-label"><input type="radio" name="q13" value="php"> PHP does not support MD5</label>
                    </div>
                </div>

                <!-- Q14 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">14.</span> What is Cross-Site Scripting (XSS) and how is it prevented in PHP/HTML?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q14" value="xss" data-correct="true"> Malicious script injection; prevented by sanitizing and escaping outputs with htmlspecialchars()</label>
                        <label class="option-label"><input type="radio" name="q14" value="crash"> It is when a CSS file crashes the server</label>
                        <label class="option-label"><input type="radio" name="q14" value="firewall"> It is prevented by closing the browser</label>
                    </div>
                </div>

                <!-- Q15 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">15.</span> What is the primary purpose of a session in web applications?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q15" value="session" data-correct="true"> Maintain authenticated user state across multiple stateless HTTP requests</label>
                        <label class="option-label"><input type="radio" name="q15" value="database"> Backup the MySQL database to disk</label>
                        <label class="option-label"><input type="radio" name="q15" value="css"> Style the webpage with CSS</label>
                    </div>
                </div>

                <!-- Q16 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">16.</span> Which SQL clause is used to filter records returned by a SELECT query?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q16" value="where" data-correct="true"> WHERE</label>
                        <label class="option-label"><input type="radio" name="q16" value="order"> ORDER BY</label>
                        <label class="option-label"><input type="radio" name="q16" value="group"> GROUP BY</label>
                    </div>
                </div>

                <!-- Q17 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">17.</span> What is a Primary Key in a relational database table?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q17" value="pk" data-correct="true"> A unique identifier for each record that prevents duplicate row identities</label>
                        <label class="option-label"><input type="radio" name="q17" value="pass"> The password to access the database</label>
                        <label class="option-label"><input type="radio" name="q17" value="table"> The first column created in any table</label>
                    </div>
                </div>

                <!-- Q18 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">18.</span> Which HTTP status code signifies a successful request?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q18" value="200" data-correct="true"> 200 OK</label>
                        <label class="option-label"><input type="radio" name="q18" value="404"> 404 Not Found</label>
                        <label class="option-label"><input type="radio" name="q18" value="500"> 500 Internal Server Error</label>
                    </div>
                </div>

                <!-- Q19 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">19.</span> Which Git command creates and immediately switches to a new branch?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q19" value="checkout" data-correct="true"> git checkout -b &lt;branch-name&gt;</label>
                        <label class="option-label"><input type="radio" name="q19" value="commit"> git commit -m &lt;branch-name&gt;</label>
                        <label class="option-label"><input type="radio" name="q19" value="push"> git push origin master</label>
                    </div>
                </div>

                <!-- Q20 -->
                <div class="question-card">
                    <div class="question-title"><span class="q-num">20.</span> Which tab in browser Developer Tools is used to inspect HTTP requests, headers, and payloads?</div>
                    <div class="options-grid">
                        <label class="option-label"><input type="radio" name="q20" value="network" data-correct="true"> Network Tab</label>
                        <label class="option-label"><input type="radio" name="q20" value="elements"> Elements Tab</label>
                        <label class="option-label"><input type="radio" name="q20" value="memory"> Memory Tab</label>
                    </div>
                </div>

            </section>

            <input type="hidden" name="score" id="calculatedScoreInput" value="0">
            <input type="hidden" name="submit_quiz" value="1">

            <div class="submit-section">
                <div>
                    <h3 style="color: white; font-size: 1.1rem; margin-bottom: 4px;">Finished Answering?</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Click below to calculate your score and register it on your profile.</p>
                </div>
                <button type="submit" class="btn-submit-quiz">Submit Evaluation &rarr;</button>
            </div>

        </form>

    </main>

    <script>
        function calculateScore(e) {
            // Count correct answers for the 20 questions
            let score = 0;
            const correctRadios = document.querySelectorAll('input[type="radio"][data-correct="true"]');
            correctRadios.forEach(radio => {
                if (radio.checked) {
                    score++;
                }
            });

            document.getElementById('calculatedScoreInput').value = score;
        }

        // 10-minute session inactivity timer
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
