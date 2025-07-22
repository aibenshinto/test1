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
                $message = "Incorrect password.";
            }
        } else {
            $message = "Customer account not found.";
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
    <title>Customer Login</title>
    <style>
        body {
            background: #f0f2f5;
            font-family: Arial, sans-serif;
        }

        .login-box {
            width: 350px;
            margin: 80px auto;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .login-box h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .user-box {
            position: relative;
            margin-bottom: 25px;
        }

        .user-box input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 6px;
            outline: none;
        }

        .user-box label {
            position: absolute;
            top: -10px;
            left: 12px;
            background: #fff;
            padding: 0 5px;
            font-size: 12px;
            color: #666;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 12px;
            cursor: pointer;
        }

        .button-box {
            text-align: center;
        }

        .button-box button {
            width: 100%;
            padding: 12px;
            background: #2d89e6;
            border: none;
            border-radius: 6px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .button-box button:hover {
            background: #206cc2;
        }

        .bottom-text {
            text-align: center;
            margin-top: 16px;
            color: #333;
        }

        .bottom-text a {
            color: #2d89e6;
            text-decoration: none;
        }

        .error-message {
            background: #ffefef;
            color: #c0392b;
            padding: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #c0392b;
            border-radius: 5px;
            animation: shake 0.2s 2;
        }

        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
            100% { transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Customer Login</h2>
        <?php if (!empty($message)) : ?>
            <div class="error-message"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="POST" id="loginCustomerForm">
            <div class="user-box">
                <input type="text" name="username" id="username" required placeholder=" ">
                <label for="username">Username</label>
            </div>
            <div class="user-box">
                <input type="password" name="password" id="password" required placeholder=" ">
                <label for="password">Password</label>
                <span class="toggle-password" onclick="togglePassword()">👁️</span>
            </div>
            <div class="button-box">
                <button type="submit">Login</button>
            </div>
        </form>
        <div class="bottom-text">
            New customer? <a href="register_customer.php">Register here</a>
        </div>
    </div>

    <script>
        const form = document.getElementById('loginCustomerForm');
        form.addEventListener('submit', function (e) {
            const username = form.username.value.trim();
            const password = form.password.value.trim();
            let errorDiv = document.querySelector('.error-message');

            if (!username || !password) {
                e.preventDefault();
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.textContent = 'Please fill in all fields.';
                    form.parentElement.insertBefore(errorDiv, form);
                } else {
                    errorDiv.textContent = 'Please fill in all fields.';
                    errorDiv.style.animation = 'none';
                    errorDiv.offsetHeight;
                    errorDiv.style.animation = '';
                }
            }
        });

        function togglePassword() {
            const pwd = document.getElementById('password');
            pwd.type = pwd.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
