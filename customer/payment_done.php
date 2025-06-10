<?php
if (!isset($_GET['order_id'])) {
    header("Location: customer_dashboard.php");
    exit;
}
$order_id = intval($_GET['order_id']);
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
    <a href="customer_orders.php?order_id=<?= $order_id ?>">View Order Details</a>
</body>
</html>
