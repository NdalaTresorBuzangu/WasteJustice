<?php
require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/classes/collector_class.php';

if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Waste Collector') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $collectorID = $_SESSION['userID'];
    
    // Verify collector exists in database
    $userCheck = $conn->prepare("SELECT userID FROM User WHERE userID = ? AND userRole = 'Waste Collector'");
    $userCheck->bind_param("i", $collectorID);
    $userCheck->execute();
    if ($userCheck->get_result()->num_rows === 0) {
        header("Location: " . VIEWS_URL . "/auth/login.php?error=" . urlencode('Session expired. Please log in again.'));
        exit();
    }
    
    $plasticTypeID = intval($_POST['plasticTypeID']);
    $weight = floatval($_POST['weight']);
    $location = $_POST['location'];
    $notes = $_POST['notes'] ?? '';
    $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    $aggregatorID = !empty($_POST['aggregatorID']) ? intval($_POST['aggregatorID']) : null;
    $photoPath = '';
    
    // Handle photo upload if provided
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $uploadDir = '../uploads/waste/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $photoPath = $uploadDir . time() . '_' . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
    }
    
    $collector = new CollectorClass($conn);
    $result = $collector->addWaste($collectorID, $plasticTypeID, $weight, $location, $notes, $photoPath, $latitude, $longitude, $aggregatorID);
    
    if ($result['success']) {
        // If aggregator was pre-selected, redirect to dashboard with success
        if ($aggregatorID) {
            header("Location: " . VIEWS_URL . "/collector/dashboard.php?success=uploaded&collectionID=" . $result['collectionID']);
        } else {
            // Otherwise, redirect to view aggregators to select one
            header("Location: " . VIEWS_URL . "/collector/view_aggregators.php?success=uploaded&collectionID=" . $result['collectionID']);
        }
    } else {
        $errorUrl = VIEWS_URL . "/collector/submit_waste.php?error=" . urlencode($result['message']);
        if ($aggregatorID) {
            $errorUrl .= "&aggregatorID=" . $aggregatorID;
        }
        header("Location: " . $errorUrl);
    }
    exit();
}

header("Location: " . VIEWS_URL . "/collector/dashboard.php");
exit();
?>

