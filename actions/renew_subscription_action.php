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
    $subscriptionID = intval($_POST['subscriptionID'] ?? 0);
    
    if (!$subscriptionID) {
        echo json_encode(['success' => false, 'message' => 'Subscription ID required']);
        exit();
    }
    
    $subscriptionController = new SubscriptionController($conn);
    $result = $subscriptionController->renewSubscription($userID, $subscriptionID);
    
    echo json_encode($result);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit();
?>

