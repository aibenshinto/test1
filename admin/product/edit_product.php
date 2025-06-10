<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../authenticate/login.php");
    exit;
}

include '../../db_connect.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo "Invalid product ID.";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo "Product not found.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Product</title>
  <link rel="stylesheet" href="edit_product.css">
</head>
<body>
  <div class="container">
    <h2>Edit Product</h2>
    <form action="update_product.php" method="POST">
      <input type="hidden" name="id" value="<?= $product['id'] ?>">
      
      <label>Name:</label><br>
      <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required><br><br>
      
      <label>Price:</label><br>
      <input type="number" name="price" value="<?= $product['price'] ?>" required step="0.01"><br><br>
      
      <label>Stock:</label><br>
      <input type="number" name="stock" value="<?= $product['stock'] ?>" required><br><br>
      
      <button type="submit">Update Product</button>
      <a href="../dashboard/admin_dashboard.php?load=product"><button type="button">Cancel</button></a>
    </form>
  </div>
</body>
</html>
