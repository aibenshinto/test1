<?php
require_once '../session_manager.php';
include '../db_connect.php';
include '../delivery_utils.php';

requireCustomer();

// Check session timeout (30 minutes)
checkSessionTimeout(30);

$customer_id = getCurrentUserId();

// Fetch all orders with their items and delivery details
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
        CONCAT(s.Staff_fname, ' ', s.Staff_lname) AS delivery_staff_name,
        d.del_date AS delivery_date,
        d.del_status AS delivery_status,
        oi.item_id, 
        oi.quantity, 
        oi.price AS item_price,
        i.Item_name AS item_name, 
        i.Item_image AS item_image,
        oi.created_at AS item_created_at
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN tbl_item i ON oi.item_id = i.Item_id
    LEFT JOIN tbl_staff s ON o.delivery_staff_id = s.Staff_id
    LEFT JOIN tbl_payment p ON p.order_status = 'success'
    LEFT JOIN tbl_delivery d ON d.cart_id = p.cart_id
    LEFT JOIN tbl_cart_master cm ON cm.cart_mid = p.cart_id 
        AND cm.cust_id = o.customer_id 
        AND cm.status = 'Ordered'
    WHERE o.customer_id = ?
    GROUP BY o.id, oi.id
    ORDER BY o.order_date DESC, o.id, oi.id
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $customer_id); // VARCHAR(60)
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
            'delivery_staff_name' => $row['delivery_staff_name'],
            'delivery_date' => $row['delivery_date'],
            'delivery_status' => $row['delivery_status'],
            'items' => [],
        ];
    }

    $orders[$order_id]['items'][] = [
        'item_name' => $row['item_name'],
        'item_image' => $row['item_image'],
        'quantity' => $row['quantity'],
        'item_price' => $row['item_price'],
        'created_at' => $row['item_created_at'] ?? $row['order_date'], // Fallback
    ];
}

$stmt->close();
$conn->close();

// Debug: Uncomment to inspect query results
// echo "<pre>SQL: $sql</pre>";
// echo "<pre>Orders: "; print_r($orders); echo "</pre>";
// exit;
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
        .Ready-for-Pickup {
            background: #fff3cd;
            color: #856404;
        }
        .Out-for-Delivery {
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
                <h3>Order #<?php echo htmlspecialchars($order_id); ?> - 
                    Date: <?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?> - 
                    Total: ₹<?php echo number_format($order['total_amount'], 2); ?> - 
                    Status: <span class="status <?php echo htmlspecialchars(str_replace(' ', '-', $order['status'])); ?>">
                        <?php echo htmlspecialchars($order['status']); ?>
                    </span>
                </h3>
            </div>

            <!-- Delivery Information -->
            <div class="delivery-info">
                <h4>Delivery Information</h4>
                <p><strong>Type:</strong> <?php echo ucfirst($order['delivery_type'] ?? 'Not specified'); ?></p>
                <?php if ($order['delivery_type'] === 'delivery'): ?>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                    <p><strong>Delivery Fee:</strong> ₹<?php echo number_format($order['delivery_fee'], 2); ?></p>
                    <?php if ($order['delivery_staff_name']): ?>
                        <p><strong>Delivery Staff:</strong> <?php echo htmlspecialchars($order['delivery_staff_name']); ?></p>
                    <?php endif; ?>
                    <?php if ($order['delivery_date']): ?>
                        <p><strong>Delivery Date:</strong> <?php echo date('d M Y, h:i A', strtotime($order['delivery_date'])); ?></p>
                    <?php endif; ?>
                    <?php if ($order['delivery_status']): ?>
                        <p><strong>Delivery Status:</strong> <?php echo htmlspecialchars($order['delivery_status']); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p><strong>Pickup Location:</strong> Warehouse in Kochi</p>
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
                            <img src="../<?php echo htmlspecialchars($item['item_image']); ?>" alt="Item Image" class="product-img" />
                            <?php echo htmlspecialchars($item['item_name']); ?>
                        </td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>₹<?php echo number_format($item['item_price'], 2); ?></td>
                        <td><?php echo date('d M Y, h:i A', strtotime($item['created_at'])); ?></td>
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