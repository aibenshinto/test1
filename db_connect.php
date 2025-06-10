<?php
$host = "localhost";
$user = "root";         // your DB username
$pass = "";             // your DB password
$db = "synopsis1";      // your DB name

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>