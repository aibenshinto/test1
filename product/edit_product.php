<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Product ID is missing.");
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $image = $_POST['image'];

    $update = $conn->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, image=? WHERE id=?");
    $update->bind_param("ssdisi", $name, $desc, $price, $stock, $image, $id);
    
    if ($update->execute()) {
        header("Location: view_products.php");
        exit;
    } else {
        $message = "Update failed: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Edit Product</title></head>
<body>
<h2>Edit Product</h2>
<?php if ($message) echo "<p>$message</p>"; ?>
<form method="post">
    Name: <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required><br><br>
    Description: <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea><br><br>
    Price: <input type="number" name="price" step="0.01" value="<?= $product['price'] ?>" required><br><br>
    Stock: <input type="number" name="stock" value="<?= $product['stock'] ?>" required><br><br>
    Image URL: <input type="text" name="image" value="<?= htmlspecialchars($product['image']) ?>"><br><br>
    <input type="submit" value="Update Product">
</form>
<a href="view_products.php">Back</a>
</body>
</html>