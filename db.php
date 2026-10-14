<?php

// Ensure session is started with standard configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$username = "root";
$password = ""; // MySQL root password
$database = "my_website";

mysqli_report(MYSQLI_REPORT_OFF);

$conn = null;
$db_error = null;

// Initialize data storage directory
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0777, true);
}

// 1. Try MySQL Connection
try {
    $temp_conn = @new mysqli($host, $username, $password);
    if (!$temp_conn->connect_error) {
        $temp_conn->query("CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $temp_conn->close();

        $conn = @new mysqli($host, $username, $password, $database);
        if (!$conn->connect_error) {
            init_mysql_tables($conn);
        } else {
            $db_error = $conn->connect_error;
            $conn = null;
        }
    } else {
        $db_error = $temp_conn->connect_error;
    }
} catch (Exception $e) {
    $conn = null;
    $db_error = $e->getMessage();
}

function init_mysql_tables($c) {
    $c->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        contact_no VARCHAR(50) DEFAULT '',
        role VARCHAR(100) DEFAULT 'Web Developer',
        qualification VARCHAR(100) DEFAULT 'BCA',
        parentage VARCHAR(100) DEFAULT '',
        course_enrolled VARCHAR(150) DEFAULT 'BCA',
        college VARCHAR(200) DEFAULT 'Universal Group of Management And Technology',
        school VARCHAR(200) DEFAULT 'BHSS Sogam',
        status VARCHAR(50) DEFAULT 'active',
        login_count INT DEFAULT 0,
        logout_count INT DEFAULT 0,
        quiz_score INT DEFAULT 0,
        profile_image VARCHAR(255) DEFAULT '',
        name_changed INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME NULL,
        last_logout DATETIME NULL
    )");

    // Auto-add any missing columns
    $cols = [
        "contact_no VARCHAR(50) DEFAULT ''",
        "role VARCHAR(100) DEFAULT 'Web Developer'",
        "qualification VARCHAR(100) DEFAULT 'BCA'",
        "parentage VARCHAR(100) DEFAULT ''",
        "course_enrolled VARCHAR(150) DEFAULT 'BCA'",
        "college VARCHAR(200) DEFAULT 'Universal Group of Management And Technology'",
        "school VARCHAR(200) DEFAULT 'BHSS Sogam'",
        "status VARCHAR(50) DEFAULT 'active'",
        "login_count INT DEFAULT 0",
        "logout_count INT DEFAULT 0",
        "quiz_score INT DEFAULT 0",
        "profile_image VARCHAR(255) DEFAULT ''",
        "name_changed INT DEFAULT 0",
        "last_login DATETIME NULL",
        "last_logout DATETIME NULL"
    ];
    foreach ($cols as $colDef) {
        $colName = explode(" ", $colDef)[0];
        @$c->query("ALTER TABLE users ADD COLUMN $colDef");
    }

    $c->query("CREATE TABLE IF NOT EXISTS user_activity (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 0,
        user_name VARCHAR(100) DEFAULT '',
        user_email VARCHAR(150) DEFAULT '',
        contact_no VARCHAR(50) DEFAULT '',
        role VARCHAR(100) DEFAULT '',
        action VARCHAR(100) NOT NULL,
        details TEXT NULL,
        ip_address VARCHAR(50) DEFAULT '',
        user_agent VARCHAR(255) DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $c->query("CREATE TABLE IF NOT EXISTS page_visits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page VARCHAR(100) NOT NULL,
        user_name VARCHAR(100) DEFAULT 'Guest Visitor',
        user_email VARCHAR(150) DEFAULT '',
        ip_address VARCHAR(50) DEFAULT '',
        user_agent VARCHAR(255) DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}

// 2. High-Reliability JSON Storage Helper
function get_json_file_path($name) {
    return __DIR__ . '/data/' . $name . '.json';
}

function read_json_data($file) {
    $path = get_json_file_path($file);
    if (!file_exists($path)) {
        return [];
    }
    $content = @file_get_contents($path);
    if (empty($content)) {
        return [];
    }
    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : [];
}

function write_json_data($file, $data) {
    $path = get_json_file_path($file);
    @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

// Ensure default owner account exists
function ensure_default_owner() {
    $ownerEmail = "domicle672@gmail.com";
    $owner = db_find_user_by_email($ownerEmail);
    if (!$owner) {
        $ownerData = [
            "id" => 1,
            "name" => "INAAM",
            "email" => $ownerEmail,
            "password" => password_hash("Inaam@2026", PASSWORD_DEFAULT),
            "contact_no" => "6006541527",
            "role" => "Web Developer",
            "qualification" => "BCA",
            "parentage" => "Owner / Admin",
            "course_enrolled" => "BCA & Full Stack Development",
            "college" => "Universal Group of Management And Technology",
            "school" => "BHSS Sogam",
            "status" => "active",
            "login_count" => 1,
            "logout_count" => 0,
            "quiz_score" => 20,
            "profile_image" => "",
            "name_changed" => 0,
            "created_at" => date('Y-m-d H:i:s'),
            "last_login" => date('Y-m-d H:i:s'),
            "last_logout" => null
        ];
        db_save_user($ownerData);
    }
}

// Core User Operations
function db_get_users() {
    global $conn;
    if ($conn) {
        $res = $conn->query("SELECT * FROM users ORDER BY id ASC");
        if ($res && $res->num_rows > 0) {
            $list = [];
            while ($row = $res->fetch_assoc()) {
                $list[] = $row;
            }
            return $list;
        }
    }
    return read_json_data('users');
}

function db_find_user_by_email($email) {
    global $conn;
    $email = strtolower(trim($email));
    if ($conn) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE LOWER(email) = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $stmt->close();
                return $row;
            }
            $stmt->close();
        }
    }
    $users = read_json_data('users');
    foreach ($users as $u) {
        if (strtolower(trim($u['email'] ?? '')) === $email) {
            return $u;
        }
    }
    return null;
}

