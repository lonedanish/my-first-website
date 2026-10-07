<?php

include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($name == "" || $email == "" || $password == "") {
        $message = "Please fill all fields.";
    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, password)
                VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $hashedPassword);

        if ($stmt->execute()) {
            $message = "Registration successful! You can now login.";
        } else {
            $message = "Email already exists.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>

<body>

<h1>Create Account</h1>

<p><?php echo $message; ?></p>

<form method="POST">

    <label>Name</label><br>
    <input type="text" name="name" required>

    <br><br>

    <label>Email</label><br>
    <input type="email" name="email" required>

    <br><br>

    <label>Password</label><br>
    <input type="password" name="password" required>

    <br><br>

    <button type="submit">Register</button>

</form>

<br>

<a href="login.php">Already have an account? Login</a>

</body>
</html>