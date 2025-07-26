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
    <title>Processing Payment</title>
    <meta http-equiv="refresh" content="3;url=payment_done.php?order_id=<?php echo htmlspecialchars($order_id); ?>">
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
</head>
<body>
    <h2>Processing your payment...</h2>
    <div class="loader"></div>
</body>
</html>