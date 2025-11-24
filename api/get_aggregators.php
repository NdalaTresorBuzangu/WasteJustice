<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/collector_class.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Waste Collector') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$plasticTypeID = intval($_GET['plasticTypeID'] ?? 0);
$lat = floatval($_GET['lat'] ?? 0);
$lng = floatval($_GET['lng'] ?? 0);

if (!$plasticTypeID) {
    echo json_encode(['success' => false, 'message' => 'Plastic type required']);
    exit();
}

$collector = new CollectorClass($conn);
$result = $collector->getNearestAggregators($lat, $lng, $plasticTypeID);

$aggregators = [];
while ($row = $result->fetch_assoc()) {
    $aggregators[] = $row;
}

echo json_encode(['success' => true, 'aggregators' => $aggregators]);
?>