function db_find_user_by_id($id) {
    global $conn;
    $id = (int)$id;
    if ($conn) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $stmt->close();
                return $row;
            }
            $stmt->close();
        }
    }
    $users = read_json_data('users');
    foreach ($users as $u) {
        if ((int)($u['id'] ?? 0) === $id) {
            return $u;
        }
    }
    return null;
}

function db_save_user($data) {
    global $conn;
    $users = read_json_data('users');
    
    // Assign ID if not set
    if (empty($data['id'])) {
        $maxId = 0;
        foreach ($users as $u) {
            if (isset($u['id']) && $u['id'] > $maxId) {
                $maxId = $u['id'];
            }
        }
        $data['id'] = $maxId + 1;
    }

    if (!isset($data['created_at'])) {
        $data['created_at'] = date('Y-m-d H:i:s');
    }
    if (!isset($data['login_count'])) {
        $data['login_count'] = 0;
    }
    if (!isset($data['logout_count'])) {
        $data['logout_count'] = 0;
    }
    if (!isset($data['status'])) {
        $data['status'] = 'active';
    }

    // Try save to MySQL
    if ($conn) {
        $stmt = $conn->prepare("INSERT INTO users (id, name, email, password, contact_no, role, qualification, parentage, course_enrolled, college, school, status, login_count, logout_count, quiz_score, profile_image, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            name = VALUES(name), contact_no = VALUES(contact_no), role = VALUES(role), qualification = VALUES(qualification), parentage = VALUES(parentage), course_enrolled = VALUES(course_enrolled), college = VALUES(college), school = VALUES(school), status = VALUES(status), login_count = VALUES(login_count), logout_count = VALUES(logout_count), quiz_score = VALUES(quiz_score), profile_image = VALUES(profile_image)");
        if ($stmt) {
            $stmt->bind_param("issssssssssiisiss",
                $data['id'],
                $data['name'],
                $data['email'],
                $data['password'],
                $data['contact_no'],
                $data['role'],
                $data['qualification'],
                $data['parentage'],
                $data['course_enrolled'],
                $data['college'],
                $data['school'],
                $data['status'],
                $data['login_count'],
                $data['logout_count'],
                $data['quiz_score'],
                $data['profile_image'],
                $data['created_at']
            );
            $stmt->execute();
            $stmt->close();
        }
    }

    // Update JSON storage
    $found = false;
    foreach ($users as $idx => $u) {
        if ($u['id'] === $data['id'] || strtolower($u['email'] ?? '') === strtolower($data['email'])) {
            $users[$idx] = array_merge($u, $data);
            $found = true;
            break;
        }
    }
    if (!$found) {
        $users[] = $data;
    }
    write_json_data('users', $users);

    return $data;
}

function db_update_user($id, $fields) {
    global $conn;
    $id = (int)$id;

    if ($conn && !empty($fields)) {
        $setParts = [];
        $types = "";
        $values = [];
        foreach ($fields as $key => $val) {
            $setParts[] = "`$key` = ?";
            if (is_int($val)) {
                $types .= "i";
            } else {
                $types .= "s";
            }
            $values[] = $val;
        }
        $values[] = $id;
        $types .= "i";

        $sql = "UPDATE users SET " . implode(", ", $setParts) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $stmt->close();
        }
    }

    $users = read_json_data('users');
    foreach ($users as $idx => $u) {
        if ((int)($u['id'] ?? 0) === $id) {
            $users[$idx] = array_merge($u, $fields);
            break;
        }
    }
    write_json_data('users', $users);
}

// Activity Logging
function db_log_activity($userId, $userName, $userEmail, $action, $details = '', $contactNo = '', $role = '') {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Browser';
    $time = date('Y-m-d H:i:s');

    if ($conn) {
        $stmt = $conn->prepare("INSERT INTO user_activity (user_id, user_name, user_email, contact_no, role, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isssssssss", $userId, $userName, $userEmail, $contactNo, $role, $action, $details, $ip, $ua, $time);
            $stmt->execute();
            $stmt->close();
        }
    }

    $activities = read_json_data('activities');
    array_unshift($activities, [
        "id" => count($activities) + 1,
        "user_id" => $userId,
        "user_name" => $userName,
        "user_email" => $userEmail,
        "contact_no" => $contactNo,
        "role" => $role,
        "action" => $action,
        "details" => $details,
        "ip_address" => $ip,
        "user_agent" => $ua,
        "created_at" => $time
    ]);
    if (count($activities) > 500) {
        $activities = array_slice($activities, 0, 500);
    }
    write_json_data('activities', $activities);
}

