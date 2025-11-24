<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/collector_class.php';

if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Waste Collector') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $collectionID = intval($_POST['collectionID']);
    $collectorID = $_SESSION['userID'];
    $aggregatorID = intval($_POST['aggregatorID']);
    
    $collector = new CollectorClass($conn);
    $result = $collector->assignAggregator($collectionID, $collectorID, $aggregatorID);
    
    if ($result['success']) {
        header("Location: " . VIEWS_URL . "/collector/view_aggregators.php?success=assigned&collectionID=" . $collectionID);
    } else {
        header("Location: " . VIEWS_URL . "/collector/view_aggregators.php?error=" . urlencode($result['message']) . "&collectionID=" . $collectionID);
    }
    exit();
}

header("Location: " . VIEWS_URL . "/collector/dashboard.php");
exit();
?>

