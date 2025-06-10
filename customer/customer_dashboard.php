<?php
session_name('CUSTOMERSESSID');
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login_customer.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            margin: 0;
            padding: 0;
        }

        /* Header */
        .header {
            background-color: #2d89e6;
            color: white;
            padding: 16px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h2 {
            margin: 0;
            font-size: 22px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }

        /* Product grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
            padding: 30px;
        }

        .product-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            text-align: center;
            transition: transform 0.2s;
        }

        .product-card:hover {
            transform: scale(1.03);
        }

        .product-card img {
            width: 100%;
            height: 150px;
            object-fit: contain;
        }

        .product-name {
            font-size: 18px;
            margin: 10px 0 5px;
            color: #333;
        }

        .product-price {
            color: #2d89e6;
            font-weight: bold;
        }

        .logout {
            text-align: center;
            margin: 30px;
        }

        .logout a {
            background: #d33;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<!-- Header Section -->
<div class="header">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
    <div class="nav-links">
        <a href="customer_cart.php">Cart</a>
        <a href="customer_orders.php">Orders</a>
        <a href="customer_dashboard.php">Products</a>
    </div>
</div>

<!-- Product Listing -->
<div class="product-grid">
    <?php
    $sql = "SELECT * FROM products ORDER BY created_at DESC";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        echo "<div class='product-card' onclick=\"location.href='product_details.php?id=" . $row['id'] . "'\">";
        echo "<img src='" . htmlspecialchars($row['image']) . "' alt='Product Image'>";
        echo "<div class='product-name'>" . htmlspecialchars($row['name']) . "</div>";
        echo "<div class='product-price'>₹" . htmlspecialchars($row['price']) . "</div>";
        echo "</div>";
    }
    ?>
</div>

<!-- Logout Button -->
<div class="logout">
    <a href="logout.php">Logout</a>
</div>

</body>
</html>
