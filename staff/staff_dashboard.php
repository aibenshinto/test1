<?php
require_once '../session_manager.php';
include '../db_connect.php';

requireStaff();

// Optional: Check session timeout (30 minutes)
checkSessionTimeout(30);

$staff_role = getCurrentUserRole();
$staff_name = getCurrentUsername();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo ucfirst($staff_role); ?> Dashboard</title>
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

    .role-badge {
      background: #e8f4fd;
      color: #185a9d;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: bold;
      margin-left: 10px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }

    .stat-card {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      text-align: center;
    }

    .stat-number {
      font-size: 2em;
      font-weight: bold;
      color: #185a9d;
    }

    .stat-label {
      color: #666;
      margin-top: 5px;
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2><?php echo ucfirst($staff_role); ?> Panel</h2>
      <p>Hello, <?= htmlspecialchars($staff_name) ?> <span class="role-badge"><?= ucfirst($staff_role) ?></span></p>
      <ul>
        <li><a href="staff_dashboard.php">Staff Dashboard</a></li>
        <li><a href="staff_products.php">Manage Products</a></li>
        <li><a href="vendor_management.php">Manage Vendors</a></li>
        <li><a href="purchase_management.php">Manage Purchases</a></li>
        <li><a href="add_product.php">Add Product</a></li>
        <li><a href="staff_qna.php">Customer Q&A</a></li>
        <li><a class="logout-link" href="../authentication/logout.php">Logout</a></li>
      </ul>
    </aside>

    <main class="main-content" id="mainContent">
      <h2>Welcome to the <?php echo ucfirst($staff_role); ?> Dashboard</h2>
      
      <?php if ($staff_role === 'product_manager'): ?>
        <p>Manage products and handle customer questions.</p>
        
        <?php
        // Fetch key metrics
        $total_customers = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
        $total_products = $conn->query("SELECT COUNT(*) as count FROM tbl_item")->fetch_assoc()['count'];
        $total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
        $pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'")->fetch_assoc()['count'];
        $total_sales = $conn->query("SELECT SUM(total_amount) as sum FROM orders WHERE status = 'Delivered'")->fetch_assoc()['sum'];

        // Fetch recent orders
        $recent_orders = $conn->query("
            SELECT o.id, o.order_date, o.total_amount, c.name as customer_name, o.status 
            FROM orders o 
            JOIN customers c ON o.customer_id = c.id 
            ORDER BY o.order_date DESC 
            LIMIT 5
        ");

        // Fetch top-selling products
        $top_products = $conn->query("
            SELECT i.Item_name, SUM(oi.quantity) as total_sold
            FROM order_items oi
            JOIN tbl_item i ON oi.item_id = i.Item_id
            GROUP BY i.Item_name
            ORDER BY total_sold DESC
            LIMIT 5
        ");
        ?>
        
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-number"><?php echo $total_customers; ?></div>
            <div class="stat-label">Total Customers</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo $total_products; ?></div>
            <div class="stat-label">Total Products</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo $total_orders; ?></div>
            <div class="stat-label">Total Orders</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo $pending_orders; ?></div>
            <div class="stat-label">Pending Orders</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo $total_sales; ?></div>
            <div class="stat-label">Total Sales</div>
          </div>
        </div>
        
        <div class="stats-grid" style="margin-top: 30px;">
          <div class="stat-card">
            <h3>Recent Orders</h3>
            <ul>
              <?php
              if ($recent_orders->num_rows > 0) {
                while ($row = $recent_orders->fetch_assoc()) {
                  echo "<li>{$row['order_date']} - {$row['customer_name']} ({$row['status']})</li>";
                }
              } else {
                echo "<li>No recent orders.</li>";
              }
              ?>
            </ul>
          </div>
          <div class="stat-card">
            <h3>Top Selling Products</h3>
            <ul>
              <?php
              if ($top_products->num_rows > 0) {
                while ($row = $top_products->fetch_assoc()) {
                  echo "<li>{$row['Item_name']} (Sold: {$row['total_sold']})</li>";
                }
              } else {
                echo "<li>No top selling products.</li>";
              }
              ?>
            </ul>
          </div>
        </div>
        
      <?php elseif ($staff_role === 'delivery'): ?>
        <p>Manage delivery orders and track delivery status.</p>
        
        <?php
        // Get statistics for delivery staff
        $my_deliveries = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE delivery_staff_id = ? AND delivery_type = 'delivery'");
        $my_deliveries->bind_param("i", getCurrentUserId());
        $my_deliveries->execute();
        $my_delivery_count = $my_deliveries->get_result()->fetch_assoc()['count'];
        
        $pending_deliveries = $conn->query("SELECT COUNT(*) as count FROM orders WHERE delivery_type = 'delivery' AND status = 'Processing'")->fetch_assoc()['count'];
        $pickup_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE delivery_type = 'pickup' AND status = 'Processing'")->fetch_assoc()['count'];
        ?>
        
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-number"><?php echo $my_delivery_count; ?></div>
            <div class="stat-label">My Deliveries</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo $pending_deliveries; ?></div>
            <div class="stat-label">Pending Deliveries</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo $pickup_orders; ?></div>
            <div class="stat-label">Pickup Orders</div>
          </div>
        </div>
        
        <div style="margin-top: 30px;">
          <a href="delivery_dashboard.php" style="background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
            View Delivery Orders →
          </a>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <script>
    function loadContent(section) {
      let url = '';
      switch (section) {
        case 'add_product':
          url = 'add_product.php';
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
