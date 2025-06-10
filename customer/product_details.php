<?php
session_name('CUSTOMERSESSID');
session_start();
include '../db_connect.php';

if (!isset($_GET['id'])) {
    echo "Product not found.";
    exit;
}

$id = intval($_GET['id']);

// Check if user is logged in customer
$isLoggedInCustomer = isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer';

// Handle Add to Cart
$cartMessage = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    if (!$isLoggedInCustomer) {
        $cartMessage = "Please login to add products to cart.";
    } else {
        $customer_id = $_SESSION['user_id'];

        // Check if product is already in cart
        $stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE customer_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $customer_id, $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Product exists in cart - increment quantity by 1
            $row = $result->fetch_assoc();
            $new_quantity = $row['quantity'] + 1;

            $update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE customer_id = ? AND product_id = ?");
            $update->bind_param("iii", $new_quantity, $customer_id, $id);
            $update->execute();

            $cartMessage = "Product quantity updated in your cart!";
        } else {
            // Insert new product in cart with quantity 1
            $insert = $conn->prepare("INSERT INTO cart_items (customer_id, product_id, quantity) VALUES (?, ?, 1)");
            $insert->bind_param("ii", $customer_id, $id);
            $insert->execute();

            $cartMessage = "Product added to your cart!";
        }
    }
}

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Product not found.";
    exit;
}

$product = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($product['name']); ?> - Details</title>
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
        h2 {
            margin: 0 0 10px;
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
    </style>
</head>
<body>

<div class="product-details">
    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image">
    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
    <div class="price">₹<?php echo htmlspecialchars($product['price']); ?></div>
    <div class="stock">Stock: <?php echo htmlspecialchars($product['stock']); ?></div>

    <?php if ($cartMessage !== ''): ?>
        <div class="message"><?php echo $cartMessage; ?></div>
    <?php endif; ?>

    <div class="buttons">
        <form method="post">
            <button type="submit" name="add_to_cart">Add to Cart</button>
        </form>
        <a href="checkout_single.php?id=<?php echo $product['id']; ?>">Buy Now</a>
    </div>

    <br><a href="customer_dashboard.php">← Back to Dashboard</a>
</div>

</body>
</html>
