<?php
require_once '../session_manager.php';
include '../db_connect.php';

requireCustomer();
checkSessionTimeout(30);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$customer_id = getCurrentUserId();
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
$error = '';

// Handle Create Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_cart'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        $cart_mid = 'C' . substr(uniqid(), -5);
        $create_stmt = $conn->prepare("INSERT INTO tbl_cart_master (cart_mid, cust_id, status) VALUES (?, ?, 'Active')");
        $create_stmt->bind_param("ss", $cart_mid, $customer_id);
        if ($create_stmt->execute()) {
            header("Location: customer_cart.php?message=Cart #" . $cart_mid . " created!");
            exit;
        } else {
            $error = "Failed to create cart.";
        }
        $create_stmt->close();
    }
}

// Handle update quantities or remove items
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        $cart_mid = $_POST['cart_mid'];
        $cart_check_stmt = $conn->prepare("SELECT cart_mid FROM tbl_cart_master WHERE cart_mid = ? AND cust_id = ? AND status = 'Active'");
        $cart_check_stmt->bind_param("ss", $cart_mid, $customer_id);
        $cart_check_stmt->execute();
        if ($cart_check_stmt->get_result()->num_rows === 0) {
            $error = "Invalid cart selected.";
        } else {
            $conn->begin_transaction();
            try {
                foreach ($_POST['quantities'] as $cart_id => $quantity) {
                    $quantity = intval($quantity);
                    if ($quantity < 1) {
                        $del_stmt = $conn->prepare("DELETE FROM tbl_cart_child WHERE cart_id = ? AND cart_mid = ?");
                        $del_stmt->bind_param("ss", $cart_id, $cart_mid);
                        $del_stmt->execute();
                        $del_stmt->close();
                    } else {
                        $update_stmt = $conn->prepare("UPDATE tbl_cart_child SET item_qty = ? WHERE cart_id = ? AND cart_mid = ?");
                        $update_stmt->bind_param("iss", $quantity, $cart_id, $cart_mid);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                }
                $conn->commit();
                header("Location: customer_cart.php?message=Cart #" . $cart_mid . " updated!");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Failed to update cart: " . $e->getMessage();
            }
        }
        $cart_check_stmt->close();
    }
}

// Fetch all active carts and their items
$cart_stmt = $conn->prepare("SELECT cart_mid FROM tbl_cart_master WHERE cust_id = ? AND status = 'Active'");
$cart_stmt->bind_param("s", $customer_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$carts = [];
while ($cart_row = $cart_result->fetch_assoc()) {
    $cart_mid = $cart_row['cart_mid'];
    $sql = "SELECT cc.cart_id, cc.item_qty, cc.item_rate, i.Item_id, i.Item_name, i.Item_image, i.Item_qty AS stock_qty 
            FROM tbl_cart_child cc
            JOIN tbl_item i ON cc.item_id = i.Item_id
            WHERE cc.cart_mid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cart_mid);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    $cart_total = 0;
    while ($item = $result->fetch_assoc()) {
        $items[] = $item;
        $cart_total += $item['item_qty'] * $item['item_rate'];
    }
    $carts[$cart_mid] = ['items' => $items, 'total' => $cart_total];
    $stmt->close();
}
$cart_stmt->close();
$conn->close();

$active_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Carts - Synopsis</title>
    <link rel="stylesheet" href="../css/cart_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header class="header">
    <div class="header-logo"><a href="customer_dashboard.php">Synopsis</a></div>
    <nav class="nav-links">
        <a href="customer_dashboard.php" class="<?php echo ($active_page == 'customer_dashboard.php') ? 'active' : ''; ?>">Products</a>
        <a href="customer_orders.php" class="<?php echo ($active_page == 'customer_orders.php') ? 'active' : ''; ?>">My Orders</a>
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

<div class="container">
    <h1>My Shopping Carts</h1>

    <?php if ($message): ?><div class="message success"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?php echo $error; ?></div><?php endif; ?>

    <?php if (!empty($carts)): ?>
        <?php foreach ($carts as $cart_mid => $cart_data): ?>
            <div class="cart-section">
                <div class="cart-header">Cart #<?php echo htmlspecialchars($cart_mid); ?></div>
                <?php if (!empty($cart_data['items'])): ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="cart_mid" value="<?php echo htmlspecialchars($cart_mid); ?>">
                        <div class="cart-body">
                            <div class="cart-items">
                                <?php foreach ($cart_data['items'] as $item): ?>
                                    <div class="cart-item">
                                        <div class="item-image">
                                            <img src="../<?php echo htmlspecialchars($item['Item_image']); ?>" alt="Item Image">
                                        </div>
                                        <div class="item-details">
                                            <div class="name"><?php echo htmlspecialchars($item['Item_name']); ?></div>
                                            <div class="price">₹<?php echo number_format($item['item_rate'], 2); ?></div>
                                        </div>
                                        <div class="item-quantity">
                                            <input type="number" class="quantity-input" name="quantities[<?php echo $item['cart_id']; ?>]" value="<?php echo $item['item_qty']; ?>" min="0" max="<?php echo $item['stock_qty']; ?>">
                                        </div>
                                        <div class="item-total">
                                            ₹<?php echo number_format($item['item_rate'] * $item['item_qty'], 2); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="cart-summary">
                                <h3>Cart Summary</h3>
                                <div class="summary-row">
                                    <span>Subtotal</span>
                                    <span>₹<?php echo number_format($cart_data['total'], 2); ?></span>
                                </div>
                                <div class="summary-row summary-total">
                                    <span>Total</span>
                                    <span>₹<?php echo number_format($cart_data['total'], 2); ?></span>
                                </div>
                                <button type="submit" name="update_cart" class="btn btn-secondary">Update Cart</button>
                                <a href="checkout.php?cart_mid=<?php echo urlencode($cart_mid); ?>" class="btn btn-primary">Proceed to Checkout</a>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="empty-cart" style="padding: 25px;"><p>This cart is currently empty.</p></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-cart">
            <h3>You have no active carts.</h3>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" name="create_cart" class="btn btn-primary">Create a New Cart</button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
