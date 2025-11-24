<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';

// Check if logged in as admin
if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Admin') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['userID'])) {
    $userID = intval($_POST['userID']);
    
    // Update user status to active
    $stmt = $conn->prepare("UPDATE User SET status = 'active' WHERE userID = ?");
    $stmt->bind_param("i", $userID);
    
    if ($stmt->execute()) {
        header("Location: " . VIEWS_URL . "/admin/dashboard.php?success=approved&userID=" . $userID);
    } else {
        header("Location: " . VIEWS_URL . "/admin/dashboard.php?error=" . urlencode('Failed to approve user'));
    }
    exit();
}

header("Location: " . VIEWS_URL . "/admin/dashboard.php");
exit();
?>

