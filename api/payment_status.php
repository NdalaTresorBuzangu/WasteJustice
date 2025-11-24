<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['paid' => false, 'message' => 'Not logged in']);
    exit();
}

$collectionID = intval($_GET['collectionID'] ?? 0);

if (!$collectionID) {
    echo json_encode(['paid' => false, 'message' => 'Collection ID required']);
    exit();
}

$userID = $_SESSION['userID'];

// Check payment status for this collection
$stmt = $conn->prepare("
    SELECT * FROM Payment 
    WHERE collectionID = ? AND toUserID = ? 
    ORDER BY paymentID DESC LIMIT 1
");
$stmt->bind_param("ii", $collectionID, $userID);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if ($payment && $payment['status'] == 'completed') {
    echo json_encode([
        'paid' => true,
        'payment' => $payment
    ]);
} else {
    echo json_encode([
        'paid' => false,
        'payment' => $payment
    ]);
}
?>

