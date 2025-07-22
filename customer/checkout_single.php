<?php
require_once '../session_manager.php';
include '../db_connect.php';

requireCustomer();

$customer_id = getCurrentUserId();

if (!isset($_GET['id'])) {
    echo "Product not found.";
    exit;
}

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM tbl_item WHERE Item_id = ?");
$stmt->bind_param("s", $id);
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
    <title>Checkout - <?= htmlspecialchars($product['Item_name']) ?></title>
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
    </style>
</head>
<body>
<div class="checkout-box">
    <h2>Checkout</h2>
    <div class="product-info">
        <img src="../<?php echo htmlspecialchars($product['Item_image']); ?>" alt="Item Image">
        <div>
            <h3><?php echo htmlspecialchars($product['Item_name']); ?></h3>
            <div class="price">₹<?php echo number_format($product['Item_rate'], 2); ?></div>
        </div>
    </div>
    <p>Stock: <?= htmlspecialchars($product['Item_stock']) ?></p>

    <form method="post" action="payment_process.php">
        <input type="hidden" name="product_id" value="<?php echo $product['Item_id']; ?>">
        <input type="hidden" name="quantity" value="1">
        <button type="submit" class="pay-btn">Pay</button>
    </form>
</div>
</body>
</html>
