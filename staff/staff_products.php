<?php
session_name('ADMINSESSID');
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../authentication/login.php");
    exit;
}

$staff_id = $_SESSION['user_id'];

// Handle product deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    
    // First delete any product questions
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
    <title>Staff Products</title>
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
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .product-header h2 {
            margin: 0;
            color: #185a9d;
        }
        .add-product-btn {
            background: #2d89e6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .add-product-btn:hover {
            background: #1c6dd0;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .products-table th, .products-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .products-table th {
            background: #f5f5f5;
            font-weight: 600;
            color: #333;
        }
        .products-table tr:hover {
            background: #f9f9f9;
        }
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin-right: 5px;
        }
        .edit-btn {
            background: #2d89e6;
            color: white;
        }
        .delete-btn {
            background: #dc3545;
            color: white;
        }
        .edit-btn:hover {
            background: #1c6dd0;
        }
        .delete-btn:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <h2>Staff Panel</h2>
        <p>Hello, <?= htmlspecialchars($_SESSION['username']) ?></p>
        <ul>
            <li><a href="staff_products.php">Products</a></li>
            <li><a href="view_orders.php">Orders</a></li>
            <li><a href="staff_qna.php">Q&A</a></li>
            <li><a class="logout-link" href="../customer/logout.php">Logout</a></li>
        </ul>
    </aside>
    <main class="main-content">
        <?php if (isset($msg) && $msg): ?>
            <div class="msg <?= $msg_class ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        
        <div class="product-header">
            <h2>Manage Products</h2>
            <a href="add_product.php" class="add-product-btn">+ Add New Product</a>
        </div>

        <table class="products-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM products ORDER BY created_at DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['id']}</td>
                            <td>{$row['name']}</td>
                            <td>₹{$row['price']}</td>
                            <td>{$row['stock']}</td>
                            <td>
                                <a href='edit_product.php?id={$row['id']}' class='action-btn edit-btn'>Edit</a>
                                <button onclick='deleteProduct({$row['id']})' class='action-btn delete-btn'>Delete</button>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align: center;'>No products found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </main>
</div>

<script>
function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'staff_products.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_id';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</body>
</html> 