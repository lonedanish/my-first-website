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

$message = "";
$message_type = "";

// Track page visit
db_log_page_visit("Profile Page", $user["name"] ?? "User", $user["email"] ?? "");

// 1. Handle Profile Info Update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile_info"])) {
    $name = trim($_POST["name"] ?? "");
    $contact_no = trim($_POST["contact_no"] ?? "");
    $role = trim($_POST["role"] ?? "Web Developer");
    $qualification = trim($_POST["qualification"] ?? "BCA");
    $parentage = trim($_POST["parentage"] ?? "");
    $course_enrolled = trim($_POST["course_enrolled"] ?? "BCA");
    $college = trim($_POST["college"] ?? "");
    $school = trim($_POST["school"] ?? "");

    if (empty($name)) {
        $message = "Name cannot be empty.";
        $message_type = "error";
    } else {
        $updates = [
            "name" => $name,
            "contact_no" => $contact_no,
            "role" => $role,
            "qualification" => $qualification,
            "parentage" => $parentage,
            "course_enrolled" => $course_enrolled,
            "college" => $college,
            "school" => $school
        ];
        db_update_user($user_id, $updates);

        $_SESSION["user_name"] = $name;
        $_SESSION["user_role"] = $role;

        // Refresh user record
        $user = db_find_user_by_id($user_id);

        db_log_activity(
            $user_id,
            $name,
            $user["email"] ?? "",
            "Profile Updated",
            "Updated contact details, role to {$role}, and qualifications",
            $contact_no,
            $role
        );

        $message = "✓ Profile details updated successfully!";
        $message_type = "success";
    }
}

// 2. Handle Base64 Cropped Photo Upload (Requirement 6)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cropped_image_data"])) {
    $imgData = $_POST["cropped_image_data"];
    if (preg_match('/^data:image\/(\w+);base64,/', $imgData, $type)) {
        $imgData = substr($imgData, strpos($imgData, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, etc.

        $decodedData = base64_decode($imgData);
        if ($decodedData !== false) {
            $upload_dir = __DIR__ . "/uploads/";
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0777, true);
            }

            $new_filename = "avatar_" . $user_id . "_" . time() . ".png";
            $dest_path = $upload_dir . $new_filename;

            if (file_put_contents($dest_path, $decodedData)) {
                $relative_path = "uploads/" . $new_filename;
                db_update_user($user_id, ["profile_image" => $relative_path]);
                $_SESSION["profile_image"] = $relative_path;
                $user["profile_image"] = $relative_path;

                db_log_activity(
                    $user_id,
                    $user["name"] ?? "User",
                    $user["email"] ?? "",
                    "Profile Photo Cropped & Saved",
                    "Uploaded newly cropped profile avatar",
                    $user["contact_no"] ?? "",
                    $user["role"] ?? ""
                );

                $message = "✓ Profile photo successfully cropped and updated!";
                $message_type = "success";
            } else {
                $message = "Could not save cropped photo to uploads directory.";
                $message_type = "error";
            }
        }
    }
}

// 3. Handle Remove Picture
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove_picture"])) {
    db_update_user($user_id, ["profile_image" => ""]);
    $_SESSION["profile_image"] = "";
    $user["profile_image"] = "";

    db_log_activity(
        $user_id,
        $user["name"] ?? "User",
        $user["email"] ?? "",
        "Profile Photo Removed",
        "Profile picture reset to default avatar initials",
        $user["contact_no"] ?? "",
        $user["role"] ?? ""
    );

    $message = "Profile picture removed.";
    $message_type = "success";
}

$user_name = htmlspecialchars($user["name"] ?? "");
$user_email = htmlspecialchars($user["email"] ?? "");
$user_role = htmlspecialchars($user["role"] ?? "Web Developer");
$contact_no = htmlspecialchars($user["contact_no"] ?? "");
$qualification = htmlspecialchars($user["qualification"] ?? "BCA");
$parentage = htmlspecialchars($user["parentage"] ?? "");
$course_enrolled = htmlspecialchars($user["course_enrolled"] ?? "BCA");
$college = htmlspecialchars($user["college"] ?? "Universal Group of Management And Technology");
$school = htmlspecialchars($user["school"] ?? "BHSS Sogam");
$account_status = htmlspecialchars($user["status"] ?? "Active");
$login_count = (int)($user["login_count"] ?? 1);
$logout_count = (int)($user["logout_count"] ?? 0);
$quiz_score = (int)($user["quiz_score"] ?? 0);
$profile_image = $user["profile_image"] ?? "";

