<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../authenticate/login.php");
    exit;
}

include '../../db_connect.php';

$message = '';
$messageColor = 'green';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $image = trim($_POST['image']);
    $price = trim($_POST['price']);
    $stock = trim($_POST['stock']);

    if (empty($name) || empty($price) || empty($stock)) {
        $message = "All fields are required.";
        $messageColor = 'red';
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, price, image, stock, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("sdsi", $name, $price, $image, $stock);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: ../dashboard/admin_dashboard.php?load=product");
            exit;
        } else {
            $message = "Error adding product.";
            $messageColor = 'red';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add New Product</title>
  <style>
    /* Basic styles for the form */
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
      background: #f9fafb;
    }
    form {
      max-width: 400px;
      margin: auto;
      background: white;
      padding: 20px;
      border-radius: 6px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    label {
      display: block;
      margin-bottom: 8px;
      font-weight: bold;
    }
    input[type=text], input[type=number] {
      width: 100%;
      padding: 8px 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
    }
    button {
      background-color: #28a745;
      color: white;
      padding: 10px 15px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
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
  <h2>Add New Product</h2>
  <form method="post" action="">
    <label for="name">Product Name:</label>
    <input type="text" id="name" name="name" required>

    <label for="price">Price (₹):</label>
    <input type="number" step="0.01" id="price" name="price" required>

    <label for="stock">Stock Quantity:</label>
    <input type="number" id="stock" name="stock" required>

    <label for="stock">Image URL:</label>
    <input type="text" name="image"><br><br>
    <button type="submit">Add Product</button>

    <?php if ($message): ?>
      <p class="message <?= $messageColor === 'green' ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
  </form>
</body>
</html>
