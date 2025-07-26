<?php
// Remove the custom session name to use default session
session_start();

// Redirect logged-in users immediately
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'staff') {
        if ($_SESSION['role'] === 'delivery') {
            header("Location: ../staff/view_orders.php");
        } else {
            header("Location: ../staff/staff_dashboard.php");
        }
    }
    exit;
}

include '../db_connect.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT Staff_id, Staff_fname, Staff_email, Password, role FROM tbl_staff WHERE Staff_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $staff = $result->fetch_assoc();

            if (password_verify($password, $staff['Password'])) {
                // Store staff data in session (separate from customer)
                $_SESSION['staff_id'] = $staff['Staff_id'];
                $_SESSION['staff_name'] = $staff['Staff_fname'];
                $_SESSION['staff_email'] = $staff['Staff_email'];
                $_SESSION['staff_role'] = $staff['role'];
                $_SESSION['staff_login_time'] = time();

                if ($staff['role'] === 'admin') {
                    header("Location: ../admin/staff_management.php");
                } elseif ($staff['role'] === 'delivery') {
                    header("Location: ../staff/delivery_dashboard.php");
                } else {
                    header("Location: ../staff/staff_dashboard.php");
                }
                exit;
            } else {
                $message = "Incorrect password.";
            }
        } else {
            $message = "Staff account not found.";
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
    <title>Staff Login - E-commerce</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <h2>Staff Login</h2>
        <?php if (!empty($message)) echo "<div class='error-message'>$message</div>"; ?>
        <form method="post" action="login.php" autocomplete="off" id="loginForm">
            <div class="input-group">
                <input type="email" name="email" id="email" required placeholder=" " />
                <label for="email">Email</label>
            </div>

            <div class="input-group">
                <input type="password" name="password" id="password" required placeholder=" " />
                <label for="password">Password</label>
                <button type="button" onclick="togglePassword()" class="toggle-btn">Show</button>
            </div>

            <button type="submit" class="login-btn">Login</button>
        </form>
        <div class="bottom-text">
            Don't have an account? <a href="../staff/register_staff.php">Register</a>
        </div>
    </div>

    <script src="login.js"></script>
</body>
</html>
