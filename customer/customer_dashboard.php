<?php
require_once '../session_manager.php';
include '../db_connect.php';

// Require customer role to access this page
requireCustomer();

// Optional: Check session timeout (30 minutes)
checkSessionTimeout(30);

// Fetch categories for the filter
$categories_result = $conn->query("SELECT * FROM tbl_category ORDER BY cat_name");
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
        .filter-bar {
            background: #fff;
            padding: 18px 30px 10px 30px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            gap: 30px;
            align-items: center;
        }
        .filter-bar label {
            margin-right: 18px;
            font-weight: 500;
        }
        .product-rating {
            color: #f39c12;
            font-size: 15px;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<!-- Header Section -->
<div class="header">
    <h2>Welcome, <?php echo htmlspecialchars(getCustomerName()); ?></h2>
    <div class="nav-links">
        <a href="customer_cart.php">Cart</a>
        <a href="customer_orders.php">Orders</a>
        <a href="customer_dashboard.php">Products</a>
    </div>
</div>

<!-- Main Content with Sidebar Layout -->
<div style="display: flex; min-height: 80vh;">
    <!-- Sidebar for Filters -->
    <aside style="width: 240px; background: #fff; border-right: 1px solid #e0e0e0; padding: 30px 18px 0 18px; box-shadow: 2px 0 8px rgba(0,0,0,0.03);">
        <h3 style="margin-top: 0; color: #2d89e6;">Filter Products</h3>
        <form method="get" style="display: flex; flex-direction: column; gap: 18px;">
            <h4>Sort By</h4>
            <label><input type="radio" name="filter" value="lowest_price" <?php if (!isset($_GET['filter']) || $_GET['filter']==='lowest_price') echo 'checked'; ?>> Lowest Price</label>
            <label><input type="radio" name="filter" value="highest_price" <?php if (isset($_GET['filter']) && $_GET['filter']==='highest_price') echo 'checked'; ?>> Highest Price</label>
            <label><input type="radio" name="filter" value="lowest_rating" <?php if (isset($_GET['filter']) && $_GET['filter']==='lowest_rating') echo 'checked'; ?>> Lowest Rating</label>
            <label><input type="radio" name="filter" value="highest_rating" <?php if (isset($_GET['filter']) && $_GET['filter']==='highest_rating') echo 'checked'; ?>> Highest Rating</label>
            
            <h4 style="margin-top:20px;">Category</h4>
            <select name="category" onchange="this.form.submit()">
              <option value="">All Categories</option>
              <?php while ($category = $categories_result->fetch_assoc()): ?>
                <option value="<?php echo $category['cat_id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category['cat_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($category['cat_name']); ?>
                </option>
              <?php endwhile; ?>
            </select>

            <button type="submit" style="margin-top: 18px; background: #2d89e6; color: #fff; border: none; border-radius: 6px; padding: 10px 0; font-weight: bold;">Apply</button>
        </form>
    </aside>
    <!-- Product Grid -->
    <main style="flex: 1;">
        <div class="product-grid">
            <?php
            // Determine filter and category
            $filter = isset($_GET['filter']) ? $_GET['filter'] : 'lowest_price';
            $category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
            
            $orderBy = 'Item_rate ASC';
            if ($filter === 'highest_price') {
                $orderBy = 'Item_rate DESC';
            } elseif ($filter === 'lowest_rating') {
                $orderBy = 'Item_rating ASC';
            } elseif ($filter === 'highest_rating') {
                $orderBy = 'Item_rating DESC';
            }

            $whereClause = '';
            if ($category_filter > 0) {
                $whereClause = "WHERE Cat_id = " . $category_filter;
            }

            $sql = "SELECT * FROM tbl_item $whereClause ORDER BY $orderBy, created_at DESC";
            $result = $conn->query($sql);

            while ($row = $result->fetch_assoc()) {
                echo "<div class='product-card' onclick=\"location.href='product_details.php?id=" . $row['Item_id'] . "'\">";
                echo "<img src='../" . htmlspecialchars($row['Item_image']) . "' alt='Item Image'>";
                echo "<div class='product-name'>" . htmlspecialchars($row['Item_name']) . "</div>";
                echo "<div class='product-price'>₹" . htmlspecialchars($row['Item_rate']) . "</div>";
                echo "<div class='product-rating' style='font-size: 22px;'>";
                if ($row['Item_rating'] !== null) {
                    $rating = intval($row['Item_rating']);
                    for ($i = 0; $i < $rating; $i++) echo '★';
                    for ($i = 0; $i < 5 - $rating; $i++) echo '✩';
                } else {
                    echo "No ratings";
                }
                echo "</div>";
                echo "</div>";
            }
            ?>
        </div>
    </main>
</div>

<!-- Logout Button -->
<div class="logout">
    <a href="logout.php">Logout</a>
</div>

</body>
</html>
