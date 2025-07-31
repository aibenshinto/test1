<?php
require_once '../session_manager.php';
include '../db_connect.php';
include '../delivery_utils.php';

requireCustomer();
checkSessionTimeout(30);

$customer_id = getCurrentUserId();

// Reverted to the original SQL query logic that correctly joins the tables
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
$stmt->bind_param("s", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $order_id = $row['order_id'];
    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            'total_amount'        => $row['total_amount'],
            'order_date'          => $row['order_date'],
            'status'              => $row['status'],
            'delivery_type'       => $row['delivery_type'],
            'delivery_address'    => $row['delivery_address'],
            'delivery_fee'        => $row['delivery_fee'],
            'delivery_staff_name' => $row['delivery_staff_name'],
            'delivery_date'       => $row['delivery_date'],
            'delivery_status'     => $row['delivery_status'],
            'items'               => [],
        ];
    }
    $orders[$order_id]['items'][] = [
        'item_name'  => $row['item_name'],
        'item_image' => $row['item_image'],
        'quantity'   => $row['quantity'],
        'item_price' => $row['item_price'],
    ];
}
$stmt->close();
$conn->close();

$active_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Synopsis</title>
    <link rel="stylesheet" href="../css/orders_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Header Section -->
<header class="header">
    <div class="header-logo">
        <a href="customer_dashboard.php">Synopsis</a>
    </div>
    <nav class="nav-links">
        <a href="customer_dashboard.php" class="<?php echo ($active_page == 'customer_dashboard.php') ? 'active' : ''; ?>">Products</a>
        <a href="customer_orders.php" class="<?php echo ($active_page == 'customer_orders.php') ? 'active' : ''; ?>">My Orders</a>
    </nav>
    <div class="header-user">
        <a href="customer_cart.php" class="cart-icon"><i class="fas fa-shopping-cart"></i></a>
        <div class="user-menu">
             <i class="fas fa-user-circle"></i>
             <span><?php echo htmlspecialchars(getCustomerName()); ?></span>
             <div class="dropdown-content">
                 <a href="logout.php">Logout</a>
             </div>
        </div>
    </div>
</header>

<div class="container">
    <h1>My Orders</h1>

    <?php if (count($orders) > 0): ?>
        <?php foreach ($orders as $order_id => $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div><strong>Order Placed:</strong> <?php echo date('d M Y', strtotime($order['order_date'])); ?></div>
                    <div><strong>Total:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></div>
                    <div><strong>Order #:</strong> <?php echo htmlspecialchars($order_id); ?></div>
                    <div><span class="status <?php echo htmlspecialchars(str_replace(' ', '-', $order['status'])); ?>"><?php echo htmlspecialchars($order['status']); ?></span></div>
                </div>

                <div class="order-body">
                    <div class="order-items">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="item">
                                <div class="item-image">
                                    <img src="../<?php echo htmlspecialchars($item['item_image']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                                </div>
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                    <p>Quantity: <?php echo $item['quantity']; ?> | Price: ₹<?php echo number_format($item['item_price'], 2); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="delivery-info">
                        <h4>Delivery Information</h4>
                        <p><strong>Type:</strong> <?php echo ucfirst($order['delivery_type'] ?? 'Not specified'); ?></p>
                        <?php if ($order['delivery_type'] === 'delivery'): ?>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                            <p><strong>Fee:</strong> ₹<?php echo number_format($order['delivery_fee'], 2); ?></p>
                            <?php if ($order['delivery_staff_name']): ?>
                                <p><strong>Assigned to:</strong> <?php echo htmlspecialchars($order['delivery_staff_name']); ?></p>
                            <?php endif; ?>
                            <?php if ($order['delivery_status']): ?>
                                <p><strong>Status:</strong> <?php echo htmlspecialchars($order['delivery_status']); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p><strong>Pickup At:</strong> Warehouse, Kochi</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-orders">
            <h3>You haven't placed any orders yet.</h3>
            <p>All your past orders will be displayed here.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
