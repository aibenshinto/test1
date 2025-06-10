<?php
session_name('ADMINSESSID');
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../authentication/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Staff Dashboard</title>
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
      <h2>Staff Panel</h2>
      <p>Hello, <?= htmlspecialchars($_SESSION['username']) ?></p>
      <ul>
        <li><button onclick="loadContent('add_product')">Add Product</button></li>
        <li><button onclick="loadContent('orders')">Manage Orders</button></li>
        <li><a href="staff_qna.php">Q&A</a></li>
        <li><a class="logout-link" href="../authentication/logout.php">Logout</a></li>
      </ul>
    </aside>

    <main class="main-content" id="mainContent">
      <h2>Welcome to the Staff Dashboard</h2>
      <p>Select an option from the menu.</p>
    </main>
  </div>

  <script>
    function loadContent(section) {
      let url = '';
      switch (section) {
        case 'add_product':
          url = 'add_product.php';
          break;
        case 'orders':
          url = 'view_orders.php'; // replace with your actual file
          break;
        case 'qna':
          url = 'staff_qna.php';
          break;
        default:
          url = '';
      }

      if (url) {
        fetch(url)
          .then(response => {
            if (!response.ok) throw new Error('Failed to load ' + section);
            return response.text();
          })
          .then(html => {
            document.getElementById('mainContent').innerHTML = html;
          })
          .catch(error => {
            document.getElementById('mainContent').innerHTML = `<p>Error: ${error.message}</p>`;
          });
      }
    }

  </script>
</body>
</html>
