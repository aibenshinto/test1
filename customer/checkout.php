<?php
require_once '../session_manager.php';
include '../db_connect.php';
include '../delivery_utils.php';

requireCustomer();

// Optional: Check session timeout (30 minutes)
checkSessionTimeout(30);

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$customer_id = getCurrentUserId();
$cart_mid = isset($_GET['cart_mid']) ? $_GET['cart_mid'] : null;
$message = '';
$error = '';

// Validate cart_mid
if (!$cart_mid) {
    $error = "No cart selected. <a href='customer_cart.php'>Go back to cart</a>";
} else {
    $cart_check_stmt = $conn->prepare("SELECT cart_mid FROM tbl_cart_master WHERE cart_mid = ? AND cust_id = ? AND status = 'Active'");
    $cart_check_stmt->bind_param("ss", $cart_mid, $customer_id);
    $cart_check_stmt->execute();
    $cart_check_result = $cart_check_stmt->get_result();
    if ($cart_check_result->num_rows === 0) {
        $error = "Invalid or inactive cart. <a href='customer_cart.php'>Go back to cart</a>";
    }
    $cart_check_stmt->close();
}

if ($error) {
    // Display error and exit
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Checkout Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background-color: #f5f5f5; }
            .error-message { background: #ffefef; color: #c0392b; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #c0392b; }
        </style>
    </head>
    <body>
        <div class="error-message"><?php echo $error; ?></div>
    </body>
    </html>
    <?php
    $conn->close();
    exit;
}

// Get customer details including coordinates
$customer_sql = "SELECT Cust_fname AS name, Cust_street, Cust_city, Cust_state, latitude, longitude FROM tbl_customer WHERE Cust_id = ?";
$customer_stmt = $conn->prepare($customer_sql);
$customer_stmt->bind_param("s", $customer_id);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();
$customer = $customer_result->fetch_assoc();
$customer_stmt->close();

// Construct full address
$location = $customer['Cust_street'];
if ($customer['Cust_city']) {
    $location .= ', ' . $customer['Cust_city'];
}
if ($customer['Cust_state']) {
    $location .= ', ' . $customer['Cust_state'];
}

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

// Fetch cart items for the specific cart
$sql = "SELECT cc.item_qty AS quantity, cc.item_rate AS price, i.Item_id AS product_id, i.Item_name AS name 
        FROM tbl_cart_child cc
        JOIN tbl_item i ON cc.item_id = i.Item_id
        WHERE cc.cart_mid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cart_mid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = "This cart is empty. <a href='customer_cart.php'>Go back to cart</a>";
    $stmt->close();
    $conn->close();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Checkout Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background-color: #f5f5f5; }
            .error-message { background: #ffefef; color: #c0392b; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #c0392b; }
        </style>
    </head>
    <body>
        <div class="error-message"><?php echo $error; ?></div>
    </body>
    </html>
    <?php
    exit;
}

$cart_items = [];
$subtotal = 0;
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $subtotal += $row['price'] * $row['quantity'];
}
$stmt->close();

$grand_total = $subtotal + $delivery_fee;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - Cart #<?php echo htmlspecialchars($cart_mid); ?></title>
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
        .message.success { background: #e7f4e4; color: #2e7d32; padding: 10px; margin-bottom: 20px; border-left: 4px solid #2e7d32; border-radius: 5px; }
        .message.error { background: #ffefef; color: #c0392b; padding: 10px; margin-bottom: 20px; border-left: 4px solid #c0392b; border-radius: 5px; }
    </style>
</head>
<body>

<div class="checkout-container">
    <h2>Confirm Your Order - Cart #<?php echo htmlspecialchars($cart_mid); ?></h2>

    <?php if ($message || (isset($_GET['message']) && $_GET['message'])): ?>
        <div class="message success"><?php echo htmlspecialchars($message ?: $_GET['message']); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="delivery-info">
        <h3>Delivery Information</h3>
        <p><strong>Your Location:</strong> <?php echo htmlspecialchars($location); ?></p>
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
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="cart_mid" value="<?php echo htmlspecialchars($cart_mid); ?>">
        <input type="hidden" name="total_amount" value="<?php echo $grand_total; ?>">
        <input type="hidden" name="delivery_fee" value="<?php echo $delivery_fee; ?>">
        <input type="hidden" name="delivery_type" value="<?php echo $delivery_type; ?>">
        <input type="hidden" name="delivery_distance" value="<?php echo $distance; ?>">
        <input type="hidden" name="delivery_address" value="<?php echo htmlspecialchars($location); ?>">
        <button type="submit" class="pay-btn">Pay Now</button>
    </form>

    <br>
    <a href="customer_cart.php" class="back-link">← Back to Cart</a>
</div>

</body>
</html>
<?php
$conn->close();
?>