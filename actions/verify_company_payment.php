<?php
require_once dirname(dirname(__FILE__)) . '/config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Recycling Company') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $batchID = intval($_POST['batchID']);
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
        
        // Get payment record (from batch purchase) - also check fromUserID to ensure it's from this company
        $stmt = $conn->prepare("SELECT * FROM Payment WHERE batchID = ? AND fromUserID = ? AND status = 'pending'");
        $stmt->bind_param("ii", $batchID, $_SESSION['userID']);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        
        if ($payment) {
            // Check if the amount matches (allowing for minor floating point differences)
            // Compare against grossAmount if available, otherwise amount
            $expectedAmount = isset($payment['grossAmount']) ? $payment['grossAmount'] : $payment['amount'];
            if (abs($paymentAmount - $expectedAmount) > 0.01) {
                error_log("Amount mismatch for batchID {$batchID}: Paystack amount {$paymentAmount}, Expected amount {$expectedAmount}");
                echo json_encode(['success' => false, 'message' => 'Payment amount mismatch. Please contact support.']);
                exit();
            }

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
                $description = "Payment to aggregator for batch #" . $batchID;
                $paystackStmt->bind_param("idsss", $_SESSION['userID'], $paymentAmount, $reference, $reference, $description);
                $paystackStmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Payment verified and completed successfully']);
            } else {
                error_log("Failed to update payment record for batchID {$batchID}: " . $updateStmt->error);
                echo json_encode(['success' => false, 'message' => 'Failed to update payment record']);
            }
        } else {
            error_log("Payment record not found for batchID {$batchID} and companyID {$_SESSION['userID']}");
            echo json_encode(['success' => false, 'message' => 'Payment record not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