$is_admin = is_owner_or_admin();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit &amp; View Profile - <?php echo $user_name; ?></title>

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
            max-width: 1100px;
            margin: 2.5rem auto 4rem;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }

        .alert-box {
            padding: 0.9rem 1.2rem;
            border-radius: 12px;
            font-size: 0.92rem;
            margin-bottom: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid var(--success);
            color: #34d399;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--error);
            color: #f87171;
        }

        /* 2-Column Grid for Profile */
        .profile-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 2rem;
        }

        @media (max-width: 850px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }

        /* Profile Left Card */
        .avatar-section {
            text-align: center;
        }

        .profile-photo-wrapper {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            padding: 4px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            box-shadow: 0 10px 25px rgba(56, 189, 248, 0.3);
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            overflow: hidden;
        }

        .avatar-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-btn-group {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            margin-top: 1rem;
        }

        .btn-crop-trigger {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            padding: 0.75rem 1.2rem;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-crop-trigger:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        .btn-remove-pic {
            background: transparent;
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #f87171;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-remove-pic:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        .profile-summary-list {
            list-style: none;
            margin-top: 1.8rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            font-size: 0.9rem;
        }

        .profile-summary-item {
            display: flex;
            justify-content: space-between;
            color: #cbd5e1;
        }

        /* Profile Right Form */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
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
            transition: border-color 0.2s;
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

        .btn-save {
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
            border: none;
            padding: 0.9rem 1.8rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            transition: all 0.2s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        /* ======================================================== */
        /* PHOTO CROPPER MODAL (Requirement 6)                      */
        /* ======================================================== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
        }

        .crop-modal-content {
            background: #151f32;
            border: 1px solid var(--card-border);
            border-radius: 20px;
            max-width: 550px;
            width: 100%;
            padding: 2rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
            position: relative;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 0.8rem;
        }

        .modal-header h3 {
            font-size: 1.3rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-close-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.4rem;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close-btn:hover {
            color: white;
        }

        .canvas-container {
            width: 100%;
            height: 320px;
            background: #090e1a;
            border-radius: 12px;
            border: 1px dashed var(--card-border);
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: grab;
            user-select: none;
        }

        .canvas-container:active {
            cursor: grabbing;
        }

        #cropCanvas {
            max-width: 100%;
            max-height: 100%;
            display: block;
        }

        .crop-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .zoom-slider-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .zoom-slider-wrap input[type="range"] {
            accent-color: var(--accent);
            cursor: pointer;
        }

        .btn-crop-save {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            padding: 0.75rem 1.6rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-crop-save:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .crop-circle-overlay {
            position: absolute;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            border: 2px dashed var(--accent);
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.55);
            pointer-events: none;
        }
    </style>
</head>
<body>

    <div class="watermark-bg">INAAM</div>

    <!-- Navigation Header -->
    <nav class="navbar">
        <a href="dashboard.php" class="brand">
            <span>INAAM Portal</span>
            <span style="font-size: 0.75rem; background: rgba(56, 189, 248, 0.15); border: 1px solid var(--accent); color: var(--accent); padding: 2px 8px; border-radius: 6px;">PROFILE</span>
        </a>

        <div class="nav-links">
            <a href="dashboard.php">&larr; Back to Dashboard</a>
            <a href="profile.php" class="active">Profile</a>
            <a href="activity.php">Activity</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">

        <?php if (!empty($message)): ?>
            <div class="alert-box <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <span><?php echo $message_type === 'success' ? '✓' : '⚠️'; ?></span>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <div class="profile-grid">

            <!-- LEFT CARD: Avatar & Cropper Trigger & Quick Info -->
            <div class="card avatar-section">
                
                <div class="profile-photo-wrapper">
                    <div class="avatar-img" id="avatarPreviewContainer">
                        <?php if (!empty($profile_image) && file_exists(__DIR__ . "/" . $profile_image)): ?>
                            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Picture" id="currentAvatarImg">
                        <?php else: ?>
                            <span id="initialsAvatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <h2 style="font-size: 1.4rem; color: white;"><?php echo $user_name; ?></h2>
                <p style="color: var(--accent); font-weight: 600; font-size: 0.95rem; margin-top: 2px;"><?php echo $user_role; ?></p>
                <p style="color: var(--text-muted); font-size: 0.85rem;"><?php echo $user_email; ?></p>

                <!-- Hidden file input for Cropper -->
                <input type="file" id="photoFileInput" accept="image/png, image/jpeg, image/webp" style="display: none;" onchange="handleFileSelected(event)">

                <div class="photo-btn-group">
                    <button type="button" class="btn-crop-trigger" onclick="document.getElementById('photoFileInput').click()">
                        <span>✂️</span>
                        <span>Crop &amp; Upload New Photo</span>
                    </button>

                    <?php if (!empty($profile_image)): ?>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="remove_picture" value="1">
                            <button type="submit" class="btn-remove-pic" style="width: 100%;" onclick="return confirm('Remove profile picture?')">
                                Remove Picture
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <ul class="profile-summary-list">
                    <li class="profile-summary-item">
                        <span style="color: var(--text-muted);">Account Status:</span>
                        <span style="color: #34d399; font-weight: 600;">Active &amp; Verified</span>
                    </li>
                    <li class="profile-summary-item">
                        <span style="color: var(--text-muted);">Total Logins:</span>
                        <span style="font-weight: 600;"><?php echo $login_count; ?> times</span>
                    </li>
                    <li class="profile-summary-item">
                        <span style="color: var(--text-muted);">Total Logouts:</span>
                        <span style="font-weight: 600;"><?php echo $logout_count; ?> times</span>
                    </li>
                    <li class="profile-summary-item">
                        <span style="color: var(--text-muted);">Quiz Assessment:</span>
                        <span style="color: var(--accent); font-weight: 700;"><?php echo $quiz_score; ?> / 20</span>
                    </li>
                </ul>

            </div>

            <!-- RIGHT CARD: Profile Details Editor (Requirement 6 & 17) -->
            <div class="card">
                <h2 style="font-size: 1.4rem; color: white; margin-bottom: 0.4rem;">Edit Profile Details</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.8rem;">
                    Update your personal information, role, and qualifications below.
                </p>

                <form method="POST">
                    <input type="hidden" name="update_profile_info" value="1">

                    <div class="form-grid">
                        
                        <!-- Full Name -->
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" value="<?php echo $user_name; ?>" required>
                        </div>

                        <!-- Email Address (Readonly / Protected) -->
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo $user_email; ?>" readonly style="opacity: 0.7; cursor: not-allowed;">
                        </div>

                        <!-- Contact Number (Phone) -->
                        <div class="form-group">
                            <label>Contact Number (Phone) *</label>
                            <input type="tel" name="contact_no" value="<?php echo $contact_no; ?>" placeholder="+91 9876543210" required>
                        </div>

                        <!-- Role (Web Developer / Web Programming / Fresher) -->
                        <div class="form-group">
                            <label>Role in Dashboard *</label>
                            <select name="role" required>
                                <option value="Web Developer" <?php echo ($user_role === "Web Developer") ? "selected" : ""; ?>>Web Developer</option>
                                <option value="Web Programming" <?php echo ($user_role === "Web Programming") ? "selected" : ""; ?>>Web Programming</option>
                                <option value="Fresher" <?php echo ($user_role === "Fresher") ? "selected" : ""; ?>>Fresher</option>
                            </select>
                        </div>

                        <!-- Qualification -->
                        <div class="form-group">
                            <label>Qualification *</label>
                            <select name="qualification" required>
                                <option value="BCA" <?php echo ($qualification === "BCA") ? "selected" : ""; ?>>BCA (Bachelor of Computer Applications)</option>
                                <option value="12th" <?php echo ($qualification === "12th") ? "selected" : ""; ?>>12th Standard</option>
                                <option value="11th" <?php echo ($qualification === "11th") ? "selected" : ""; ?>>11th Standard</option>
                                <option value="10th" <?php echo ($qualification === "10th") ? "selected" : ""; ?>>10th Standard</option>
                                <option value="B.Tech" <?php echo ($qualification === "B.Tech") ? "selected" : ""; ?>>B.Tech / B.E.</option>
                                <option value="MCA" <?php echo ($qualification === "MCA") ? "selected" : ""; ?>>MCA / M.Sc</option>
                                <option value="Graduate" <?php echo ($qualification === "Graduate") ? "selected" : ""; ?>>Other Graduate</option>
                            </select>
                        </div>

                        <!-- Parentage -->
                        <div class="form-group">
                            <label>Parentage (Father / Guardian) *</label>
                            <input type="text" name="parentage" value="<?php echo $parentage; ?>" placeholder="Father or Guardian Name" required>
                        </div>

                        <!-- Course Enrolled -->
                        <div class="form-group">
                            <label>Course Enrolled *</label>
                            <input type="text" name="course_enrolled" value="<?php echo $course_enrolled; ?>" placeholder="e.g. BCA or Full Stack Development" required>
                        </div>

                        <!-- College -->
                        <div class="form-group">
                            <label>College / Institute</label>
                            <input type="text" name="college" value="<?php echo $college; ?>">
                        </div>

                        <!-- School -->
                        <div class="form-group full-width">
                            <label>School</label>
                            <input type="text" name="school" value="<?php echo $school; ?>">
                        </div>

                    </div>

                    <button type="submit" class="btn-save">Save Profile Changes &rarr;</button>
                </form>

            </div>

        </div>

    </div>

    <!-- ======================================================== -->
    <!-- INTERACTIVE PHOTO CROPPING MODAL (Requirement 6)          -->
    <!-- ======================================================== -->
    <div class="modal-overlay" id="cropModal">
        <div class="crop-modal-content">
            <div class="modal-header">
                <h3><span>✂️</span> <span>Crop Your Profile Photo</span></h3>
                <button type="button" class="modal-close-btn" onclick="closeCropModal()">&times;</button>
            </div>

            <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1rem;">
                Drag to reposition and use the zoom slider to adjust your photo inside the circular frame.
            </p>

            <div class="canvas-container" id="canvasWrapper">
                <canvas id="cropCanvas" width="400" height="400"></canvas>
                <div class="crop-circle-overlay"></div>
            </div>

            <div class="crop-controls">
                <div class="zoom-slider-wrap">
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Zoom:</span>
                    <input type="range" id="zoomRange" min="0.5" max="3" step="0.05" value="1" oninput="onZoomChange(this.value)">
                </div>

                <button type="button" class="btn-crop-save" onclick="applyAndSaveCrop()">
                    Crop &amp; Save Avatar &rarr;
                </button>
            </div>

            <!-- Form to submit cropped Base64 to server -->
            <form id="croppedUploadForm" method="POST" style="display: none;">
                <input type="hidden" name="cropped_image_data" id="croppedImageDataInput">
            </form>
        </div>
    </div>

    <script>
        // Interactive Canvas Cropper Implementation
        let rawImage = null;
        let imgX = 0;
        let imgY = 0;
        let imgScale = 1;
        let isDragging = false;
        let startX = 0;
        let startY = 0;

        const canvas = document.getElementById('cropCanvas');
        const ctx = canvas.getContext('2d');
        const canvasWrapper = document.getElementById('canvasWrapper');

        function handleFileSelected(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(evt) {
                rawImage = new Image();
                rawImage.onload = function() {
                    openCropModal();
                };
                rawImage.src = evt.target.result;
            };
            reader.readAsDataURL(file);
        }

        function openCropModal() {
            document.getElementById('cropModal').style.display = 'flex';
            // Center image in canvas
            imgScale = Math.max(canvas.width / rawImage.width, canvas.height / rawImage.height);
            document.getElementById('zoomRange').value = imgScale;
            imgX = (canvas.width - rawImage.width * imgScale) / 2;
            imgY = (canvas.height - rawImage.height * imgScale) / 2;
            drawCanvas();
        }

        function closeCropModal() {
            document.getElementById('cropModal').style.display = 'none';
            document.getElementById('photoFileInput').value = '';
        }

        function drawCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            if (!rawImage) return;

            ctx.drawImage(
                rawImage,
                imgX,
                imgY,
                rawImage.width * imgScale,
                rawImage.height * imgScale
            );
        }

        function onZoomChange(val) {
            const prevScale = imgScale;
            imgScale = parseFloat(val);

            // Zoom centered on canvas middle
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;

            imgX = centerX - (centerX - imgX) * (imgScale / prevScale);
            imgY = centerY - (centerY - imgY) * (imgScale / prevScale);

            drawCanvas();
        }

        // Mouse drag listeners
        canvasWrapper.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX - imgX;
            startY = e.clientY - imgY;
        });

        window.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            imgX = e.clientX - startX;
            imgY = e.clientY - startY;
            drawCanvas();
        });

        window.addEventListener('mouseup', () => {
            isDragging = false;
        });

        // Touch drag listeners for mobile
        canvasWrapper.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                isDragging = true;
                startX = e.touches[0].clientX - imgX;
                startY = e.touches[0].clientY - imgY;
            }
        });

        window.addEventListener('touchmove', (e) => {
            if (!isDragging || e.touches.length !== 1) return;
            imgX = e.touches[0].clientX - startX;
            imgY = e.touches[0].clientY - startY;
            drawCanvas();
        });

        window.addEventListener('touchend', () => {
            isDragging = false;
        });

        function applyAndSaveCrop() {
            // Create circular cropped off-screen canvas (300x300)
            const exportCanvas = document.createElement('canvas');
            const size = 300;
            exportCanvas.width = size;
            exportCanvas.height = size;
            const eCtx = exportCanvas.getContext('2d');

            // Circular clip mask
            eCtx.beginPath();
            eCtx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
            eCtx.closePath();
            eCtx.clip();

            // The crop circle in our 400x400 canvas has diameter 220, center at (200, 200)
            const cropCenterX = canvas.width / 2;
            const cropCenterY = canvas.height / 2;
            const cropRadius = 110;

            const sx = cropCenterX - cropRadius;
            const sy = cropCenterY - cropRadius;
            const sWidth = cropRadius * 2;
            const sHeight = cropRadius * 2;

            eCtx.drawImage(
                canvas,
                sx, sy, sWidth, sHeight,
                0, 0, size, size
            );

            const base64Data = exportCanvas.toDataURL('image/png');
            document.getElementById('croppedImageDataInput').value = base64Data;
            document.getElementById('croppedUploadForm').submit();
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