<?php
require_once '../session_manager.php';
include '../db_connect.php';

// Require product manager role to access this page
requireProductManager();

// Optional: Check session timeout (30 minutes)
checkSessionTimeout(30);

$message = '';
$error = '';
$product = null;

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header("Location: staff_products.php");
    exit;
}

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: staff_products.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    
    if (!$name || !$description || $price <= 0 || $stock < 0) {
        $error = "Please fill in all fields correctly.";
    } else {
        $image_path = $product['image']; // Keep existing image by default
        
        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
                $upload_dir = '../uploads/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid() . '.' . $file_extension;
                $new_image_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $new_image_path)) {
                    // Delete old image if it exists
                    if ($product['image'] && file_exists('../' . $product['image'])) {
                        unlink('../' . $product['image']);
                    }
                    $image_path = 'uploads/products/' . $file_name;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid image format or size. Please use JPEG, PNG, or GIF under 5MB.";
            }
        }
        
        if (!$error) {
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?");
            $stmt->bind_param("ssdisi", $name, $description, $price, $stock, $image_path, $product_id);
            
            if ($stmt->execute()) {
                $message = "Product updated successfully!";
                // Update the product array with new values
                $product['name'] = $name;
                $product['description'] = $description;
                $product['price'] = $price;
                $product['stock'] = $stock;
                $product['image'] = $image_path;
            } else {
                $error = "Error updating product: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Product</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      height: 100vh;
      background: linear-gradient(135deg, #43cea2, #185a9d);
      color: #333;
    }

    .dashboard {
      display: flex;
      width: 100%;
    }

    .sidebar {
      background: white;
      padding: 20px;
      width: 250px;
      box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }

    .sidebar h2 {
      margin-bottom: 10px;
      color: #185a9d;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
    }

    .sidebar li {
      margin: 10px 0;
    }

    .sidebar button, .sidebar a {
      background: #185a9d;
      color: white;
      padding: 10px 15px;
      border: none;
      border-radius: 8px;
      text-decoration: none;
      cursor: pointer;
      width: 100%;
      text-align: left;
      transition: background 0.3s ease;
      display: block;
    }

    .sidebar button:hover, .sidebar a:hover {
      background: #0b3d72;
    }

    .main-content {
      flex: 1;
      padding: 40px;
      background: rgba(255,255,255,0.95);
      overflow-y: auto;
    }

    .logout-link {
      margin-top: 20px;
      display: block;
      color: red;
    }

    .role-badge {
      background: #e8f4fd;
      color: #185a9d;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: bold;
      margin-left: 10px;
    }

    .section {
      background: white;
      padding: 30px;
      border-radius: 8px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      max-width: 600px;
    }

    .section h2 {
      color: #2d89e6;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #333;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      font-family: inherit;
    }

    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }

    .form-group input[type="file"] {
      padding: 8px;
      border: 2px dashed #ddd;
      background: #f9f9f9;
    }

    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      text-decoration: none;
      display: inline-block;
    }

    .btn-primary { background: #2d89e6; color: white; }
    .btn-success { background: #27ae60; color: white; }
    .btn-warning { background: #f39c12; color: white; }

    .btn:hover {
      opacity: 0.9;
    }

    .message {
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
    }

    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    .file-info {
      font-size: 12px;
      color: #666;
      margin-top: 5px;
    }

    .current-image {
      margin: 10px 0;
      padding: 10px;
      background: #f9f9f9;
      border-radius: 4px;
    }

    .current-image img {
      max-width: 200px;
      max-height: 150px;
      border-radius: 4px;
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2>Product Manager Panel</h2>
      <p>Hello, <?= htmlspecialchars(getCurrentUsername()) ?> <span class="role-badge">Product Manager</span></p>
      <ul>
        <li><a href="staff_dashboard.php">Staff Dashboard</a></li>
        <li><a href="staff_products.php">Manage Products</a></li>
        <li><a href="add_product.php">Add Product</a></li>
        <li><a href="staff_qna.php">Customer Q&A</a></li>
        <li><a class="logout-link" href="../authentication/logout.php">Logout</a></li>
      </ul>
    </aside>

    <main class="main-content">
      <h2>Edit Product</h2>
      <p>Update product information and details.</p>

      <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <div class="section">
        <form method="post" enctype="multipart/form-data">
          <div class="form-group">
            <label for="name">Product Name *</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
          </div>

          <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
          </div>

          <div class="form-group">
            <label for="price">Price (₹) *</label>
            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
          </div>

          <div class="form-group">
            <label for="stock">Stock Quantity *</label>
            <input type="number" id="stock" name="stock" min="0" value="<?php echo $product['stock']; ?>" required>
          </div>

          <div class="form-group">
            <label for="image">Product Image</label>
            <?php if ($product['image']): ?>
              <div class="current-image">
                <strong>Current Image:</strong><br>
                <img src="<?php echo htmlspecialchars('../' . $product['image']); ?>" alt="Current Product Image">
              </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/*">
            <div class="file-info">Accepted formats: JPEG, PNG, GIF. Max size: 5MB. Leave empty to keep current image.</div>
          </div>

          <button type="submit" class="btn btn-primary">Update Product</button>
          <a href="staff_products.php" class="btn btn-success" style="margin-left: 10px;">Back to Products</a>
        </form>
      </div>
    </main>
  </div>
</body>
</html> 