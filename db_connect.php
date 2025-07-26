<?php
$host = "localhost";        // your host
$user = "root";             // your username
$pass = "";                 // your password
$db = "ecommerce_jul24";      // your DB name

$conn = new mysqli('localhost', 'root', '', 'ecommerce_jul24');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>