<?php
session_start();
include '../db_connect.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT Cust_id, Cust_fname, Username, Password FROM tbl_customer WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $customer = $result->fetch_assoc();

            if (password_verify($password, $customer['Password'])) {
                $_SESSION['customer_id'] = $customer['Cust_id'];
                $_SESSION['customer_name'] = $customer['Cust_fname'];
                $_SESSION['customer_username'] = $customer['Username'];
                $_SESSION['customer_login_time'] = time();

                header("Location: customer_dashboard.php");
                exit;
            } else {
                $message = "Incorrect username or password.";
            }
        } else {
            $message = "Incorrect username or password.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login</title>
    <!-- Link to the new external stylesheet -->
    <link rel="stylesheet" href="../css/login_style.css">
</head>
<body>
    <div class="login-container">
        <h2>Customer Login</h2>
        
        <?php if (!empty($message)) : ?>
            <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="login_customer.php" autocomplete="off">
            <div class="input-group">
                <input type="text" name="username" id="username" required placeholder=" ">
                <label for="username">Username</label>
            </div>
            
            <div class="input-group">
                <input type="password" name="password" id="password" required placeholder=" ">
                <label for="password">Password</label>
            </div>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <div class="bottom-text">
            New customer? <a href="register_customer.php">Register here</a>
        </div>
    </div>
</body>
</html>
