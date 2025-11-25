<?php
session_start();
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session
header("Location: login.php?logout=true"); // Redirect to login with a logout notice
exit;
?>
