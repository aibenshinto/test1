<?php
include '../../db_connect.php';

$message = '';
$messageColor = 'green';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Check if username exists
    $stmtCheck = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmtCheck->bind_param("s", $username);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        $message = "Error: Username already taken. Please choose another username.";
        $messageColor = 'red';
        $stmtCheck->close();
    } else {
        $stmtCheck->close();

        // Check if email exists
        $stmtCheckEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmtCheckEmail->bind_param("s", $email);
        $stmtCheckEmail->execute();
        $stmtCheckEmail->store_result();

        if ($stmtCheckEmail->num_rows > 0) {
            $message = "Error: Email already used. Please use a different email.";
            $messageColor = 'red';
            $stmtCheckEmail->close();
        } else {
            $stmtCheckEmail->close();

            // Hash password
            $hashedPwd = password_hash($password, PASSWORD_DEFAULT);

            // Insert new staff
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'staff', NOW())");
            $stmt->bind_param("sss", $username, $email, $hashedPwd);

            if ($stmt->execute()) {
                $message = "Staff added successfully.";
                $messageColor = 'green';
                header("Location: ../dashboard/admin_dashboard.php");
                exit;
            } else {
                $message = "Error adding staff.";
                $messageColor = 'red';
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add New Staff</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f9fafb;
      margin: 0;
      padding: 40px;
      color: #333;
    }
    h2 {
      color: #2d89e6;
      margin-bottom: 20px;
    }
    form {
      background: white;
      padding: 25px 30px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      max-width: 400px;
      margin: auto;
    }
    label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
      color: #555;
    }
    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 10px 12px;
      margin-bottom: 20px;
      border: 1.5px solid #ccc;
      border-radius: 6px;
      font-size: 15px;
      transition: border-color 0.3s ease;
    }
    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus {
      border-color: #2d89e6;
      outline: none;
    }
    button {
      background-color: #28a745;
      color: white;
      border: none;
      padding: 12px 18px;
      border-radius: 6px;
      font-weight: 700;
      cursor: pointer;
      transition: background-color 0.3s ease;
      width: 100%;
      font-size: 16px;
    }
    button:hover {
      background-color: #218838;
    }
    .message {
      font-weight: 600;
      margin-top: 10px;
    }
    .message.success {
      color: green;
    }
    .message.error {
      color: red;
    }
  </style>
</head>
<body>
  <h2>Add New Staff</h2>
  <form method="post" action="">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Add Staff</button>

    <?php if ($message): ?>
      <p class="message <?= $messageColor === 'green' ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
  </form>
</body>
</html>
