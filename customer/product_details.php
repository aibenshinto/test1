<?php
require_once '../session_manager.php';
include '../db_connect.php';

if (!isset($_GET['id'])) {
    echo "Item not found.";
    exit;
}

$id = $_GET['id'];

// Check if user is logged in customer
$isLoggedInCustomer = isCustomer();

// Handle Add to Cart
$cartMessage = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    if (!$isLoggedInCustomer) {
        $cartMessage = "Please login to add products to cart.";
    } else {
        $customer_id = getCurrentUserId();

        // Check if product is already in cart
        $stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE customer_id = ? AND item_id = ?");
        $stmt->bind_param("is", $customer_id, $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $new_quantity = $row['quantity'] + 1;
            $update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE customer_id = ? AND item_id = ?");
            $update->bind_param("iis", $new_quantity, $customer_id, $id);
            $update->execute();
            $cartMessage = "Product quantity updated in your cart!";
        } else {
            $insert = $conn->prepare("INSERT INTO cart_items (customer_id, item_id, quantity) VALUES (?, ?, 1)");
            $insert->bind_param("is", $customer_id, $id);
            $insert->execute();
            $cartMessage = "Product added to your cart!";
        }
    }
}

// Handle Question Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ask_question']) && $isLoggedInCustomer) {
    $question = trim($_POST['question']);
    if (!empty($question)) {
        $customer_id = getCurrentUserId();
        $insertQ = $conn->prepare("INSERT INTO product_questions (item_id, customer_id, question) VALUES (?, ?, ?)");
        $insertQ->bind_param("sis", $id, $customer_id, $question);
        $insertQ->execute();
    }
}

// Fetch item details
$stmt = $conn->prepare("SELECT i.*, c.cat_name FROM tbl_item i LEFT JOIN categories c ON i.Cat_id = c.cat_id WHERE i.Item_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Item not found.";
    exit;
}

$product = $result->fetch_assoc();
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
        .message {
            color: green;
            margin: 10px 0;
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
    </style>
</head>
<body>

<div class="product-details">
    <img src="<?php echo htmlspecialchars('../'.$product['Item_image']); ?>" alt="Item Image">
    <h2><?php echo htmlspecialchars($product['Item_name']); ?></h2>
    <p><strong>Category:</strong> <?php echo htmlspecialchars($product['cat_name'] ?? 'N/A'); ?></p>
    <p><strong>Brand:</strong> <?php echo htmlspecialchars($product['Item_brand']); ?></p>
    <p><strong>Model:</strong> <?php echo htmlspecialchars($product['Item_model']); ?></p>
    <p><strong>Quality:</strong> <?php echo htmlspecialchars($product['Item_quality']); ?></p>
    <div class="price">₹<?php echo htmlspecialchars($product['Item_rate']); ?></div>
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

    <?php if ($cartMessage !== ''): ?>
        <div class="message"><?php echo $cartMessage; ?></div>
    <?php endif; ?>

    <div class="buttons">
        <form method="post">
            <button type="submit" name="add_to_cart">Add to Cart</button>
        </form>
        <a href="checkout_single.php?id=<?php echo $product['Item_id']; ?>">Buy Now</a>
        <a href="product_qna.php?id=<?php echo $product['Item_id']; ?>" style="background: #f39c12;">Q&A</a>
    </div>

    <br><a href="customer_dashboard.php">← Back to Dashboard</a>

    <!-- Q&A Section -->

</div>

</body>
</html>
