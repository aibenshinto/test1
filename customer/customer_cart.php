<?php
require_once '../session_manager.php';
include '../db_connect.php';

requireCustomer();

// Check session timeout (30 minutes)
checkSessionTimeout(30);

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$customer_id = getCurrentUserId();
$message = '';
$error = '';

// Handle Create Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_cart'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $cart_mid = 'C' . substr(uniqid(), -6);
        $status = 'Active';
        $create_stmt = $conn->prepare("INSERT INTO tbl_cart_master (cart_mid, cust_id, status) VALUES (?, ?, ?)");
        $create_stmt->bind_param("sss", $cart_mid, $customer_id, $status);
        if ($create_stmt->execute()) {
            $message = "Cart #$cart_mid created successfully!";
            header("Location: customer_cart.php?message=" . urlencode($message));
            exit;
        } else {
            $error = "Failed to create cart: " . $conn->error;
        }
        $create_stmt->close();
    }
}

// Fetch all active carts for the customer
$cart_stmt = $conn->prepare("SELECT cart_mid FROM tbl_cart_master WHERE cust_id = ? AND status = 'Active'");
$cart_stmt->bind_param("s", $customer_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$active_carts = [];
while ($cart = $cart_result->fetch_assoc()) {
    $active_carts[] = $cart['cart_mid'];
}
$cart_stmt->close();

// Fetch items for each active cart
$carts = [];
foreach ($active_carts as $cart_mid) {
    $sql = "SELECT cc.cart_id, cc.item_qty, cc.item_rate, i.Item_id, i.Item_name, i.Item_image, i.Item_qty AS stock_qty 
            FROM tbl_cart_child cc
            JOIN tbl_cart_master cm ON cc.cart_mid = cm.cart_mid
            JOIN tbl_item i ON cc.item_id = i.Item_id
            WHERE cm.cart_mid = ? AND cm.status = 'Active'";
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
    $result->close();
}

// Handle update quantities or remove items
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $cart_mid = $_POST['cart_mid'];
        // Verify cart belongs to customer
        $cart_check_stmt = $conn->prepare("SELECT cart_mid FROM tbl_cart_master WHERE cart_mid = ? AND cust_id = ? AND status = 'Active'");
        $cart_check_stmt->bind_param("ss", $cart_mid, $customer_id);
        $cart_check_stmt->execute();
        $cart_check_result = $cart_check_stmt->get_result();
        if ($cart_check_result->num_rows === 0) {
            $error = "Invalid cart selected.";
        } else {
            if (isset($_POST['quantities'])) {
                $conn->begin_transaction();
                try {
                    foreach ($_POST['quantities'] as $cart_id => $quantity) {
                        $quantity = intval($quantity);
                        // Check stock availability
                        $stock_stmt = $conn->prepare("SELECT i.Item_qty FROM tbl_cart_child cc JOIN tbl_item i ON cc.item_id = i.Item_id WHERE cc.cart_id = ?");
                        $stock_stmt->bind_param("s", $cart_id);
                        $stock_stmt->execute();
                        $stock_result = $stock_stmt->get_result();
                        $stock_data = $stock_result->fetch_assoc();
                        $stock_qty = $stock_data['Item_qty'];
                        $stock_stmt->close();
                        
                        if ($quantity < 1) {
                            // Remove item from cart
                            $del_stmt = $conn->prepare("DELETE FROM tbl_cart_child WHERE cart_id = ? AND cart_mid = ?");
                            $del_stmt->bind_param("ss", $cart_id, $cart_mid);
                            $del_stmt->execute();
                            $del_stmt->close();
                        } elseif ($quantity > $stock_qty) {
                            throw new Exception("Quantity for item in cart #$cart_mid exceeds available stock ($stock_qty).");
                        } else {
                            // Update quantity
                            $update_stmt = $conn->prepare("UPDATE tbl_cart_child SET item_qty = ?, updated_at = CURRENT_TIMESTAMP WHERE cart_id = ? AND cart_mid = ?");
                            $update_stmt->bind_param("iss", $quantity, $cart_id, $cart_mid);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                    }
                    $conn->commit();
                    $message = "Cart #$cart_mid updated successfully!";
                    header("Location: customer_cart.php?message=" . urlencode($message));
                    exit;
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Failed to update cart: " . $e->getMessage();
                }
            }
        }
        $cart_check_stmt->close();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Carts</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fefefe;
            margin: 40px;
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .cart-section {
            margin-bottom: 40px;
        }
        .cart-section h3 {
            color: #2d89e6;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        th, td {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #2d89e6;
            color: white;
        }
        img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        input[type="number"] {
            width: 60px;
            padding: 5px;
            text-align: center;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .actions {
            margin-top: 20px;
            text-align: right;
        }
        button, a {
            background: #2d89e6;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            margin-left: 10px;
        }
        a.checkout {
            background: #4CAF50;
        }
        .empty-message {
            text-align: center;
            font-size: 18px;
            color: #555;
            margin-top: 50px;
        }
        .message.success {
            background: #e7f4e4;
            color: #2e7d32;
            padding: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
            border-radius: 5px;
        }
        .message.error {
            background: #ffefef;
            color: #c0392b;
            padding: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #c0392b;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<h2>Your Shopping Carts</h2>

<?php if ($message || (isset($_GET['message']) && $_GET['message'])): ?>
    <div class="message success"><?php echo htmlspecialchars($message ?: $_GET['message']); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (!empty($carts)): ?>
    <?php foreach ($carts as $cart_mid => $cart_data): ?>
        <div class="cart-section">
            <h3>Cart #<?php echo htmlspecialchars($cart_mid); ?></h3>
            <?php if (!empty($cart_data['items'])): ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="cart_mid" value="<?php echo htmlspecialchars($cart_mid); ?>">
                    <table>
                        <tr>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                        <tbody>
                        <?php foreach ($cart_data['items'] as $item): ?>
                            <tr>
                                <td>
                                    <img src="../<?php echo htmlspecialchars($item['Item_image']); ?>" alt="Item Image" class="product-image">
                                </td>
                                <td><?php echo htmlspecialchars($item['Item_name']); ?></td>
                                <td>₹<?php echo number_format($item['item_rate'], 2); ?></td>
                                <td>
                                    <input type="number" name="quantities[<?php echo $item['cart_id']; ?>]" value="<?php echo $item['item_qty']; ?>" min="0" max="<?php echo $item['stock_qty']; ?>" class="quantity-input">
                                </td>
                                <td>₹<?php echo number_format($item['item_rate'] * $item['item_qty'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tr>
                            <th colspan="4" style="text-align:right;">Cart Total:</th>
                            <th>₹<?php echo number_format($cart_data['total'], 2); ?></th>
                        </tr>
                    </table>
                    <div class="actions">
                        <button type="submit" name="update_cart">Update Cart</button>
                        <a href="checkout.php?cart_mid=<?php echo urlencode($cart_mid); ?>" class="checkout">Proceed to Checkout</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-message">This cart is empty.</div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="empty-message">
        You have no active carts.
        <form method="post" style="display: inline;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" name="create_cart" style="background: #2d89e6; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; margin-left: 10px;">Create a Cart</button>
        </form>
    </div>
<?php endif; ?>

<br>
<a href="customer_dashboard.php">← Back to Dashboard</a>

</body>
</html>
<?php
$conn->close();
?>
</xArtifact>