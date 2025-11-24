<?php
require_once dirname(dirname(__FILE__)) . '/config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Aggregator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['collectionID'])) {
    echo json_encode(['success' => false, 'message' => 'Collection ID required']);
    exit();
}

$collectionID = intval($_GET['collectionID']);

// Get collector details from collection
$stmt = $conn->prepare("
    SELECT u.userEmail, u.userName, u.userContact
    FROM WasteCollection wc
    JOIN User u ON wc.collectorID = u.userID
    WHERE wc.collectionID = ?
");
$stmt->bind_param("i", $collectionID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $collector = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'collectorEmail' => $collector['userEmail'],
        'collectorName' => $collector['userName'],
        'collectorContact' => $collector['userContact']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Collector not found']);
}
?>

