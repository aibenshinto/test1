<?php
session_name('ADMINSESSID');
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../authentication/login.php");
    exit;
}

$msg = '';
$msg_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $image = '';
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_extension, $allowed_extensions)) {
            $image = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $image;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Image uploaded successfully
            } else {
                $msg = "Failed to upload image.";
                $msg_class = 'msg-error';
            }
        } else {
            $msg = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
            $msg_class = 'msg-error';
        }
    }
    
    if (empty($msg)) {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdis", $name, $description, $price, $stock, $image);
        
        if ($stmt->execute()) {
            $msg = "Product added successfully!";
            $msg_class = 'msg-success';
            // Clear form data
            $name = $description = '';
            $price = $stock = 0;
        } else {
            $msg = "Failed to add product.";
            $msg_class = 'msg-error';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add New Product</title>
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
        .form-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .submit-btn {
            background: #5cb85c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background: #449d44;
        }
        .cancel-btn {
            background: #d9534f;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        .cancel-btn:hover {
            background: #c9302c;
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
            <li><a class="logout-link" href="../authentication/logout.php">Logout</a></li>
        </ul>
    </aside>
    <main class="main-content">
        <?php if ($msg): ?>
            <div class="msg <?= $msg_class ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h2>Add New Product</h2>
            <form method="post" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Product Name:</label>
                    <input type="text" id="name" name="name" required value="<?= isset($name) ? htmlspecialchars($name) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required><?= isset($description) ? htmlspecialchars($description) : '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price (₹):</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required value="<?= isset($price) ? $price : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock:</label>
                    <input type="number" id="stock" name="stock" min="0" required value="<?= isset($stock) ? $stock : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="image">Product Image:</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                </div>
                
                <button type="submit" class="submit-btn">Add Product</button>
                <a href="staff_products.php" class="cancel-btn">Cancel</a>
            </form>
        </div>
    </main>
</div>
</body>
</html> 