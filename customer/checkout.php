<?php
require_once '../session_manager.php';
include '../db_connect.php';
include '../delivery_utils.php';

requireCustomer();

// Optional: Check session timeout (30 minutes)
checkSessionTimeout(30);

$customer_id = getCurrentUserId();

// Get customer details including coordinates
$customer_sql = "SELECT name, location, latitude, longitude FROM customers WHERE id = ?";
$customer_stmt = $conn->prepare($customer_sql);
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();
$customer = $customer_result->fetch_assoc();

// Calculate delivery distance and fee
$distance = 0;
$delivery_fee = 0;
$delivery_type = 'pickup';
$delivery_message = '';

if ($customer && $customer['latitude'] && $customer['longitude']) {
    $distance = getDistanceFromWarehouse($customer['latitude'], $customer['longitude']);
    $delivery_fee = calculateDeliveryFee($distance);
    $delivery_type = getDeliveryType($distance);
    $delivery_message = getDeliveryMessage($distance);
} else {
    $delivery_message = "Location coordinates not available. Please update your profile with coordinates for delivery calculation.";
}

// Fetch all cart items for this customer
$sql = "SELECT ci.quantity, p.id AS product_id, p.name, p.price 
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.customer_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Your cart is empty. <a href='customer_dashboard.php'>Go back</a>";
    exit;
}

$cart_items = [];
$subtotal = 0;
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $subtotal += $row['price'] * $row['quantity'];
}

$grand_total = $subtotal + $delivery_fee;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f5f5f5; }
        .checkout-container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 15px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #2d89e6; color: white; }
        .delivery-info { background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2d89e6; }
        .delivery-info h3 { margin: 0 0 10px 0; color: #2d89e6; }
        .total-section { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .total-row { display: flex; justify-content: space-between; margin: 5px 0; }
        .grand-total { font-weight: bold; font-size: 18px; color: #2d89e6; border-top: 2px solid #ddd; padding-top: 10px; }
        .pay-btn {
            background: #4CAF50;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            display: block;
            width: 150px;
            margin-left: auto;
        }
        .pay-btn:hover { background: #45a049; }
        .back-link { color: #2d89e6; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="checkout-container">
    <h2>Confirm Your Order</h2>

    <div class="delivery-info">
        <h3>Delivery Information</h3>
        <p><strong>Your Location:</strong> <?php echo htmlspecialchars($customer['location']); ?></p>
        <?php if ($customer['latitude'] && $customer['longitude']): ?>
            <p><strong>Distance from Warehouse:</strong> <?php echo number_format($distance, 2); ?> km</p>
        <?php endif; ?>
        <p><strong>Delivery Type:</strong> <?php echo ucfirst($delivery_type); ?></p>
        <p><strong>Message:</strong> <?php echo htmlspecialchars($delivery_message); ?></p>
    </div>

    <table>
        <tr>
            <th>Product</th>
            <th>Price (₹)</th>
            <th>Quantity</th>
            <th>Total (₹)</th>
        </tr>
        <?php foreach ($cart_items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td><?php echo number_format($item['price'], 2); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>₹<?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div class="total-row">
            <span>Delivery Fee:</span>
            <span>₹<?php echo number_format($delivery_fee, 2); ?></span>
        </div>
        <div class="total-row grand-total">
            <span>Grand Total:</span>
            <span>₹<?php echo number_format($grand_total, 2); ?></span>
        </div>
    </div>

    <form action="payment_processing.php" method="post">
        <input type="hidden" name="total_amount" value="<?php echo $grand_total; ?>">
        <input type="hidden" name="delivery_fee" value="<?php echo $delivery_fee; ?>">
        <input type="hidden" name="delivery_type" value="<?php echo $delivery_type; ?>">
        <input type="hidden" name="delivery_distance" value="<?php echo $distance; ?>">
        <button type="submit" class="pay-btn">Pay Now</button>
    </form>

    <br>
    <a href="customer_cart.php" class="back-link">← Back to Cart</a>
</div>

</body>
</html>
