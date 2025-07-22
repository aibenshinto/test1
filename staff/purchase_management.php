<?php
require_once '../session_manager.php';
include '../db_connect.php';

requireProductManager();

$message = '';
$error = '';

// Fetch vendors and items for dropdowns
$vendors_result = $conn->query("SELECT vendor_id, vendor_name FROM vendors ORDER BY vendor_name");
$products_result = $conn->query("SELECT Item_id, Item_name FROM tbl_item ORDER BY Item_name");

$edit_order_id = isset($_GET['edit_order']) ? $_GET['edit_order'] : null;
$current_order_items = [];

if ($edit_order_id) {
    $items_stmt = $conn->prepare("SELECT pc.P_cid, i.Item_name, pc.P_qty, pc.P_rate FROM tbl_purchase_child pc JOIN tbl_item i ON pc.item_id = i.Item_id WHERE pc.P_mid = ?");
    $items_stmt->bind_param("s", $edit_order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    while ($row = $items_result->fetch_assoc()) {
        $current_order_items[] = $row;
    }
}


// Handle Create Purchase Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_purchase_order'])) {
    $vendor_id = $_POST['vendor_id'];
    $p_date = date('Y-m-d');
    
    // Generate unique P_mid
    $p_mid = 'P' . substr(uniqid(), -5);

    if (!empty($vendor_id)) {
        // Start with total amount 0, it will be updated as items are added
        $stmt = $conn->prepare("INSERT INTO tb1_purchase_master (P_mid, Vendor_id, P_date, Total_amt) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $p_mid, $vendor_id, $p_date);
        if ($stmt->execute()) {
            $message = "Purchase order #$p_mid created successfully! You can now add items to it.";
        } else {
            $error = "Failed to create purchase order.";
        }
    } else {
        $error = "Please select a vendor.";
    }
}

// Handle Add Item to Purchase Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $p_mid = $_POST['p_mid'];
    $item_id = $_POST['item_id'];
    $p_qty = intval($_POST['p_qty']);
    $p_rate = floatval($_POST['p_rate']);

    // Generate unique P_cid
    $p_cid = 'C' . substr(uniqid(), -5);
    
    if (!empty($p_mid) && !empty($item_id) && $p_qty > 0 && $p_rate > 0) {
        $conn->begin_transaction();
        try {
            // Insert into child table
            $stmt = $conn->prepare("INSERT INTO tbl_purchase_child (P_cid, P_mid, item_id, P_qty, P_rate) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssid", $p_cid, $p_mid, $item_id, $p_qty, $p_rate);
            $stmt->execute();
            
            // Update total amount in master table
            $item_total = $p_qty * $p_rate;
            $update_stmt = $conn->prepare("UPDATE tb1_purchase_master SET Total_amt = Total_amt + ? WHERE P_mid = ?");
            $update_stmt->bind_param("ds", $item_total, $p_mid);
            $update_stmt->execute();
            
            $conn->commit();
            $message = "Item added to purchase order #$p_mid successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to add item. Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all item details correctly.";
    }
}

// Handle Remove Item from Purchase Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $p_cid_to_remove = $_POST['p_cid'];
    $p_mid_of_item = $_POST['p_mid'];
    $item_qty = intval($_POST['item_qty']);
    $item_rate = floatval($_POST['item_rate']);

    $conn->begin_transaction();
    try {
        // Delete from child table
        $stmt = $conn->prepare("DELETE FROM tbl_purchase_child WHERE P_cid = ?");
        $stmt->bind_param("s", $p_cid_to_remove);
        $stmt->execute();
        
        // Update total amount in master table
        $item_total_to_remove = $item_qty * $item_rate;
        $update_stmt = $conn->prepare("UPDATE tb1_purchase_master SET Total_amt = Total_amt - ? WHERE P_mid = ?");
        $update_stmt->bind_param("ds", $item_total_to_remove, $p_mid_of_item);
        $update_stmt->execute();
        
        $conn->commit();
        $message = "Item removed successfully!";
        // Refresh the page to show the updated item list
        header("Location: purchase_management.php?edit_order=" . urlencode($p_mid_of_item));
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to remove item. Error: " . $e->getMessage();
    }
}


// Fetch all purchase orders
$purchases = $conn->query("
    SELECT pm.P_mid, pm.P_date, pm.Total_amt, v.vendor_name 
    FROM tb1_purchase_master pm 
    JOIN vendors v ON pm.Vendor_id = v.vendor_id 
    ORDER BY pm.P_date DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Management</title>
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
        <h2>Purchase Management</h2>
        <p>Create purchase orders and add items from vendors.</p>
        
        <?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <!-- Create Purchase Order Section -->
        <div class="section">
            <h3>Create New Purchase Order</h3>
            <form method="post">
                <div class="form-group">
                    <label for="vendor_id">Select Vendor *</label>
                    <select id="vendor_id" name="vendor_id" required>
                        <option value="">Choose a vendor...</option>
                        <?php while ($vendor = $vendors_result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($vendor['vendor_id']) . "'>" . htmlspecialchars($vendor['vendor_name']) . "</option>";
                        } ?>
                    </select>
                </div>
                <button type="submit" name="create_purchase_order" class="btn btn-primary">Create Order</button>
            </form>
        </div>
        
        <!-- Add Item to Purchase Order Section -->
        <div class="section">
            <h3>Add Item to Purchase Order</h3>
            <form method="post">
                <div class="form-group">
                    <label for="p_mid">Select Purchase Order # *</label>
                    <select id="p_mid" name="p_mid" required>
                        <option value="">Choose an order...</option>
                        <?php 
                        $purchases_for_dropdown = $conn->query("SELECT P_mid FROM tb1_purchase_master ORDER BY P_date DESC");
                        while ($order = $purchases_for_dropdown->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($order['P_mid']) . "'>" . htmlspecialchars($order['P_mid']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="item_id">Select Product *</label>
                    <select id="item_id" name="item_id" required>
                        <option value="">Choose a product...</option>
                        <?php mysqli_data_seek($products_result, 0); // Reset pointer
                        while ($product = $products_result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($product['Item_id']) . "'>" . htmlspecialchars($product['Item_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="p_qty">Quantity *</label>
                    <input type="number" id="p_qty" name="p_qty" min="1" required>
                </div>
                <div class="form-group">
                    <label for="p_rate">Rate (per item) *</label>
                    <input type="number" id="p_rate" name="p_rate" step="0.01" min="0.01" required>
                </div>
                <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
            </form>
        </div>
        
        <?php if ($edit_order_id && !empty($current_order_items)): ?>
        <div class="section">
            <h3>Items in Purchase Order #<?php echo htmlspecialchars($edit_order_id); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Rate</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($current_order_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['Item_name']); ?></td>
                        <td><?php echo $item['P_qty']; ?></td>
                        <td>₹<?php echo number_format($item['P_rate'], 2); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="p_cid" value="<?php echo $item['P_cid']; ?>">
                                <input type="hidden" name="p_mid" value="<?php echo $edit_order_id; ?>">
                                <input type="hidden" name="item_qty" value="<?php echo $item['P_qty']; ?>">
                                <input type="hidden" name="item_rate" value="<?php echo $item['P_rate']; ?>">
                                <button type="submit" name="remove_item" class="btn btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html> 