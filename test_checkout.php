<?php
require_once 'session_manager.php';
include 'db_connect.php';

echo "<h2>Checkout Debug Information</h2>";

// Test database connection
echo "<h3>Database Connection Test</h3>";
if ($conn) {
    echo "✅ Database connection successful<br>";
} else {
    echo "❌ Database connection failed<br>";
    exit;
}

// Test session manager
echo "<h3>Session Manager Test</h3>";
if (function_exists('isLoggedIn')) {
    echo "✅ Session manager functions available<br>";
    echo "Is logged in: " . (isLoggedIn() ? 'Yes' : 'No') . "<br>";
    if (isLoggedIn()) {
        echo "User ID: " . getCurrentUserId() . "<br>";
        echo "User Role: " . getCurrentUserRole() . "<br>";
        echo "Username: " . getCurrentUsername() . "<br>";
    }
} else {
    echo "❌ Session manager functions not available<br>";
}

// Test tables structure
echo "<h3>Database Tables Test</h3>";

$tables = ['customers', 'orders', 'order_items', 'cart_items', 'products', 'product_questions'];

foreach ($tables as $table) {
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        echo "✅ Table '$table' exists<br>";
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'] . ' (' . $row['Type'] . ')';
        }
        echo "Columns: " . implode(', ', $columns) . "<br><br>";
    } else {
        echo "❌ Table '$table' does not exist<br><br>";
    }
}

// Test customer data
echo "<h3>Customer Data Test</h3>";
if (isLoggedIn() && isCustomer()) {
    $customer_id = getCurrentUserId();
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    
    if ($customer) {
        echo "✅ Customer found<br>";
        echo "ID: " . $customer['id'] . "<br>";
        echo "Name: " . $customer['name'] . "<br>";
        echo "Email: " . $customer['email'] . "<br>";
        echo "Location: " . $customer['location'] . "<br>";
        echo "Latitude: " . $customer['latitude'] . "<br>";
        echo "Longitude: " . $customer['longitude'] . "<br>";
    } else {
        echo "❌ Customer not found<br>";
    }
} else {
    echo "Not logged in as customer<br>";
}

// Test cart items
echo "<h3>Cart Items Test</h3>";
if (isLoggedIn() && isCustomer()) {
    $customer_id = getCurrentUserId();
    $stmt = $conn->prepare("SELECT ci.*, p.name FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "✅ Cart has " . $result->num_rows . " items<br>";
        while ($item = $result->fetch_assoc()) {
            echo "- " . $item['name'] . " (Qty: " . $item['quantity'] . ")<br>";
        }
    } else {
        echo "❌ Cart is empty<br>";
    }
} else {
    echo "Not logged in as customer<br>";
}

echo "<h3>Session Variables</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?> 