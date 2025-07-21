<?php
require_once '../session_manager.php';
include '../db_connect.php';
include '../delivery_utils.php';

requireCustomer();

$customer_id = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['total_amount'])) {
    header("Location: checkout.php");
    exit;
}

$total_amount = floatval($_POST['total_amount']);
$delivery_fee = floatval($_POST['delivery_fee'] ?? 0);
$delivery_type = $_POST['delivery_type'] ?? 'pickup';
$delivery_distance = floatval($_POST['delivery_distance'] ?? 0);

// Validate required data
if ($total_amount <= 0) {
    die("Invalid order amount. Please try again.");
}

// Get customer details for delivery address
$customer_sql = "SELECT location FROM customers WHERE id = ?";
$customer_stmt = $conn->prepare($customer_sql);
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();
$customer = $customer_result->fetch_assoc();

if (!$customer) {
    die("Customer not found. Please log in again.");
}

$delivery_address = $delivery_type === 'delivery' ? $customer['location'] : 'Warehouse Pickup';

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
    // Insert order with delivery information (status will use default 'Pending')
    $orderInsert = $conn->prepare("INSERT INTO orders (customer_id, total_amount, order_date, delivery_type, delivery_address, delivery_distance, delivery_fee) VALUES (?, ?, NOW(), ?, ?, ?, ?)");
    $orderInsert->bind_param("idssdd", $customer_id, $total_amount, $delivery_type, $delivery_address, $delivery_distance, $delivery_fee);
    
    if (!$orderInsert->execute()) {
        throw new Exception("Failed to create order: " . $orderInsert->error);
    }
    
    $order_id = $conn->insert_id;

    // Insert order items
    $orderItemInsert = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    while ($item = $cart_result->fetch_assoc()) {
        $orderItemInsert->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        if (!$orderItemInsert->execute()) {
            throw new Exception("Failed to insert order item: " . $orderItemInsert->error);
        }
    }

    // Clear cart
    $clearCart = $conn->prepare("DELETE FROM cart_items WHERE customer_id = ?");
    $clearCart->bind_param("i", $customer_id);
    $clearCart->execute();

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Payment processing error: " . $e->getMessage());
    die("Error processing your order. Please try again. Error: " . $e->getMessage());
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
