<?php
require_once '../session_manager.php';
include '../db_connect.php';

requireProductManager();

$message = '';
$error = '';
$edit_vendor = null;

// Handle add vendor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vendor'])) {
    // Generate unique vendor_id
    function generateVendorId($conn) {
        do {
            $id = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $check = $conn->prepare("SELECT vendor_id FROM vendors WHERE vendor_id = ?");
            $check->bind_param("s", $id);
            $check->execute();
            $check->store_result();
        } while ($check->num_rows > 0);
        return $id;
    }
    $vendor_id = generateVendorId($conn);
    $vendor_name = trim($_POST['vendor_name']);
    $vendor_phone = trim($_POST['vendor_phone']);
    $vendor_mail = trim($_POST['vendor_mail']);
    $vendor_city = trim($_POST['vendor_city']);
    $vendor_state = trim($_POST['vendor_state']);
    $staff_id = getCurrentUserId();

    if (!$vendor_name || !$vendor_phone || !$vendor_mail || !$vendor_city || !$vendor_state) {
        $error = "Please fill in all fields.";
    } elseif (!preg_match('/^[0-9]{10}$/', $vendor_phone)) {
        $error = "Vendor phone must be 10 digits.";
    } elseif (!filter_var($vendor_mail, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("INSERT INTO vendors (vendor_id, staff_id, vendor_name, vendor_phone, vendor_mail, vendor_city, vendor_state) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssss", $vendor_id, $staff_id, $vendor_name, $vendor_phone, $vendor_mail, $vendor_city, $vendor_state);
        if ($stmt->execute()) {
            $message = "Vendor added successfully! Vendor ID: $vendor_id";
        } else {
            $error = "Failed to add vendor. Please try again.";
        }
    }
}

// Handle edit vendor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vendor'])) {
    $vendor_id = strtoupper(trim($_POST['vendor_id']));
    $vendor_name = trim($_POST['vendor_name']);
    $vendor_phone = trim($_POST['vendor_phone']);
    $vendor_mail = trim($_POST['vendor_mail']);
    $vendor_city = trim($_POST['vendor_city']);
    $vendor_state = trim($_POST['vendor_state']);
    $staff_id = getCurrentUserId();

    if (!$vendor_id || !$vendor_name || !$vendor_phone || !$vendor_mail || !$vendor_city || !$vendor_state) {
        $error = "Please fill in all fields.";
    } elseif (!preg_match('/^[A-Z0-9]{6}$/', $vendor_id)) {
        $error = "Vendor ID must be 6 uppercase letters or digits.";
    } elseif (!preg_match('/^[0-9]{10}$/', $vendor_phone)) {
        $error = "Vendor phone must be 10 digits.";
    } elseif (!filter_var($vendor_mail, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("UPDATE vendors SET staff_id=?, vendor_name=?, vendor_phone=?, vendor_mail=?, vendor_city=?, vendor_state=? WHERE vendor_id=?");
        $stmt->bind_param("issssss", $staff_id, $vendor_name, $vendor_phone, $vendor_mail, $vendor_city, $vendor_state, $vendor_id);
        if ($stmt->execute()) {
            $message = "Vendor updated successfully!";
        } else {
            $error = "Failed to update vendor.";
        }
    }
}

// Handle delete vendor
if (isset($_GET['delete']) && $_GET['delete']) {
    $vendor_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM vendors WHERE vendor_id = ?");
    $stmt->bind_param("s", $vendor_id);
    if ($stmt->execute()) {
        $message = "Vendor deleted successfully!";
    } else {
        $error = "Failed to delete vendor.";
    }
}

