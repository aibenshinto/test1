<?php
require_once '../session_manager.php';
include '../db_connect.php';

requireAdmin();

$message = '';
$error = '';

// Handle staff deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $staff_id_to_delete = intval($_GET['delete']);
    
    // Prevent admin from deleting themselves
    if ($staff_id_to_delete === getCurrentUserId()) {
        $error = "You cannot delete your own account.";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM staff WHERE id = ?");
        $delete_stmt->bind_param("i", $staff_id_to_delete);
        
        if ($delete_stmt->execute()) {
            $message = "Staff member deleted successfully!";
        } else {
            $error = "Failed to delete staff member.";
        }
    }
}

// Fetch all staff members
$sql = "SELECT * FROM staff ORDER BY role, name";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin - Staff Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/admin_dashboard.css">
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2>Admin Panel</h2>
      <p>Hello, <?= htmlspecialchars(getCurrentUsername()) ?> <span class="role-badge">Admin</span></p>
      <ul>
        <li><a href="staff_management.php">Manage Staff</a></li>
        <li><a href="category_management.php">Manage Categories</a></li>
        <li><a class="logout-link" href="../authentication/logout.php">Logout</a></li>
      </ul>
    </aside>

    <main class="main-content">
      <h2>Staff Management</h2>
      <p>Add, edit, or remove staff members.</p>

      <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <div class="section">
        <a href="add_staff.php" class="add-product-btn">+ Add New Staff</a>

        <h3>All Staff Members</h3>
        <?php if ($result->num_rows > 0): ?>
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created At</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($staff = $result->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($staff['name']); ?></td>
                  <td><?php echo htmlspecialchars($staff['email']); ?></td>
                  <td><span class="role-badge-table <?php echo htmlspecialchars($staff['role']); ?>"><?php echo ucfirst(str_replace('_', ' ', $staff['role'])); ?></span></td>
                  <td><?php echo date('M d, Y', strtotime($staff['created_at'])); ?></td>
                  <td>
                    <a href="edit_staff.php?id=<?php echo $staff['id']; ?>" class="btn btn-warning">Edit</a>
                    <?php if ($staff['id'] !== getCurrentUserId()): // Prevent self-deletion button ?>
                      <a href="?delete=<?php echo $staff['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this staff member?')">Delete</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No staff members found.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html> 