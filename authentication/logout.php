<?php
require_once '../session_manager.php';

// Logout only staff session (keep customer session if exists)
logout('staff');

// Redirect to login page
header("Location: login.php");
exit;
?>