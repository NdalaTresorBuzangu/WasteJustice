<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';

// Check if logged in
if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Waste Collector') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

$userName = $_SESSION['userName'];
$userID = $_SESSION['userID'];

// Get collector location
$collectorLocation = $conn->prepare("SELECT latitude, longitude, address FROM User WHERE userID = ?");
$collectorLocation->bind_param("i", $userID);
$collectorLocation->execute();
$location = $collectorLocation->get_result()->fetch_assoc();

// Get collection details if collectionID is provided
$collectionID = isset($_GET['collectionID']) ? intval($_GET['collectionID']) : null;
$collectionLocation = null;
$collectionPlasticType = null;
$collectionLat = null;
$collectionLng = null;

if ($collectionID) {
    // Get collection with coordinates (latitude/longitude may not exist in older schema)
    $collectionStmt = $conn->prepare("SELECT location, plasticTypeID, latitude, longitude FROM WasteCollection WHERE collectionID = ? AND collectorID = ?");
    $collectionStmt->bind_param("ii", $collectionID, $userID);
    $collectionStmt->execute();
    $collectionData = $collectionStmt->get_result()->fetch_assoc();
    if ($collectionData) {
        $collectionLocation = $collectionData['location'];
        $collectionPlasticType = $collectionData['plasticTypeID'];
        $collectionLat = isset($collectionData['latitude']) ? $collectionData['latitude'] : null;
        $collectionLng = isset($collectionData['longitude']) ? $collectionData['longitude'] : null;
    }
}

// Use collection coordinates if available (from GPS capture), otherwise fall back to user profile location
$collectorLat = $collectionLat ?? $location['latitude'] ?? null;
$collectorLng = $collectionLng ?? $location['longitude'] ?? null;

