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
    
    $aggregator = new AggregatorClass($conn);
    $result = $aggregator->acceptDelivery($collectionID, $aggregatorID);
    
    // Return JSON response for AJAX calls
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

header("Location: " . VIEWS_URL . "/aggregator/dashboard.php");
exit();
?>

