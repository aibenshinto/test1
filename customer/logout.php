<?php
session_name('CUSTOMERSESSID');
session_start();
session_unset();
session_destroy();
header("Location: login_customer.php");
exit;
?>