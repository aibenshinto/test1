<?php
session_name('ADMINSESSID');
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit;
?>