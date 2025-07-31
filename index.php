<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Project Portal</title>
    <link rel="stylesheet" href="css/portal_style.css">
</head>
<body>
    <div class="portal-container">
        <header>
            <h1>Welcome to Your E-Commerce Platform</h1>
            <p>Please select your login type to continue.</p>
        </header>
        
        <div class="login-options">
            <!-- Customer Login Card -->
            <a href="customer/login_customer.php" class="login-card">
                <div class="card-icon">👤</div>
                <h2>Customer</h2>
                <p>Access your account, view orders, and shop.</p>
            </a>

            <!-- Staff & Admin Login Card -->
            <a href="authentication/login.php" class="login-card">
                <div class="card-icon">⚙️</div>
                <h2>Staff & Admin</h2>
                <p>Manage products, orders, and site settings.</p>
            </a>
        </div>
    </div>
</body>
</html>
