<?php
require_once '../session_manager.php';
include '../db_connect.php';

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_GET['id'])) {
    echo "Item not found.";
    exit;
}

$id = $_GET['id'];
$cartMessage = '';
$errorMessage = '';
$isLoggedInCustomer = isCustomer();

// Fetch item details
$stmt = $conn->prepare("SELECT i.*, c.cat_name FROM tbl_item i LEFT JOIN tbl_category c ON i.Cat_id = c.cat_id WHERE i.Item_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Item not found.";
    $stmt->close();
    $conn->close();
    exit;
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
} // End if ($isLoggedInCustomer)

// Handle Create Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_cart'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMessage = "Invalid CSRF token.";
    } elseif (!$isLoggedInCustomer) {
        $errorMessage = "Please login to create a cart.";
    } else {
        $customer_id = getCurrentUserId();
        $cart_mid = 'C' . substr(uniqid(), -5);
        $status = 'Active';
        $create_stmt = $conn->prepare("INSERT INTO tbl_cart_master (cart_mid, cust_id, status) VALUES (?, ?, ?)");
        $create_stmt->bind_param("sss", $cart_mid, $customer_id, $status);
        if ($create_stmt->execute()) {
            $cartMessage = "Cart #$cart_mid created successfully!";
            header("Location: product_details.php?id=" . urlencode($id) . "&message=" . urlencode($cartMessage));
            exit;
        } else {
            $errorMessage = "Failed to create cart: " . $conn->error;
        }
        $create_stmt->close();
    }
} // End if (create_cart)

