<?php
require_once '../session_manager.php';
include '../db_connect.php';

requireAdmin();

$message = '';
$error = '';
$edit_category = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new category
    if (isset($_POST['add_category'])) {
        $cat_name = trim($_POST['cat_name']);
        if (!empty($cat_name)) {
            $stmt = $conn->prepare("INSERT INTO categories (cat_name) VALUES (?)");
            $stmt->bind_param("s", $cat_name);
            if ($stmt->execute()) {
                $message = "Category added successfully!";
            } else {
                $error = "Failed to add category. It might already exist.";
            }
        } else {
            $error = "Category name cannot be empty.";
        }
    }

    // Update existing category
    if (isset($_POST['update_category'])) {
        $cat_id = intval($_POST['cat_id']);
        $cat_name = trim($_POST['cat_name']);
        if (!empty($cat_name) && $cat_id > 0) {
            $stmt = $conn->prepare("UPDATE categories SET cat_name = ? WHERE cat_id = ?");
            $stmt->bind_param("si", $cat_name, $cat_id);
            if ($stmt->execute()) {
                $message = "Category updated successfully!";
            } else {
                $error = "Failed to update category.";
            }
        } else {
            $error = "Invalid data for category update.";
        }
    }
}

// Handle category deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $cat_id_to_delete = intval($_GET['delete']);
    $delete_stmt = $conn->prepare("DELETE FROM categories WHERE cat_id = ?");
    $delete_stmt->bind_param("i", $cat_id_to_delete);
    if ($delete_stmt->execute()) {
        $message = "Category deleted successfully!";
    } else {
        $error = "Failed to delete category. It might be in use by some products.";
    }
}

// Check if we are in edit mode
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $cat_id_to_edit = intval($_GET['edit']);
    $edit_stmt = $conn->prepare("SELECT * FROM categories WHERE cat_id = ?");
    $edit_stmt->bind_param("i", $cat_id_to_edit);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    $edit_category = $edit_result->fetch_assoc();
}

// Fetch all categories
$sql = "SELECT * FROM categories ORDER BY cat_name";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin - Category Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/admin_dashboard.css">
  <link rel="stylesheet" href="../css/admin_forms.css">
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2>Admin Panel</h2>
      <ul>
        <li><a href="staff_management.php">Manage Staff</a></li>
        <li><a href="category_management.php">Manage Categories</a></li>
        <li><a class="logout-link" href="../authentication/logout.php">Logout</a></li>
      </ul>
    </aside>

    <main class="main-content">
      <h2>Category Management</h2>
      <p>Add, edit, or remove product categories.</p>

      <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <!-- Add/Edit Form -->
      <div class="section">
        <h3><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h3>
        <form method="post" action="category_management.php">
          <?php if ($edit_category): ?>
            <input type="hidden" name="cat_id" value="<?php echo $edit_category['cat_id']; ?>">
          <?php endif; ?>
          <div class="form-group" style="display:flex; align-items:flex-end; gap:15px;">
            <div style="flex-grow:1;">
              <label for="cat_name">Category Name *</label>
              <input type="text" id="cat_name" name="cat_name" value="<?php echo $edit_category ? htmlspecialchars($edit_category['cat_name']) : ''; ?>" required>
            </div>
            <?php if ($edit_category): ?>
              <button type="submit" name="update_category" class="btn btn-primary">Update</button>
              <a href="category_management.php" class="btn btn-secondary">Cancel Edit</a>
            <?php else: ?>
              <button type="submit" name="add_category" class="btn btn-primary">Add</button>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Category List -->
      <div class="section">
        <h3>All Categories</h3>
        <?php if ($result->num_rows > 0): ?>
          <table>
            <thead>
              <tr>
                <th>Category Name</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($category = $result->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($category['cat_name']); ?></td>
                  <td>
                    <a href="?edit=<?php echo $category['cat_id']; ?>" class="btn btn-warning">Edit</a>
                    <a href="?delete=<?php echo $category['cat_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No categories found.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html> 