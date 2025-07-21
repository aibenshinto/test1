<?php
require_once '../session_manager.php';
include '../db_connect.php';

// Optional: Check session timeout (30 minutes)
checkSessionTimeout(30);

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Validation
    if (!$name || !$email || !$password || !$confirm_password || !$role) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!in_array($role, ['product_manager', 'delivery'])) {
        $error = "Invalid role selected.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM staff WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            // Hash password and insert new staff member
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO staff (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $message = "Staff member registered successfully!";
                // Clear form data
                $_POST = array();
            } else {
                $error = "Error registering staff member: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Staff</title>
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
    .form-group select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      font-family: inherit;
    }

    .form-group select {
      background: white;
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

    .role-info {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 6px;
      margin-bottom: 20px;
      border-left: 4px solid #2d89e6;
    }

    .role-info h4 {
      margin: 0 0 10px 0;
      color: #2d89e6;
    }

    .role-info ul {
      margin: 0;
      padding-left: 20px;
    }

    .role-info li {
      margin: 5px 0;
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2>Staff Registration</h2>
      <p>Register new staff members</p>
      <ul>
        <li><a href="../authentication/login.php">Staff Login</a></li>
        <li><a href="../customer/login_customer.php">Customer Login</a></li>
        <li><a href="../customer/register_customer.php">Customer Registration</a></li>
      </ul>
    </aside>

    <main class="main-content">
      <h2>Register New Staff Member</h2>
      <p>Create a new staff account with appropriate role.</p>

      <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <div class="section">
        <div class="role-info">
          <h4>Available Roles:</h4>
          <ul>
            <li><strong>Product Manager:</strong> Manage products, inventory, and customer Q&A</li>
            <li><strong>Delivery Staff:</strong> Handle order delivery and pickup management</li>
          </ul>
        </div>

        <form method="post">
          <div class="form-group">
            <label for="name">Full Name *</label>
            <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
          </div>

          <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
          </div>

          <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required>
            <small style="color: #666;">Minimum 6 characters</small>
          </div>

          <div class="form-group">
            <label for="confirm_password">Confirm Password *</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
          </div>

          <div class="form-group">
            <label for="role">Role *</label>
            <select id="role" name="role" required>
              <option value="">Select a role</option>
              <option value="product_manager" <?php echo (isset($_POST['role']) && $_POST['role'] === 'product_manager') ? 'selected' : ''; ?>>Product Manager</option>
              <option value="delivery" <?php echo (isset($_POST['role']) && $_POST['role'] === 'delivery') ? 'selected' : ''; ?>>Delivery Staff</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary">Register Staff Member</button>
          <a href="../authentication/login.php" class="btn btn-success" style="margin-left: 10px;">Back to Login</a>
        </form>
      </div>
    </main>
  </div>
</body>
</html> 