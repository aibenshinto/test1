<?php
require_once '../session_manager.php';
include '../db_connect.php';
include '../delivery_utils.php';

requireCustomer();
checkSessionTimeout(30);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$customer_id = getCurrentUserId();
$error = '';
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';

if (!isset($_GET['id'])) {
    die("Product not found. <a href='customer_dashboard.php'>Go back</a>");
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT Item_id, Item_name, Item_rate, Item_qty, Item_image FROM tbl_item WHERE Item_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Product not found. <a href='customer_dashboard.php'>Go back</a>");
}
$product = $result->fetch_assoc();
$stmt->close();

// Handle Add New Card
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_new_card'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        $card_name = trim($_POST['card_name']);
        $card_no = trim($_POST['card_no']);
        $card_expiry_month = trim($_POST['card_expiry']); // YYYY-MM format
        
        // Append a day to make it a valid DATE for the database
        $card_expiry_date = $card_expiry_month . '-01';

        if (empty($card_name) || empty($card_no) || empty($card_expiry_month)) {
            $error = "Please fill in all card details.";
        } elseif (!preg_match('/^\d{16}$/', $card_no)) {
            $error = "Invalid card number. It must be 16 digits.";
        } else {
            $insert_card_stmt = $conn->prepare("INSERT INTO tbl_card (cust_id, card_name, card_no, card_expiry) VALUES (?, ?, ?, ?)");
            $insert_card_stmt->bind_param("ssss", $customer_id, $card_name, $card_no, $card_expiry_date);
            if ($insert_card_stmt->execute()) {
                header("Location: checkout_single.php?id=" . urlencode($id) . "&message=Card added successfully!");
                exit;
            } else {
                $error = "Failed to add new card.";
            }
            $insert_card_stmt->close();
        }
    }
}

// Handle Proceed to Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_to_payment'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } elseif (empty($_POST['quantity']) || intval($_POST['quantity']) < 1) {
        $error = "Quantity must be at least 1.";
    } elseif (intval($_POST['quantity']) > $product['Item_qty']) {
        $error = "Insufficient stock.";
    } elseif (empty($_POST['card_id'])) {
        $error = "Please select a payment card.";
    } else {
        // Redirect to payment_process.php
        echo '<form id="paymentForm" method="post" action="payment_process.php">';
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
        echo '<input type="hidden" name="product_id" value="' . htmlspecialchars($product['Item_id']) . '">';
        echo '<input type="hidden" name="quantity" value="' . intval($_POST['quantity']) . '">';
        echo '<input type="hidden" name="delivery_fee" value="' . floatval($_POST['delivery_fee']) . '">';
        echo '<input type="hidden" name="delivery_type" value="' . htmlspecialchars($_POST['delivery_type']) . '">';
        echo '<input type="hidden" name="delivery_distance" value="' . floatval($_POST['delivery_distance']) . '">';
        echo '<input type="hidden" name="delivery_address" value="' . htmlspecialchars($_POST['delivery_address']) . '">';
        echo '<input type="hidden" name="card_id" value="' . htmlspecialchars($_POST['card_id']) . '">';
        echo '</form><script>document.getElementById("paymentForm").submit();</script>';
        exit;
    }
}

// Fetch customer details and saved cards
$customer_sql = "SELECT Cust_street, Cust_city, Cust_state, latitude, longitude FROM tbl_customer WHERE Cust_id = ?";
$customer_stmt = $conn->prepare($customer_sql);
$customer_stmt->bind_param("s", $customer_id);
$customer_stmt->execute();
$customer = $customer_stmt->get_result()->fetch_assoc();
$customer_stmt->close();

$saved_cards_stmt = $conn->prepare("SELECT card_id, card_name, card_no FROM tbl_card WHERE cust_id = ?");
$saved_cards_stmt->bind_param("s", $customer_id);
$saved_cards_stmt->execute();
$saved_cards = $saved_cards_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$saved_cards_stmt->close();

$location = implode(', ', array_filter([$customer['Cust_street'], $customer['Cust_city'], $customer['Cust_state']]));
$distance = ($customer && !empty($customer['latitude'])) ? getDistanceFromWarehouse($customer['latitude'], $customer['longitude']) : 0;
$delivery_fee = calculateDeliveryFee($distance);
$delivery_type = getDeliveryType($distance);
$delivery_message = getDeliveryMessage($distance);

