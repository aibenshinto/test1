<?php
require_once '../session_manager.php';
include '../db_connect.php';

// Require customer role to access this page
requireCustomer();

// Optional: Check session timeout (30 minutes)
checkSessionTimeout(30);

// Fetch categories for the filter
$categories_result = $conn->query("SELECT * FROM tbl_category ORDER BY cat_name");

// Determine the active page for navigation styling
$active_page = basename($_SERVER['PHP_SELF']);

// --- Filtering and Searching Logic ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'newest';
$category_filter = isset($_GET['category']) && !empty($_GET['category']) ? intval($_GET['category']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the WHERE clause
$where_conditions = [];
if ($category_filter > 0) {
    $where_conditions[] = "Cat_id = " . $category_filter;
}
if (!empty($search_query)) {
    // Use prepared statements to prevent SQL injection
    $search_term = "%" . $conn->real_escape_string($search_query) . "%";
    $where_conditions[] = "Item_name LIKE '" . $search_term . "'";
}
$whereClause = count($where_conditions) > 0 ? "WHERE " . implode(' AND ', $where_conditions) : '';

// Build the ORDER BY clause
$orderBy = 'created_at DESC'; // Default to newest
if ($filter === 'price_asc') $orderBy = 'Item_rate ASC';
elseif ($filter === 'price_desc') $orderBy = 'Item_rate DESC';
elseif ($filter === 'rating_desc') $orderBy = 'Item_rating DESC';

$sql = "SELECT * FROM tbl_item $whereClause ORDER BY $orderBy";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Synopsis</title>
    <link rel="stylesheet" href="../css/dashboard_style.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Header Section -->
<header class="header">
    <div class="header-logo">
        <a href="customer_dashboard.php">Synopsis</a>
    </div>
    <nav class="nav-links">
        <a href="customer_dashboard.php" class="<?php echo ($active_page == 'customer_dashboard.php') ? 'active' : ''; ?>">Products</a>
        <a href="customer_orders.php" class="<?php echo ($active_page == 'customer_orders.php') ? 'active' : ''; ?>">My Orders</a>
    </nav>
    <div class="header-user">
        <a href="customer_cart.php" class="cart-icon"><i class="fas fa-shopping-cart"></i></a>
        <div class="user-menu">
             <i class="fas fa-user-circle"></i>
             <span><?php echo htmlspecialchars(getCustomerName()); ?></span>
             <div class="dropdown-content">
                 <a href="logout.php">Logout</a>
             </div>
        </div>
    </div>
</header>

<!-- Main Content -->
<div class="container">
    <aside class="sidebar">
        <h3><i class="fas fa-filter"></i> Filters</h3>
        <form method="get" id="filter-form">
            <div class="filter-group">
                <h4>Search Products</h4>
                <input type="search" name="search" placeholder="e.g., Samsung TV" value="<?php echo htmlspecialchars($search_query); ?>">
            </div>

            <div class="filter-group">
                <h4>Category</h4>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php mysqli_data_seek($categories_result, 0); // Reset pointer ?>
                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo $category['cat_id']; ?>" <?php echo ($category_filter == $category['cat_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['cat_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-group">
                <h4>Sort By</h4>
                <select name="filter">
                    <option value="newest" <?php if ($filter === 'newest') echo 'selected'; ?>>Newest Arrivals</option>
                    <option value="price_asc" <?php if ($filter === 'price_asc') echo 'selected'; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php if ($filter === 'price_desc') echo 'selected'; ?>>Price: High to Low</option>
                    <option value="rating_desc" <?php if ($filter === 'rating_desc') echo 'selected'; ?>>Highest Rated</option>
                </select>
            </div>
            
            <button type="submit" class="apply-btn">Apply Filters</button>
            <a href="customer_dashboard.php" class="clear-btn">Clear All</a>
        </form>
    </aside>

    <main class="main-content">
        <div class="product-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="product-card" onclick="location.href='product_details.php?id=<?php echo $row['Item_id']; ?>'">
                        <div class="product-image-container">
                            <img src="../<?php echo htmlspecialchars($row['Item_image']); ?>" alt="<?php echo htmlspecialchars($row['Item_name']); ?>">
                        </div>
                        <div class="product-info">
                            <h4 class="product-name"><?php echo htmlspecialchars($row['Item_name']); ?></h4>
                            <div class="product-details">
                                <div class="product-price">₹<?php echo number_format($row['Item_rate']); ?></div>
                                <div class="product-rating">
                                    <?php 
                                    $rating = round($row['Item_rating'] ?? 0);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-products-found">
                    <i class="fas fa-box-open"></i>
                    <h3>No Products Found</h3>
                    <p>We couldn't find any products matching your search or filters. Try clearing them to see more.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
