<?php
require_once '../session_manager.php';
include '../db_connect.php';
include '../delivery_utils.php';

requireCustomer();

// Check session timeout (30 minutes)
checkSessionTimeout(30);

$customer_id = getCurrentUserId();
$error = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    header("Location: customer_dashboard.php");
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid CSRF token.");
}

$product_id = $_POST['product_id'];
$quantity = intval($_POST['quantity']);
$delivery_fee = floatval($_POST['delivery_fee'] ?? 0);
$delivery_type = $_POST['delivery_type'] ?? 'pickup';
$delivery_distance = floatval($_POST['delivery_distance'] ?? 0);
$delivery_address = $_POST['delivery_address'] ?? 'Warehouse Pickup';

// Validate quantity
if ($quantity < 1) {
    die("Invalid quantity. Please try again.");
}

// Fetch product details
$stmt = $conn->prepare("SELECT Item_id, Item_name, Item_rate, Item_qty FROM tbl_item WHERE Item_id = ?");
$stmt->bind_param("s", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    die("Invalid product. <a href='customer_dashboard.php'>Go back</a>");
}

$product = $result->fetch_assoc();
$stmt->close();

if ($quantity > $product['Item_qty']) {
    die("Insufficient stock. Only {$product['Item_qty']} items available. <a href='customer_dashboard.php'>Go back</a>");
}

$total_amount = $product['Item_rate'] * $quantity + $delivery_fee;

// Get customer details for validation
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

// Process order and insert into DB
$conn->begin_transaction();

try {
    // Create a temporary cart
    $cart_mid = 'C' . substr(uniqid(), -6);
    $status = 'Active';
    $create_stmt = $conn->prepare("INSERT INTO tbl_cart_master (cart_mid, cust_id, status) VALUES (?, ?, ?)");
    $create_stmt->bind_param("sss", $cart_mid, $customer_id, $status);
    if (!$create_stmt->execute()) {
        throw new Exception("Failed to create cart: " . $create_stmt->error);
    }
    $create_stmt->close();

    // Add item to cart
    $cart_id = 'CI' . substr(uniqid(), -4);
    $insert_stmt = $conn->prepare("INSERT INTO tbl_cart_child (cart_id, cart_mid, item_id, item_qty, item_rate) VALUES (?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("sssid", $cart_id, $cart_mid, $product_id, $quantity, $product['Item_rate']);
    if (!$insert_stmt->execute()) {
        throw new Exception("Failed to add item to cart: " . $insert_stmt->error);
    }
    $insert_stmt->close();

    // Insert order
    $order_date = date('Y-m-d H:i:s');
    $order_status = 'Pending';
    $order_stmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, order_date, status, delivery_type, delivery_address, delivery_distance, delivery_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $order_stmt->bind_param("sdssssdd", $customer_id, $total_amount, $order_date, $order_status, $delivery_type, $delivery_address, $delivery_distance, $delivery_fee);
    if (!$order_stmt->execute()) {
        throw new Exception("Failed to create order: " . $order_stmt->error);
    }
    $order_id = $conn->insert_id;
    $order_stmt->close();

    // Insert order item
    $order_item_stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
    $order_item_stmt->bind_param("isid", $order_id, $product_id, $quantity, $product['Item_rate']);
    if (!$order_item_stmt->execute()) {
        throw new Exception("Failed to insert order item: " . $order_item_stmt->error);
    }
    $order_item_stmt->close();

    // Update stock
    $update_stock_stmt = $conn->prepare("UPDATE tbl_item SET Item_qty = Item_qty - ? WHERE Item_id = ?");
    $update_stock_stmt->bind_param("is", $quantity, $product_id);
    if (!$update_stock_stmt->execute()) {
        throw new Exception("Failed to update stock for item: " . $product['Item_name']);
    }
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

    // Redirect to payment_loading.php
    header("Location: payment_loading.php?order_id=$order_id");
    exit;
} catch (Exception $e) {
    $conn->rollback();
    error_log("Payment processing error: " . $e->getMessage());
    die("Error processing your order. Please try again. Error: " . $e->getMessage());
}

$conn->close();
?>