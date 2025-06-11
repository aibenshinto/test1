<?php
session_name('ADMINSESSID');
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../authentication/login.php");
    exit;
}

$msg = '';
$msg_class = '';

// Handle staff deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['staff_id']) && isset($_POST['action'])) {
    $staff_id = intval($_POST['staff_id']);
    $action = $_POST['action'];
    
    if ($action === 'delete') {
        $delete = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'");
        $delete->bind_param("i", $staff_id);
        
        if ($delete->execute()) {
            $msg = "Staff member removed successfully!";
            $msg_class = 'msg-success';
        } else {
            $msg = "Failed to remove staff member.";
            $msg_class = 'msg-error';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Staff Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #43cea2, #185a9d);
            color: #333;
            min-height: 100vh;
        }
        .dashboard {
            display: flex;
            width: 100vw;
            min-height: 100vh;
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
        .msg {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 6px;
            font-size: 16px;
        }
        .msg-success {
            background: #e6f7ff;
            color: #2d89e6;
            border: 1px solid #2d89e6;
        }
        .msg-error {
            background: #ffe6e6;
            color: #d0021b;
            border: 1px solid #d0021b;
        }
        .staff-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .staff-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f9f9f9;
        }
        .staff-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .staff-header h3 {
            margin: 0;
            color: #185a9d;
        }
        .staff-details {
            margin-bottom: 15px;
        }
        .action-btn {
            background: #2d89e6;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        .action-btn:hover {
            background: #1c6dd0;
        }
        .action-btn.delete {
            background: #d9534f;
        }
        .action-btn.delete:hover {
            background: #c9302c;
        }
        .add-staff-btn {
            background: #5cb85c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
            text-decoration: none;
            display: inline-block;
        }
        .add-staff-btn:hover {
            background: #449d44;
        }
    </style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <h2>Admin Panel</h2>
        <p>Hello, <?= htmlspecialchars($_SESSION['username']) ?></p>
        <ul>
            <li><a href="../staff/staff_management.php">Staff</a></li>
            <li><a href="../product/product_management.php">Products</a></li>
            <li><a href="../orders/order_management.php">Orders</a></li>
            <li><a href="../sales/sales_statistics.php">Sales</a></li>
            <li><a class="logout-link" href="../../authentication/logout.php">Logout</a></li>
        </ul>
    </aside>
    <main class="main-content">
        <?php if ($msg): ?>
            <div class="msg <?= $msg_class ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="staff-container">
            <h2>Staff Management</h2>
            <a href="add_staff.php" class="add-staff-btn">Add New Staff</a>
            
            <?php
            // Fetch all staff members
            $sql = "SELECT id, username, email, created_at 
                    FROM users 
                    WHERE role = 'staff' 
                    ORDER BY created_at DESC";
            
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($staff = $result->fetch_assoc()) {
                    ?>
                    <div class="staff-card">
                        <div class="staff-header">
                            <h3><?= htmlspecialchars($staff['username']) ?></h3>
                            <div>
                                <a href="edit_staff.php?id=<?= $staff['id'] ?>" class="action-btn">Edit</a>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                    <button type="submit" name="action" value="delete" class="action-btn delete" 
                                            onclick="return confirm('Are you sure you want to remove this staff member?')">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="staff-details">
                            <p><strong>Email:</strong> <?= htmlspecialchars($staff['email']) ?></p>
                            <p><strong>Joined:</strong> <?= date('F j, Y', strtotime($staff['created_at'])) ?></p>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p>No staff members found.</p>";
            }
            ?>
        </div>
    </main>
</div>
</body>
</html> 