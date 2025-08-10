<?php
// Remove the custom session name to use default session
session_start();

// Redirect logged-in users immediately
if (isset($_SESSION['staff_id'])) { // Check for staff_id specifically
    if ($_SESSION['staff_role'] === 'admin') {
        header("Location: ../admin/staff_management.php");
    } elseif ($_SESSION['staff_role'] === 'delivery') {
        header("Location: ../staff/delivery_dashboard.php");
    } else {
        header("Location: ../staff/staff_dashboard.php");
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
                // Store staff data in session
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
                $message = "Incorrect email or password.";
            }
        } else {
            $message = "Incorrect email or password.";
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
    <title>Staff & Admin Login - Synopsis</title>
    <!-- Link to the shared login stylesheet -->
    <link rel="stylesheet" href="../css/login_style.css">
</head>
<body>
    <div class="login-container">
        <h2>Staff & Admin Login</h2>
        
        <?php if (!empty($message)) : ?>
            <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="login.php" autocomplete="off">
            <div class="input-group">
                <input type="email" name="email" id="email" required placeholder=" ">
                <label for="email">Email</label>
            </div>
            
            <div class="input-group">
                <input type="password" name="password" id="password" required placeholder=" ">
                <label for="password">Password</label>
            </div>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <div class="bottom-text">
            Don't have an account? <a href="../staff/register_staff.php">Register here</a>
        </div>
    </div>
</body>
</html>
