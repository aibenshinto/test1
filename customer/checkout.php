<?php
session_name('CUSTOMERSESSID');
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

// Fetch all cart items for this customer
$sql = "SELECT ci.quantity, p.id AS product_id, p.name, p.price 
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.customer_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Your cart is empty. <a href='customer_dashboard.php'>Go back</a>";
    exit;
}

$cart_items = [];
$grand_total = 0;
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $grand_total += $row['price'] * $row['quantity'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; border-bottom: 1px solid #ddd; text-align: center; }
        th { background-color: #2d89e6; color: white; }
        .pay-btn {
            background: #4CAF50;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            display: block;
            width: 150px;
            margin-left: auto;
        }
    </style>
</head>
<body>

<h2>Confirm Your Order</h2>

<table>
    <tr>
        <th>Product</th>
        <th>Price (₹)</th>
        <th>Quantity</th>
        <th>Total (₹)</th>
    </tr>
    <?php foreach ($cart_items as $item): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['name']); ?></td>
            <td><?php echo number_format($item['price'], 2); ?></td>
            <td><?php echo $item['quantity']; ?></td>
            <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
        </tr>
    <?php endforeach; ?>
    <tr>
        <th colspan="3" style="text-align:right;">Grand Total:</th>
        <th>₹<?php echo number_format($grand_total, 2); ?></th>
    </tr>
</table>

<form action="payment_processing.php" method="post">
    <input type="hidden" name="total_amount" value="<?php echo $grand_total; ?>">
    <button type="submit" class="pay-btn">Pay Now</button>
</form>

<br>
<a href="customer_cart.php">← Back to Cart</a>

</body>
</html>
