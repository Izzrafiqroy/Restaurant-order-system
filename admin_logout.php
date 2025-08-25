
<?php
session_start();

// Clear all admin session data
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_username']);

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: admin_login.php");
exit;
?>