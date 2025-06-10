<?php
session_name('ADMINSESSID');
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../authenticate/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="dashboard.css">
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2>Admin Panel</h2>
      <p>Hello, <?= htmlspecialchars($_SESSION['username']) ?></p>
      <nav>
        <ul>
          <li><button onclick="loadContent('staff')">Staff</button></li>
          <li><button onclick="loadContent('product')">Products</button></li>
          <li><button onclick="loadContent('orders')">Orders</button></li>
          <br>
          <li><a href="../../authentication/logout.php">Logout</a></li>
        </ul>
      </nav>
    </aside>

    <main class="main-content" id="mainContent">
      <h2>Welcome to the Admin Dashboard</h2>
      <p>Select a section to view details.</p>
    </main>
  </div>
  <script src="dashboard.js"></script>
  <script src="../product/product.js?v=1.0"></script>
</body>
</html>
