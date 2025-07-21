<?php
include '../db_connect.php';

$message = '';
$error = '';

// Function to geocode address to coordinates
function geocodeAddress($address) {
    // Use Nominatim (OpenStreetMap) geocoding service
    $address = urlencode($address);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$address}&limit=1";
    
    // Set user agent to avoid being blocked
    $context = stream_context_create([
        'http' => [
            'header' => 'User-Agent: EcommerceApp/1.0'
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
        return [
            'latitude' => floatval($data[0]['lat']),
            'longitude' => floatval($data[0]['lon'])
        ];
    }
    
    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $location = trim($_POST['location']);

    if (!$name || !$email || !$password || !$location) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM customers WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            // Geocode the address to get coordinates
            $coordinates = geocodeAddress($location);
            
            if ($coordinates) {
                $latitude = $coordinates['latitude'];
                $longitude = $coordinates['longitude'];
                
                // Hash password and insert customer
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO customers (name, email, password, location, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssdd", $name, $email, $hash, $location, $latitude, $longitude);
                
                if ($stmt->execute()) {
                    $message = "Customer registered successfully! Your coordinates have been automatically calculated.";
                } else {
                    $error = "Error: " . $conn->error;
                }
                $stmt->close();
            } else {
                $error = "Could not find coordinates for the provided address. Please check your address and try again.";
            }
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
            max-width: 450px;
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
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        .address-info {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #009688;
        }
        .address-info h4 {
            margin: 0 0 10px 0;
            color: #009688;
        }
        .address-info ul {
            margin: 0;
            padding-left: 20px;
        }
        .address-info li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Register as Customer</h2>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="address-info">
            <h4>📍 Address Guidelines</h4>
            <ul>
                <li>Enter your complete address including street, city, and state</li>
                <li>Coordinates will be automatically calculated</li>
                <li>Delivery is available within 5km of Kochi warehouse</li>
                <li>Example: "123 Main Street, Ernakulam, Kerala, India"</li>
            </ul>
        </div>

        <form method="post" id="registerForm" autocomplete="off">
            <div class="input-group">
                <input type="text" name="name" id="name" required placeholder=" " value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" />
                <label for="name">Full Name</label>
            </div>
            <div class="input-group">
                <input type="email" name="email" id="email" required placeholder=" " value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                <label for="email">Email</label>
            </div>
            <div class="input-group">
                <input type="password" name="password" id="password" required placeholder=" " />
                <label for="password">Password</label>
            </div>
            <div class="input-group">
                <input type="text" name="location" id="location" required placeholder=" " value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" />
                <label for="location">Complete Address</label>
                <div class="info-text">Enter your full address for automatic coordinate calculation</div>
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
                const name = form.name.value.trim();
                const email = form.email.value.trim();
                const password = form.password.value.trim();
                const location = form.location.value.trim();
                let messageBox = document.querySelector('.message');

                if (!name || !email || !password || !location) {
                    e.preventDefault();
                    if (!messageBox) {
                        messageBox = document.createElement('div');
                        messageBox.className = 'message error';
                        form.parentElement.insertBefore(messageBox, form);
                    }
                    messageBox.textContent = "Please fill in all required fields.";
                }
            });
        });
    </script>
</body>
</html>
