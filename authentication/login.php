<?php
session_name('ADMINSESSID');
session_start();

// Redirect logged-in users immediately
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard/admin_dashboard.php");
    } elseif ($_SESSION['role'] === 'staff') {
        header("Location: ../staff/staff_dashboard.php");
    }
    exit;
}

include '../db_connect.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usernameOrEmail = trim($_POST['username_email']);
    $password = $_POST['password'];

    if (empty($usernameOrEmail) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard/admin_dashboard.php");
                } elseif ($user['role'] === 'staff') {
                    header("Location: ../staff/staff_dashboard.php");
                }
                exit;
            } else {
                $message = "Incorrect password.";
            }
        } else {
            $message = "User not found.";
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
    <title>Login - E-commerce</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <h2>Welcome Back</h2>
        <?php if (!empty($message)) echo "<div class='error-message'>$message</div>"; ?>
        <form method="post" action="login.php" autocomplete="off" id="loginForm">
            <div class="input-group">
                <input type="text" name="username_email" id="username_email" required placeholder=" " />
                <label for="username_email">Username or Email</label>
            </div>

            <div class="input-group">
                <input type="password" name="password" id="password" required placeholder=" " />
                <label for="password">Password</label>
                <button type="button" onclick="togglePassword()" class="toggle-btn">Show</button>
            </div>

            <button type="submit" class="login-btn">Login</button>
        </form>
        <div class="bottom-text">
            Don't have an account? <a href="../admin/register_admin.php">Register</a>
        </div>
    </div>

    <script src="login.js"></script>
</body>
</html>
