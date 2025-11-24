<?php
require_once dirname(dirname(__FILE__)) . '/config/config.php';

header('Content-Type: application/json');

// Check if reference is provided
if (!isset($_GET['reference'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Payment reference is required'
    ]);
    exit;
}

$reference = $_GET['reference'];

// Paystack secret key
$secretKey = 'sk_test_025d3d01dc59baf7db9080ac7cced762e22c795a';

// Initialize cURL
$curl = curl_init();

// Set cURL options
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $reference,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $secretKey,
        "Content-Type: application/json"
    ]
]);

// Execute request
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo json_encode([
        'status' => 'error',
        'message' => 'cURL Error: ' . $err
    ]);
    exit;
}

// Decode response
$result = json_decode($response, true);

if ($result['status'] === true && $result['data']['status'] === 'success') {
    // Payment verified successfully
    $paymentData = $result['data'];
    
    // Extract payment details
    $amount = $paymentData['amount'] / 100; // Convert from pesewas to GHS
    $paystackReference = $paymentData['reference'];
    $customerEmail = $paymentData['customer']['email'];
    $paymentMethod = $paymentData['channel'] ?? 'card';
    $paidAt = $paymentData['paid_at'] ?? date('Y-m-d H:i:s');
    
    // Get user ID from metadata or find by email
    $userId = null;
    if (isset($paymentData['metadata']['custom_fields'])) {
        foreach ($paymentData['metadata']['custom_fields'] as $field) {
            if ($field['variable_name'] === 'user_id') {
                $userId = intval($field['value']);
                break;
            }
        }
    }
    
    // If user ID not in metadata, try to find by email
    if (!$userId) {
        $userStmt = $conn->prepare("SELECT userID FROM User WHERE userEmail = ?");
        $userStmt->bind_param("s", $customerEmail);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        if ($userResult->num_rows > 0) {
            $userId = $userResult->fetch_assoc()['userID'];
        }
    }
    
    if (!$userId) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
        exit;
    }
    
    // Check if payment already exists
    $checkStmt = $conn->prepare("SELECT payment_id FROM PaystackPayments WHERE reference = ? OR paystack_reference = ?");
    $checkStmt->bind_param("ss", $reference, $paystackReference);
    $checkStmt->execute();
    $existingPayment = $checkStmt->get_result()->fetch_assoc();
    
    if ($existingPayment) {
        // Update existing payment
        $updateStmt = $conn->prepare("
            UPDATE PaystackPayments 
            SET status = 'success', 
                paystack_reference = ?, 
                payment_method = ?,
                verified_at = NOW()
            WHERE reference = ? OR paystack_reference = ?
        ");
        $updateStmt->bind_param("ssss", $paystackReference, $paymentMethod, $reference, $paystackReference);
        $updateStmt->execute();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Payment already verified',
            'payment_id' => $existingPayment['payment_id']
        ]);
    } else {
        // Insert new payment record
        $description = 'Payment via Paystack';
        if (isset($paymentData['metadata']['custom_fields'])) {
            foreach ($paymentData['metadata']['custom_fields'] as $field) {
                if ($field['variable_name'] === 'description') {
                    $description = $field['value'];
                    break;
                }
            }
        }
        
        $insertStmt = $conn->prepare("
            INSERT INTO PaystackPayments 
            (user_id, amount, reference, status, paystack_reference, payment_method, currency, description, verified_at) 
            VALUES (?, ?, ?, 'success', ?, ?, 'GHS', ?, NOW())
        ");
        $insertStmt->bind_param("idssss", $userId, $amount, $reference, $paystackReference, $paymentMethod, $description);
        
        if ($insertStmt->execute()) {
            $paymentId = $conn->insert_id;
            
            // Here you can add logic to unlock features, send notifications, etc.
            // For example, update user subscription status, send email, etc.
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Payment verified and recorded successfully',
                'payment_id' => $paymentId,
                'amount' => $amount,
                'reference' => $reference
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to save payment record: ' . $conn->error
            ]);
        }
    }
} else {
    // Payment verification failed
    $errorMessage = $result['message'] ?? 'Payment verification failed';
    
    // Try to update payment status to failed if it exists
    $updateStmt = $conn->prepare("
        UPDATE PaystackPayments 
        SET status = 'failed' 
        WHERE reference = ?
    ");
    $updateStmt->bind_param("s", $reference);
    $updateStmt->execute();
    
    echo json_encode([
        'status' => 'error',
        'message' => $errorMessage
    ]);
}

