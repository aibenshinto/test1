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
    $item_name = trim($_POST['item_name']);
    $item_desc = trim($_POST['item_desc']);
    $item_brand = trim($_POST['item_brand']);
    $item_model = trim($_POST['item_model']);
    $item_rate = floatval($_POST['item_rate']);
    $item_quality = trim($_POST['item_quality']);
    $item_qty = intval($_POST['item_qty']);
    $item_rating = intval($_POST['item_rating']);
    $category_id = intval($_POST['category_id']);
    
    if (!$item_name || !$item_desc || !$item_brand || !$item_model || $item_rate <= 0 || !$item_quality || $item_qty < 0 || $item_rating < 0 || $item_rating > 5 || $category_id <= 0) {
        $error = "Please fill in all fields correctly.";
    } else {
        $item_image_path = $product['Item_image']; // Keep existing image by default
        
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
                    if ($product['Item_image'] && file_exists('../' . $product['Item_image'])) {
                        unlink('../' . $product['Item_image']);
                    }
                    $item_image_path = 'uploads/products/' . $file_name;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid image format or size. Please use JPEG, PNG, or GIF under 5MB.";
            }
        }
        
        if (!$error) {
            $stmt = $conn->prepare("UPDATE tbl_item SET Cat_id=?, Item_name=?, Item_desc=?, Item_brand=?, Item_model=?, Item_rate=?, Item_quality=?, Item_qty=?, Item_image=?, Item_rating=? WHERE Item_id = ?");
            $stmt->bind_param("isssssisiss", $category_id, $item_name, $item_desc, $item_brand, $item_model, $item_rate, $item_quality, $item_qty, $item_image_path, $item_rating, $product_id);
            
            if ($stmt->execute()) {
                $message = "Item updated successfully!";
                // Update the product array with new values
                $product['Item_name'] = $item_name;
                $product['Item_desc'] = $item_desc;
                $product['Item_brand'] = $item_brand;
                $product['Item_model'] = $item_model;
                $product['Item_rate'] = $item_rate;
                $product['Item_quality'] = $item_quality;
                $product['Item_qty'] = $item_qty;
                $product['Item_image'] = $item_image_path;
                $product['Item_rating'] = $item_rating;
                $product['Cat_id'] = $category_id;
            } else {
                $error = "Error updating item: " . $conn->error;
            }
        }
    }
}

// Fetch categories for the dropdown
$categories_result = $conn->query("SELECT * FROM categories ORDER BY cat_name");
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
        <li><a href="vendor_management.php">Manage Vendors</a></li>
        <li><a href="purchase_management.php">Manage Purchases</a></li>
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
            <label for="item_name">Item Name *</label>
            <input type="text" id="item_name" name="item_name" value="<?php echo htmlspecialchars($product['Item_name']); ?>" required>
          </div>

          <div class="form-group">
            <label for="item_desc">Description *</label>
            <textarea id="item_desc" name="item_desc" required><?php echo htmlspecialchars($product['Item_desc']); ?></textarea>
          </div>

          <div class="form-group">
            <label for="item_brand">Brand *</label>
            <input type="text" id="item_brand" name="item_brand" value="<?php echo htmlspecialchars($product['Item_brand']); ?>" required>
          </div>

          <div class="form-group">
            <label for="item_model">Model *</label>
            <input type="text" id="item_model" name="item_model" value="<?php echo htmlspecialchars($product['Item_model']); ?>" required>
          </div>

          <div class="form-group">
            <label for="item_rate">Rate (₹) *</label>
            <input type="number" id="item_rate" name="item_rate" step="0.01" min="0" value="<?php echo $product['Item_rate']; ?>" required>
          </div>

          <div class="form-group">
            <label for="item_quality">Quality *</label>
            <input type="text" id="item_quality" name="item_quality" value="<?php echo htmlspecialchars($product['Item_quality']); ?>" required>
          </div>

          <div class="form-group">
            <label for="item_qty">Quantity *</label>
            <input type="number" id="item_qty" name="item_qty" min="0" value="<?php echo $product['Item_qty']; ?>" required>
          </div>

          <div class="form-group">
            <label for="item_rating">Rating (0-5) *</label>
            <input type="number" id="item_rating" name="item_rating" min="0" max="5" value="<?php echo $product['Item_rating']; ?>" required>
          </div>

          <div class="form-group">
            <label for="category_id">Category *</label>
            <select id="category_id" name="category_id" required>
              <option value="">Select a category...</option>
              <?php while ($category = $categories_result->fetch_assoc()): ?>
                <option value="<?php echo $category['cat_id']; ?>" <?php echo ($product['Cat_id'] == $category['cat_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($category['cat_name']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="image">Product Image</label>
            <?php if ($product['Item_image']): ?>
              <div class="current-image">
                <strong>Current Image:</strong><br>
                <img src="<?php echo htmlspecialchars('../' . $product['Item_image']); ?>" alt="Current Product Image">
              </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/*">
            <div class="file-info">Accepted formats: JPEG, PNG, GIF. Max size: 5MB. Leave empty to keep current image.</div>
          </div>

          <button type="submit" class="btn btn-primary">Update Item</button>
          <a href="staff_products.php" class="btn btn-success" style="margin-left: 10px;">Back to Items</a>
        </form>
      </div>
    </main>
  </div>
</body>
</html> 