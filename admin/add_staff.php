<?php
require_once '../session_manager.php';
include '../db_connect.php';

requireAdmin();

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!in_array($role, ['delivery', 'product_manager', 'admin'])) {
        $error = "Invalid role selected.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT Staff_id FROM tbl_staff WHERE Staff_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "A staff member with this email already exists.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Update insert to use all required fields
            $insert_stmt = $conn->prepare("INSERT INTO tbl_staff (Staff_id, Staff_fname, Staff_lname, Staff_street, Staff_city, Staff_age, Staff_gender, Staff_ph, Staff_email, Staff_DOJ, Username, Password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $staff_id = 'STF' . bin2hex(random_bytes(3));
            $staff_fname = $name; // You may want to split name into fname/lname
            $staff_lname = '';
            $staff_street = '';
            $staff_city = '';
            $staff_age = 0;
            $staff_gender = '';
            $staff_ph = '';
            $staff_doj = date('Y-m-d');
            $username = $email;
            $insert_stmt->bind_param("sssssisisssss", $staff_id, $staff_fname, $staff_lname, $staff_street, $staff_city, $staff_age, $staff_gender, $staff_ph, $email, $staff_doj, $username, $hashed_password, $role);

            if ($insert_stmt->execute()) {
                $message = "Staff member added successfully!";
            } else {
                $error = "Error adding staff member: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin - Add Staff</title>
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
      <h2>Add New Staff Member</h2>
      <p>Create a new account for a staff member.</p>

      <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <div class="section">
        <form method="post">
          <div class="form-group">
            <label for="name">Full Name *</label>
            <input type="text" id="name" name="name" required>
          </div>
          <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" required>
          </div>
          <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required>
          </div>
          <div class="form-group">
            <label for="role">Role *</label>
            <select id="role" name="role" required>
              <option value="">Select a role...</option>
              <option value="delivery">Delivery</option>
              <option value="product_manager">Product Manager</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Add Staff</button>
          <a href="staff_management.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
        </form>
      </div>
    </main>
  </div>
</body>
</html> 