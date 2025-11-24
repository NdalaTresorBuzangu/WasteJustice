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
    $weight = isset($_POST['weight']) ? floatval($_POST['weight']) : null;
    $plasticTypeID = isset($_POST['plasticTypeID']) ? intval($_POST['plasticTypeID']) : null;
    $location = $_POST['location'] ?? null;
    
    $collector = new CollectorClass($conn);
    $result = $collector->updateWaste($collectionID, $collectorID, $weight, $plasticTypeID, $location);
    
    if ($result['success']) {
        header("Location: " . VIEWS_URL . "/collector/dashboard.php?success=updated");
    } else {
        header("Location: " . VIEWS_URL . "/collector/dashboard.php?error=" . urlencode($result['message']));
    }
    exit();
}

header("Location: " . VIEWS_URL . "/collector/dashboard.php");
exit();
?>