// Handle Add to Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMessage = "Invalid CSRF token.";
    } elseif (!$isLoggedInCustomer) {
        $errorMessage = "Please login to add products to cart.";
    } else {
        $customer_id = getCurrentUserId();
        $cart_mid = trim($_POST['cart_mid']);
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        // Validate cart_mid
        $cart_check_stmt = $conn->prepare("SELECT cart_mid FROM tbl_cart_master WHERE cart_mid = ? AND cust_id = ? AND status = 'Active'");
        $cart_check_stmt->bind_param("ss", $cart_mid, $customer_id);
        $cart_check_stmt->execute();
        $cart_check_result = $cart_check_stmt->get_result();

        // Validate stock availability
        $stock_check_stmt = $conn->prepare("SELECT Item_qty, Item_rate FROM tbl_item WHERE Item_id = ?");
        $stock_check_stmt->bind_param("s", $id);
        $stock_check_stmt->execute();
        $stock_result = $stock_check_stmt->get_result();
        $stock_data = $stock_result->fetch_assoc();
        $stock_qty = $stock_data['Item_qty'];
        $item_rate = $stock_data['Item_rate'];

        if ($cart_check_result->num_rows === 0) {
            $errorMessage = "Please select a valid active cart.";
        } elseif ($quantity < 1) {
            $errorMessage = "Quantity must be at least 1.";
        } elseif ($stock_qty < $quantity) {
            $errorMessage = "Insufficient stock. Only $stock_qty items available.";
        } else {
            // Check if product is already in the selected cart
            $item_check_stmt = $conn->prepare("SELECT cart_id, item_qty FROM tbl_cart_child WHERE cart_mid = ? AND item_id = ?");
            $item_check_stmt->bind_param("ss", $cart_mid, $id);
            $item_check_stmt->execute();
            $item_result = $item_check_stmt->get_result();

            $conn->begin_transaction();
            try {
                if ($item_result->num_rows > 0) {
                    // Update quantity if item exists
                    $row = $item_result->fetch_assoc();
                    $new_quantity = $row['item_qty'] + $quantity;
                    if ($new_quantity > $stock_qty) {
                        throw new Exception("Total quantity ($new_quantity) exceeds available stock ($stock_qty).");
                    }
                    $update_stmt = $conn->prepare("UPDATE tbl_cart_child SET item_qty = ?, updated_at = CURRENT_TIMESTAMP WHERE cart_id = ?");
                    $update_stmt->bind_param("is", $new_quantity, $row['cart_id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                    $cartMessage = "Product quantity updated in your cart!";
                } else {
                    // Insert new item into cart
                    $cart_id = 'CI' . substr(uniqid(), -4);
                    $insert_stmt = $conn->prepare("INSERT INTO tbl_cart_child (cart_id, cart_mid, item_id, item_qty, item_rate) VALUES (?, ?, ?, ?, ?)");
                    $insert_stmt->bind_param("sssid", $cart_id, $cart_mid, $id, $quantity, $item_rate);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                    $cartMessage = "Product added to your cart!";
                }
                $conn->commit();
                header("Location: product_details.php?id=" . urlencode($id) . "&message=" . urlencode($cartMessage));
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $errorMessage = "Failed to add product to cart: " . $e->getMessage();
            }
            $item_check_stmt->close();
        }
        $cart_check_stmt->close();
        $stock_check_stmt->close();
    }
} // End if (add_to_cart)

// Handle Question Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ask_question']) && $isLoggedInCustomer) {
    $question = trim($_POST['question']);
    if (!empty($question)) {
        $customer_id = getCurrentUserId();
        $insertQ = $conn->prepare("INSERT INTO product_questions (item_id, customer_id, question) VALUES (?, ?, ?)");
        $insertQ->bind_param("sis", $id, $customer_id, $question);
        $insertQ->execute();
        $insertQ->close();
        header("Location: product_details.php?id=" . urlencode($id) . "&message=Question submitted successfully!");
        exit;
    } else {
        $errorMessage = "Please enter a valid question.";
    }
} // End if (ask_question)

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($product['Item_name']); ?> - Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fefefe;
            margin: 40px;
        }
        .product-details {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        img {
            width: 100%;
            height: 300px;
            object-fit: contain;
            margin-bottom: 20px;
        }
        .price {
            color: #2d89e6;
            font-size: 20px;
            margin: 10px 0;
        }
        .stock {
            color: #555;
        }
        .buttons {
            margin-top: 20px;
        }
        .buttons form, .buttons a {
            display: inline-block;
            margin-right: 10px;
        }
        .buttons button, .buttons a {
            background: #2d89e6;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }
        .buttons a {
            background: #4CAF50;
        }
        .buttons a.qna {
            background: #f39c12;
        }
        .message {
            background: #e7f4e4;
            color: #2e7d32;
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #2e7d32;
            border-radius: 5px;
        }
        .error-message {
            background: #ffefef;
            color: #c0392b;
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #c0392b;
            border-radius: 5px;
        }
        .qa-section {
            margin-top: 40px;
        }
        .qa-box {
            padding: 10px;
            border-bottom: 1px solid #ccc;
        }
        textarea {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
        }
        select, input[type="number"] {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="product-details">
    <img src="<?php echo htmlspecialchars('../' . $product['Item_image']); ?>" alt="Item Image">
    <h2><?php echo htmlspecialchars($product['Item_name']); ?></h2>
    <p><strong>Category:</strong> <?php echo htmlspecialchars($product['cat_name'] ?? 'N/A'); ?></p>
    <p><strong>Brand:</strong> <?php echo htmlspecialchars($product['Item_brand']); ?></p>
    <p><strong>Model:</strong> <?php echo htmlspecialchars($product['Item_model']); ?></p>
    <p><strong>Quality:</strong> <?php echo htmlspecialchars($product['Item_quality']); ?></p>
    <div class="price">₹<?php echo number_format($product['Item_rate'], 2); ?></div>
    <div class="stock">Stock: <?php echo htmlspecialchars($product['Item_qty']); ?></div>

    <!-- Rating Section -->
    <div style="margin: 18px 0 10px 0; padding: 12px; background: #f8f8f8; border-radius: 8px; font-size: 22px; color: #f39c12;">
        <strong>Rating:</strong> 
        <?php 
        $rating = intval($product['Item_rating']);
        for ($i = 0; $i < $rating; $i++) echo '★';
        for ($i = 0; $i < 5 - $rating; $i++) echo '✩';
        ?>
    </div>

    <?php if ($cartMessage || (isset($_GET['message']) && $_GET['message'])): ?>
        <div class="message"><?php echo htmlspecialchars($cartMessage ?: $_GET['message']); ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <div class="buttons">
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
                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['Item_qty']; ?>" style="width: 60px;">
                    <button type="submit" name="add_to_cart">Add to Cart</button>
                </form>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <button type="submit" name="create_cart">Create a Cart</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <p><a href="login_customer.php" style="color: #2d89e6;">Login to add to cart</a></p>
        <?php endif; ?>
        <a href="checkout_single.php?id=<?php echo urlencode($product['Item_id']); ?>">Buy Now</a>
        <a href="product_qna.php?id=<?php echo urlencode($id); ?>" class="qna">Q&A</a>
    </div>

    <br><a href="customer_dashboard.php">← Back to Customer Dashboard</a>
</div>
</body>
</html>
<?php
$conn->close();
?>