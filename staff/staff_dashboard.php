<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit;
}
echo "<h2>Welcome Staff " . $_SESSION['username'] . "</h2>";
echo "<a href='add_product.php'>Add Product</a> | ";
echo "<a href='logout.php'>Logout</a>";
?>
<a href="view_products.php">Manage Products</a>