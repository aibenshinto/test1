<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../../db_connect.php';

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
}

$stmt->close();
$conn->close();
?>