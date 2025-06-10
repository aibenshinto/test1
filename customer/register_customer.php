<?php
include '../db_connect.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!$username || !$email || !$password) {
        $message = "All fields required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Username or Email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')");
            $stmt->bind_param("sss", $username, $email, $hash);
            if ($stmt->execute()) {
                $message = "Customer registered successfully!";
            } else {
                $message = "Error: " . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Customer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #00b4db, #0083b0);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .register-container {
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
            padding: 12px;
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
        .register-btn {
            width: 100%;
            padding: 12px;
            background: #009688;
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .register-btn:hover {
            background: #00796b;
        }
        .message {
            margin-bottom: 16px;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            background-color: #fdd;
            color: #b00020;
        }
        .bottom-text {
            text-align: center;
            margin-top: 16px;
            color: #333;
        }
        .bottom-text a {
            color: #009688;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Register as Customer</h2>
        <?php if ($message) echo "<div class='message'>" . htmlspecialchars($message) . "</div>"; ?>
        <form method="post" id="registerForm" autocomplete="off">
            <div class="input-group">
                <input type="text" name="username" id="username" required placeholder=" " />
                <label for="username">Username</label>
            </div>
            <div class="input-group">
                <input type="email" name="email" id="email" required placeholder=" " />
                <label for="email">Email</label>
            </div>
            <div class="input-group">
                <input type="password" name="password" id="password" required placeholder=" " />
                <label for="password">Password</label>
            </div>
            <input type="submit" value="Register" class="register-btn">
        </form>
        <div class="bottom-text">
            Already have an account? <a href="login_customer.php">Login</a>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('registerForm');

            form.addEventListener('submit', function (e) {
                const username = form.username.value.trim();
                const email = form.email.value.trim();
                const password = form.password.value.trim();
                let messageBox = document.querySelector('.message');

                if (!username || !email || !password) {
                    e.preventDefault();
                    if (!messageBox) {
                        messageBox = document.createElement('div');
                        messageBox.className = 'message';
                        form.parentElement.insertBefore(messageBox, form);
                    }
                    messageBox.textContent = "Please fill in all fields.";
                }
            });
        });
    </script>
</body>
</html>
