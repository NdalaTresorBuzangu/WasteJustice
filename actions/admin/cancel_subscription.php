<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';

// Check if logged in as admin
if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Admin') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subscriptionID'])) {
    $subscriptionID = intval($_POST['subscriptionID']);
    
    $conn->begin_transaction();
    
    try {
        // Get subscription details
        $stmt = $conn->prepare("SELECT userID FROM Subscriptions WHERE subscriptionID = ?");
        $stmt->bind_param("i", $subscriptionID);
        $stmt->execute();
        $subscription = $stmt->get_result()->fetch_assoc();
        
        if (!$subscription) {
            throw new Exception('Subscription not found');
        }
        
        // Cancel subscription
        $updateStmt = $conn->prepare("
            UPDATE Subscriptions 
            SET isActive = 0
            WHERE subscriptionID = ?
        ");
        $updateStmt->bind_param("i", $subscriptionID);
        $updateStmt->execute();
        
        // Update user subscription status
        $updateUserStmt = $conn->prepare("
            UPDATE User 
            SET subscription_status = 'cancelled'
            WHERE userID = ?
        ");
        $updateUserStmt->bind_param("i", $subscription['userID']);
        $updateUserStmt->execute();
        
        $conn->commit();
        
        header("Location: " . VIEWS_URL . "/admin/subscriptions.php?success=" . urlencode("Subscription #{$subscriptionID} cancelled successfully"));
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: " . VIEWS_URL . "/admin/subscriptions.php?error=" . urlencode($e->getMessage()));
    }
    exit();
}

header("Location: " . VIEWS_URL . "/admin/subscriptions.php");
exit();
?>

