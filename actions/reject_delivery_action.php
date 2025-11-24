<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/aggregator_class.php';

if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Aggregator') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $collectionID = intval($_POST['collectionID']);
    $aggregatorID = $_SESSION['userID'];
    $reason = $_POST['reason'] ?? '';
    
    $aggregator = new AggregatorClass($conn);
    $result = $aggregator->rejectDelivery($collectionID, $aggregatorID);
    
    if ($result['success']) {
        header("Location: " . VIEWS_URL . "/aggregator/dashboard.php?success=rejected");
    } else {
        header("Location: " . VIEWS_URL . "/aggregator/dashboard.php?error=" . urlencode($result['message']));
    }
    exit();
}

header("Location: " . VIEWS_URL . "/aggregator/dashboard.php");
exit();
?>