$conn->close();
$active_page = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Synopsis</title>
    <link rel="stylesheet" href="../css/checkout_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header class="header">
    <div class="header-logo"><a href="customer_dashboard.php">Synopsis</a></div>
    <nav class="nav-links">
        <a href="customer_dashboard.php">Products</a>
        <a href="customer_orders.php">My Orders</a>
    </nav>
    <div class="header-user">
        <a href="customer_cart.php" class="cart-icon"><i class="fas fa-shopping-cart"></i></a>
        <div class="user-menu">
             <i class="fas fa-user-circle"></i>
             <span><?php echo htmlspecialchars(getCustomerName()); ?></span>
             <div class="dropdown-content"><a href="logout.php">Logout</a></div>
        </div>
    </div>
</header>

<div class="checkout-container">
    <h1>Checkout</h1>
    <?php if ($error): ?><div class="error-message"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($message): ?><div class="success-message"><?php echo $message; ?></div><?php endif; ?>

    <div class="checkout-layout">
        <div class="checkout-details">
            <h3>Your Item</h3>
            <div class="product-item">
                <img src="../<?php echo htmlspecialchars($product['Item_image']); ?>" alt="Item Image">
                <div class="product-item-info">
                    <div class="name"><?php echo htmlspecialchars($product['Item_name']); ?></div>
                    <div class="price">₹<?php echo number_format($product['Item_rate'], 2); ?></div>
                    <p>Stock: <?php echo htmlspecialchars($product['Item_qty']); ?></p>
                </div>
            </div>
            
            <h3>Delivery Information</h3>
            <div class="delivery-info">
                <p><strong>Your Location:</strong> <?php echo htmlspecialchars($location); ?></p>
                <p><strong>Distance:</strong> <?php echo number_format($distance, 2); ?> km</p>
                <p><strong>Type:</strong> <?php echo ucfirst($delivery_type); ?></p>
                <p><strong>Note:</strong> <?php echo htmlspecialchars($delivery_message); ?></p>
            </div>
            <a href="product_details.php?id=<?php echo urlencode($product['Item_id']); ?>" class="back-link">← Back to Product</a>
        </div>

        <div class="checkout-sidebar">
            <div class="order-summary">
                <h3>Order Summary</h3>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="summary-row">
                        <span>Product Price:</span>
                        <span>₹<?php echo number_format($product['Item_rate'], 2); ?></span>
                    </div>
                     <div class="summary-row">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" class="quantity-input" value="1" min="1" max="<?php echo $product['Item_qty']; ?>" required>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee:</span>
                        <span>₹<?php echo number_format($delivery_fee, 2); ?></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total Amount:</span>
                        <span>₹<?php echo number_format(($product['Item_rate'] * 1) + $delivery_fee, 2); // Initial total based on quantity 1 ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <label for="card_id" style="font-weight: bold;">Select Card to Pay</label>
                        <select id="card_id" name="card_id" class="select-input" required>
                            <option value="">-- Select a Saved Card --</option>
                            <?php foreach ($saved_cards as $card): ?>
                                <option value="<?php echo $card['card_id']; ?>">
                                    <?php echo htmlspecialchars($card['card_name']) . ' - **** ' . substr($card['card_no'], -4); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <input type="hidden" name="delivery_fee" value="<?php echo $delivery_fee; ?>">
                    <input type="hidden" name="delivery_type" value="<?php echo htmlspecialchars($delivery_type); ?>">
                    <input type="hidden" name="delivery_distance" value="<?php echo $distance; ?>">
                    <input type="hidden" name="delivery_address" value="<?php echo htmlspecialchars($location); ?>">
                    <button type="submit" name="proceed_to_payment" class="btn-pay">Proceed to Payment</button>
                </form>
            </div>

            <div class="payment-card">
                <h3>Add a New Card</h3>
                <form method="post" id="addCardForm">
                     <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="card_name">Name on Card</label>
                        <input type="text" id="card_name" name="card_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="card_no">Card Number</label>
                        <input type="text" id="card_no" name="card_no" class="form-control" required pattern="\d{16}" title="16-digit card number">
                    </div>
                    <div class="form-group">
                        <label for="card_expiry">Expiry Date</label>
                        <input type="month" id="card_expiry" name="card_expiry" class="form-control" required>
                    </div>
                    <button type="submit" name="add_new_card" class="btn-secondary">Save Card</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
