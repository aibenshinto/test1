<?php
session_name('CUSTOMERSESSID');
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../authentication/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $customer_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    // Get product price from database
    $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Invalid product.");
    }

    $product = $result->fetch_assoc();
    $price = $product['price'];
    $total_amount = $price * $quantity;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert order
        $orderStmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, order_date) VALUES (?, ?, NOW())");
        $orderStmt->bind_param("id", $customer_id, $total_amount);
        $orderStmt->execute();

        $order_id = $conn->insert_id;

        // Insert order item
        $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $itemStmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
        $itemStmt->execute();

        $conn->commit();

        // Redirect to payment loading page
        header("Location: payment_loading.php?order_id=$order_id");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("Error processing your order. Please try again.");
    }
}
?>