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

// Handle product deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    
    // First delete any cart items
    $delete_cart_items = $conn->prepare("DELETE FROM cart_items WHERE product_id = ?");
    $delete_cart_items->bind_param("i", $delete_id);
    $delete_cart_items->execute();
    
    // Then delete any product questions
    $delete_questions = $conn->prepare("DELETE FROM product_questions WHERE product_id = ?");
    $delete_questions->bind_param("i", $delete_id);
    $delete_questions->execute();
    
    // Then delete any order items
    $delete_order_items = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
    $delete_order_items->bind_param("i", $delete_id);
    $delete_order_items->execute();
    
    // Finally delete the product
    $delete = $conn->prepare("DELETE FROM products WHERE id = ?");
    $delete->bind_param("i", $delete_id);
    if ($delete->execute()) {
        $msg = "Product deleted successfully.";
        $msg_class = 'msg-success';
    } else {
        $msg = "Failed to delete product.";
        $msg_class = 'msg-error';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Product Management</title>
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
        .product-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .product-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f9f9f9;
        }
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .product-header h3 {
            margin: 0;
            color: #185a9d;
        }
        .product-details {
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
        .add-product-btn {
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
        .add-product-btn:hover {
            background: #449d44;
        }
        .product-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        .product-info {
            display: flex;
            align-items: center;
        }
        .stock-warning {
            color: #d9534f;
            font-weight: bold;
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

        <div class="product-container">
            <h2>Product Management</h2>
            <a href="add_product.php" class="add-product-btn">Add New Product</a>
            
            <?php
            // Fetch all products
            $sql = "SELECT * FROM products ORDER BY created_at DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($product = $result->fetch_assoc()) {
                    ?>
                    <div class="product-card">
                        <div class="product-header">
                            <div class="product-info">
                                <?php if ($product['image']): ?>
                                    <img src="../../uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         class="product-image">
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                            </div>
                            <div>
                                <a href="edit_product.php?id=<?= $product['id'] ?>" class="action-btn">Edit</a>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="delete_id" value="<?= $product['id'] ?>">
                                    <button type="submit" name="action" value="delete" class="action-btn delete" 
                                            onclick="return confirm('Are you sure you want to remove this product? This will also remove it from any orders.')">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="product-details">
                            <p><strong>Price:</strong> ₹<?= number_format($product['price'], 2) ?></p>
                            <p><strong>Stock:</strong> 
                                <span class="<?= $product['stock'] <= 5 ? 'stock-warning' : '' ?>">
                                    <?= $product['stock'] ?>
                                </span>
                            </p>
                            <p><strong>Description:</strong> <?= htmlspecialchars($product['description']) ?></p>
                            <p><strong>Added:</strong> <?= date('F j, Y', strtotime($product['created_at'])) ?></p>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p>No products found.</p>";
            }
            ?>
        </div>
    </main>
</div>
</body>
</html> 