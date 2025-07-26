<?php
require_once '../session_manager.php';
include '../db_connect.php';
include '../delivery_utils.php';

requireDeliveryStaff();

$staff_id = getCurrentUserId();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    if (updateOrderStatus($conn, $order_id, $status)) {
        $message = "Order status updated successfully!";
    } else {
        $error = "Failed to update order status.";
    }
}

// Handle delivery assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_order']) && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    
    if (assignDeliveryStaff($conn, $order_id, $staff_id)) {
        $message = "Order assigned to you successfully!";
    } else {
        $error = "Failed to assign order.";
    }
}

// Get delivery orders
$delivery_orders = getDeliveryOrders($conn, $staff_id);
$unassigned_orders = getDeliveryOrders($conn); // Get all delivery orders for assignment
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Delivery Dashboard</title>
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

    .section {
      background: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .section h2 {
      color: #2d89e6;
      margin-bottom: 15px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #2d89e6;
      color: white;
    }

    .status-pending { color: #f39c12; font-weight: bold; }
    .status-processing { color: #3498db; font-weight: bold; }
    .status-ready { color: #27ae60; font-weight: bold; }
    .status-delivered { color: #27ae60; font-weight: bold; }
    .status-cancelled { color: #e74c3c; font-weight: bold; }

    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin: 2px;
    }

    .btn-primary { background: #2d89e6; color: white; }
    .btn-success { background: #27ae60; color: white; }
    .btn-warning { background: #f39c12; color: white; }
    .btn-danger { background: #e74c3c; color: white; }

    .message {
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
    }

    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
  </style>
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2>Delivery Panel</h2>
      <p>Hello, <?= htmlspecialchars(getCurrentUsername()) ?> <span class="role-badge">Delivery</span></p>
      <ul>
        <li><a href="delivery_dashboard.php">Staff Dashboard</a></li>
        <!-- <li><a href="delivery_dashboard.php">Delivery Orders</a></li> -->
        <li><a href="view_orders.php">All Orders</a></li>
        <li><a class="logout-link" href="../authentication/logout.php">Logout</a></li>
      </ul>
    </aside>

    <main class="main-content">
      <h2>Delivery Dashboard</h2>
      <p>Manage delivery orders and track delivery status.</p>

      <?php if (isset($message)): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if (isset($error)): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <!-- My Assigned Orders -->
      <div class="section">
        <h3>My Assigned Orders</h3>
        <?php if (empty($delivery_orders)): ?>
          <p>No orders assigned to you yet.</p>
        <?php else: ?>
          <table>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Address</th>
              <th>Distance</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Order Date</th>
              <th>Actions</th>
            </tr>
            <?php foreach ($delivery_orders as $order): ?>
              <tr>
                <td>#<?php echo $order['id']; ?></td>
                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($order['delivery_address']); ?></td>
                <td><?php echo number_format($order['delivery_distance'], 2); ?> km</td>
                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                <td class="status-<?php echo strtolower($order['status']); ?>">
                  <?php echo $order['status']; ?>
                </td>
                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                <td>
                  <form method="post" style="display: inline;">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <?php if ($order['status'] === 'Processing'): ?>
                      <button type="submit" name="status" value="Ready for Pickup" class="btn btn-warning">Ready</button>
                    <?php elseif ($order['status'] === 'Ready for Pickup'): ?>
                      <button type="submit" name="status" value="Out for Delivery" class="btn btn-primary">Start Delivery</button>
                    <?php elseif ($order['status'] === 'Out for Delivery'): ?>
                      <button type="submit" name="status" value="Delivered" class="btn btn-success">Mark Delivered</button>
                    <?php endif; ?>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php endif; ?>
      </div>

      <!-- Unassigned Orders -->
      <div class="section">
        <h3>Unassigned Delivery Orders</h3>
        <?php 
        $unassigned = array_filter($unassigned_orders, function($order) {
          return $order['delivery_staff_id'] === null && $order['status'] === 'Processing';
        });
        ?>
        <?php if (empty($unassigned)): ?>
          <p>No unassigned orders available.</p>
        <?php else: ?>
          <table>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Address</th>
              <th>Distance</th>
              <th>Amount</th>
              <th>Order Date</th>
              <th>Actions</th>
            </tr>
            <?php foreach ($unassigned as $order): ?>
              <tr>
                <td>#<?php echo $order['id']; ?></td>
                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($order['delivery_address']); ?></td>
                <td><?php echo number_format($order['delivery_distance'], 2); ?> km</td>
                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                <td>
                  <form method="post" style="display: inline;">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <button type="submit" name="assign_order" value="1" class="btn btn-primary">Assign to Me</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php endif; ?>
      </div>

      <!-- Pickup Orders -->
      <div class="section">
        <h3>Pickup Orders (Ready for Customer Pickup)</h3>
        <?php 
        $pickup_orders = array_filter($unassigned_orders, function($order) {
          return $order['delivery_type'] === 'pickup' && $order['status'] === 'Processing';
        });
        ?>
        <?php if (empty($pickup_orders)): ?>
          <p>No pickup orders available.</p>
        <?php else: ?>
          <table>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Amount</th>
              <th>Order Date</th>
              <th>Actions</th>
            </tr>
            <?php foreach ($pickup_orders as $order): ?>
              <tr>
                <td>#<?php echo $order['id']; ?></td>
                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                <td>
                  <form method="post" style="display: inline;">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <button type="submit" name="status" value="Ready for Pickup" class="btn btn-warning">Mark Ready</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>