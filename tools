<?php
require_once 'config/config.php';

echo "<h2>Debug: Waste Collections</h2>";

// Get all collections
$allCollections = $conn->query("
    SELECT 
        wc.collectionID,
        wc.collectorID,
        wc.aggregatorID,
        wc.statusID,
        wc.weight,
        wc.collectionDate,
        u1.userName as collectorName,
        u2.userName as aggregatorName,
        pt.typeName
    FROM WasteCollection wc
    LEFT JOIN User u1 ON wc.collectorID = u1.userID
    LEFT JOIN User u2 ON wc.aggregatorID = u2.userID
    LEFT JOIN PlasticType pt ON wc.plasticTypeID = pt.plasticTypeID
    ORDER BY wc.collectionID DESC
    LIMIT 20
");

echo "<h3>All Collections (Last 20):</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Collection ID</th><th>Collector</th><th>Aggregator ID</th><th>Aggregator Name</th><th>Status ID</th><th>Weight</th><th>Plastic Type</th><th>Date</th></tr>";

while ($row = $allCollections->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['collectionID'] . "</td>";
    echo "<td>" . $row['collectorName'] . " (ID: " . $row['collectorID'] . ")</td>";
    echo "<td>" . ($row['aggregatorID'] ?? 'NULL') . "</td>";
    echo "<td>" . ($row['aggregatorName'] ?? 'Not Assigned') . "</td>";
    echo "<td>" . $row['statusID'] . "</td>";
    echo "<td>" . $row['weight'] . " kg</td>";
    echo "<td>" . $row['typeName'] . "</td>";
    echo "<td>" . $row['collectionDate'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Get aggregators
$aggregators = $conn->query("
    SELECT userID, userName, userRole 
    FROM User 
    WHERE userRole = 'Aggregator' 
    ORDER BY userID DESC
    LIMIT 10
");

echo "<h3>Aggregators:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Aggregator ID</th><th>Name</th><th>Collections Assigned</th></tr>";

while ($agg = $aggregators->fetch_assoc()) {
    $count = $conn->query("
        SELECT COUNT(*) as total 
        FROM WasteCollection 
        WHERE aggregatorID = " . $agg['userID'] . " AND statusID = 1
    ")->fetch_assoc()['total'];
    
    echo "<tr>";
    echo "<td>" . $agg['userID'] . "</td>";
    echo "<td>" . $agg['userName'] . "</td>";
    echo "<td>" . $count . " pending</td>";
    echo "</tr>";
}
echo "</table>";

// Get unassigned collections
$unassigned = $conn->query("
    SELECT COUNT(*) as total 
    FROM WasteCollection 
    WHERE aggregatorID IS NULL AND statusID = 1
")->fetch_assoc()['total'];

echo "<h3>Unassigned Collections (statusID=1, aggregatorID=NULL): " . $unassigned . "</h3>";
?>

