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
        $card_expiry_month = trim($_POST['card_expiry']);
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

// Payment Processing Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_to_payment'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } elseif (empty($_POST['quantity']) || intval($_POST['quantity']) < 1) {
        $error = "Quantity must be at least 1.";
    } elseif (intval($_POST['quantity']) > $product['Item_qty']) {
        $error = "Insufficient stock.";
    } elseif (empty($_POST['payment_method'])) {
        $error = "Please select a payment method.";
    } else {
        $payment_method = $_POST['payment_method'];

        if ($payment_method === 'card' && empty($_POST['card_id'])) {
            $error = "Please select a payment card.";
        } elseif ($payment_method === 'upi' && empty(trim($_POST['upi_id']))) {
            $error = "Please enter your UPI ID.";
        } elseif ($payment_method === 'upi' && !preg_match('/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/', trim($_POST['upi_id']))) {
            $error = "Please enter a valid UPI ID format (e.g., yourname@bank).";
        } else {
            // Redirect to payment_process.php
            echo '<form id="paymentForm" method="post" action="payment_process.php">';
            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
            echo '<input type="hidden" name="product_id" value="' . htmlspecialchars($product['Item_id']) . '">';
            echo '<input type="hidden" name="quantity" value="' . intval($_POST['quantity']) . '">';
            echo '<input type="hidden" name="total_amount" value="' . floatval($_POST['total_amount']) . '">';
            echo '<input type="hidden" name="delivery_fee" value="' . floatval($_POST['delivery_fee']) . '">';
            echo '<input type="hidden" name="delivery_type" value="' . htmlspecialchars($_POST['delivery_type']) . '">';
            echo '<input type="hidden" name="delivery_distance" value="' . floatval($_POST['delivery_distance']) . '">';
            echo '<input type="hidden" name="delivery_address" value="' . htmlspecialchars($_POST['delivery_address']) . '">';
            
            // Send payment method and identifier
            echo '<input type="hidden" name="payment_method" value="' . htmlspecialchars($payment_method) . '">';
            if ($payment_method === 'card') {
                 echo '<input type="hidden" name="payment_identifier" value="' . htmlspecialchars($_POST['card_id']) . '">';
            } else {
                 echo '<input type="hidden" name="payment_identifier" value="' . htmlspecialchars(trim($_POST['upi_id'])) . '">';
            }
            
            echo '</form><script>document.getElementById("paymentForm").submit();</script>';
            exit;
        }
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
$has_saved_cards = count($saved_cards) > 0;

$location = implode(', ', array_filter([$customer['Cust_street'], $customer['Cust_city'], $customer['Cust_state']]));
$distance = ($customer && !empty($customer['latitude'])) ? getDistanceFromWarehouse($customer['latitude'], $customer['longitude']) : 0;
$delivery_fee = calculateDeliveryFee($distance);
$delivery_type = getDeliveryType($distance);
$delivery_message = getDeliveryMessage($distance);

// Initial totals
$initial_quantity = 1;
$subtotal = $product['Item_rate'] * $initial_quantity;
$grand_total = $subtotal + $delivery_fee;

$conn->close();
$active_page = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Synopsis</title>
    <link rel="stylesheet" href="../css/checkout_cart_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Add a small style for the quantity input to make it look good in the summary */
        .quantity-input {
            width: 70px;
            height: 38px;
            text-align: center;
            padding: 0 5px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            font-size: 1rem;
        }
        .quantity-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
    </style>
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
            <h3>Item Details</h3>
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="name"><?php echo htmlspecialchars($product['Item_name']); ?></div>
                    <div class="price" data-rate="<?php echo $product['Item_rate']; ?>">
                        Price: ₹<?php echo number_format($product['Item_rate'], 2); ?>
                    </div>
                     <p>Stock Available: <?php echo htmlspecialchars($product['Item_qty']); ?></p>
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
                        <span>Subtotal:</span>
                        <span id="subtotal-amount">₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>

                    <div class="summary-row">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" class="quantity-input" value="<?php echo $initial_quantity; ?>" min="1" max="<?php echo $product['Item_qty']; ?>" required>
                    </div>

                    <div class="summary-row">
                        <span>Delivery Fee:</span>
                        <span data-fee="<?php echo $delivery_fee; ?>">₹<?php echo number_format($delivery_fee, 2); ?></span>
                    </div>

                    <div class="summary-row summary-total">
                        <span>Grand Total:</span>
                        <span id="grand-total-amount">₹<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                    
                    <div class="payment-selection">
                        <h4>Select Payment Method</h4>
                        
                        <?php if ($has_saved_cards): ?>
                        <div class="payment-option">
                            <input type="radio" id="pay_card" name="payment_method" value="card" checked class="payment-radio">
                            <label for="pay_card" class="payment-label">
                                <div class="payment-text">
                                    <span class="payment-title">Pay with Saved Card</span>
                                </div>
                                <div class="payment-content">
                                    <select id="card_id" name="card_id" class="select-input">
                                        <option value="">-- Select a Saved Card --</option>
                                        <?php foreach ($saved_cards as $card): ?>
                                            <option value="<?php echo $card['card_id']; ?>">
                                                <?php echo htmlspecialchars($card['card_name']) . ' - **** ' . substr($card['card_no'], -4); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </label>
                        </div>
                        <?php endif; ?>

                        <div class="payment-option">
                            <input type="radio" id="pay_upi" name="payment_method" value="upi" <?php if (!$has_saved_cards) echo 'checked'; ?> class="payment-radio">
                            <label for="pay_upi" class="payment-label">
                                <div class="payment-text">
                                    <span class="payment-title">Pay with UPI</span>
                                </div>
                                <div class="payment-content">
                                    <input type="text" id="upi_id" name="upi_id" class="form-control" placeholder="Enter your UPI ID">
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <input type="hidden" name="total_amount" id="total_amount_hidden" value="<?php echo $grand_total; ?>">
                    <input type="hidden" name="delivery_fee" value="<?php echo $delivery_fee; ?>">
                    <input type="hidden" name="delivery_type" value="<?php echo htmlspecialchars($delivery_type); ?>">
                    <input type="hidden" name="delivery_distance" value="<?php echo $distance; ?>">
                    <input type="hidden" name="delivery_address" value="<?php echo htmlspecialchars($location); ?>">
                    
                    <button type="submit" name="proceed_to_payment" class="btn-pay">Pay Now</button>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('quantity');
    const subtotalSpan = document.getElementById('subtotal-amount');
    const grandTotalSpan = document.getElementById('grand-total-amount');
    const totalAmountHiddenInput = document.getElementById('total_amount_hidden');
    
    // Read the base product rate and delivery fee from data attributes
    const productRate = parseFloat(document.querySelector('.price').dataset.rate);
    const deliveryFee = parseFloat(document.querySelector('[data-fee]').dataset.fee);

    function updateTotal() {
        const quantity = parseInt(quantityInput.value) || 0;
        
        // Calculate subtotal and grand total
        const subtotal = productRate * quantity;
        const grandTotal = subtotal + deliveryFee;
        
        // Format to 2 decimal places with commas
        const formatCurrency = (num) => '₹' + num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");

        // Update the displayed text
        subtotalSpan.textContent = formatCurrency(subtotal);
        grandTotalSpan.textContent = formatCurrency(grandTotal);

        // Update the hidden input for form submission
        totalAmountHiddenInput.value = grandTotal.toFixed(2);
    }
    
    if(quantityInput) {
        quantityInput.addEventListener('input', updateTotal);
    }

    // Initial calculation on page load
    updateTotal();
});
</script>

</body>
</html>