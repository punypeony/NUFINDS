<?php
$servername = "127.0.0.1";
$username = "root";
$password = ""; // change if your MySQL/MariaDB has a password
$dbname = "nufindsdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
