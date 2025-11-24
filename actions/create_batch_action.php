<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/aggregator_class.php';

if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Aggregator') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plasticTypeID = intval($_POST['plasticTypeID']);
    $collectionIDs = $_POST['collectionIDs'];
    $aggregatorID = $_SESSION['userID'];
    
    $aggregator = new AggregatorClass($conn);
    $result = $aggregator->createBatch($aggregatorID, $plasticTypeID, $collectionIDs);
    
    if ($result['success']) {
        header("Location: " . VIEWS_URL . "/aggregator/dashboard.php?success=batch_created&batchID=" . $result['batchID']);
    } else {
        header("Location: " . VIEWS_URL . "/aggregator/dashboard.php?error=" . urlencode($result['message']));
    }
    exit();
}

header("Location: " . VIEWS_URL . "/aggregator/dashboard.php");
exit();
?>

