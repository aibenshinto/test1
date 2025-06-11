<?php
session_name('ADMINSESSID');
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../authentication/login.php");
    exit;
}

$staff_id = $_SESSION['user_id'];
$msg = '';
$msg_class = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    $update = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $update->bind_param("si", $status, $order_id);
    
    if ($update->execute()) {
        $msg = "Order status updated successfully!";
        $msg_class = 'msg-success';
    } else {
        $msg = "Failed to update order status.";
        $msg_class = 'msg-error';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #43cea2, #185a9d);
            color: #333;
            min-height: 100vh;
        }
        .dashboard {
            display: flex;
            width: 100vw;
            min-height: 100vh;
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
        .msg {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 6px;
            font-size: 16px;
        }
        .msg-success {
            background: #e6f7ff;
            color: #2d89e6;
            border: 1px solid #2d89e6;
        }
        .msg-error {
            background: #ffe6e6;
            color: #d0021b;
            border: 1px solid #d0021b;
        }
        .orders-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .order-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f9f9f9;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .order-header h3 {
            margin: 0;
            color: #185a9d;
        }
        .order-details {
            margin-bottom: 15px;
        }
        .order-items {
            margin-bottom: 15px;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: white;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        .status-select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
        }
        .status-select:focus {
            border-color: #2d89e6;
            outline: none;
        }
        .update-btn {
            background: #2d89e6;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        .update-btn:hover {
            background: #1c6dd0;
        }
        .status-pending { color: #f0ad4e; }
        .status-processing { color: #5bc0de; }
        .status-shipped { color: #5cb85c; }
        .status-delivered { color: #5cb85c; }
        .status-cancelled { color: #d9534f; }
    </style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <h2>Staff Panel</h2>
        <p>Hello, <?= htmlspecialchars($_SESSION['username']) ?></p>
        <ul>
            <li><a href="staff_products.php">Products</a></li>
            <li><a href="view_orders.php">Orders</a></li>
            <li><a href="staff_qna.php">Q&A</a></li>
            <li><a class="logout-link" href="../authentication/logout.php">Logout</a></li>
        </ul>
    </aside>
    <main class="main-content">
        <?php if ($msg): ?>
            <div class="msg <?= $msg_class ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="orders-container">
            <h2>Manage Orders</h2>
            <?php
            // Fetch orders with customer details and items
            $sql = "SELECT o.*, u.username as customer_name, u.email as customer_email,
                    GROUP_CONCAT(
                        CONCAT(p.name, ' (', oi.quantity, ' x ₹', oi.price, ')')
                        SEPARATOR '||'
                    ) as items
                    FROM orders o
                    JOIN users u ON o.customer_id = u.id
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN products p ON oi.product_id = p.id
                    WHERE u.role = 'customer'
                    GROUP BY o.id
                    ORDER BY o.order_date DESC";
            
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($order = $result->fetch_assoc()) {
                    ?>
                    <div class="order-card">
                        <div class="order-header">
                            <h3>Order #<?= $order['id'] ?></h3>
                            <div>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="status" class="status-select status-<?= $order['status'] ?>">
                                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="update-btn">Update Status</button>
                                </form>
                            </div>
                        </div>
                        <div class="order-details">
                            <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?> (<?= htmlspecialchars($order['customer_email']) ?>)</p>
                            <p><strong>Order Date:</strong> <?= date('F j, Y, g:i a', strtotime($order['order_date'])) ?></p>
                            <p><strong>Total Amount:</strong> ₹<?= number_format($order['total_amount'], 2) ?></p>
                        </div>
                        <div class="order-items">
                            <h4>Order Items:</h4>
                            <?php
                            $items = explode('||', $order['items']);
                            foreach ($items as $item) {
                                echo "<div class='order-item'>" . htmlspecialchars($item) . "</div>";
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p>No orders found.</p>";
            }
            ?>
        </div>
    </main>
</div>
</body>
</html> 