<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$plasticTypeID = intval($_GET['plasticTypeID'] ?? 0);

if (!$plasticTypeID) {
    echo json_encode(['success' => false, 'message' => 'Plastic type required']);
    exit();
}

// Get all aggregator prices for this plastic type
$stmt = $conn->prepare("
    SELECT 
        ap.pricePerKg,
        ar.businessName as aggregatorName,
        u.rating,
        u.totalRatings
    FROM AggregatorPricing ap
    JOIN User u ON ap.aggregatorID = u.userID
    JOIN AggregatorRegistration ar ON u.userID = ar.userID
    WHERE ap.plasticTypeID = ? AND ap.isActive = TRUE
    ORDER BY ap.pricePerKg DESC
");
$stmt->bind_param("i", $plasticTypeID);
$stmt->execute();
$result = $stmt->get_result();

$prices = [];
while ($row = $result->fetch_assoc()) {
    $prices[] = $row;
}

echo json_encode(['success' => true, 'prices' => $prices]);
?>

