<?php
require_once '../session_manager.php';
include '../db_connect.php';

requireAdmin();

$message = '';
$error = '';
$tbl_staff = null;

// Get staff ID from URL
$staff_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($staff_id)) {
    header("Location: staff_management.php");
    exit;
}

// Fetch staff details
$stmt = $conn->prepare("SELECT * FROM tbl_staff WHERE Staff_id = ?");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$tbl_staff = $result->fetch_assoc();

if (!$tbl_staff) {
    header("Location: staff_management.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    if (empty($fname) || empty($lname) || empty($email) || empty($role)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($tbl_staff['Staff_id'] === getCurrentUserId() && $role !== 'admin') {
        $error = "You cannot change your own role from Admin.";
    } else {
        // Check if email already exists for another user
        $check_email_stmt = $conn->prepare("SELECT Staff_id FROM tbl_staff WHERE Staff_email = ? AND Staff_id != ?");
        $check_email_stmt->bind_param("ss", $email, $staff_id);
        $check_email_stmt->execute();
        $check_email_stmt->store_result();

        if ($check_email_stmt->num_rows > 0) {
            $error = "Another staff member is already using this email address.";
        } else {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE tbl_staff SET Staff_fname = ?, Staff_lname = ?, Staff_email = ?, role = ?, Password = ? WHERE Staff_id = ?");
                $update_stmt->bind_param("ssssss", $fname, $lname, $email, $role, $hashed_password, $staff_id);
            } else {
                $update_stmt = $conn->prepare("UPDATE tbl_staff SET Staff_fname = ?, Staff_lname = ?, Staff_email = ?, role = ? WHERE Staff_id = ?");
                $update_stmt->bind_param("sssss", $fname, $lname, $email, $role, $staff_id);
            }

            if ($update_stmt->execute()) {
                $message = "Staff member updated successfully!";
                // Refresh data
                $stmt->execute();
                $tbl_staff = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "Error updating staff member: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin - Edit Staff</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/admin_forms.css">
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2>Admin Panel</h2>
      <ul>
        <li><a href="staff_management.php">Manage Staff</a></li>
        <li><a class="logout-link" href="../authentication/logout.php">Logout</a></li>
      </ul>
    </aside>

    <main class="main-content">
      <h2>Edit Staff Member</h2>
      <p>Update staff member details.</p>

      <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <div class="section">
        <form method="post">
          <div class="form-group">
            <label for="fname">First Name *</label>
            <input type="text" id="fname" name="fname" value="<?php echo htmlspecialchars($tbl_staff['Staff_fname']); ?>" required>
          </div>
          <div class="form-group">
            <label for="lname">Last Name *</label>
            <input type="text" id="lname" name="lname" value="<?php echo htmlspecialchars($tbl_staff['Staff_lname']); ?>" required>
          </div>
          <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($tbl_staff['Staff_email']); ?>" required>
          </div>
          <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password">
            <small>Leave blank to keep the current password.</small>
          </div>
          <div class="form-group">
            <label for="role">Role *</label>
            <select id="role" name="role" required>
              <option value="delivery" <?php if ($tbl_staff['role'] === 'delivery') echo 'selected'; ?>>Delivery</option>
              <option value="product_manager" <?php if ($tbl_staff['role'] === 'product_manager') echo 'selected'; ?>>Product Manager</option>
              <option value="admin" <?php if ($tbl_staff['role'] === 'admin') echo 'selected'; ?>>Admin</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Update Staff</button>
          <a href="staff_management.php" class="btn btn-secondary" style="margin-left: 10px;">Back to Management</a>
        </form>
      </div>
    </main>
  </div>
</body>
</html> 