<?php
require_once dirname(dirname(__FILE__)) . '/config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Recycling Company') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['batchID'])) {
    echo json_encode(['success' => false, 'message' => 'Batch ID required']);
    exit();
}

$batchID = intval($_GET['batchID']);

// Get aggregator details from batch
$stmt = $conn->prepare("
    SELECT u.userEmail, u.userName, u.userContact, ar.businessName
    FROM AggregatorBatch ab
    JOIN User u ON ab.aggregatorID = u.userID
    LEFT JOIN AggregatorRegistration ar ON u.userID = ar.userID
    WHERE ab.batchID = ?
");
$stmt->bind_param("i", $batchID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $aggregator = $result->fetch_assoc();
    // Get aggregatorID from batch
    $batchStmt = $conn->prepare("SELECT aggregatorID FROM AggregatorBatch WHERE batchID = ?");
    $batchStmt->bind_param("i", $batchID);
    $batchStmt->execute();
    $batchResult = $batchStmt->get_result();
    $batch = $batchResult->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'aggregatorEmail' => $aggregator['userEmail'],
        'aggregatorName' => $aggregator['businessName'] ?: $aggregator['userName'],
        'aggregatorContact' => $aggregator['userContact'],
        'aggregatorID' => $batch['aggregatorID'] ?? null
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Aggregator not found']);
}
?>

