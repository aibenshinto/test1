<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $image = $_POST['image']; // Just storing image URL for now

    if (!$name || !$price || $stock < 0) {
        $message = "Please fill all required fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, stock) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsi", $name, $description, $price, $image, $stock);

        if ($stmt->execute()) {
            $message = "Product added successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Add Product</title></head>
<body>
<h2>Add New Product</h2>
<?php if ($message) echo "<p>$message</p>"; ?>
<form method="post">
  Name: <input type="text" name="name" required><br><br>
  Description: <textarea name="description"></textarea><br><br>
  Price: <input type="number" step="0.01" name="price" required><br><br>
  Stock: <input type="number" name="stock" required><br><br>
  Image URL: <input type="text" name="image"><br><br>
  <input type="submit" value="Add Product">
</form>
<a href="<?php echo $_SESSION['role'] . '_dashboard.php'; ?>">Back to Dashboard</a>
</body>
</html>
