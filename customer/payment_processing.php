<?php
session_name('CUSTOMERSESSID');
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['total_amount'])) {
    header("Location: checkout.php");
    exit;
}

$total_amount = floatval($_POST['total_amount']);

// Fetch cart items for the order
$sql = "SELECT ci.product_id, ci.quantity, p.price FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.customer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cart_result = $stmt->get_result();

if ($cart_result->num_rows === 0) {
    echo "Cart empty. <a href='customer_dashboard.php'>Go back</a>";
    exit;
}

// Process order and insert into DB
$conn->begin_transaction();

try {
    // Insert order
    $orderInsert = $conn->prepare("INSERT INTO orders (customer_id, total_amount, order_date) VALUES (?, ?, NOW())");
    $orderInsert->bind_param("id", $customer_id, $total_amount);
    $orderInsert->execute();
    $order_id = $conn->insert_id;

    // Insert order items
    $orderItemInsert = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    while ($item = $cart_result->fetch_assoc()) {
        $orderItemInsert->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $orderItemInsert->execute();
    }

    // Clear cart
    $clearCart = $conn->prepare("DELETE FROM cart_items WHERE customer_id = ?");
    $clearCart->bind_param("i", $customer_id);
    $clearCart->execute();

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    die("Error processing your order. Please try again.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Processing Payment...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fffbea;
            text-align: center;
            padding: 100px;
        }
        h2 {
            font-size: 28px;
            color: #444;
        }
        .loader {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            margin: 20px auto;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <script>
        // Simulate payment processing delay then redirect
        setTimeout(function() {
            window.location.href = "payment_done.php?order_id=<?php echo $order_id; ?>";
        }, 3000); // 3 seconds delay
    </script>
</head>
<body>
    <h2>Processing your payment...</h2>
    <div class="loader"></div>
</body>
</html>
