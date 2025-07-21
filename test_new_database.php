<?php
/**
 * Test Script for New Database
 * This script tests the fresh database structure
 */

include 'db_connect.php';

echo "<h1>New Database Test Results</h1>";

// Test 1: Check database connection
echo "<h2>1. Database Connection</h2>";
if ($conn->ping()) {
    echo "✅ Database connection successful<br>";
} else {
    echo "❌ Database connection failed<br>";
}

// Test 2: Check if tables exist
echo "<h2>2. Table Structure</h2>";
$tables = ['customers', 'staff', 'products', 'product_questions', 'orders', 'order_items', 'cart_items'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' does not exist<br>";
    }
}

// Test 3: Check sample data
echo "<h2>3. Sample Data</h2>";

// Count customers
$result = $conn->query("SELECT COUNT(*) as count FROM customers");
$row = $result->fetch_assoc();
echo "Customers: {$row['count']}<br>";

// Count staff
$result = $conn->query("SELECT COUNT(*) as count FROM staff");
$row = $result->fetch_assoc();
echo "Staff: {$row['count']}<br>";

// Count products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$row = $result->fetch_assoc();
echo "Products: {$row['count']}<br>";

// Test 4: Test login functionality
echo "<h2>4. Login Test</h2>";

// Test customer login
$stmt = $conn->prepare("SELECT id, name, email FROM customers WHERE email = ?");
$email = "alice@example.com";
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $customer = $result->fetch_assoc();
    echo "✅ Customer login test passed: {$customer['name']} ({$customer['email']})<br>";
} else {
    echo "❌ Customer login test failed<br>";
}

// Test staff login
$stmt = $conn->prepare("SELECT id, name, email, role FROM staff WHERE email = ?");
$email = "manager@example.com";
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $staff = $result->fetch_assoc();
    echo "✅ Staff login test passed: {$staff['name']} ({$staff['role']})<br>";
} else {
    echo "❌ Staff login test failed<br>";
}

// Test 5: Check foreign key relationships
echo "<h2>5. Foreign Key Relationships</h2>";

// Test cart_items relationship
$result = $conn->query("
    SELECT COUNT(*) as count 
    FROM cart_items ci 
    LEFT JOIN customers c ON ci.customer_id = c.id 
    WHERE c.id IS NULL
");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    echo "✅ Cart items have valid customer references<br>";
} else {
    echo "❌ Cart items have {$row['count']} orphaned customer references<br>";
}

// Test orders relationship
$result = $conn->query("
    SELECT COUNT(*) as count 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.id 
    WHERE c.id IS NULL
");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    echo "✅ Orders have valid customer references<br>";
} else {
    echo "❌ Orders have {$row['count']} orphaned customer references<br>";
}

// Test 6: Show sample data
echo "<h2>6. Sample Data Preview</h2>";

echo "<h3>Products:</h3>";
$result = $conn->query("SELECT name, price, stock FROM products LIMIT 3");
echo "<table border='1'><tr><th>Name</th><th>Price</th><th>Stock</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['name']}</td><td>\${$row['price']}</td><td>{$row['stock']}</td></tr>";
}
echo "</table>";

echo "<h3>Customers:</h3>";
$result = $conn->query("SELECT name, email, location FROM customers LIMIT 3");
echo "<table border='1'><tr><th>Name</th><th>Email</th><th>Location</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['name']}</td><td>{$row['email']}</td><td>{$row['location']}</td></tr>";
}
echo "</table>";

echo "<h3>Staff:</h3>";
$result = $conn->query("SELECT name, email, role FROM staff");
echo "<table border='1'><tr><th>Name</th><th>Email</th><th>Role</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['name']}</td><td>{$row['email']}</td><td>{$row['role']}</td></tr>";
}
echo "</table>";

echo "<h2>Test Complete!</h2>";
echo "<p>If all tests pass, your new database is ready for the application.</p>";
echo "<p><strong>Next:</strong> Test the application with the sample users:</p>";
echo "<ul>";
echo "<li>Customer: alice@example.com / password123</li>";
echo "<li>Product Manager: manager@example.com / password123</li>";
echo "<li>Delivery Staff: delivery@example.com / password123</li>";
echo "</ul>";

$conn->close();
?> 