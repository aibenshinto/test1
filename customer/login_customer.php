<?php
session_name('CUSTOMERSESSID');
session_start();
include '../db_connect.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usernameOrEmail = trim($_POST['username_email']);
    $password = $_POST['password'];

    if (empty($usernameOrEmail) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE (username = ? OR email = ?) AND role = 'customer'");
        $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #42a5f5, #478ed1);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        h2 {
            text-align: center;
            margin-bottom: 24px;
            color: #333;
        }

        .input-group {
            position: relative;
            margin-bottom: 24px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 12px 12px 8px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: transparent;
            outline: none;
        }

        .input-group label {
            position: absolute;
            top: 12px;
            left: 12px;
            color: #aaa;
            pointer-events: none;
            transition: 0.2s ease all;
            background: white;
            padding: 0 4px;
        }

        .input-group input:focus + label,
        .input-group input:not(:placeholder-shown) + label {
            top: -8px;
            left: 8px;
            font-size: 12px;
            color: #333;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: #2d89e6;
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .login-btn:hover {
            background: #1c6dd0;
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
            color: #b00020;
            background-color: #fdd;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

        @media (max-width: 500px) {
            .login-container {
                padding: 20px;
                margin: 0 10px;
            }
        }

        .toggle-btn {
            margin-bottom: 15px;
            background: none;
            border: none;
            color: #2d89e6;
            cursor: pointer;
            font-size: 14px;
            text-align: right;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Customer Login</h2>
        <?php if (!empty($message)) echo "<div class='error-message'>$message</div>"; ?>
        <form method="post" action="" autocomplete="off" id="loginCustomerForm">
            <div class="input-group">
                <input type="text" name="username_email" id="username_email" required placeholder=" " />
                <label for="username_email">Username or Email</label>
            </div>

            <div class="input-group">
                <input type="password" name="password" id="password" required placeholder=" " />
                <label for="password">Password</label>
            </div>

            <button type="button" class="toggle-btn" onclick="togglePassword()">Show/Hide Password</button>

            <input type="submit" value="Login" class="login-btn">
        </form>
        <div class="bottom-text">
            New customer? <a href="register_customer.php">Register here</a>
        </div>
    </div>

    <script>
        const form = document.getElementById('loginCustomerForm');

        form.addEventListener('submit', function (e) {
            const usernameEmail = form.username_email.value.trim();
            const password = form.password.value.trim();
            let errorDiv = document.querySelector('.error-message');

            if (!usernameEmail || !password) {
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
