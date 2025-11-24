<?php
require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once __DIR__ . '/../controllers/subscription_controller.php';
require_once __DIR__ . '/../controllers/role_controller.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$userID = $_SESSION['userID'];
$userRole = $_SESSION['userRole'];

$subscriptionController = new SubscriptionController($conn);
$roleController = new RoleController($conn);

// Get subscription info
$subscription = $subscriptionController->hasActiveSubscription($userID);

// Get expiry notice
$expiryNotice = $roleController->getExpiryNotice($userID);

echo json_encode([
    'success' => true,
    'subscription' => $subscription,
    'expiryNotice' => $expiryNotice
]);
?>

