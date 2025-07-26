<?php
require_once '../session_manager.php';
include '../db_connect.php';
include '../delivery_utils.php';

requireCustomer();

// Check session timeout (30 minutes)
checkSessionTimeout(30);

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$customer_id = getCurrentUserId();
$error = '';
$cart_mid = '';

if (!isset($_GET['id'])) {
    $error = "Product not found. <a href='customer_dashboard.php'>Go back</a>";
}

// Fetch product details
$id = $_GET['id'];
$stmt = $conn->prepare("SELECT Item_id, Item_name, Item_rate, Item_qty, Item_image FROM tbl_item WHERE Item_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = "Product not found. <a href='customer_dashboard.php'>Go back</a>";
}

$product = $result->fetch_assoc();
$stmt->close();

// Handle form submission (proceed to payment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_to_payment'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } elseif (!isset($_POST['quantity']) || intval($_POST['quantity']) < 1) {
        $error = "Quantity must be at least 1.";
    } elseif (intval($_POST['quantity']) > $product['Item_qty']) {
        $error = "Insufficient stock. Only {$product['Item_qty']} items available.";
    } else {
        $quantity = intval($_POST['quantity']);
        $delivery_fee = floatval($_POST['delivery_fee']);
        $delivery_type = $_POST['delivery_type'];
        $delivery_distance = floatval($_POST['delivery_distance']);
        $delivery_address = $_POST['delivery_address'];

        // Redirect to payment_process.php with POST data
        ?>
        <form id="paymentForm" method="post" action="payment_process.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['Item_id']); ?>">
            <input type="hidden" name="quantity" value="<?php echo $quantity; ?>">
            <input type="hidden" name="delivery_fee" value="<?php echo $delivery_fee; ?>">
            <input type="hidden" name="delivery_type" value="<?php echo htmlspecialchars($delivery_type); ?>">
            <input type="hidden" name="delivery_distance" value="<?php echo $delivery_distance; ?>">
            <input type="hidden" name="delivery_address" value="<?php echo htmlspecialchars($delivery_address); ?>">
        </form>
        <script>
            document.getElementById('paymentForm').submit();
        </script>
        <?php
        exit;
    }
}

// Get customer details for delivery address
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - <?php echo htmlspecialchars($product['Item_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9f9f9;
            padding: 40px;
        }
        .checkout-box {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
        }
        img {
            width: 100%;
            height: 250px;
            object-fit: contain;
            margin-bottom: 15px;
        }
        .price {
            color: #2d89e6;
            font-size: 20px;
            margin: 10px 0;
        }
        .delivery-info {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2d89e6;
        }
        .delivery-info h3 {
            margin: 0 0 10px 0;
            color: #2d89e6;
        }
        .pay-btn {
            display: block;
            width: 100%;
            background: #28a745;
            color: white;
            border: none;
            padding: 12px;
            font-size: 18px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 20px;
        }
        .pay-btn:hover {
            background: #218838;
        }
        .error-message {
            background: #ffefef;
            color: #c0392b;
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #c0392b;
            border-radius: 5px;
        }
        input[type="number"] {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            width: 60px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="checkout-box">
    <h2>Checkout</h2>

    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="product-info">
        <img src="../<?php echo htmlspecialchars($product['Item_image']); ?>" alt="Item Image">
        <div>
            <h3><?php echo htmlspecialchars($product['Item_name']); ?></h3>
            <div class="price">₹<?php echo number_format($product['Item_rate'], 2); ?></div>
            <p>Stock: <?php echo htmlspecialchars($product['Item_qty']); ?></p>
        </div>
    </div>

    <div class="delivery-info">
        <h3>Delivery Information</h3>
        <p><strong>Your Location:</strong> <?php echo htmlspecialchars($location); ?></p>
        <?php if ($customer['latitude'] && $customer['longitude']): ?>
            <p><strong>Distance from Warehouse:</strong> <?php echo number_format($distance, 2); ?> km</p>
        <?php endif; ?>
        <p><strong>Delivery Type:</strong> <?php echo ucfirst($delivery_type); ?></p>
        <p><strong>Message:</strong> <?php echo htmlspecialchars($delivery_message); ?></p>
    </div>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['Item_id']); ?>">
        <label for="quantity">Quantity:</label>
        <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['Item_qty']; ?>" required>
        <input type="hidden" name="delivery_fee" value="<?php echo $delivery_fee; ?>">
        <input type="hidden" name="delivery_type" value="<?php echo htmlspecialchars($delivery_type); ?>">
        <input type="hidden" name="delivery_distance" value="<?php echo $delivery_distance; ?>">
        <input type="hidden" name="delivery_address" value="<?php echo htmlspecialchars($location); ?>">
        <button type="submit" name="proceed_to_payment" class="pay-btn">Proceed to Payment</button>
    </form>

    <br>
    <a href="product_details.php?id=<?php echo urlencode($product['Item_id']); ?>" style="color: #2d89e6;">← Back to Product</a>
</div>
</body>
</html>
<?php
$conn->close();
?>