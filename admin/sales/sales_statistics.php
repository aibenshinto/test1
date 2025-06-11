<?php
session_name('ADMINSESSID');
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../authentication/login.php");
    exit;
}

// Get current year
$current_year = date('Y');

// Create array of all months in the current year
$months = [];
$sales = [];
$orders = [];

// Initialize all months with zero values
for ($month = 1; $month <= 12; $month++) {
    $months[] = date('M Y', mktime(0, 0, 0, $month, 1, $current_year));
    $sales[] = 0;
    $orders[] = 0;
}

// Get monthly sales data for current year
$query = "SELECT 
            DATE_FORMAT(o.order_date, '%m') as month,
            COUNT(*) as total_orders,
            SUM(o.total_amount) as total_sales
          FROM orders o
          WHERE o.status != 'cancelled'
          AND YEAR(o.order_date) = ?
          GROUP BY DATE_FORMAT(o.order_date, '%m')
          ORDER BY month ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $current_year);
$stmt->execute();
$result = $stmt->get_result();

// Update the arrays with actual sales data
while ($row = $result->fetch_assoc()) {
    $month_index = intval($row['month']) - 1; // Convert to 0-based index
    $sales[$month_index] = $row['total_sales'];
    $orders[$month_index] = $row['total_orders'];
}

// Get top selling products
$top_products_query = "SELECT 
                        p.name,
                        SUM(oi.quantity) as total_quantity,
                        SUM(oi.quantity * oi.price) as total_revenue
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.id
                      JOIN orders o ON oi.order_id = o.id
                      WHERE o.status != 'cancelled'
                      AND YEAR(o.order_date) = ?
                      GROUP BY p.id
                      ORDER BY total_quantity DESC
                      LIMIT 5";

$stmt = $conn->prepare($top_products_query);
$stmt->bind_param("i", $current_year);
$stmt->execute();
$top_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate year-to-date totals
$ytd_sales = array_sum($sales);
$ytd_orders = array_sum($orders);
$ytd_aov = $ytd_orders > 0 ? $ytd_sales / $ytd_orders : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sales Statistics</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #185a9d;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2d89e6;
        }
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .top-products {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .top-products h3 {
            margin: 0 0 20px 0;
            color: #185a9d;
        }
        .product-list {
            list-style: none;
            padding: 0;
        }
        .product-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .product-item:last-child {
            border-bottom: none;
        }
        .product-name {
            font-weight: bold;
        }
        .product-stats {
            color: #666;
        }
        .year-selector {
            margin-bottom: 20px;
            text-align: right;
        }
        .year-selector select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
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
            <li><a href="sales_statistics.php">Sales</a></li>
            <li><a class="logout-link" href="../../authentication/logout.php">Logout</a></li>
        </ul>
    </aside>
    <main class="main-content">
        <h2>Sales Statistics - <?= $current_year ?></h2>
        
        <div class="stats-container">
            <div class="stat-card">
                <h3>Year-to-Date Sales</h3>
                <div class="stat-value">₹<?= number_format($ytd_sales, 2) ?></div>
            </div>
            <div class="stat-card">
                <h3>Year-to-Date Orders</h3>
                <div class="stat-value"><?= $ytd_orders ?></div>
            </div>
            <div class="stat-card">
                <h3>Average Order Value</h3>
                <div class="stat-value">₹<?= number_format($ytd_aov, 2) ?></div>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="salesChart"></canvas>
        </div>

        <div class="top-products">
            <h3>Top Selling Products (<?= $current_year ?>)</h3>
            <ul class="product-list">
                <?php foreach ($top_products as $product): ?>
                <li class="product-item">
                    <span class="product-name"><?= htmlspecialchars($product['name']) ?></span>
                    <span class="product-stats">
                        <?= $product['total_quantity'] ?> units sold
                        (₹<?= number_format($product['total_revenue'], 2) ?>)
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </main>
</div>

<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Monthly Sales (₹)',
            data: <?= json_encode($sales) ?>,
            borderColor: '#185a9d',
            backgroundColor: 'rgba(24, 90, 157, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Monthly Sales Trend - <?= $current_year ?>'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>
</body>
</html> 