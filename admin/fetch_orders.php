<?php
include '../db_connect.php';

$sql = "SELECT * FROM orders ORDER BY created_at DESC LIMIT 10";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h3>Recent Orders</h3>";
    echo "<table border='1' cellpadding='10'><tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Created At</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['customer_name']}</td>
                <td>₹{$row['total_amount']}</td>
                <td>{$row['status']}</td>
                <td>{$row['created_at']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No orders found.</p>";
}

$conn->close();
?>