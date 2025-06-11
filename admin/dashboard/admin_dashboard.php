<?php
session_name('ADMINSESSID');
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../authentication/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      height: 100vh;
      background: linear-gradient(135deg, #43cea2, #185a9d);
      color: #333;
    }

    .dashboard {
      display: flex;
      width: 100%;
    }

    .sidebar {
      background: white;
      padding: 20px;
      width: 250px;
      box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }

    .sidebar h2 {
      margin-bottom: 10px;
      color: #185a9d;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
    }

    .sidebar li {
      margin: 10px 0;
    }

    .sidebar button, .sidebar a {
      background: #185a9d;
      color: white;
      padding: 10px 15px;
      border: none;
      border-radius: 8px;
      text-decoration: none;
      cursor: pointer;
      width: 100%;
      text-align: left;
      transition: background 0.3s ease;
      display: block;
    }

    .sidebar button:hover, .sidebar a:hover {
      background: #0b3d72;
    }

    .main-content {
      flex: 1;
      padding: 40px;
      background: rgba(255,255,255,0.95);
      overflow-y: auto;
    }

    .logout-link {
      margin-top: 20px;
      display: block;
      color: red;
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2>Admin Panel</h2>
      <p>Hello, <?= htmlspecialchars($_SESSION['username']) ?></p>
      <ul>
        <li><a href="../staff/staff_management.php">Staff</a></li>
        <li><a href="../product/product_management.php">Products</a></li>
        <li><a href="../orders/order_management.php">Orders</a></li>
        <li><a href="../sales/sales_statistics.php">Sales</a></li>
        <li><a class="logout-link" href="../../authentication/logout.php">Logout</a></li>
      </ul>
    </aside>

    <main class="main-content">
      <h2>Welcome to the Admin Dashboard</h2>
      <p>Select an option from the menu.</p>
    </main>
  </div>
</body>
</html>