// Get all registered aggregators with active subscriptions and distance calculation using Haversine formula
// Show ALL subscribed aggregators, calculate distance for those with GPS coordinates
// Debug: Check if query is working
$debugQuery = $conn->query("
    SELECT COUNT(*) as total FROM User u
    JOIN AggregatorRegistration ar ON u.userID = ar.userID
    WHERE u.userRole = 'Aggregator' AND u.status = 'active'
");
$debugResult = $debugQuery->fetch_assoc();
$totalAggregators = $debugResult['total'] ?? 0;

// Check for subscribed aggregators - use simpler query that gets latest subscription per user
$subscribedQuery = $conn->query("
    SELECT COUNT(DISTINCT u.userID) as total 
    FROM User u
    JOIN AggregatorRegistration ar ON u.userID = ar.userID
    WHERE u.userRole = 'Aggregator' 
    AND u.status = 'active'
    AND u.address IS NOT NULL 
    AND u.address != ''
    AND TRIM(u.address) != ''
    AND EXISTS (
        SELECT 1 FROM Subscriptions s
        WHERE s.userID = u.userID
        AND s.paymentStatus = 'Success'
        AND s.isActive = 1
        AND (s.subscriptionEnd IS NULL OR s.subscriptionEnd >= CURDATE())
        AND s.subscriptionID = (
            SELECT MAX(s2.subscriptionID) 
            FROM Subscriptions s2 
            WHERE s2.userID = u.userID
        )
    )
");
$subscribedResult = $subscribedQuery->fetch_assoc();
$subscribedAggregators = $subscribedResult['total'] ?? 0;

if ($collectorLat && $collectorLng) {
    $aggregatorsQuery = "
        SELECT 
            u.userID, 
            u.userName, 
            u.userContact, 
            u.userEmail, 
            ar.businessName, 
            u.latitude, 
            u.longitude, 
            u.rating, 
            u.totalRatings, 
            u.address,
            CASE 
                WHEN u.latitude IS NOT NULL AND u.longitude IS NOT NULL 
                THEN (6371 * acos(
                    cos(radians(?)) * 
                    cos(radians(u.latitude)) * 
                    cos(radians(u.longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(u.latitude))
                ))
                ELSE NULL
            END AS distance
        FROM User u
        JOIN AggregatorRegistration ar ON u.userID = ar.userID
        WHERE u.userRole = 'Aggregator' 
        AND u.status = 'active'
        AND u.address IS NOT NULL 
        AND u.address != ''
        AND TRIM(u.address) != ''
        AND EXISTS (
            SELECT 1 FROM Subscriptions s
            WHERE s.userID = u.userID
            AND s.paymentStatus = 'Success'
            AND s.isActive = 1
            AND (s.subscriptionEnd IS NULL OR s.subscriptionEnd >= CURDATE())
            AND s.subscriptionID = (
                SELECT MAX(s2.subscriptionID) 
                FROM Subscriptions s2 
                WHERE s2.userID = u.userID
            )
        )
        ORDER BY 
            CASE WHEN u.latitude IS NOT NULL AND u.longitude IS NOT NULL THEN 0 ELSE 1 END,
            distance ASC,
            u.userName
    ";
    $stmt = $conn->prepare($aggregatorsQuery);
    if ($stmt) {
        $stmt->bind_param("ddd", $collectorLat, $collectorLng, $collectorLat);
        $stmt->execute();
        $aggregators = $stmt->get_result();
    } else {
        // Query failed, show error
        $aggregators = false;
        $queryError = $conn->error;
    }
} else {
    // If no location, just get aggregators without distance (only subscribed ones with addresses)
    $aggregators = $conn->query("
        SELECT DISTINCT 
            u.userID, 
            u.userName, 
            u.userContact, 
            u.userEmail, 
            ar.businessName, 
            u.latitude, 
            u.longitude, 
            u.rating, 
            u.totalRatings, 
            u.address,
            NULL AS distance
        FROM User u
        JOIN AggregatorRegistration ar ON u.userID = ar.userID
        WHERE u.userRole = 'Aggregator' 
        AND u.status = 'active'
        AND u.address IS NOT NULL 
        AND u.address != ''
        AND TRIM(u.address) != ''
        AND EXISTS (
            SELECT 1 FROM Subscriptions s
            WHERE s.userID = u.userID
            AND s.paymentStatus = 'Success'
            AND s.isActive = 1
            AND (s.subscriptionEnd IS NULL OR s.subscriptionEnd >= CURDATE())
            AND s.subscriptionID = (
                SELECT MAX(s2.subscriptionID) 
                FROM Subscriptions s2 
                WHERE s2.userID = u.userID
            )
        )
        ORDER BY u.userName
    ");
    if (!$aggregators) {
        $queryError = $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycling Companies - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🌍 <?php echo APP_NAME; ?></h1>
                <p>Waste Collector Dashboard</p>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo VIEWS_URL; ?>/collector/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo VIEWS_URL; ?>/collector/view_aggregators.php">View Aggregators</a></li>
                    <li><a href="<?php echo VIEWS_URL; ?>/collector/submit_waste.php">Submit Waste</a></li>
                    <li><a href="<?php echo ACTIONS_URL; ?>/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_GET['success']) && $_GET['success'] == 'uploaded'): ?>
            <div class="alert alert-success">
                ✓ Waste submitted successfully! Collection ID: #<?php echo htmlspecialchars($_GET['collectionID'] ?? ''); ?>. Now select an aggregator below to deliver your waste.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'assigned'): ?>
            <div class="alert alert-success">
                ✓ Aggregator assigned successfully! Wait for them to accept your delivery.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 style="color: var(--primary-green); margin: 0;">📍 Nearest Aggregators</h2>
            </div>
            <p style="margin-top: 1rem;">View all registered aggregators sorted by distance from your location. Compare prices, ratings, and distances to select the best aggregator for your waste.</p>
            
            <?php if ($collectorLat && $collectorLng): ?>
                <div style="margin-top: 1rem; padding: 1rem; background: linear-gradient(135deg, var(--very-light-green), #e8f5e9); border-radius: 0.5rem; border-left: 4px solid var(--primary-green);">
                    <strong>📍 Collection Location:</strong> 
                    <?php if ($collectionID && $collectionLat && $collectionLng): ?>
                        GPS coordinates captured (<?php echo number_format($collectionLat, 6); ?>, <?php echo number_format($collectionLng, 6); ?>)
                        <?php if ($collectionLocation): ?>
                            <br><small style="color: var(--gray);">Address: <?php echo htmlspecialchars($collectionLocation); ?></small>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php echo htmlspecialchars($location['address'] ?? 'Using registered location'); ?>
                    <?php endif; ?>
                    <br><small style="color: var(--gray);">Aggregators are sorted by distance from this location</small>
                </div>
            <?php else: ?>
                <div style="margin-top: 1rem; padding: 1rem; background: var(--orange); color: white; border-radius: 0.5rem;">
                    <strong>⚠️ Location Not Set:</strong> 
                    <?php if ($collectionID): ?>
                        GPS location was not captured for this collection. Please <a href="<?php echo VIEWS_URL; ?>/collector/submit_waste.php" style="color: white; text-decoration: underline;">submit a new collection</a> and click "Get My Location" to enable distance calculation.
                    <?php else: ?>
                        Please <a href="<?php echo VIEWS_URL; ?>/collector/submit_waste.php" style="color: white; text-decoration: underline;">submit a waste collection</a> and click "Get My Location" to see distances to aggregators.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($collectionID): ?>
                <div style="margin-top: 1rem; padding: 1rem; background: var(--very-light-green); border-radius: 0.5rem; border-left: 4px solid var(--primary-green);">
                    <strong>📋 Selecting aggregator for Collection #<?php echo htmlspecialchars($collectionID); ?></strong>
                    <?php if ($collectionLocation): ?>
                        <br><small style="color: var(--gray);">Collection Location: <?php echo htmlspecialchars($collectionLocation); ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (isset($queryError)): ?>
            <div class="alert alert-error">
                <strong>Database Error:</strong> <?php echo htmlspecialchars($queryError); ?>
                <br><small>Total aggregators in database: <?php echo $totalAggregators; ?>, Subscribed: <?php echo $subscribedAggregators; ?></small>
            </div>
        <?php endif; ?>
        
        <?php if ($totalAggregators > 0 && $subscribedAggregators == 0): ?>
            <div class="alert alert-warning">
                <strong>⚠️ No Subscribed Aggregators:</strong> There are <?php echo $totalAggregators; ?> aggregator(s) registered, but none have active subscriptions yet. 
                Aggregators must subscribe before they become visible to waste collectors.
                <?php 
                // Debug: Show subscription details
                $debugSubs = $conn->query("
                    SELECT u.userID, u.userName, ar.businessName, 
                           s.paymentStatus, s.isActive, s.subscriptionEnd, s.subscriptionID
                    FROM User u
                    JOIN AggregatorRegistration ar ON u.userID = ar.userID
                    LEFT JOIN Subscriptions s ON u.userID = s.userID
                    WHERE u.userRole = 'Aggregator' AND u.status = 'active'
                    ORDER BY s.subscriptionID DESC
                ");
                if ($debugSubs->num_rows > 0): ?>
                    <details style="margin-top: 1rem;">
                        <summary style="cursor: pointer; font-weight: bold;">🔍 Debug: View Subscription Details</summary>
                        <table style="margin-top: 0.5rem; width: 100%; font-size: 0.9rem;">
                            <tr>
                                <th>UserID</th><th>Name</th><th>Payment Status</th><th>isActive</th><th>End Date</th>
                            </tr>
                            <?php while ($debug = $debugSubs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $debug['userID']; ?></td>
                                    <td><?php echo htmlspecialchars($debug['businessName'] ?? $debug['userName']); ?></td>
                                    <td><?php echo htmlspecialchars($debug['paymentStatus'] ?? 'None'); ?></td>
                                    <td><?php echo $debug['isActive'] ? '1 (TRUE)' : '0 (FALSE)'; ?></td>
                                    <td><?php echo $debug['subscriptionEnd'] ?? 'NULL'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    </details>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($aggregators && $aggregators->num_rows > 0): ?>
            <div style="margin-bottom: 1.5rem; padding: 1rem; background: linear-gradient(135deg, #e3f2fd, #bbdefb); border-radius: 0.5rem; border-left: 4px solid #2196f3;">
                <strong>📍 Location-Based Matching:</strong> Aggregators are sorted by distance from your registered location. 
                <?php if ($collectorLat && $collectorLng): ?>
                    The nearest aggregator is shown first. Distance is calculated using GPS coordinates.
                <?php else: ?>
                    <a href="<?php echo VIEWS_URL; ?>/collector/dashboard.php" style="color: #1976d2; text-decoration: underline;">Update your location</a> to see distances.
                <?php endif; ?>
            </div>
            
            <div style="margin-bottom: 1.5rem; padding: 1rem; background: linear-gradient(135deg, #e8f5e9, #c8e6c9); border-radius: 0.5rem; border-left: 4px solid var(--primary-green);">
                <strong>💰 Competitive Pricing:</strong> Each aggregator has unique pricing for different plastic types. 
                Compare prices across aggregators to get the best deal for your waste. Prices are automatically generated and vary by aggregator.
            </div>
            
            <?php 
            $aggregatorCount = $aggregators->num_rows;
            $aggregators->data_seek(0); // Reset pointer for display
            ?>
            <div style="margin-bottom: 1rem; color: var(--gray);">
                <strong><?php echo $aggregatorCount; ?> aggregator<?php echo $aggregatorCount != 1 ? 's' : ''; ?> found</strong>
                <?php if ($collectorLat && $collectorLng): ?>
                    - Sorted by distance (nearest first)
                <?php endif; ?>
            </div>
            
            <?php while ($aggregator = $aggregators->fetch_assoc()): ?>
                <?php
                $distance = $aggregator['distance'] ?? null;
                $distanceKm = $distance ? number_format($distance, 2) : null;
                $distanceMeters = $distance ? number_format($distance * 1000, 0) : null;
                
                // Determine card border color based on distance (closer = greener)
                $borderColor = 'var(--primary-green)';
                if ($distance !== null) {
                    if ($distance < 5) {
                        $borderColor = '#4caf50'; // Very close - bright green
                    } elseif ($distance < 10) {
                        $borderColor = 'var(--primary-green)'; // Close - normal green
                    } elseif ($distance < 20) {
                        $borderColor = '#8bc34a'; // Medium - light green
                    } else {
                        $borderColor = '#cddc39'; // Far - yellow-green
                    }
                }
                ?>
                
                <div class="card" style="border-left: 4px solid <?php echo $borderColor; ?>; position: relative;">
                    <?php if ($distance !== null): ?>
                        <div style="position: absolute; top: 1rem; right: 1rem; background: linear-gradient(135deg, var(--primary-green), var(--dark-green)); color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            📍 <?php echo $distanceKm; ?> km
                            <?php if ($distance < 1): ?>
                                <br><small style="font-size: 0.8rem; opacity: 0.9;">(<?php echo $distanceMeters; ?> m)</small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <h3 style="color: var(--primary-green); margin-bottom: 1rem; padding-right: <?php echo $distance !== null ? '120px' : '0'; ?>;">
                        📦 <?php 
                        // Display business name properly
                        // If businessName exists and is reasonable, use it; otherwise use userName
                        $businessName = trim($aggregator['businessName'] ?? '');
                        $userName = trim($aggregator['userName'] ?? 'Aggregator');
                        
                        // Use businessName if it exists, is not empty, and is different from userName
                        // Also check if it's not too long (likely a description rather than a name)
                        if (!empty($businessName) && $businessName != $userName && strlen($businessName) <= 100) {
                            $displayName = $businessName;
                        } else {
                            // Fallback to userName
                            $displayName = $userName;
                        }
                        echo htmlspecialchars($displayName); 
                        ?>
                        <?php if ($aggregator['rating'] > 0): ?>
                            <span style="font-size: 0.9rem; font-weight: normal; color: var(--orange);">
                                ⭐ <?php echo number_format($aggregator['rating'], 1); ?>/5.0 (<?php echo $aggregator['totalRatings']; ?> reviews)
                            </span>
                        <?php endif; ?>
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <strong>📞 Contact:</strong> <?php echo htmlspecialchars($aggregator['userContact']); ?>
                        </div>
                        <div>
                            <strong>📍 Address:</strong> <?php echo htmlspecialchars($aggregator['address'] ?? 'Not provided'); ?>
                        </div>
                        <?php if ($distance !== null): ?>
                            <div>
                                <strong>🚗 Distance:</strong> 
                                <span style="color: var(--primary-green); font-weight: bold; font-size: 1.1rem;">
                                    <?php echo $distanceKm; ?> km away
                                    <?php if ($distance < 1): ?>
                                        (<?php echo $distanceMeters; ?> meters)
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div>
                                <strong>📍 Location:</strong> 
                                <span style="color: var(--gray);">Distance unavailable</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php 
                    // Get pricing for this aggregator
                    $aggregatorID = $aggregator['userID'];
                    $pricesQuery = "
                        SELECT pt.typeName, pt.plasticTypeID, ap.pricePerKg, ap.updatedAt
                        FROM AggregatorPricing ap
                        JOIN PlasticType pt ON ap.plasticTypeID = pt.plasticTypeID
                        WHERE ap.aggregatorID = $aggregatorID AND ap.isActive = TRUE
                        ORDER BY pt.typeName
                    ";
                    $prices = $conn->query($pricesQuery);
                    
                    if ($prices->num_rows > 0): 
                        // Highlight price for the collection's plastic type if available
                        $highlightType = $collectionPlasticType;
                    ?>
                        <h4 style="color: var(--dark); margin-top: 1.5rem; margin-bottom: 1rem;">
                            💰 Transparent Buying Prices
                            <span style="font-size: 0.85rem; font-weight: normal; color: var(--gray); margin-left: 0.5rem;">
                                (Unique pricing for this aggregator)
                            </span>
                        </h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Plastic Type</th>
                                    <th>Price per Kg</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $priceArray = [];
                                while ($price = $prices->fetch_assoc()) {
                                    $priceArray[] = $price;
                                }
                                // Sort by price (highest first) to show best deals
                                usort($priceArray, function($a, $b) {
                                    return $b['pricePerKg'] <=> $a['pricePerKg'];
                                });
                                foreach ($priceArray as $price): 
                                    $isHighlighted = ($highlightType && $price['plasticTypeID'] == $highlightType);
                                    // Color code: higher prices = greener, lower = more orange
                                    $priceColor = $price['pricePerKg'] >= 4.5 ? 'var(--primary-green)' : ($price['pricePerKg'] >= 3.5 ? '#8bc34a' : 'var(--orange)');
                                ?>
                                    <tr style="<?php echo $isHighlighted ? 'background: var(--very-light-green); font-weight: bold; border-left: 4px solid var(--primary-green);' : ''; ?>">
                                        <td>
                                            <?php echo htmlspecialchars($price['typeName']); ?>
                                            <?php if ($isHighlighted): ?>
                                                <span style="color: var(--primary-green);">← Your Collection</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: <?php echo $priceColor; ?>; font-weight: bold; font-size: 1.1rem;">
                                            GH₵ <?php echo number_format($price['pricePerKg'], 2); ?>/kg
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($price['updatedAt'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="color: var(--gray); padding: 1.5rem; background: linear-gradient(135deg, #fff3cd, #ffe69c); border-radius: 0.5rem; border-left: 4px solid var(--orange);">
                            <strong style="color: var(--orange); display: block; margin-bottom: 0.5rem;">⚠️ Pricing Not Available</strong>
                            <p style="margin: 0.5rem 0; color: var(--dark);">
                                This aggregator hasn't set up pricing yet. Please contact them directly to inquire about prices.
                            </p>
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: var(--gray);">
                                <strong>Contact:</strong> <?php echo htmlspecialchars($aggregator['userContact'] ?? $aggregator['userName'] ?? 'N/A'); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['collectionID'])): ?>
                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid var(--light-gray);">
                            <form method="POST" action="<?php echo ACTIONS_URL; ?>/assign_aggregator_action.php" style="margin: 0;">
                                <input type="hidden" name="collectionID" value="<?php echo htmlspecialchars($_GET['collectionID']); ?>">
                                <input type="hidden" name="aggregatorID" value="<?php echo $aggregatorID; ?>">
                                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 1rem; font-weight: bold;" onclick="return confirm('Assign this aggregator to Collection #<?php echo htmlspecialchars($_GET['collectionID']); ?>?')">
                                    ✅ Select This Aggregator
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">
                No aggregators registered yet. Please check back later.
            </div>
        <?php endif; ?>
        

        <div style="margin-top: 2rem; text-align: center;">
            <a href="<?php echo VIEWS_URL; ?>/collector/submit_waste.php" class="btn btn-primary">
                ♻️ Upload Plastic Waste
            </a>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 <?php echo APP_NAME; ?>. Building a cleaner Ghana together.</p>
        <p style="margin-top: 0.5rem;">Fair • Transparent • Connected</p>
    </footer>
</body>
</html>



