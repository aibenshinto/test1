<?php
require_once '../session_manager.php';
include '../db_connect.php';
include '../delivery_utils.php';

requireCustomer();

// Check session timeout (30 minutes)
checkSessionTimeout(30);

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid CSRF token.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cart_mid']) || !isset($_POST['total_amount'])) {
    header("Location: customer_cart.php");
    exit;
}

$customer_id = getCurrentUserId();
$cart_mid = $_POST['cart_mid'];
$total_amount = floatval($_POST['total_amount']);
$delivery_fee = floatval($_POST['delivery_fee'] ?? 0);
$delivery_type = $_POST['delivery_type'] ?? 'pickup';
$delivery_distance = floatval($_POST['delivery_distance'] ?? 0);
$delivery_address = $_POST['delivery_address'] ?? 'Warehouse Pickup';

// Validate cart_mid
$cart_check_stmt = $conn->prepare("SELECT cart_mid FROM tbl_cart_master WHERE cart_mid = ? AND cust_id = ? AND status = 'Active'");
$cart_check_stmt->bind_param("ss", $cart_mid, $customer_id);
$cart_check_stmt->execute();
$cart_check_result = $cart_check_stmt->get_result();
if ($cart_check_result->num_rows === 0) {
    $cart_check_stmt->close();
    die("Invalid or inactive cart. <a href='customer_cart.php'>Go back to cart</a>");
}
$cart_check_stmt->close();

// Validate total_amount
if ($total_amount <= 0) {
    die("Invalid order amount. Please try again.");
}

// Get customer details for delivery address
$customer_sql = "SELECT Cust_street, Cust_city, Cust_state FROM tbl_customer WHERE Cust_id = ?";
$customer_stmt = $conn->prepare($customer_sql);
$customer_stmt->bind_param("s", $customer_id);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();
$customer = $customer_result->fetch_assoc();
$customer_stmt->close();

if (!$customer) {
    die("Customer not found. Please log in again.");
}

// Use provided delivery_address or construct from customer details
if ($delivery_type === 'delivery' && !$delivery_address) {
    $delivery_address = $customer['Cust_street'];
    if ($customer['Cust_city']) {
        $delivery_address .= ', ' . $customer['Cust_city'];
    }
    if ($customer['Cust_state']) {
        $delivery_address .= ', ' . $customer['Cust_state'];
    }
}

// Fetch cart items for the specific cart
$sql = "SELECT cc.item_id, cc.item_qty AS quantity, cc.item_rate AS price, i.Item_name, i.Item_qty AS stock_qty 
        FROM tbl_cart_child cc 
        JOIN tbl_item i ON cc.item_id = i.Item_id 
        WHERE cc.cart_mid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cart_mid);
$stmt->execute();
$cart_result = $stmt->get_result();

if ($cart_result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    die("Cart is empty. <a href='customer_cart.php'>Go back to cart</a>");
}

$cart_items = [];
while ($item = $cart_result->fetch_assoc()) {
    $cart_items[] = $item;
}
$stmt->close();

// Process order and insert into DB
$conn->begin_transaction();

try {
    // Insert order
    $order_date = date('Y-m-d H:i:s');
    $status = 'Pending';
    $order_stmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, order_date, status, delivery_type, delivery_address, delivery_distance, delivery_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $order_stmt->bind_param("sdssssdd", $customer_id, $total_amount, $order_date, $status, $delivery_type, $delivery_address, $delivery_distance, $delivery_fee);
    if (!$order_stmt->execute()) {
        throw new Exception("Failed to create order: " . $order_stmt->error);
    }
    $order_id = $conn->insert_id;
    $order_stmt->close();

    // Insert order items and update stock
    $order_item_stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
    $update_stock_stmt = $conn->prepare("UPDATE tbl_item SET Item_qty = Item_qty - ? WHERE Item_id = ?");
    
    foreach ($cart_items as $item) {
        $item_id = $item['item_id'];
        $quantity = $item['quantity'];
        $price = $item['price'];
        if ($quantity > $item['stock_qty']) {
            throw new Exception("Insufficient stock for item: " . $item['Item_name']);
        }
        // Insert order item
        $order_item_stmt->bind_param("isid", $order_id, $item_id, $quantity, $price);
        if (!$order_item_stmt->execute()) {
            throw new Exception("Failed to insert order item: " . $order_item_stmt->error);
        }
        // Update stock
        $update_stock_stmt->bind_param("is", $quantity, $item_id);
        if (!$update_stock_stmt->execute()) {
            throw new Exception("Failed to update stock for item: " . $item['Item_name']);
        }
    }
    $order_item_stmt->close();
    $update_stock_stmt->close();

    // Insert payment record
    $pay_id = 'P' . substr(uniqid(), -8);
    $pay_status = 'Paid';
    $pay_date = date('Y-m-d');
    $pay_stmt = $conn->prepare("INSERT INTO tbl_payment (pay_id, cart_id, order_status, pay_amt, pay_date) VALUES (?, ?, ?, ?, ?)");
    $pay_stmt->bind_param("sssds", $pay_id, $cart_mid, $pay_status, $total_amount, $pay_date);
    if (!$pay_stmt->execute()) {
        throw new Exception("Failed to create payment record: " . $pay_stmt->error);
    }
    $pay_stmt->close();

    // Insert delivery record
    $del_id = 'D' . substr(uniqid(), -8);
    $del_pincode = '682001'; // Default pincode for Kochi, update as needed
    $del_status = 'Pending';
    $del_date = date('Y-m-d');
    $del_stmt = $conn->prepare("INSERT INTO tbl_delivery (Del_id, cart_id, cust_id, del_pincode, del_date, del_status) VALUES (?, ?, ?, ?, ?, ?)");
    $del_stmt->bind_param("ssssss", $del_id, $cart_mid, $customer_id, $del_pincode, $del_date, $del_status);
    if (!$del_stmt->execute()) {
        throw new Exception("Failed to create delivery record: " . $del_stmt->error);
    }
    $del_stmt->close();

    // Update cart status
    $update_cart_stmt = $conn->prepare("UPDATE tbl_cart_master SET status = 'Ordered', updated_at = CURRENT_TIMESTAMP WHERE cart_mid = ?");
    $update_cart_stmt->bind_param("s", $cart_mid);
    if (!$update_cart_stmt->execute()) {
        throw new Exception("Failed to update cart status: " . $update_cart_stmt->error);
    }
    $update_cart_stmt->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    error_log("Payment processing error: " . $e->getMessage());
    die("Error processing your order. Please try again. Error: " . $e->getMessage());
}

$conn->close();

// Redirect to payment_loading.php
header("Location: payment_loading.php?order_id=$order_id");
exit;
?>