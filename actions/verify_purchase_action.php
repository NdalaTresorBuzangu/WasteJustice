<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/recycling_class.php';

if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Recycling Company') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $batchID = intval($_POST['batchID']);
    $companyID = $_SESSION['userID'];
    $qualityVerified = isset($_POST['qualityVerified']) && $_POST['qualityVerified'] == '1';
    
    $recycling = new RecyclingClass($conn);
    $result = $recycling->verifyAndPurchase($batchID, $companyID, $qualityVerified);
    
    if ($result['success']) {
        header("Location: " . VIEWS_URL . "/recycling/dashboard.php?success=purchased&salePrice=" . $result['salePrice']);
    } else {
        header("Location: " . VIEWS_URL . "/recycling/dashboard.php?error=" . urlencode($result['message']));
    }
    exit();
}

header("Location: " . VIEWS_URL . "/recycling/dashboard.php");
exit();
?>

