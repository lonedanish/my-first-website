<?php

session_start();

include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email = ?";

    $stmt = $conn->prepare($sql);
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
        $message = "Account not found.";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
</head>

<body>

    <h1>Login</h1>

    <p><?php echo $message; ?></p>

    <form method="POST">

        <label>Email</label><br>
        <input type="email" name="email" required>

        <br><br>

        <label>Password</label><br>
        <input type="password" name="password" required>

        <br><br>

        <button type="submit">Login</button>

    </form>

    <br>

    <a href="register.php">Create an account</a>

</body>

</html>