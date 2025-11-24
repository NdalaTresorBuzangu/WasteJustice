<?php
session_start();
session_unset();
session_destroy();
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
header("Location: " . VIEWS_URL . "/auth/login.php");
exit();
?>
