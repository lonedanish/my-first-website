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
    <title>Profile</title>
</head>

<body>

<h1>My Profile</h1>

<p>Name: <?php echo htmlspecialchars($_SESSION["user_name"]); ?></p>

<p>Email: <?php echo htmlspecialchars($_SESSION["user_email"]); ?></p>

<br>

<a href="dashboard.php">Dashboard</a>

</body>

</html>