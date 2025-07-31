<?php
require_once '../session_manager.php';
include '../db_connect.php';

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_GET['id'])) {
    die("Item not found.");
}

$id = $_GET['id'];
$cartMessage = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
$errorMessage = '';
$isLoggedInCustomer = isCustomer();

// Fetch item details
$stmt = $conn->prepare("SELECT i.*, c.cat_name FROM tbl_item i LEFT JOIN tbl_category c ON i.Cat_id = c.cat_id WHERE i.Item_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Item not found.");
}
$product = $result->fetch_assoc();
$stmt->close();

// Fetch active carts for the logged-in customer
$active_carts = [];
if ($isLoggedInCustomer) {
    $customer_id = getCurrentUserId();
    $cart_stmt = $conn->prepare("SELECT cart_mid FROM tbl_cart_master WHERE cust_id = ? AND status = 'Active'");
    $cart_stmt->bind_param("s", $customer_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    while ($cart = $cart_result->fetch_assoc()) {
        $active_carts[] = $cart['cart_mid'];
    }
    $cart_stmt->close();
}

// Handle Form Submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errorMessage = "Invalid CSRF token.";
    } elseif (!$isLoggedInCustomer) {
        $errorMessage = "Please login to perform this action.";
    } else {
        $customer_id = getCurrentUserId();
        
        // Handle Create Cart
        if (isset($_POST['create_cart'])) {
            $cart_mid = 'C' . substr(uniqid(), -5);
            $create_stmt = $conn->prepare("INSERT INTO tbl_cart_master (cart_mid, cust_id, status) VALUES (?, ?, 'Active')");
            $create_stmt->bind_param("ss", $cart_mid, $customer_id);
            if ($create_stmt->execute()) {
                header("Location: product_details.php?id=" . urlencode($id) . "&message=Cart #" . $cart_mid . " created!");
                exit;
            } else {
                $errorMessage = "Failed to create cart.";
            }
            $create_stmt->close();
        }

        // Handle Add to Cart
        if (isset($_POST['add_to_cart'])) {
            $cart_mid = trim($_POST['cart_mid']);
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

            if (empty($cart_mid) || !in_array($cart_mid, $active_carts)) {
                 $errorMessage = "Please select a valid active cart.";
            } elseif ($quantity < 1) {
                $errorMessage = "Quantity must be at least 1.";
            } elseif ($product['Item_qty'] < $quantity) {
                $errorMessage = "Insufficient stock. Only " . $product['Item_qty'] . " items available.";
            } else {
                // Transaction for adding to cart
                $conn->begin_transaction();
                try {
                    $item_check_stmt = $conn->prepare("SELECT cart_id, item_qty FROM tbl_cart_child WHERE cart_mid = ? AND item_id = ?");
                    $item_check_stmt->bind_param("ss", $cart_mid, $id);
                    $item_check_stmt->execute();
                    $item_result = $item_check_stmt->get_result();

                    if ($item_result->num_rows > 0) { // Update existing item
                        $row = $item_result->fetch_assoc();
                        $new_quantity = $row['item_qty'] + $quantity;
                        if ($new_quantity > $product['Item_qty']) {
                            throw new Exception("Total quantity ($new_quantity) exceeds available stock.");
                        }
                        $update_stmt = $conn->prepare("UPDATE tbl_cart_child SET item_qty = ? WHERE cart_id = ?");
                        $update_stmt->bind_param("is", $new_quantity, $row['cart_id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                        $cartMessage = "Product quantity updated in your cart!";
                    } else { // Insert new item
                        $cart_id = 'CI' . substr(uniqid(), -4);
                        $insert_stmt = $conn->prepare("INSERT INTO tbl_cart_child (cart_id, cart_mid, item_id, item_qty, item_rate) VALUES (?, ?, ?, ?, ?)");
                        $insert_stmt->bind_param("sssid", $cart_id, $cart_mid, $id, $quantity, $product['Item_rate']);
                        $insert_stmt->execute();
                        $insert_stmt->close();
                        $cartMessage = "Product added to your cart!";
                    }
                    $conn->commit();
                    header("Location: product_details.php?id=" . urlencode($id) . "&message=" . urlencode($cartMessage));
                    exit;
                } catch (Exception $e) {
                    $conn->rollback();
                    $errorMessage = "Failed to add to cart: " . $e->getMessage();
                }
                $item_check_stmt->close();
            }
        }
    }
}

$active_page = ''; // To prevent errors in included header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['Item_name']); ?> - Synopsis</title>
    <link rel="stylesheet" href="../css/product_details_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Header Section -->
<header class="header">
    <div class="header-logo">
        <a href="customer_dashboard.php">Synopsis</a>
    </div>
    <nav class="nav-links">
        <a href="customer_dashboard.php">Products</a>
        <a href="customer_orders.php">My Orders</a>
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

<div class="product-container">
    <div class="product-image">
        <img src="<?php echo htmlspecialchars('../' . $product['Item_image']); ?>" alt="<?php echo htmlspecialchars($product['Item_name']); ?>">
    </div>

    <div class="product-info">
        <p class="category"><?php echo htmlspecialchars($product['cat_name'] ?? 'N/A'); ?></p>
        <h1><?php echo htmlspecialchars($product['Item_name']); ?></h1>
        
        <div class="price">₹<?php echo number_format($product['Item_rate'], 2); ?></div>
        
        <div class="rating">
            <strong>Rating:</strong>
            <?php 
            $rating = round($product['Item_rating'] ?? 0);
            for ($i = 1; $i <= 5; $i++) {
                echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
            }
            ?>
        </div>

        <ul class="product-specs">
            <li><strong>Brand:</strong> <span><?php echo htmlspecialchars($product['Item_brand']); ?></span></li>
            <li><strong>Model:</strong> <span><?php echo htmlspecialchars($product['Item_model']); ?></span></li>
            <li><strong>Quality:</strong> <span><?php echo htmlspecialchars($product['Item_quality']); ?></span></li>
        </ul>

        <div class="stock <?php echo ($product['Item_qty'] <= 0) ? 'out-of-stock' : ''; ?>">
            <?php echo ($product['Item_qty'] > 0) ? 'In Stock: ' . htmlspecialchars($product['Item_qty']) . ' units' : 'Out of Stock'; ?>
        </div>

        <?php if ($cartMessage): ?>
            <div class="message"><?php echo $cartMessage; ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="error-message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <div class="actions">
            <?php if ($isLoggedInCustomer): ?>
                <?php if (!empty($active_carts)): ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <select name="cart_mid" required>
                            <option value="">Select a Cart</option>
                            <?php foreach ($active_carts as $cart_mid): ?>
                                <option value="<?php echo htmlspecialchars($cart_mid); ?>">Cart #<?php echo htmlspecialchars($cart_mid); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['Item_qty']; ?>">
                        <button type="submit" name="add_to_cart" class="btn-primary">Add to Cart</button>
                    </form>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <button type="submit" name="create_cart" class="btn-secondary">Create a Cart to Add Items</button>
                    </form>
                <?php endif; ?>
                <a href="checkout_single.php?id=<?php echo urlencode($product['Item_id']); ?>" class="btn-primary">Buy Now</a>
            <?php else: ?>
                <p class="login-prompt"><a href="login_customer.php">Login to add to cart or buy now</a></p>
            <?php endif; ?>
        </div>
        
        <a href="customer_dashboard.php" class="back-link">← Back to Products</a>
    </div>
</div>

</body>
</html>
