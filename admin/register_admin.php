<?php
include '../db_connect.php';

$username = "admin";
$email = "admin@shop.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$role = "admin";

// Check if admin already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
$stmt->bind_param("ss", $email, $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "Admin already exists.";
} else {
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $password, $role);

    if ($stmt->execute()) {
        echo "Admin created successfully.";
    } else {
        echo "Error: " . htmlspecialchars($stmt->error);
    }
}

$stmt->close();
$conn->close();
?>