<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('Unauthorized access'); window.location.href='../authenticate/login.php';</script>";
    exit;
}

include '../../db_connect.php';

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$stock = intval($_POST['stock'] ?? 0);

if (!$id || !$name || $price < 0 || $stock < 0) {
    echo "<script>alert('Invalid input'); window.history.back();</script>";
    exit;
}

$stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, stock = ? WHERE id = ?");
$stmt->bind_param("sdii", $name, $price, $stock, $id);

if ($stmt->execute()) {
    echo "<script>
        alert('Product updated successfully!');
        window.location.href = '../dashboard/admin_dashboard.php?load=product';
    </script>";
} else {
    echo "<script>
        alert('Failed to update product.');
        window.history.back();
    </script>";
}

$stmt->close();
$conn->close();
?>
