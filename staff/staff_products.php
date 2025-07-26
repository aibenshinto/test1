<?php
require_once '../session_manager.php';
include '../db_connect.php';

// Require product manager role to access this page
requireProductManager();

// Optional: Check session timeout (30 minutes)
checkSessionTimeout(30);

$staff_id = getCurrentUserId();

$message = '';
$error = '';

// Handle product deletion
if (isset($_GET['delete']) && $_GET['delete']) {
    $item_id_to_delete = $_GET['delete'];
    
    // Get item image before deletion
    $stmt = $conn->prepare("SELECT Item_image FROM tbl_item WHERE Item_id = ?");
    $stmt->bind_param("s", $item_id_to_delete);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        
        // Delete the item
        $delete_stmt = $conn->prepare("DELETE FROM tbl_item WHERE Item_id = ?");
        $delete_stmt->bind_param("s", $item_id_to_delete);
        
        if ($delete_stmt->execute()) {
            // Delete the image file if it exists
            if ($item['Item_image'] && file_exists('../' . $item['Item_image'])) {
                unlink('../' . $item['Item_image']);
            }
            $message = "Item deleted successfully!";
        } else {
            $error = "Failed to delete item.";
        }
    }
}

// Fetch all items
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'lowest_price';
$orderBy = 'Item_rate ASC';
if ($filter === 'highest_price') {
    $orderBy = 'Item_rate DESC';
} elseif ($filter === 'lowest_rating') {
    $orderBy = 'Item_rating ASC';
} elseif ($filter === 'highest_rating') {
    $orderBy = 'Item_rating DESC';
}
$sql = "SELECT i.*, c.cat_name FROM tbl_item i LEFT JOIN tbl_category c ON i.Cat_id = c.cat_id ORDER BY $orderBy, i.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product Management</title>
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
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .section h2 {
      color: #2d89e6;
      margin-bottom: 15px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #2d89e6;
      color: white;
    }

    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin: 2px;
      text-decoration: none;
      display: inline-block;
    }

    .btn-primary { background: #2d89e6; color: white; }
    .btn-success { background: #27ae60; color: white; }
    .btn-warning { background: #f39c12; color: white; }
    .btn-danger { background: #e74c3c; color: white; }

    .message {
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
    }

    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    .product-image {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 4px;
    }

    .add-product-btn {
      background: #27ae60;
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      margin-bottom: 20px;
      text-decoration: none;
      display: inline-block;
    }

    .add-product-btn:hover {
      background: #229954;
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
      <h2>Product Management</h2>
      <p>Manage your product catalog and inventory.</p>

      <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <!-- Filter Bar -->
      <div class="filter-bar" style="margin-bottom: 18px;">
        <form method="get" style="display: flex; align-items: center; gap: 18px;">
          <label><input type="radio" name="filter" value="lowest_price" <?php if (!isset($_GET['filter']) || $_GET['filter']==='lowest_price') echo 'checked'; ?>> Lowest Price</label>
          <label><input type="radio" name="filter" value="highest_price" <?php if (isset($_GET['filter']) && $_GET['filter']==='highest_price') echo 'checked'; ?>> Highest Price</label>
          <label><input type="radio" name="filter" value="lowest_rating" <?php if (isset($_GET['filter']) && $_GET['filter']==='lowest_rating') echo 'checked'; ?>> Lowest Rating</label>
          <label><input type="radio" name="filter" value="highest_rating" <?php if (isset($_GET['filter']) && $_GET['filter']==='highest_rating') echo 'checked'; ?>> Highest Rating</label>
          <button type="submit" style="margin-left: 18px;">Apply</button>
        </form>
      </div>

      <div class="section">
        <a href="add_product.php" class="add-product-btn">+ Add New Product</a>

        <h3>All Products</h3>
        <?php if ($result->num_rows > 0): ?>
          <table>
            <thead>
              <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Price (₹)</th>
                <th>Rating</th>
                <th>Stock</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($item = $result->fetch_assoc()): ?>
                <tr>
                  <td>
                    <?php if ($item['Item_image']): ?>
                      <img src="<?php echo htmlspecialchars('../' . $item['Item_image']); ?>" alt="Item Image" class="product-image">
                    <?php else: ?>
                      <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999;">
                        No Image
                      </div>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($item['Item_name']); ?></td>
                  <td><?php echo htmlspecialchars($item['cat_name'] ?? 'N/A'); ?></td>
                  <td><?php echo htmlspecialchars($item['Item_brand']); ?></td>
                  <td><?php echo htmlspecialchars($item['Item_model']); ?></td>
                  <td>₹<?php echo number_format($item['Item_rate'], 2); ?></td>
                  <td style="color: #f39c12; font-weight: bold;">
                    <?php 
                        $rating = intval($item['Item_rating']);
                        for ($i = 0; $i < $rating; $i++) echo '★';
                        for ($i = 0; $i < 5 - $rating; $i++) echo '✩';
                    ?>
                  </td>
                  <td><?php echo $item['Item_qty']; ?></td>
                  <td>
                    <a href="edit_product.php?id=<?php echo $item['Item_id']; ?>" class="btn btn-warning">Edit</a>
                    <a href="?delete=<?php echo $item['Item_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No products found. <a href="add_product.php">Add your first product</a></p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html> 