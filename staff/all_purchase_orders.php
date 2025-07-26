<?php
require_once '../session_manager.php';
include '../db_connect.php';

requireProductManager();

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

// Handle purchase order deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_purchase_order'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $p_mid_to_delete = trim($_POST['p_mid']);
        
        // Deleting the master record will cascade and delete child records due to schema design
        $delete_stmt = $conn->prepare("DELETE FROM tb1_purchase_master WHERE P_mid = ?");
        $delete_stmt->bind_param("s", $p_mid_to_delete);
        
        if ($delete_stmt->execute()) {
            $message = "Purchase order #$p_mid_to_delete deleted successfully!";
            header("Location: all_purchase_orders.php?message=" . urlencode($message));
            exit;
        } else {
            $error = "Failed to delete purchase order: " . $conn->error;
        }
        $delete_stmt->close();
    }
}

// Fetch all purchase orders
$purchases = $conn->query("
    SELECT pm.P_mid, pm.P_date, pm.Total_amt, v.vendor_name 
    FROM tb1_purchase_master pm 
    JOIN tbl_vendor v ON pm.Vendor_id = v.vendor_id 
    ORDER BY pm.P_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All Purchase Orders</title>
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
      <h2>All Purchase Orders</h2>

      <?php if ($message || (isset($_GET['message']) && $_GET['message'])): ?>
        <div class="message success"><?php echo htmlspecialchars($message ?: $_GET['message']); ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <div class="section">
        <table>
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Vendor</th>
              <th>Date</th>
              <th>Total Amount (₹)</th>
              <th>Items</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($purchase = $purchases->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($purchase['P_mid']); ?></td>
                <td><?php echo htmlspecialchars($purchase['vendor_name']); ?></td>
                <td><?php echo date('M d, Y', strtotime($purchase['P_date'])); ?></td>
                <td>₹<?php echo number_format($purchase['Total_amt'], 2); ?></td>
                <td>
                    <?php
                    $child_stmt = $conn->prepare("SELECT i.Item_name, pc.P_qty, pc.P_rate FROM tbl_purchase_child pc JOIN tbl_item i ON pc.item_id = i.Item_id WHERE pc.P_mid = ?");
                    $child_stmt->bind_param("s", $purchase['P_mid']);
                    $child_stmt->execute();
                    $child_result = $child_stmt->get_result();
                    if ($child_result->num_rows > 0) {
                        echo "<ul>";
                        while ($item = $child_result->fetch_assoc()) {
                            echo "<li>" . htmlspecialchars($item['Item_name']) . " (" . $item['P_qty'] . " x ₹" . number_format($item['P_rate'], 2) . ")</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "No items yet.";
                    }
                    $child_stmt->close();
                    $child_result->close();
                    ?>
                </td>
                <td>
                    <a href="purchase_management.php?edit_order=<?php echo urlencode($purchase['P_mid']); ?>" class="btn btn-warning">Edit</a>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="p_mid" value="<?php echo htmlspecialchars($purchase['P_mid']); ?>">
                        <button type="submit" name="delete_purchase_order" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this entire purchase order?')">Delete</button>
                    </form>
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
<?php
$purchases->close();
$conn->close();
?>