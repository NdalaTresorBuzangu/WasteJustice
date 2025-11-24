<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/aggregator_class.php';

if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Aggregator') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $batchID = intval($_POST['batchID']);
    $aggregatorID = $_SESSION['userID'];
    $companyID = intval($_POST['companyID']);
    
    $aggregator = new AggregatorClass($conn);
    $result = $aggregator->sellBatchToCompany($batchID, $aggregatorID, $companyID);
    
    if ($result['success']) {
        header("Location: " . VIEWS_URL . "/aggregator/dashboard.php?success=sold&salePrice=" . $result['salePrice']);
    } else {
        header("Location: " . VIEWS_URL . "/aggregator/dashboard.php?error=" . urlencode($result['message']));
    }
    exit();
}

header("Location: " . VIEWS_URL . "/aggregator/dashboard.php");
exit();
?>

