<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - E-commerce</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: url('') no-repeat center center/cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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
            color: red;
            text-align: center;
            margin-bottom: 16px;
        }

        @media (max-width: 500px) {
            .login-container {
                padding: 20px;
                margin: 0 10px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Welcome Back</h2>
        <?php if (!empty($message)) echo "<div class='error-message'>$message</div>"; ?>
        <form method="post" action="login.php" autocomplete="off">
            <div class="input-group">
                <input type="text" name="username_email" id="username_email" required placeholder=" " />
                <label for="username_email">Username or Email</label>
            </div>

            <div class="input-group">
                <input type="password" name="password" id="password" required placeholder=" " />
                <label for="password">Password</label>
            </div>

            <input type="submit" value="Login" class="login-btn">
        </form>
        <div class="bottom-text">
            Don't have an account? <a href="register_customer.php">Register as Customer</a>
        </div>
    </div>
</body>
</html> 