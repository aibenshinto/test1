<?php
include '../db_connect.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Cust_fname = trim($_POST['Cust_fname']);
    $Cust_lname = trim($_POST['Cust_lname']);
    $Cust_street = trim($_POST['Cust_street']);
    $Cust_city = trim($_POST['Cust_city']);
    $Cust_state = trim($_POST['Cust_state']);
    $Cust_gender = trim($_POST['Cust_gender']);
    $Cust_ph = trim($_POST['Cust_ph']);
    $Cust_email = filter_var(trim($_POST['Cust_email']), FILTER_SANITIZE_EMAIL);
    $Username = trim($_POST['Username']);
    $Password = $_POST['Password'];
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;

    if (!$Cust_fname || !$Cust_lname || !$Cust_street || !$Cust_city || !$Cust_state || !$Cust_gender || !$Cust_ph || !$Cust_email || !$Username || !$Password) {
        $error = "All fields are required.";
    } elseif (!filter_var($Cust_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($Password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!$latitude || !$longitude) {
        $error = "Location not detected. Please allow location access.";
    } else {
        $check = $conn->prepare("SELECT Cust_id FROM tbl_customer WHERE Cust_email = ?");
        $check->bind_param("s", $Cust_email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            $Cust_id = 'CUST' . bin2hex(random_bytes(3)); 
            $hash = password_hash($Password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO tbl_customer (Cust_id, Cust_fname, Cust_lname, Cust_street, Cust_city, Cust_state, Cust_gender, Cust_ph, Cust_email, Username, Password, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssdd", $Cust_id, $Cust_fname, $Cust_lname, $Cust_street, $Cust_city, $Cust_state, $Cust_gender, $Cust_ph, $Cust_email, $Username, $hash, $latitude, $longitude);

            if ($stmt->execute()) {
                $message = "Customer registered successfully!";
            } else {
                $error = "Error: " . $conn->error;
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
    <title>Customer Registration</title>
    <style>
        body {
            background-color: #F3F4F6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px;
        }

        form {
            background-color: #fff;
            padding: 30px;
            max-width: 600px;
            margin: auto;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #2563EB;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #1D4ED8;
        }

        .message {
            text-align: center;
            margin-bottom: 15px;
            color: green;
        }

        .error {
            text-align: center;
            margin-bottom: 15px;
            color: red;
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
    </style>
</head>
<body>

<form method="post">
    <h2>Customer Registration</h2>

    <?php if ($message): ?>
        <p class="message"><?= $message ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <input type="text" name="Cust_fname" placeholder="First Name" required>
    <input type="text" name="Cust_lname" placeholder="Last Name" required>
    <input type="text" name="Cust_street" placeholder="Street" required>
    <input type="text" name="Cust_city" placeholder="City" required>
    <input type="text" name="Cust_state" placeholder="State" required>
    <select name="Cust_gender" required>
        <option value="" disabled selected>Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
    </select>
    <input type="text" name="Cust_ph" placeholder="Phone" required>
    <input type="email" name="Cust_email" placeholder="Email" required>
    <input type="text" name="Username" placeholder="Username" required>
    <input type="password" name="Password" placeholder="Password" required>

    <!-- Hidden fields for geolocation -->
    <input type="hidden" name="latitude" id="latitude">
    <input type="hidden" name="longitude" id="longitude">

    <input type="submit" value="Register">
</form>

<div class="bottom-text">
    Already registered? <a href="login_customer.php">Login here</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            position => {
                document.getElementById('latitude').value = position.coords.latitude;
                document.getElementById('longitude').value = position.coords.longitude;
            },
            error => {
                console.warn('Geolocation error:', error.message);
            }
        );
    } else {
        console.warn('Geolocation not supported.');
    }
});
</script>

</body>
</html>
