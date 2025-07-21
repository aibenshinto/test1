<?php
require_once '../session_manager.php';
include '../db_connect.php';
include '../delivery_utils.php';

requireCustomer();

// Optional: Check session timeout (30 minutes)
checkSessionTimeout(30);

$customer_id = getCurrentUserId();

$sql = "
    SELECT 
        o.id AS order_id, 
        o.total_amount, 
        o.order_date,
        o.status,
        o.delivery_type,
        o.delivery_address,
        o.delivery_distance,
        o.delivery_fee,
        o.estimated_delivery_time,
        o.actual_delivery_time,
        s.name AS delivery_staff_name,
        oi.product_id, 
        oi.quantity, 
        oi.price AS item_price,
        p.name AS product_name, 
        p.image AS product_image,
        oi.created_at AS item_created_at
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN staff s ON o.delivery_staff_id = s.id
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
            'delivery_type' => $row['delivery_type'],
            'delivery_address' => $row['delivery_address'],
            'delivery_distance' => $row['delivery_distance'],
            'delivery_fee' => $row['delivery_fee'],
            'estimated_delivery_time' => $row['estimated_delivery_time'],
            'actual_delivery_time' => $row['actual_delivery_time'],
            'delivery_staff_name' => $row['delivery_staff_name'],
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
        .delivery-info {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #2d89e6;
        }
        .delivery-info h4 {
            margin: 0 0 10px 0;
            color: #2d89e6;
        }
        .delivery-info p {
            margin: 5px 0;
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
        .Processing {
            background: #bee5eb;
            color: #0c5460;
        }
        .Ready for Pickup {
            background: #fff3cd;
            color: #856404;
        }
        .Out for Delivery {
            background: #d1ecf1;
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

            <!-- Delivery Information -->
            <div class="delivery-info">
                <h4>Delivery Information</h4>
                <p><strong>Type:</strong> <?= ucfirst($order['delivery_type'] ?? 'Not specified'); ?></p>
                <?php if ($order['delivery_type'] === 'delivery'): ?>
                    <p><strong>Address:</strong> <?= htmlspecialchars($order['delivery_address']); ?></p>
                    <p><strong>Distance:</strong> <?= number_format($order['delivery_distance'], 2); ?> km</p>
                    <p><strong>Delivery Fee:</strong> ₹<?= number_format($order['delivery_fee'], 2); ?></p>
                    <?php if ($order['delivery_staff_name']): ?>
                        <p><strong>Delivery Staff:</strong> <?= htmlspecialchars($order['delivery_staff_name']); ?></p>
                    <?php endif; ?>
                    <?php if ($order['estimated_delivery_time']): ?>
                        <p><strong>Estimated Delivery:</strong> <?= date('d M Y, h:i A', strtotime($order['estimated_delivery_time'])); ?></p>
                    <?php endif; ?>
                    <?php if ($order['actual_delivery_time']): ?>
                        <p><strong>Delivered On:</strong> <?= date('d M Y, h:i A', strtotime($order['actual_delivery_time'])); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p><strong>Pickup Location:</strong> Warehouse in Kochi</p>
                    <p><strong>Distance:</strong> <?= number_format($order['delivery_distance'], 2); ?> km (from warehouse)</p>
                    <p><strong>Note:</strong> Please collect your order from our warehouse during business hours.</p>
                <?php endif; ?>
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
