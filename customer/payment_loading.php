<?php
require_once '../session_manager.php';
requireCustomer();

$order_id = isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment - Synopsis</title>
    <link rel="stylesheet" href="../css/payment_loading_style.css">
</head>
<body>
    <div class="payment-container">
        <div class="animation-window">
            <!-- The CSS animation is replaced with this video tag -->
            <video id="rocketVideo" width="100%" height="100%" autoplay muted playsinline>
                <source src="../uploads/Rocket Launch.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
        </div>

        <h1 id="status-text">Processing Payment...</h1>
        <p class="subtext">Your order #<?php echo $order_id; ?> is being confirmed.</p>
    </div>

    <script>
        const container = document.querySelector('.payment-container');
        const statusText = document.getElementById('status-text');
        const rocketVideo = document.getElementById('rocketVideo');

        // Listen for when the video has finished playing
        rocketVideo.onended = function() {
            // Step 1: Show the success state
            container.classList.add('success');
            statusText.textContent = 'Payment Successful!';
            
            // Step 2: Wait on the success screen, then redirect
            setTimeout(function() {
                window.location.href = 'customer_orders.php?message=Order #<?php echo $order_id; ?> placed successfully!';
            }, 2000); // 2 second delay on success screen
        };
    </script>
</body>
</html>
