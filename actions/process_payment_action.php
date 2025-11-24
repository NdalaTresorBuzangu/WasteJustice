<?php
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['userID'])) {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

// Process payment (called when aggregator accepts delivery)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['collectionID'])) {
    $collectionID = intval($_POST['collectionID']);
    $paymentMethod = $_POST['paymentMethod'] ?? 'Mobile Money';
    $mobileMoneyNumber = $_POST['mobileMoneyNumber'] ?? '';
    $referenceNumber = $_POST['referenceNumber'] ?? '';
    
    // Get payment record
    $stmt = $conn->prepare("SELECT * FROM Payment WHERE collectionID = ? AND status = 'pending'");
    $stmt->bind_param("i", $collectionID);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if ($payment) {
        // Update payment status to completed
        $updateStmt = $conn->prepare("
            UPDATE Payment 
            SET status = 'completed', 
                paymentMethod = ?, 
                mobileMoneyNumber = ?, 
                referenceNumber = ?,
                paidAt = NOW()
            WHERE paymentID = ?
        ");
        $updateStmt->bind_param("sssi", $paymentMethod, $mobileMoneyNumber, $referenceNumber, $payment['paymentID']);
        
        if ($updateStmt->execute()) {
            header("Location: " . VIEWS_URL . "/aggregator/dashboard.php?success=payment_processed");
        } else {
            header("Location: " . VIEWS_URL . "/aggregator/dashboard.php?error=payment_failed");
        }
    }
    exit();
}

header("Location: " . VIEWS_URL . "/aggregator/dashboard.php");
exit();
?>

