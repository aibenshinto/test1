<?php
require_once '../session_manager.php';
include '../db_connect.php';

// Check if user is logged in and has appropriate role
if (!isLoggedIn()) {
    header('Location: ../authentication/login.php');
    exit();
}

$user_role = getCurrentUserRole();
if ($user_role !== 'delivery' && $user_role !== 'product_manager') {
    header('Location: staff_dashboard.php');
    exit();
}

// Optional: Check session timeout (30 minutes)
checkSessionTimeout(30);

$message = '';
$error = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        $message = "Order status updated successfully!";
    } else {
        $error = "Failed to update order status.";
    }
}

// Fetch all orders with customer and product details
$sql = "SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
               GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as products
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        GROUP BY o.id
        ORDER BY o.order_date DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Orders</title>
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

    .order-details {
      font-size: 14px;
      color: #666;
      margin-top: 5px;
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2><?php echo ucfirst($user_role); ?> Panel</h2>
      <p>Hello, <?= htmlspecialchars(getCurrentUsername()) ?> <span class="role-badge"><?php echo ucfirst($user_role); ?></span></p>
      <ul>
        <li><a href="staff_dashboard.php">Staff Dashboard</a></li>
        <?php if ($user_role === 'product_manager'): ?>
          <li><a href="staff_products.php">Manage Products</a></li>
          <li><a href="add_product.php">Add Product</a></li>
          <li><a href="staff_qna.php">Customer Q&A</a></li>
        <?php endif; ?>
        <?php if ($user_role === 'delivery'): ?>
          <li><a href="delivery_dashboard.php">Delivery Orders</a></li>
        <?php endif; ?>
        <li><a href="view_orders.php">All Orders</a></li>
        <li><a class="logout-link" href="../authentication/logout.php">Logout</a></li>
      </ul>
    </aside>

    <main class="main-content">
      <h2>All Orders</h2>
      <p>View and manage all customer orders.</p>

      <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <div class="section">
        <h3>Order List</h3>
        <?php if ($result->num_rows > 0): ?>
          <table>
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Products</th>
                <th>Total Amount</th>
                <th>Delivery Type</th>
                <th>Status</th>
                <th>Order Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($order = $result->fetch_assoc()): ?>
                <tr>
                  <td>#<?php echo $order['id']; ?></td>
                  <td>
                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                    <div class="order-details">
                      <?php echo htmlspecialchars($order['customer_email']); ?><br>
                      <?php echo htmlspecialchars($order['customer_phone']); ?>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($order['products']); ?></td>
                  <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                  <td><?php echo ucfirst($order['delivery_type']); ?></td>
                  <td class="status-<?php echo strtolower($order['status']); ?>">
                    <?php echo $order['status']; ?>
                  </td>
                  <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                  <td>
                    <form method="post" style="display: inline;">
                      <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                      <select name="status" style="padding: 4px; margin-right: 5px;">
                        <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Processing" <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="Ready for Pickup" <?php echo $order['status'] === 'Ready for Pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                        <option value="Out for Delivery" <?php echo $order['status'] === 'Out for Delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                        <option value="Delivered" <?php echo $order['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="Cancelled" <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                      </select>
                      <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No orders found.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html> 