<?php
session_name('CUSTOMERSESSID');
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

$sql = "
    SELECT 
        o.id AS order_id, 
        o.total_amount, 
        o.order_date,
        o.status,
        oi.product_id, 
        oi.quantity, 
        oi.price AS item_price,
        p.name AS product_name, 
        p.image AS product_image,
        oi.created_at AS item_created_at
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.customer_id = ?
    ORDER BY o.order_date DESC, o.id, oi.id
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];

while ($row = $result->fetch_assoc()) {
    $order_id = $row['order_id'];

    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            'total_amount' => $row['total_amount'],
            'order_date' => $row['order_date'],
            'status' => $row['status'],
            'items' => [],
        ];
    }

    $orders[$order_id]['items'][] = [
        'product_name' => $row['product_name'],
        'product_image' => $row['product_image'],
        'quantity' => $row['quantity'],
        'item_price' => $row['item_price'],
        'created_at' => $row['item_created_at'] ?? $row['order_date'],  // fallback
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Orders</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #2d89e6;
            color: white;
            padding: 16px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h2 {
            margin: 0;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }
        .container {
            padding: 30px;
        }
        .order {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .order-header {
            margin-bottom: 15px;
        }
        .order-header h3 {
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #2d89e6;
            color: white;
        }
        .status {
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 4px;
        }
        .Pending {
            background: #ffeeba;
            color: #856404;
        }
        .Shipped {
            background: #bee5eb;
            color: #0c5460;
        }
        .Delivered {
            background: #c3e6cb;
            color: #155724;
        }
        .Cancelled {
            background: #f5c6cb;
            color: #721c24;
        }
        img.product-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Your Orders</h2>
    <div class="nav-links">
        <a href="customer_dashboard.php">Products</a>
        <a href="customer_cart.php">Cart</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
<?php if (count($orders) > 0): ?>
    <?php foreach ($orders as $order_id => $order): ?>
        <div class="order">
            <div class="order-header">
                <h3>Order #<?= htmlspecialchars($order_id) ?> - 
                    Date: <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?> - 
                    Total: ₹<?= number_format($order['total_amount'], 2) ?> - 
                    Status: <span class="status <?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span>
                </h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Added On</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td>
                            <img src="<?= htmlspecialchars($item['product_image']) ?>" alt="Product Image" class="product-img" />
                            <?= htmlspecialchars($item['product_name']) ?>
                        </td>
                        <td><?= $item['quantity'] ?></td>
                        <td>₹<?= number_format($item['item_price'], 2) ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($item['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>You have not placed any orders yet.</p>
<?php endif; ?>
</div>

</body>
</html>
