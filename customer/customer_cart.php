<?php
require_once '../session_manager.php';
include '../db_connect.php';

requireCustomer();

// Optional: Check session timeout (30 minutes)
checkSessionTimeout(30);

$customer_id = getCurrentUserId();

// Fetch all cart items for this customer
$sql = "SELECT ci.quantity, p.id AS product_id, p.name, p.price, p.image, p.stock 
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.customer_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

// Handle update quantities or remove items (optional)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            $quantity = intval($quantity);
            if ($quantity < 1) {
                // Remove from cart if quantity less than 1
                $delStmt = $conn->prepare("DELETE FROM cart_items WHERE customer_id = ? AND product_id = ?");
                $delStmt->bind_param("ii", $customer_id, $product_id);
                $delStmt->execute();
            } else {
                // Update quantity
                $updateStmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE customer_id = ? AND product_id = ?");
                $updateStmt->bind_param("iii", $quantity, $customer_id, $product_id);
                $updateStmt->execute();
            }
        }
        header("Location: customer_cart.php"); // Refresh page
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Cart</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
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
        a {
            background: #4CAF50;
        }
        .empty-message {
            text-align: center;
            font-size: 18px;
            color: #555;
            margin-top: 50px;
        }
    </style>
</head>
<body>

<h2>Your Shopping Cart</h2>

<?php if ($result->num_rows > 0): ?>
<form method="post">
<table>
    <tr>
        <th>Product</th>
        <th>Image</th>
        <th>Price</th>
        <th>Quantity</th>
        <th>Total</th>
    </tr>
    <?php 
    $grand_total = 0;
    while ($row = $result->fetch_assoc()): 
        $total_price = $row['price'] * $row['quantity'];
        $grand_total += $total_price;
    ?>
    <tr>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Product Image"></td>
        <td>₹<?php echo number_format($row['price'], 2); ?></td>
        <td>
            <input type="number" name="quantities[<?php echo $row['product_id']; ?>]" value="<?php echo $row['quantity']; ?>" min="0">
            <small>(Set 0 to remove)</small>
        </td>
        <td>₹<?php echo number_format($total_price, 2); ?></td>
    </tr>
    <?php endwhile; ?>
    <tr>
        <th colspan="4" style="text-align:right;">Grand Total:</th>
        <th>₹<?php echo number_format($grand_total, 2); ?></th>
    </tr>
</table>

<div class="actions">
    <button type="submit" name="update_cart">Update Cart</button>
    <a href="checkout.php">Proceed to Checkout</a>
</div>
</form>
<?php else: ?>
    <div class="empty-message">Your cart is empty.</div>
<?php endif; ?>

<br>
<a href="customer_dashboard.php">← Back to Dashboard</a>

</body>
</html>
