<?php
include '../../db_connect.php';

if (!isset($_GET['id'])) {
    echo "Invalid request";
    exit;
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM users WHERE id = $id AND role = 'staff'";
$result = $conn->query($sql);

if ($result->num_rows != 1) {
    echo "Staff not found.";
    exit;
}

$staff = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Staff</title>
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
    .note {
      font-size: 13px;
      color: #777;
      margin-top: -15px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <h2>Edit Staff</h2>
  <form method="post" action="update_staff.php">
    <input type="hidden" name="id" value="<?= $staff['id'] ?>">

    <label for="username">Username:</label>
    <input type="text" id="username" name="username" value="<?= htmlspecialchars($staff['username']) ?>" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($staff['email']) ?>" required>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
    <div class="note">Leave blank if you don't want to change the password.</div>

    <button type="submit">Update</button>
  </form>
</body>
</html>
