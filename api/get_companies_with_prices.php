<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/aggregator_class.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Aggregator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$plasticTypeID = intval($_GET['plasticTypeID'] ?? 0);

if (!$plasticTypeID) {
    echo json_encode(['success' => false, 'message' => 'Plastic type required']);
    exit();
}

$aggregatorClass = new AggregatorClass($conn);
$companies = $aggregatorClass->getCompaniesWithPrices($plasticTypeID);

$companiesList = [];
while ($row = $companies->fetch_assoc()) {
    $companiesList[] = $row;
}

echo json_encode(['success' => true, 'companies' => $companiesList]);
?>

