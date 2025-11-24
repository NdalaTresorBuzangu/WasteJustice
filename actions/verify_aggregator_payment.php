<?php
require_once dirname(dirname(__FILE__)) . '/config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Aggregator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $collectionID = intval($_POST['collectionID']);
    $reference = $_POST['reference'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    
    // Verify payment with Paystack
    $secretKey = 'sk_test_025d3d01dc59baf7db9080ac7cced762e22c795a';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/" . $reference);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $secretKey
    ]);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        echo json_encode(['success' => false, 'message' => 'Payment verification failed: ' . $err]);
        exit();
    }
    
    $result = json_decode($response, true);
    
    if ($result && $result['status'] == true && $result['data']['status'] == 'success') {
        // Payment verified - update payment record
        $paymentAmount = $result['data']['amount'] / 100; // Convert from pesewas to GHS
        
        // Get payment record
        $stmt = $conn->prepare("SELECT * FROM Payment WHERE collectionID = ? AND status = 'pending'");
        $stmt->bind_param("i", $collectionID);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        
        if ($payment) {
            // Update payment status
            $updateStmt = $conn->prepare("
                UPDATE Payment 
                SET status = 'completed', 
                    referenceNumber = ?,
                    paymentMethod = 'Paystack',
                    paidAt = NOW()
                WHERE paymentID = ?
            ");
            $updateStmt->bind_param("si", $reference, $payment['paymentID']);
            
            if ($updateStmt->execute()) {
                // Save Paystack payment record
                $paystackStmt = $conn->prepare("
                    INSERT INTO PaystackPayments (user_id, amount, reference, status, paystack_reference, payment_method, currency, description, verified_at)
                    VALUES (?, ?, ?, 'success', ?, 'Paystack', 'GHS', ?, NOW())
                    ON DUPLICATE KEY UPDATE status = 'success', verified_at = NOW()
                ");
                $description = "Payment to collector for collection #" . $collectionID;
                $paystackStmt->bind_param("idsss", $_SESSION['userID'], $paymentAmount, $reference, $reference, $description);
                $paystackStmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Payment verified and completed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update payment record']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Payment record not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

