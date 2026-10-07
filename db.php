<?php

$host = "localhost";
$username = "root";
$password = ""; // If your MySQL has a password, enter it here
$database = "my_website";

mysqli_report(MYSQLI_REPORT_OFF);

try {
    $conn = @new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        $db_error = $conn->connect_error;
    }
} catch (Exception $e) {
    $conn = null;
    $db_error = $e->getMessage();
}

?>