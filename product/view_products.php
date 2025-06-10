<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit;
}

$result = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head><title>Product Listings</title></head>
<body>
<h2>Manage Products</h2>
<a href="add_product.php">Add Product</a><br><br>
<table border="1" cellpadding="8">
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Price</th>
        <th>Stock</th>
        <th>Image</th>
        <th>Actions</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['description']) ?></td>
        <td>$<?= $row['price'] ?></td>
        <td><?= $row['stock'] ?></td>
        <td>
            <?php if ($row['image']): ?>
                <img src="<?= $row['image'] ?>" width="80">
            <?php else: ?>
                No Image
            <?php endif; ?>
        </td>
        <td>
            <a href="edit_product.php?id=<?= $row['id'] ?>">Edit</a> |
            <a href="delete_product.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<a href="<?= $_SESSION['role'] ?>_dashboard.php">Back to Dashboard</a>
</body>
</html>