<?php
require_once '../session_manager.php';

// Logout only customer session (keep staff session if exists)
logout('customer');

// Redirect to customer login page
header("Location: login_customer.php");
exit;
?>