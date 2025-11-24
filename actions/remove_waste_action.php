<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/collector_class.php';

if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Waste Collector') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

if (isset($_GET['collectionID'])) {
    $collectionID = intval($_GET['collectionID']);
    $collectorID = $_SESSION['userID'];
    
    $collector = new CollectorClass($conn);
    $result = $collector->removeWaste($collectionID, $collectorID);
    
    if ($result['success']) {
        header("Location: " . VIEWS_URL . "/collector/dashboard.php?success=removed");
    } else {
        header("Location: " . VIEWS_URL . "/collector/dashboard.php?error=" . urlencode($result['message']));
    }
    exit();
}

header("Location: " . VIEWS_URL . "/collector/dashboard.php");
exit();
?>

