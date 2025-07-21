<?php
require_once 'session_manager.php';

echo "<h1>Session Test Page</h1>";
echo "<p>This page shows the current session status for both customer and staff.</p>";

echo "<h2>Customer Session:</h2>";
if (isCustomerLoggedIn()) {
    echo "✅ Customer logged in:<br>";
    echo "- ID: " . $_SESSION['customer_id'] . "<br>";
    echo "- Name: " . getCustomerName() . "<br>";
    echo "- Email: " . $_SESSION['customer_email'] . "<br>";
    echo "- Login Time: " . date('Y-m-d H:i:s', $_SESSION['customer_login_time']) . "<br>";
} else {
    echo "❌ No customer session<br>";
}

echo "<h2>Staff Session:</h2>";
if (isStaffLoggedIn()) {
    echo "✅ Staff logged in:<br>";
    echo "- ID: " . $_SESSION['staff_id'] . "<br>";
    echo "- Name: " . getStaffName() . "<br>";
    echo "- Email: " . $_SESSION['staff_email'] . "<br>";
    echo "- Role: " . $_SESSION['staff_role'] . "<br>";
    echo "- Login Time: " . date('Y-m-d H:i:s', $_SESSION['staff_login_time']) . "<br>";
} else {
    echo "❌ No staff session<br>";
}

echo "<h2>Quick Links:</h2>";
echo "<a href='customer/login_customer.php'>Customer Login</a><br>";
echo "<a href='authentication/login.php'>Staff Login</a><br>";
echo "<a href='customer/logout.php'>Customer Logout</a><br>";
echo "<a href='authentication/logout.php'>Staff Logout</a><br>";

echo "<h2>Dashboard Links:</h2>";
if (isCustomerLoggedIn()) {
    echo "<a href='customer/customer_dashboard.php'>Customer Dashboard</a><br>";
}
if (isStaffLoggedIn()) {
    if ($_SESSION['staff_role'] === 'delivery') {
        echo "<a href='staff/delivery_dashboard.php'>Delivery Dashboard</a><br>";
    } else {
        echo "<a href='staff/staff_dashboard.php'>Product Manager Dashboard</a><br>";
    }
}
?> 