// Handle edit mode
if (isset($_GET['edit']) && $_GET['edit']) {
    $vendor_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM vendors WHERE vendor_id = ?");
    $stmt->bind_param("s", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_vendor = $result->fetch_assoc();
}

// Fetch all vendors
$vendors = $conn->query("SELECT v.*, s.name as staff_name FROM vendors v LEFT JOIN staff s ON v.staff_id = s.id ORDER BY v.vendor_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Vendor Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/admin_dashboard.css">
  <link rel="stylesheet" href="../css/admin_forms.css">
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2>Staff Panel</h2>
      <ul>
        <li><a href="staff_dashboard.php">Staff Dashboard</a></li>
        <li><a href="vendor_management.php">Manage Vendors</a></li>
        <li><a href="purchase_management.php">Manage Purchases</a></li>
        <li><a href="all_purchase_orders.php">All Purchase Orders</a></li>
        <li><a class="logout-link" href="../authentication/logout.php">Logout</a></li>
      </ul>
    </aside>
    <main class="main-content">
      <h2>Vendor Management</h2>
      <p>Add, edit, or remove vendors.</p>
      <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <div class="section">
        <h3><?php echo $edit_vendor ? 'Edit Vendor' : 'Add New Vendor'; ?></h3>
        <form method="post" action="vendor_management.php<?php echo $edit_vendor ? '?edit=' . urlencode($edit_vendor['vendor_id']) : ''; ?>">
          <?php if ($edit_vendor): ?>
            <div class="form-group">
              <label for="vendor_id">Vendor ID</label>
              <input type="text" id="vendor_id" name="vendor_id" maxlength="6" value="<?php echo htmlspecialchars($edit_vendor['vendor_id']); ?>" readonly>
            </div>
          <?php endif; ?>
          <div class="form-group">
            <label for="vendor_name">Vendor Name *</label>
            <input type="text" id="vendor_name" name="vendor_name" maxlength="20" value="<?php echo $edit_vendor ? htmlspecialchars($edit_vendor['vendor_name']) : ''; ?>" required>
          </div>
          <div class="form-group">
            <label for="vendor_phone">Vendor Phone *</label>
            <input type="text" id="vendor_phone" name="vendor_phone" maxlength="10" value="<?php echo $edit_vendor ? htmlspecialchars($edit_vendor['vendor_phone']) : ''; ?>" required>
          </div>
          <div class="form-group">
            <label for="vendor_mail">Vendor Email *</label>
            <input type="email" id="vendor_mail" name="vendor_mail" maxlength="15" value="<?php echo $edit_vendor ? htmlspecialchars($edit_vendor['vendor_mail']) : ''; ?>" required>
          </div>
          <div class="form-group">
            <label for="vendor_city">Vendor City *</label>
            <input type="text" id="vendor_city" name="vendor_city" maxlength="10" value="<?php echo $edit_vendor ? htmlspecialchars($edit_vendor['vendor_city']) : ''; ?>" required>
          </div>
          <div class="form-group">
            <label for="vendor_state">Vendor State *</label>
            <input type="text" id="vendor_state" name="vendor_state" maxlength="10" value="<?php echo $edit_vendor ? htmlspecialchars($edit_vendor['vendor_state']) : ''; ?>" required>
          </div>
          <?php if ($edit_vendor): ?>
            <button type="submit" name="update_vendor" class="btn btn-primary">Update Vendor</button>
            <a href="vendor_management.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel Edit</a>
          <?php else: ?>
            <button type="submit" name="add_vendor" class="btn btn-primary">Add Vendor</button>
          <?php endif; ?>
        </form>
      </div>
      <div class="section">
        <h3>All Vendors</h3>
        <table>
          <thead>
            <tr>
              <th>Vendor ID</th>
              <th>Name</th>
              <th>Phone</th>
              <th>Email</th>
              <th>City</th>
              <th>State</th>
              <th>Created By (Staff)</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($vendor = $vendors->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($vendor['vendor_id']); ?></td>
                <td><?php echo htmlspecialchars($vendor['vendor_name']); ?></td>
                <td><?php echo htmlspecialchars($vendor['vendor_phone']); ?></td>
                <td><?php echo htmlspecialchars($vendor['vendor_mail']); ?></td>
                <td><?php echo htmlspecialchars($vendor['vendor_city']); ?></td>
                <td><?php echo htmlspecialchars($vendor['vendor_state']); ?></td>
                <td><?php echo htmlspecialchars($vendor['staff_name']); ?></td>
                <td>
                  <a href="vendor_management.php?edit=<?php echo urlencode($vendor['vendor_id']); ?>" class="btn btn-warning">Edit</a>
                  <a href="vendor_management.php?delete=<?php echo urlencode($vendor['vendor_id']); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this vendor?')">Delete</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html> 