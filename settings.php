<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Settings</title>
</head>

<body>

<h1>Settings</h1>

<p>Account Settings</p>

<p>More settings can be added here later.</p>

<br>

<a href="dashboard.php">Dashboard</a>

</body>

</html>