// Page Visitor Logging
function db_log_page_visit($pageName, $userName = null, $userEmail = null) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Browser';
    $time = date('Y-m-d H:i:s');

    if ($userName === null) {
        $userName = $_SESSION['user_name'] ?? 'Guest Visitor';
        $userEmail = $_SESSION['user_email'] ?? '';
    }

    if ($conn) {
        $stmt = $conn->prepare("INSERT INTO page_visits (page, user_name, user_email, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssss", $pageName, $userName, $userEmail, $ip, $ua, $time);
            $stmt->execute();
            $stmt->close();
        }
    }

    $visits = read_json_data('visits');
    array_unshift($visits, [
        "id" => count($visits) + 1,
        "page" => $pageName,
        "user_name" => $userName,
        "user_email" => $userEmail,
        "ip_address" => $ip,
        "user_agent" => $ua,
        "created_at" => $time
    ]);
    if (count($visits) > 500) {
        $visits = array_slice($visits, 0, 500);
    }
    write_json_data('visits', $visits);
}

function db_get_activities($limit = 100) {
    global $conn;
    if ($conn) {
        $res = $conn->query("SELECT * FROM user_activity ORDER BY id DESC LIMIT " . (int)$limit);
        if ($res && $res->num_rows > 0) {
            $list = [];
            while ($row = $res->fetch_assoc()) {
                $list[] = $row;
            }
            return $list;
        }
    }
    $activities = read_json_data('activities');
    return array_slice($activities, 0, $limit);
}

function db_get_page_visits($limit = 100) {
    global $conn;
    if ($conn) {
        $res = $conn->query("SELECT * FROM page_visits ORDER BY id DESC LIMIT " . (int)$limit);
        if ($res && $res->num_rows > 0) {
            $list = [];
            while ($row = $res->fetch_assoc()) {
                $list[] = $row;
            }
            return $list;
        }
    }
    $visits = read_json_data('visits');
    return array_slice($visits, 0, $limit);
}

function db_record_login($userId) {
    $user = db_find_user_by_id($userId);
    if ($user) {
        $count = (int)($user['login_count'] ?? 0) + 1;
        $now = date('Y-m-d H:i:s');
        db_update_user($userId, [
            "login_count" => $count,
            "last_login" => $now
        ]);
        db_log_activity(
            $userId,
            $user['name'] ?? 'User',
            $user['email'] ?? '',
            'User Login',
            "Logged into account (Total Logins: {$count})",
            $user['contact_no'] ?? '',
            $user['role'] ?? 'Web Developer'
        );
    }
}

function db_record_logout($userId) {
    $user = db_find_user_by_id($userId);
    if ($user) {
        $count = (int)($user['logout_count'] ?? 0) + 1;
        $now = date('Y-m-d H:i:s');
        db_update_user($userId, [
            "logout_count" => $count,
            "last_logout" => $now
        ]);
        db_log_activity(
            $userId,
            $user['name'] ?? 'User',
            $user['email'] ?? '',
            'User Logout',
            "Logged out of account (Total Logouts: {$count})",
            $user['contact_no'] ?? '',
            $user['role'] ?? 'Web Developer'
        );
    }
}

// Check if currently authenticated user is Owner / Admin (INAAM)
function is_owner_or_admin() {
    if (!isset($_SESSION["user_id"])) {
        return false;
    }
    $email = strtolower($_SESSION["user_email"] ?? "");
    $name = strtoupper($_SESSION["user_name"] ?? "");
    $role = strtolower($_SESSION["user_role"] ?? "");
    
    return ($email === "domicle672@gmail.com" || $name === "INAAM" || str_contains($role, "admin"));
}

// Session Inactivity Timeout Checker (10 minutes = 600 seconds)
function check_session_timeout() {
    if (isset($_SESSION["user_id"])) {
        $timeoutDuration = 600; // 10 minutes in seconds
        if (isset($_SESSION["last_activity"]) && (time() - $_SESSION["last_activity"] > $timeoutDuration)) {
            $uid = $_SESSION["user_id"];
            db_record_logout($uid);
            db_log_activity(
                $uid,
                $_SESSION["user_name"] ?? "User",
                $_SESSION["user_email"] ?? "",
                "Session Expired",
                "Automatically logged out due to 10 minutes of inactivity"
            );
            session_unset();
            session_destroy();
            header("Location: login.php?timeout=1");
            exit();
        }
        $_SESSION["last_activity"] = time();
    }
}

// Make sure owner account is seeded
ensure_default_owner();

?>