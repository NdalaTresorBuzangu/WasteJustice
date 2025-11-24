<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/subscription_controller.php';

if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userID = $_SESSION['userID'];
    
    $subscriptionController = new SubscriptionController($conn);
    $result = $subscriptionController->cancelSubscription($userID);
    
    echo json_encode($result);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit();
?>

