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
        $stmt = $conn->prepare("SELECT * FROM Subscriptions WHERE subscriptionID = ?");
        $stmt->bind_param("i", $subscriptionID);
        $stmt->execute();
        $subscription = $stmt->get_result()->fetch_assoc();
        
        if (!$subscription) {
            throw new Exception('Subscription not found');
        }
        
        // Approve subscription (set payment status to Success and activate)
        $updateStmt = $conn->prepare("
            UPDATE Subscriptions 
            SET paymentStatus = 'Success', 
                isActive = 1, 
                paymentDate = NOW()
            WHERE subscriptionID = ?
        ");
        $updateStmt->bind_param("i", $subscriptionID);
        $updateStmt->execute();
        
        // Update user subscription status
        $updateUserStmt = $conn->prepare("
            UPDATE User 
            SET subscription_status = 'active', 
                subscription_expires = ?
            WHERE userID = ?
        ");
        $subscriptionEnd = $subscription['subscriptionEnd'] ?? date('Y-m-d', strtotime('+1 month'));
        $updateUserStmt->bind_param("si", $subscriptionEnd, $subscription['userID']);
        $updateUserStmt->execute();
        
        $conn->commit();
        
        header("Location: " . VIEWS_URL . "/admin/subscriptions.php?success=" . urlencode("Subscription #{$subscriptionID} approved successfully"));
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: " . VIEWS_URL . "/admin/subscriptions.php?error=" . urlencode($e->getMessage()));
    }
    exit();
}

header("Location: " . VIEWS_URL . "/admin/subscriptions.php");
exit();
?>

