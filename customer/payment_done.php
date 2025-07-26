<?php
require_once '../session_manager.php';
include '../db_connect.php';

requireCustomer();

// Check session timeout (30 minutes)
checkSessionTimeout(30);

$customer_id = getCurrentUserId();

if (!isset($_GET['order_id'])) {
    header("Location: customer_dashboard.php");
    exit;
}

$order_id = intval($_GET['order_id']);

// Validate order exists and belongs to customer
$stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND customer_id = ?");
$stmt->bind_param("is", $order_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: customer_dashboard.php");
    exit;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eafbea;
            text-align: center;
            padding: 100px;
        }
        h2 {
            font-size: 28px;
            color: #28a745;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            background: #2d89e6;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <h2>Payment Done Successfully!</h2>
    <a href="customer_orders.php?order_id=<?php echo htmlspecialchars($order_id); ?>">View Order Details</a>
</body>
</html>