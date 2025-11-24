<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/subscription_controller.php';

if (!isset($_SESSION['userID'])) {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];
$userRole = $_SESSION['userRole'];

// Collectors don't need subscription - redirect
if ($userRole == 'Waste Collector') {
    header("Location: " . VIEWS_URL . "/collector/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $planName = $_POST['planName'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $paymentMethod = $_POST['paymentMethod'] ?? 'Mobile Money';
    $mobileMoneyNumber = $_POST['mobileMoneyNumber'] ?? '';
    $referenceNumber = $_POST['referenceNumber'] ?? '';
    $freeTrial = isset($_POST['freeTrial']) && $_POST['freeTrial'] == '1';
    
    // Validate inputs
    if (empty($planName)) {
        header("Location: " . VIEWS_URL . "/subscription.php?error=" . urlencode('Please select a plan'));
        exit();
    }
    
    if (!$freeTrial && empty($referenceNumber)) {
        header("Location: " . VIEWS_URL . "/subscription.php?error=" . urlencode('Payment reference number required'));
        exit();
    }
    
    // Standard E-commerce: Payment verification flow
    // For free trials: Auto-activate
    // For paid subscriptions: Create as Pending, require payment verification
    
    $subscriptionController = new SubscriptionController($conn);
    $result = $subscriptionController->createSubscription(
        $userID,
        $planName,
        $amount,
        $paymentMethod,
        $mobileMoneyNumber,
        $referenceNumber,
        $freeTrial
    );
    
    if ($result['success']) {
        if ($freeTrial) {
            // Free trial: Activate immediately
            $_SESSION['subscription_status'] = 'trial';
            $_SESSION['subscription_expires'] = $result['subscriptionEnd'];
            
            // Redirect to appropriate dashboard
            if ($userRole == 'Aggregator') {
                header("Location: " . VIEWS_URL . "/aggregator/dashboard.php?subscription=active");
            } elseif ($userRole == 'Recycling Company') {
                header("Location: " . VIEWS_URL . "/recycling/dashboard.php?subscription=active");
            }
        } elseif ($result['paymentStatus'] == 'Success' && $result['isActive'] == 1) {
            // Already approved (shouldn't happen for new paid subscriptions, but handle it)
            $_SESSION['subscription_status'] = 'active';
            $_SESSION['subscription_expires'] = $result['subscriptionEnd'];
            
            if ($userRole == 'Aggregator') {
                header("Location: " . VIEWS_URL . "/aggregator/dashboard.php?subscription=active");
            } elseif ($userRole == 'Recycling Company') {
                header("Location: " . VIEWS_URL . "/recycling/dashboard.php?subscription=active");
            }
        } else {
            // Paid subscription pending admin approval (Standard E-commerce practice)
            $_SESSION['subscription_status'] = 'pending';
            
            // Redirect with pending message
            $pendingMessage = "Subscription created successfully! Your subscription is pending admin approval. You will become visible to waste collectors once the admin verifies your payment and approves your subscription.";
            
            if ($userRole == 'Aggregator') {
                header("Location: " . VIEWS_URL . "/aggregator/dashboard.php?subscription=pending&message=" . urlencode($pendingMessage));
            } elseif ($userRole == 'Recycling Company') {
                header("Location: " . VIEWS_URL . "/recycling/dashboard.php?subscription=pending&message=" . urlencode($pendingMessage));
            }
        }
        exit();
    } else {
        header("Location: " . VIEWS_URL . "/subscription.php?error=" . urlencode($result['message']));
        exit();
    }
}

header("Location: " . VIEWS_URL . "/subscription.php");
exit();
?>

