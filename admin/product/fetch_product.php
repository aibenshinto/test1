<?php
include '../../db_connect.php';

echo '<div class="product-header">';
echo '<h3>Latest Products</h3>';
echo '<a href="../product/add_product.php"><button id="addProductBtn">+ Add Product</button></a>';
echo '</div>';

$sql = "SELECT * FROM products ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'><tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Actions</th></tr>";
    while($row = $result->fetch_assoc()) {
    echo "<tr>
    <td>{$row['id']}</td>
    <td>{$row['name']}</td>
    <td>₹{$row['price']}</td>
    <td>{$row['stock']}</td>
    <td>
        <a href='../product/edit_product.php?id={$row['id']}' class='edit-product-btn'>Edit</a>
        <button class='delete-btn' data-id='{$row['id']}'>Delete</button>
    </td>
  </tr>";
}
    echo "</table>";
} else {
    echo "<p>No products found.</p>";
}

$conn->close();
?>
