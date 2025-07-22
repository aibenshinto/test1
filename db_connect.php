<?php
$host = "localhost";        // your host
$user = "root";             // your username
$pass = "";                 // your password
$db = "ecommerce_jul22";      // your DB name

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>