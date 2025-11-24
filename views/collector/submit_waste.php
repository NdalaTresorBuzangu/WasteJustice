<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';

// Check if logged in
if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Waste Collector') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

$userName = $_SESSION['userName'];
$userID = $_SESSION['userID'];

// Get collector location for distance calculation (fallback, but we'll use GPS from page)
$collectorLocation = $conn->prepare("SELECT latitude, longitude, address FROM User WHERE userID = ?");
$collectorLocation->bind_param("i", $userID);
$collectorLocation->execute();
$location = $collectorLocation->get_result()->fetch_assoc();

// Get selected aggregator if provided
$selectedAggregatorID = isset($_GET['aggregatorID']) ? intval($_GET['aggregatorID']) : null;
$selectedAggregator = null;
$selectedAggregatorPricing = [];

if ($selectedAggregatorID) {
    // Get aggregator details (only if they have active subscription)
    $aggStmt = $conn->prepare("
        SELECT u.userID, u.userName, u.userContact, u.address, u.latitude, u.longitude, 
               ar.businessName, u.rating, u.totalRatings
        FROM User u
        JOIN AggregatorRegistration ar ON u.userID = ar.userID
        INNER JOIN Subscriptions s ON u.userID = s.userID
        WHERE u.userID = ? 
        AND u.userRole = 'Aggregator' 
        AND u.status = 'active'
        AND s.paymentStatus = 'Success'
        AND s.isActive = 1
        AND (s.subscriptionEnd IS NULL OR s.subscriptionEnd >= CURDATE())
    ");
    $aggStmt->bind_param("i", $selectedAggregatorID);
    $aggStmt->execute();
    $selectedAggregator = $aggStmt->get_result()->fetch_assoc();
    
    if ($selectedAggregator) {
        // Get pricing for this aggregator
        $priceStmt = $conn->prepare("
            SELECT pt.plasticTypeID, pt.typeName, ap.pricePerKg
            FROM AggregatorPricing ap
            JOIN PlasticType pt ON ap.plasticTypeID = pt.plasticTypeID
            WHERE ap.aggregatorID = ? AND ap.isActive = TRUE
            ORDER BY pt.typeName
        ");
        $priceStmt->bind_param("i", $selectedAggregatorID);
        $priceStmt->execute();
        $prices = $priceStmt->get_result();
        while ($price = $prices->fetch_assoc()) {
            $selectedAggregatorPricing[$price['plasticTypeID']] = $price;
        }
    }
}

// Get all plastic types
$plasticTypes = $conn->query("SELECT * FROM PlasticType ORDER BY typeName");

// Get all aggregators with active subscriptions (distance will be calculated client-side using GPS)
// Only show aggregators with addresses (address is mandatory for aggregators)
$nearestAggregators = $conn->query("
    SELECT DISTINCT 
        u.userID, 
        u.userName, 
        u.userContact,
        ar.businessName, 
        u.address,
        u.latitude, 
        u.longitude, 
        u.rating, 
        u.totalRatings
    FROM User u
    JOIN AggregatorRegistration ar ON u.userID = ar.userID
    INNER JOIN Subscriptions s ON u.userID = s.userID
    WHERE u.userRole = 'Aggregator' 
    AND u.status = 'active'
    AND u.address IS NOT NULL 
    AND u.address != ''
    AND TRIM(u.address) != ''
    AND s.paymentStatus = 'Success'
    AND s.isActive = 1
    AND (s.subscriptionEnd IS NULL OR s.subscriptionEnd >= CURDATE())
    ORDER BY 
        CASE WHEN u.latitude IS NOT NULL AND u.longitude IS NOT NULL THEN 0 ELSE 1 END,
        u.userName
");

// Prepare aggregators array for JavaScript
$aggregatorsArray = [];
if ($nearestAggregators && $nearestAggregators->num_rows > 0) {
    $nearestAggregators->data_seek(0);
    while ($agg = $nearestAggregators->fetch_assoc()) {
        // Clean business name - remove weird text
        $businessName = trim($agg['businessName'] ?? '');
        if (empty($businessName) || strlen($businessName) > 100 || 
            stripos($businessName, 'former') !== false || 
            stripos($businessName, 'duolingo') !== false ||
            stripos($businessName, 'refugee') !== false) {
            $businessName = $agg['userName'];
        }
        
        $aggregatorsArray[] = [
            'userID' => (int)$agg['userID'],
            'businessName' => $businessName,
            'userName' => $agg['userName'],
            'userContact' => $agg['userContact'] ?: $agg['userName'],
            'address' => trim($agg['address'] ?: '') ?: 'Address not set',
            'latitude' => ($agg['latitude'] && $agg['latitude'] != 0) ? (float)$agg['latitude'] : null,
            'longitude' => ($agg['longitude'] && $agg['longitude'] != 0) ? (float)$agg['longitude'] : null,
            'rating' => (float)($agg['rating'] ?? 0),
            'totalRatings' => (int)($agg['totalRatings'] ?? 0)
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Waste - <?php echo APP_NAME; ?></title>
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
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                ✓ Waste submitted successfully! <a href="<?php echo VIEWS_URL; ?>/collector/view_aggregators.php" style="color: inherit; text-decoration: underline;">View nearest aggregators</a> to select where to deliver.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
            </div>
        <?php endif; ?>

        <?php if (!$selectedAggregatorID): ?>
        <div class="card">
            <div class="card-header">
                <h2 style="color: var(--primary-green); margin: 0;">📍 Step 1: Get Your Location</h2>
            </div>
            <p style="margin-top: 1rem;">First, click the button below to share your current location. This helps us find the nearest aggregators.</p>
            
            <!-- Step 1: Google Maps Location Capture -->
            <div id="step1Location" style="margin-top: 1.5rem;">
                <div style="padding: 2rem; text-align: center; background: linear-gradient(135deg, #e3f2fd, #bbdefb); border-radius: 0.75rem; margin-bottom: 1rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📍</div>
                    <h3 style="color: #1976d2; margin-bottom: 1rem;">Share Your Location</h3>
                    <p style="color: var(--gray-dark); margin-bottom: 1.5rem;">We need your location to find the nearest aggregators and calculate travel times.</p>
                    <button type="button" id="getLocationBtn" onclick="getMyLocationWithGoogleMaps()" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.2rem; font-weight: bold; cursor: pointer;">
                        📍 Get My Location
                    </button>
                    <div id="locationStatus" style="margin-top: 1.5rem; display: none;"></div>
                </div>
                <!-- Location Confirmation Container -->
                <div id="locationConfirmContainer" style="display: none; margin-top: 1rem;">
                    <div style="padding: 2rem; text-align: center; background: linear-gradient(135deg, #e8f5e9, #c8e6c9); border-radius: 0.75rem; border: 2px solid var(--primary-green);">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
                        <h3 style="color: var(--primary-green); margin-bottom: 1rem;">Location Confirmed!</h3>
                        <div id="locationDetails" style="background: white; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem; display: inline-block;">
                            <p style="margin: 0.5rem 0; color: var(--dark);"><strong>📍 Latitude:</strong> <span id="displayLat"></span></p>
                            <p style="margin: 0.5rem 0; color: var(--dark);"><strong>📍 Longitude:</strong> <span id="displayLng"></span></p>
                            <a href="#" id="viewOnMapsLink" target="_blank" class="btn btn-secondary" style="margin-top: 1rem; text-decoration: none; padding: 0.5rem 1rem; display: inline-block;">
                                🗺️ View on Google Maps
                            </a>
                        </div>
                        <p style="color: var(--gray); margin-bottom: 1.5rem;">Your location has been captured. Click below to see the nearest aggregators.</p>
                        <button type="button" onclick="proceedToStep2()" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem; font-weight: bold;">
                            Continue to Step 2 →
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Aggregators List (Hidden until location is captured) -->
            <div id="step2Aggregators" style="display: none; margin-top: 2rem;">
                <div class="card-header" style="margin-bottom: 1rem;">
                    <h2 style="color: var(--primary-green); margin: 0;">📍 Step 2: Select Nearest Aggregator</h2>
                </div>
                <p style="margin-bottom: 1.5rem;">Here are the nearest aggregators sorted by distance. Click "Select & Go" to choose one.</p>
                <div id="aggregatorsListContainer"></div>
            </div>
            
            <script>
            let userLat = null;
            let userLng = null;
            
            // Get location with Google Maps visualization
            function getMyLocationWithGoogleMaps() {
                const btn = document.getElementById('getLocationBtn');
                const statusDiv = document.getElementById('locationStatus');
                const mapContainer = document.getElementById('mapContainer');
                
                // Check if geolocation is supported
                if (!navigator.geolocation) {
                    statusDiv.innerHTML = '<div style="color: var(--error); padding: 1rem; background: #ffebee; border-radius: 0.5rem;"><strong>❌ Geolocation not supported</strong><br>Please use a modern browser like Chrome, Firefox, or Edge.</div>';
                    statusDiv.style.display = 'block';
                    return;
                }
                
                // Update button and status
                btn.disabled = true;
                btn.textContent = '⏳ Getting location...';
                statusDiv.innerHTML = '<div style="color: #1976d2; padding: 1rem; background: #e3f2fd; border-radius: 0.5rem;"><strong>📍 Requesting location...</strong><br>Please allow location access when your browser prompts you.</div>';
                statusDiv.style.display = 'block';
                
                // Get location
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // Success!
                        userLat = position.coords.latitude;
                        userLng = position.coords.longitude;
                        
                        console.log('✅ Location captured:', userLat, userLng);
                        
                        // Store globally
                        window.collectorLat = userLat;
                        window.collectorLng = userLng;
                        
                        // Show location confirmation
                        showLocationConfirmation(userLat, userLng);
                        
                        // Update status
                        statusDiv.innerHTML = '<div style="color: var(--primary-green); padding: 1rem; background: #e8f5e9; border-radius: 0.5rem;"><strong>✅ Location found!</strong><br>Latitude: ' + userLat.toFixed(6) + ', Longitude: ' + userLng.toFixed(6) + '</div>';
                    },
                    function(error) {
                        // Error
                        let errorMsg = 'Error getting location: ';
                        if (error.code === 1) {
                            errorMsg = '❌ Permission Denied<br>Please allow location access in your browser settings and try again.';
                        } else if (error.code === 2) {
                            errorMsg = '❌ Location Unavailable<br>Please check your GPS/network connection.';
                        } else if (error.code === 3) {
                            errorMsg = '⏱️ Request Timed Out<br>Please try again.';
                        }
                        
                        statusDiv.innerHTML = '<div style="color: var(--error); padding: 1rem; background: #ffebee; border-radius: 0.5rem;"><strong>' + errorMsg + '</strong><br><button type="button" onclick="getMyLocationWithGoogleMaps()" class="btn btn-primary" style="margin-top: 0.5rem;">🔄 Try Again</button></div>';
                        btn.disabled = false;
                        btn.textContent = '📍 Get My Location';
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            }
            
            // Show location confirmation (simple, no Google Maps required)
            function showLocationConfirmation(lat, lng) {
                const locationContainer = document.getElementById('locationConfirmContainer');
                const displayLat = document.getElementById('displayLat');
                const displayLng = document.getElementById('displayLng');
                const viewOnMapsLink = document.getElementById('viewOnMapsLink');
                
                locationContainer.style.display = 'block';
                
                // Display coordinates
                displayLat.textContent = lat.toFixed(6);
                displayLng.textContent = lng.toFixed(6);
                
                // Set Google Maps link
                viewOnMapsLink.href = `https://www.google.com/maps?q=${lat},${lng}`;
            }
            
            // Proceed to Step 2
            function proceedToStep2() {
                if (!userLat || !userLng) {
                    alert('Please get your location first.');
                    return;
                }
                
                const step1 = document.getElementById('step1Location');
                const step2 = document.getElementById('step2Aggregators');
                
                step1.style.display = 'none';
                step2.style.display = 'block';
                
                // Load aggregators
                loadAggregatorsWithMaps(userLat, userLng);
            }
            
            // Load aggregators using Google Maps Distance Matrix API
            function loadAggregatorsWithMaps(collectorLat, collectorLng) {
                const container = document.getElementById('aggregatorsListContainer');
                container.innerHTML = '<div style="text-align: center; padding: 2rem;"><div style="font-size: 2rem; margin-bottom: 1rem;">⏳</div><strong>Loading aggregators...</strong></div>';
                
                // Get aggregators data from PHP
                const aggregators = <?php echo json_encode($aggregatorsArray); ?>;
                
                console.log('Aggregators from database:', aggregators);
                
                if (!aggregators || aggregators.length === 0) {
                    container.innerHTML = '<div class="alert alert-info" style="padding: 1.5rem; text-align: center;"><strong>No aggregators available</strong><br>There are no subscribed aggregators in the database at this time.</div>';
                    return;
                }
                
                // Filter aggregators with valid coordinates
                const aggregatorsWithCoords = aggregators.filter(agg => agg.latitude != null && agg.longitude != null && !isNaN(agg.latitude) && !isNaN(agg.longitude));
                
                console.log('Aggregators with coordinates:', aggregatorsWithCoords);
                
                if (aggregatorsWithCoords.length === 0) {
                    // Show all aggregators even without coordinates
                    container.innerHTML = '<div class="alert alert-warning" style="padding: 1.5rem;"><strong>⚠️ No aggregators with location data</strong><br>Showing aggregators without distance information:</div>';
                    displayAggregatorsList(aggregators, container, collectorLat, collectorLng);
                    return;
                }
                
                // Use Google Maps Distance Matrix API to get distances and travel times
                calculateDistancesWithGoogleMaps(collectorLat, collectorLng, aggregatorsWithCoords, container);
            }
            
            // Calculate distances using Google Maps Distance Matrix API
            function calculateDistancesWithGoogleMaps(originLat, originLng, aggregators, container) {
                // Validate origin coordinates
                if (!originLat || !originLng || isNaN(originLat) || isNaN(originLng)) {
                    console.error('Invalid origin coordinates:', originLat, originLng);
                    container.innerHTML = '<div class="alert alert-error">Error: Invalid location coordinates. Please try getting your location again.</div>';
                    return;
                }
                
                // Filter and validate aggregator coordinates
                const validAggregators = aggregators.filter(agg => {
                    const hasValidCoords = agg.latitude != null && agg.longitude != null && 
                                          !isNaN(agg.latitude) && !isNaN(agg.longitude) &&
                                          agg.latitude !== 0 && agg.longitude !== 0 &&
                                          Math.abs(agg.latitude) <= 90 && Math.abs(agg.longitude) <= 180;
                    if (!hasValidCoords) {
                        console.warn('Invalid coordinates for aggregator:', agg.userID, agg.latitude, agg.longitude);
                    }
                    return hasValidCoords;
                });
                
                // Calculate using Haversine formula
                validAggregators.forEach(agg => {
                    const distance = calculateHaversineDistance(originLat, originLng, agg.latitude, agg.longitude);
                    agg.distanceKm = distance;
                    agg.distanceMeters = Math.round(distance * 1000);
                    agg.walkingTime = Math.round((distance / 5) * 60); // 5 km/h walking
                    agg.cyclingTime = Math.round((distance / 15) * 60); // 15 km/h cycling
                    agg.drivingTime = Math.round((distance / 30) * 60); // 30 km/h city driving
                });
                
                // Sort by distance
                validAggregators.sort((a, b) => a.distanceKm - b.distanceKm);
                
                // Add aggregators without valid coordinates at the end
                const invalidAggregators = aggregators.filter(agg => {
                    const hasValidCoords = agg.latitude != null && agg.longitude != null && 
                                          !isNaN(agg.latitude) && !isNaN(agg.longitude) &&
                                          agg.latitude !== 0 && agg.longitude !== 0 &&
                                          Math.abs(agg.latitude) <= 90 && Math.abs(agg.longitude) <= 180;
                    return !hasValidCoords;
                });
                
                // Combine: valid coordinates first (sorted), then invalid
                const allAggregators = [...validAggregators, ...invalidAggregators];
                
                // Display aggregators
                displayAggregatorsList(allAggregators, container, originLat, originLng);
            }
            
            // Haversine formula for distance calculation
            function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
                const R = 6371; // Earth's radius in km
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = 
                    Math.sin(dLat/2) * Math.sin(dLat/2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLon/2) * Math.sin(dLon/2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                return R * c;
            }
            
            // Display aggregators list
            function displayAggregatorsList(aggregators, container, collectorLat, collectorLng) {
                if (!aggregators || aggregators.length === 0) {
                    container.innerHTML = '<div class="alert alert-info" style="padding: 1.5rem; text-align: center;"><strong>No aggregators found</strong></div>';
                    return;
                }
                
                let html = '';
                
                aggregators.forEach((agg, index) => {
                    const hasDistance = agg.distanceKm != null && !isNaN(agg.distanceKm);
                    const hasAddress = agg.address && agg.address.trim() !== '' && agg.address !== 'Address not set';
                    const hasGPS = agg.latitude && agg.longitude && !isNaN(agg.latitude) && !isNaN(agg.longitude);
                    const isClosest = hasDistance && index === 0;
                    const distanceText = hasDistance ? (agg.distanceKm < 1 ? 
                        agg.distanceMeters + ' meters' : 
                        agg.distanceKm.toFixed(2) + ' km') : 'Distance unavailable';
                    
                    // Create unique ID for button
                    const buttonId = 'selectBtn_' + agg.userID;
                    
                    html += `
                        <div class="aggregator-card" style="margin-bottom: 1.5rem; padding: 1.5rem; border: 2px solid ${isClosest ? 'var(--primary-green)' : '#e0e0e0'}; border-radius: 0.75rem; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            ${isClosest ? '<div style="background: var(--primary-green); color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; text-align: center; font-weight: bold;">⭐ CLOSEST</div>' : ''}
                            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1rem;">
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 0.5rem 0; color: var(--primary-green);">📦 ${escapeHtml(agg.businessName || agg.userName)}</h3>
                                    <p style="margin: 0.25rem 0; color: var(--gray);"><strong>📍 Address:</strong> ${escapeHtml(agg.address || 'Address not set')}</p>
                                    <p style="margin: 0.25rem 0; color: var(--gray);"><strong>📞 Contact:</strong> ${escapeHtml(agg.userContact || agg.userName)}</p>
                                    ${agg.rating > 0 ? `<p style="margin: 0.25rem 0; color: var(--orange);"><strong>⭐ Rating:</strong> ${agg.rating.toFixed(1)}/5.0 (${agg.totalRatings} reviews)</p>` : ''}
                                </div>
                                <div style="text-align: right;">
                                    ${hasDistance ? `
                                    <div style="background: ${isClosest ? '#4caf50' : '#2196f3'}; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-weight: bold; font-size: 1.1rem;">
                                        📍 ${distanceText}
                                    </div>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 1rem;">
                                        <div style="text-align: center; padding: 0.75rem; background: #f5f5f5; border-radius: 0.5rem;">
                                            <div style="font-size: 1.5rem;">🚶</div>
                                            <div style="font-weight: bold; color: var(--dark);">${agg.walkingTime || 0} min</div>
                                            <div style="font-size: 0.75rem; color: var(--gray);">Walking</div>
                                        </div>
                                        <div style="text-align: center; padding: 0.75rem; background: #f5f5f5; border-radius: 0.5rem;">
                                            <div style="font-size: 1.5rem;">🚴</div>
                                            <div style="font-weight: bold; color: var(--dark);">${agg.cyclingTime || 0} min</div>
                                            <div style="font-size: 0.75rem; color: var(--gray);">Cycling</div>
                                        </div>
                                        <div style="text-align: center; padding: 0.75rem; background: #f5f5f5; border-radius: 0.5rem;">
                                            <div style="font-size: 1.5rem;">🚗</div>
                                            <div style="font-weight: bold; color: var(--dark);">${agg.drivingTime || 0} min</div>
                                            <div style="font-size: 0.75rem; color: var(--gray);">Driving</div>
                                        </div>
                                    </div>
                                    ` : hasAddress ? `
                                    <div style="background: #2196f3; color: white; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-weight: bold; font-size: 1rem;">
                                        📍 Address Available
                                    </div>
                                    <div style="background: #e3f2fd; color: #1976d2; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem;">
                                        <strong>ℹ️ Distance calculation unavailable</strong><br>
                                        <small>GPS coordinates not set. Address is available for directions.</small>
                                    </div>
                                    ` : '<div style="background: #ff9800; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-weight: bold;">⚠️ Location not set</div>'}
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        ${hasGPS ? `
                                        <a href="https://www.google.com/maps/dir/?api=1&destination=${agg.latitude},${agg.longitude}" 
                                           target="_blank" 
                                           class="btn btn-secondary" 
                                           style="white-space: nowrap; text-decoration: none; padding: 0.75rem 1.5rem; text-align: center;">
                                            🗺️ Get Directions
                                        </a>
                                        ` : hasAddress ? `
                                        <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(agg.address)}" 
                                           target="_blank" 
                                           class="btn btn-secondary" 
                                           style="white-space: nowrap; text-decoration: none; padding: 0.75rem 1.5rem; text-align: center;">
                                            🗺️ Get Directions
                                        </a>
                                        ` : ''}
                                        <button id="${buttonId}" data-aggregator-id="${agg.userID}" onclick="(function(id){console.log('Button clicked, ID:', id); const url='<?php echo VIEWS_URL; ?>/collector/submit_waste.php?aggregatorID='+id; console.log('Redirecting to:', url); window.location.href=url;})(${agg.userID})" class="btn btn-primary" style="white-space: nowrap; padding: 0.75rem 1.5rem; font-weight: bold; cursor: pointer;">
                                            ✅ Select & Go
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                // Set HTML first
                container.innerHTML = html;
                
                // Attach event listeners to all Select & Go buttons (after HTML is set)
                // Also keep inline onclick as backup
                setTimeout(() => {
                    aggregators.forEach((agg) => {
                        const buttonId = 'selectBtn_' + agg.userID;
                        const button = document.getElementById(buttonId);
                        if (button) {
                            // Add click event listener (in addition to inline onclick)
                            button.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                console.log('✅ Select & Go button clicked (event listener) for aggregator:', agg.userID);
                                
                                // Get aggregator ID from data attribute
                                const aggID = parseInt(button.getAttribute('data-aggregator-id')) || agg.userID;
                                console.log('Aggregator ID to redirect:', aggID);
                                
                                // Use the global function or direct redirect
                                if (typeof window.selectAggregator === 'function') {
                                    window.selectAggregator(aggID);
                                } else {
                                    console.warn('selectAggregator function not found, using direct redirect');
                                    window.location.href = '<?php echo VIEWS_URL; ?>/collector/submit_waste.php?aggregatorID=' + aggID;
                                }
                            });
                            
                            console.log('✅ Event listener attached to button:', buttonId, 'for aggregator:', agg.userID);
                        } else {
                            console.error('❌ Button not found:', buttonId);
                        }
                    });
                }, 200);
            }
            
            // Helper function to escape HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Select aggregator function - make it globally accessible IMMEDIATELY
            // Define it once at the top level to avoid conflicts
            if (typeof window.selectAggregator === 'undefined') {
                window.selectAggregator = function(aggregatorID) {
                    console.log('📍 Select & Go clicked - Aggregator ID:', aggregatorID);
                    
                    if (!aggregatorID || aggregatorID === 'undefined' || aggregatorID === 'null' || isNaN(aggregatorID)) {
                        alert('Error: Invalid aggregator ID. Please try again.');
                        console.error('Invalid aggregator ID:', aggregatorID);
                        return;
                    }
                    
                    const url = '<?php echo VIEWS_URL; ?>/collector/submit_waste.php?aggregatorID=' + parseInt(aggregatorID);
                    console.log('Redirecting to:', url);
                    
                    // Redirect to the submit waste page with selected aggregator
                    window.location.href = url;
                };
                
                // Also define it without window for compatibility
                function selectAggregator(aggregatorID) {
                    if (typeof window.selectAggregator === 'function') {
                        window.selectAggregator(aggregatorID);
                    } else {
                        // Fallback direct redirect
                        window.location.href = '<?php echo VIEWS_URL; ?>/collector/submit_waste.php?aggregatorID=' + parseInt(aggregatorID);
                    }
                }
                
                console.log('✅ selectAggregator function defined and ready');
            }
            </script>
            
            <!-- Aggregators List (show immediately, update with location like Uber) -->
            <?php if (!$selectedAggregatorID): ?>
            <div id="aggregatorsList" style="display: block;">
                <?php if ($nearestAggregators->num_rows > 0): ?>
                    <div style="margin-top: 1.5rem;">
                        <h3 style="color: var(--dark); margin-bottom: 1rem;">📍 Nearest Aggregators</h3>
                        <p style="color: var(--gray); margin-bottom: 1.5rem;">Sorted by distance - closest first. Estimated travel times shown below.</p>
                        <div id="aggregatorsContainer" style="display: grid; gap: 1rem;">
                            <!-- Aggregators will be populated here by JavaScript -->
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" style="margin-top: 1rem;">
                        No aggregators available at this time. Please check back later.
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($selectedAggregatorID): ?>
                <?php if ($selectedAggregator): ?>
                    <div style="margin-top: 1.5rem; padding: 1.5rem; background: linear-gradient(135deg, var(--very-light-green), #e8f5e9); border-radius: 0.75rem; border-left: 4px solid var(--primary-green);">
                        <h3 style="margin: 0 0 1rem 0; color: var(--primary-green);">✅ Selected Aggregator</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div>
                                <strong>📦 Business:</strong> <?php echo htmlspecialchars($selectedAggregator['businessName']); ?>
                            </div>
                            <div>
                                <strong>📍 Address:</strong> <?php echo htmlspecialchars($selectedAggregator['address'] ?? 'Not provided'); ?>
                            </div>
                            <div>
                                <strong>📞 Contact:</strong> <?php echo htmlspecialchars($selectedAggregator['userContact']); ?>
                            </div>
                            <?php if ($selectedAggregator['rating'] > 0): ?>
                                <div>
                                    <strong>⭐ Rating:</strong> <?php echo number_format($selectedAggregator['rating'], 1); ?>/5.0
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                            <a href="<?php echo VIEWS_URL; ?>/collector/submit_waste.php" class="btn btn-secondary" style="padding: 0.5rem 1rem;">
                                🔄 Change Aggregator
                            </a>
                            <?php if ($selectedAggregator['latitude'] && $selectedAggregator['longitude']): ?>
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $selectedAggregator['latitude']; ?>,<?php echo $selectedAggregator['longitude']; ?>" 
                                   target="_blank" 
                                   class="btn btn-primary" 
                                   style="padding: 0.5rem 1rem; text-decoration: none;">
                                    🗺️ Open in Maps
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($selectedAggregatorID && $selectedAggregator): ?>
        <!-- Show selected aggregator info -->
        <div class="card">
            <div style="padding: 1.5rem; background: linear-gradient(135deg, var(--very-light-green), #e8f5e9); border-radius: 0.75rem; border-left: 4px solid var(--primary-green);">
                <h3 style="margin: 0 0 1rem 0; color: var(--primary-green);">✅ Selected Aggregator</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <strong>📦 Business:</strong> <?php echo htmlspecialchars($selectedAggregator['businessName']); ?>
                    </div>
                    <div>
                        <strong>📍 Address:</strong> <?php echo htmlspecialchars($selectedAggregator['address'] ?? 'Not provided'); ?>
                    </div>
                    <div>
                        <strong>📞 Contact:</strong> <?php echo htmlspecialchars($selectedAggregator['userContact']); ?>
                    </div>
                    <?php if ($selectedAggregator['rating'] > 0): ?>
                        <div>
                            <strong>⭐ Rating:</strong> <?php echo number_format($selectedAggregator['rating'], 1); ?>/5.0
                        </div>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="<?php echo VIEWS_URL; ?>/collector/submit_waste.php" class="btn btn-secondary" style="padding: 0.5rem 1rem;">
                        🔄 Change Aggregator
                    </a>
                    <?php if ($selectedAggregator['latitude'] && $selectedAggregator['longitude']): ?>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $selectedAggregator['latitude']; ?>,<?php echo $selectedAggregator['longitude']; ?>" 
                           target="_blank" 
                           class="btn btn-primary" 
                           style="padding: 0.5rem 1rem; text-decoration: none;">
                            🗺️ Open in Maps
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h2 style="color: var(--primary-green); margin: 0;">♻️ Step 3: Submit Waste at Aggregator Location</h2>
            </div>
            <p style="margin-top: 1rem;">You have selected <strong><?php echo htmlspecialchars($selectedAggregator['businessName']); ?></strong>. Now capture your GPS location and submit your waste collection to get the correct price.</p>
        </div>

        <div class="card">
            <form method="POST" action="<?php echo ACTIONS_URL; ?>/upload_waste_action.php" id="wasteForm" enctype="multipart/form-data">
                <input type="hidden" name="aggregatorID" value="<?php echo $selectedAggregatorID; ?>">
                <div class="form-group">
                    <label for="plasticType">
                        <strong>Plastic Type *</strong>
                        <span style="color: var(--gray); font-weight: normal; font-size: 0.9rem;">(Select the type of plastic you collected)</span>
                    </label>
                    <select id="plasticType" name="plasticTypeID" required style="padding: 0.75rem; font-size: 1rem;">
                        <option value="">-- Select plastic type --</option>
                        <?php 
                        $plasticTypes->data_seek(0); // Reset pointer
                        while ($type = $plasticTypes->fetch_assoc()): 
                            $hasPricing = isset($selectedAggregatorPricing[$type['plasticTypeID']]);
                            $price = $hasPricing ? $selectedAggregatorPricing[$type['plasticTypeID']]['pricePerKg'] : null;
                        ?>
                            <option value="<?php echo $type['plasticTypeID']; ?>" 
                                    data-description="<?php echo htmlspecialchars($type['description'] ?? ''); ?>"
                                    data-price="<?php echo $price ?? 0; ?>"
                                    data-has-pricing="<?php echo $hasPricing ? '1' : '0'; ?>">
                                <?php echo htmlspecialchars($type['typeName']); ?>
                                <?php if ($price): ?>
                                    - GH₵<?php echo number_format($price, 2); ?>/kg
                                <?php elseif ($hasPricing === false): ?>
                                    - (No pricing set)
                                <?php endif; ?>
                                <?php if ($type['description']): ?>
                                    - <?php echo htmlspecialchars($type['description']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small id="plasticTypeHelp" style="color: var(--gray); display: block; margin-top: 0.5rem;"></small>
                    <div id="priceDisplay" style="margin-top: 0.5rem; padding: 1rem; background: var(--very-light-green); border-radius: 0.5rem; display: none;">
                        <strong style="color: var(--primary-green);">💰 Price for this aggregator:</strong>
                        <span id="pricePerKg" style="font-size: 1.2rem; font-weight: bold; color: var(--primary-green);"></span>
                        <span id="totalPrice" style="display: block; margin-top: 0.5rem; font-size: 1.1rem;"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="weight">
                        <strong>Weight (kg) *</strong>
                        <span style="color: var(--gray); font-weight: normal; font-size: 0.9rem;">(Enter the total weight of plastic collected)</span>
                    </label>
                    <input 
                        type="number" 
                        id="weight" 
                        name="weight" 
                        step="0.01" 
                        min="0.01"
                        required 
                        placeholder="e.g., 25.50"
                        style="padding: 0.75rem; font-size: 1rem;"
                    >
                    <small style="color: var(--gray); display: block; margin-top: 0.5rem;">
                        💡 Tip: Use a scale to get accurate weight for better pricing
                    </small>
                </div>

                <div class="form-group">
                    <label for="location">
                        <strong>Current Location at Aggregator *</strong>
                        <span style="color: var(--gray); font-weight: normal; font-size: 0.9rem;">(Capture your GPS location at the aggregator's location)</span>
                    </label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input 
                            type="text" 
                            id="location" 
                            name="location" 
                            required 
                            value="<?php echo htmlspecialchars($selectedAggregator['address'] ?? ''); ?>"
                            placeholder="Aggregator address will be auto-filled"
                            style="padding: 0.75rem; font-size: 1rem; flex: 1;"
                        >
                        <button type="button" id="getLocationBtn" onclick="getCurrentLocation()" class="btn btn-primary" style="padding: 0.75rem 1.5rem; white-space: nowrap;">
                            📍 Capture Location
                        </button>
                    </div>
                    <div id="locationStatus" style="margin-top: 0.5rem; padding: 0.75rem; border-radius: 0.5rem; display: none;"></div>
                    <small style="color: var(--gray); display: block; margin-top: 0.5rem;">
                        ⚠️ <strong>Important:</strong> Click "Capture Location" to verify you are at the aggregator's location. This ensures accurate pricing and transaction processing.
                    </small>
                    <input type="hidden" id="latitude" name="latitude" value="">
                    <input type="hidden" id="longitude" name="longitude" value="">
                </div>

                <div class="form-group">
                    <label for="photo">
                        <strong>Upload Photo (Optional)</strong>
                        <span style="color: var(--gray); font-weight: normal; font-size: 0.9rem;">(Show the quality of your plastic waste)</span>
                    </label>
                    <input 
                        type="file" 
                        id="photo" 
                        name="photo" 
                        accept="image/*"
                        onchange="previewPhoto(this)"
                        style="padding: 0.5rem; font-size: 1rem;"
                    >
                    <small style="color: var(--gray); display: block; margin-top: 0.5rem;">
                        📸 Photos help aggregators verify quality (Max 5MB, JPG/PNG)
                    </small>
                    <div id="photoPreview" style="margin-top: 1rem; display: none;">
                        <img id="previewImg" src="" alt="Preview" style="max-width: 300px; max-height: 200px; border-radius: 0.5rem; border: 2px solid var(--light-gray);">
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">
                        <strong>Additional Notes (Optional)</strong>
                        <span style="color: var(--gray); font-weight: normal; font-size: 0.9rem;">(Any extra information about the waste)</span>
                    </label>
                    <textarea 
                        id="notes" 
                        name="notes" 
                        rows="4"
                        placeholder="e.g., Clean and sorted, collected from beach cleanup, stored in dry place..."
                        style="padding: 0.75rem; font-size: 1rem; resize: vertical;"
                    ></textarea>
                </div>

                <div style="background: linear-gradient(135deg, var(--very-light-green), var(--light-green)); padding: 1.5rem; border-radius: 0.75rem; margin: 1.5rem 0; border-left: 4px solid var(--primary-green);">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-green);">💡 What Happens Next?</h4>
                    <ol style="margin: 0.5rem 0 0 1.5rem; padding: 0; line-height: 1.8; color: var(--gray-dark);">
                        <li>Submit your waste collection with accurate weight and plastic type</li>
                        <li>The aggregator will review and accept your delivery</li>
                        <li>You'll receive payment via mobile money after acceptance</li>
                        <li>Leave feedback about your experience</li>
                    </ol>
                </div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; font-weight: bold;">
                        ♻️ Submit Waste Collection at This Location
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if (!$selectedAggregatorID): ?>
        <div class="card" style="background: linear-gradient(135deg, var(--very-light-green), #e8f5e9); border-left: 4px solid var(--primary-green);">
            <div class="card-header">
                <h2 style="color: var(--primary-green); margin: 0;">📋 Complete Process Guide</h2>
            </div>
            <div style="margin-top: 1rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
                    <div style="padding: 1rem; background: white; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">1️⃣</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-green);">Select Aggregator</h4>
                        <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">Find and select the nearest aggregator with best prices and ratings</p>
                    </div>
                    <div style="padding: 1rem; background: white; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">2️⃣</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-green);">Go to Location</h4>
                        <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">Travel to the aggregator's location with your plastic waste</p>
                    </div>
                    <div style="padding: 1rem; background: white; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">3️⃣</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-green);">Submit at Location</h4>
                        <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">Capture GPS location and submit waste to get accurate pricing</p>
                    </div>
                    <div style="padding: 1rem; background: white; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">4️⃣</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-green);">Get Paid</h4>
                        <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">Wait for acceptance and receive payment via mobile money</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2025 <?php echo APP_NAME; ?>. Building a cleaner Ghana together.</p>
        <p style="margin-top: 0.5rem;">Fair • Transparent • Connected</p>
    </footer>

    <script src="<?php echo BASE_URL; ?>/js/collector.js"></script>
    <script>
        // Store aggregators data from PHP
        const aggregatorsData = [
            <?php 
            $nearestAggregators->data_seek(0);
            $first = true;
            while ($agg = $nearestAggregators->fetch_assoc()): 
                if (!$first) echo ',';
                $first = false;
            ?>
            {
                userID: <?php echo $agg['userID']; ?>,
                businessName: <?php echo json_encode($agg['businessName']); ?>,
                userName: <?php echo json_encode($agg['userName']); ?>,
                userContact: <?php echo json_encode($agg['userContact'] ?? ''); ?>,
                address: <?php echo json_encode($agg['address'] ?? ''); ?>,
                latitude: <?php echo $agg['latitude'] ? $agg['latitude'] : 'null'; ?>,
                longitude: <?php echo $agg['longitude'] ? $agg['longitude'] : 'null'; ?>,
                rating: <?php echo $agg['rating'] ?? 0; ?>,
                totalRatings: <?php echo $agg['totalRatings'] ?? 0; ?>
            }
            <?php endwhile; ?>
        ];
        
        let collectorLat = null;
        let collectorLng = null;
        
        // Make collectorLat and collectorLng globally accessible
        window.collectorLat = null;
        window.collectorLng = null;
        
        // Capture collector's current location for distance calculation (Auto-request like Uber)
        function captureCollectorLocation() {
            console.log('captureCollectorLocation called'); // Debug
            
            const statusDiv = document.getElementById('locationCaptureStatus');
            const locationSection = document.getElementById('locationCaptureSection');
            const aggregatorsList = document.getElementById('aggregatorsList');
            
            // Check if elements exist
            if (!statusDiv || !locationSection || !aggregatorsList) {
                console.error('Required elements not found', {statusDiv, locationSection, aggregatorsList});
                return;
            }
            
            if (!navigator.geolocation) {
                statusDiv.innerHTML = '<div style="color: var(--error); padding: 1rem;"><strong>❌ Geolocation not supported</strong><br><small>Please use a modern browser like Chrome, Firefox, or Edge.</small></div>';
                statusDiv.style.background = '#ffebee';
                return;
            }
            
            // Update status with animated indicator
            statusDiv.innerHTML = '<div style="display: flex; align-items: center; gap: 1rem;"><div style="font-size: 2rem; animation: pulse 1.5s ease-in-out infinite;">📍</div><div><strong style="color: #1976d2;">Requesting location access...</strong><p style="margin: 0.5rem 0 0 0; color: var(--gray-dark); font-size: 0.9rem;">Please allow location access when your browser prompts you</p></div></div>';
            statusDiv.style.background = '#e3f2fd';
            
            // Add pulse animation
            if (!document.getElementById('locationPulseStyle')) {
                const style = document.createElement('style');
                style.id = 'locationPulseStyle';
                style.textContent = '@keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.6; transform: scale(1.1); } }';
                document.head.appendChild(style);
            }
            
            console.log('Calling navigator.geolocation.getCurrentPosition...');
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    console.log('✅ Location captured successfully:', position.coords.latitude, position.coords.longitude);
                    collectorLat = position.coords.latitude;
                    collectorLng = position.coords.longitude;
                    // Also set global variables
                    window.collectorLat = position.coords.latitude;
                    window.collectorLng = position.coords.longitude;
                    
                    // Update status briefly
                    statusDiv.innerHTML = '<div style="color: var(--primary-green); padding: 1rem;"><strong>✅ Location found!</strong><br><small>Updating distances...</small></div>';
                    statusDiv.style.background = '#e8f5e9';
                    
                    // Update aggregators with distances (fast like Uber)
                    calculateAndDisplayAggregators();
                    
                    // Hide location status after a brief moment
                    setTimeout(() => {
                        locationSection.style.display = 'none';
                    }, 1000);
                },
                function(error) {
                    console.error('❌ Geolocation error:', error, 'Code:', error.code, 'Message:', error.message);
                    let errorMsg = '';
                    let errorDetails = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = '❌ Location Permission Denied';
                            errorDetails = 'Please click "Allow" when your browser asks for location access, or enable location in your browser settings.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = '❌ Location Unavailable';
                            errorDetails = 'Your device location could not be determined. Please check your GPS/network connection and try again.';
                            break;
                        case error.TIMEOUT:
                            errorMsg = '⏱️ Location Request Timed Out';
                            errorDetails = 'The location request took too long. Please check your connection and try again.';
                            break;
                        default:
                            errorMsg = '❌ Location Error';
                            errorDetails = 'An error occurred while getting your location. Error code: ' + error.code;
                            break;
                    }
                    statusDiv.innerHTML = '<div style="color: var(--error); padding: 1rem;"><strong>' + errorMsg + '</strong><br><small style="display: block; margin-top: 0.5rem;">' + errorDetails + '</small><button type="button" onclick="window.captureCollectorLocation()" class="btn btn-primary" style="margin-top: 1rem; padding: 0.75rem 1.5rem; font-size: 1rem; cursor: pointer;">🔄 Try Again</button></div>';
                    statusDiv.style.background = '#ffebee';
                },
                {
                    enableHighAccuracy: true, // Better accuracy for distance calculations
                    timeout: 15000, // 15 seconds - enough time for user to grant permission
                    maximumAge: 0 // Always get fresh location
                }
            );
        }
        
        // Make functions globally available IMMEDIATELY
        window.captureCollectorLocation = captureCollectorLocation;
        window.calculateAndDisplayAggregators = calculateAndDisplayAggregators;
        
        // Initialize everything when DOM is ready
        function initializeLocationCapture() {
            console.log('Initializing location capture...');
            
            // Show aggregators immediately without waiting for location
            calculateAndDisplayAggregators();
            
            // Attach click handler to manual button
            const manualBtn = document.getElementById('manualLocationBtn');
            if (manualBtn) {
                // Remove inline onclick if present
                manualBtn.removeAttribute('onclick');
                
                // Remove any existing event listeners by cloning
                const newBtn = manualBtn.cloneNode(true);
                manualBtn.parentNode.replaceChild(newBtn, manualBtn);
                
                // Add click handler with proper error handling
                newBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📍 Manual location button clicked - event listener');
                    
                    // Double check function exists
                    if (typeof window.captureCollectorLocation === 'function') {
                        console.log('✅ Calling captureCollectorLocation...');
                        try {
                            window.captureCollectorLocation();
                        } catch(err) {
                            console.error('❌ Error calling captureCollectorLocation:', err);
                            alert('Error getting location: ' + err.message);
                        }
                    } else {
                        console.error('❌ captureCollectorLocation function not found!');
                        alert('Location function not ready. Please refresh the page.');
                    }
                });
                
                // Also set onclick as backup
                newBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📍 Manual location button clicked - onclick backup');
                    if (typeof window.captureCollectorLocation === 'function') {
                        window.captureCollectorLocation();
                    }
                };
                
                console.log('✅ Manual location button handler attached (both event listener and onclick)');
            } else {
                console.error('❌ Manual location button not found in DOM');
            }
            
            // Auto-request location in background (like Uber)
            setTimeout(() => {
                console.log('Auto-requesting location in background...');
                if (typeof window.captureCollectorLocation === 'function') {
                    window.captureCollectorLocation();
                } else {
                    console.warn('captureCollectorLocation function not ready, will retry...');
                }
            }, 1000);
        }
        
        // Run initialization when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeLocationCapture);
        } else {
            // DOM already loaded
            initializeLocationCapture();
        }
        
        // Calculate distance using Haversine formula (like maps)
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Earth's radius in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = 
                Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }
        
        // Calculate travel times (like Uber)
        function calculateTravelTimes(distance) {
            const walkingSpeed = 5; // km/h
            const cyclingSpeed = 15; // km/h
            const drivingSpeed = 30; // km/h (city average)
            
            return {
                walking: Math.round((distance / walkingSpeed) * 60), // minutes
                cycling: Math.round((distance / cyclingSpeed) * 60), // minutes
                driving: Math.round((distance / drivingSpeed) * 60)  // minutes
            };
        }
        
        // Get badge color and text based on distance
        function getDistanceBadge(distance) {
            if (distance < 1) {
                return { color: '#4caf50', text: 'Very Close' };
            } else if (distance < 3) {
                return { color: 'var(--primary-green)', text: 'Close' };
            } else if (distance < 5) {
                return { color: '#8bc34a', text: 'Nearby' };
            } else {
                return { color: '#cddc39', text: 'Moderate' };
            }
        }
        
        // Calculate distances and display aggregators (Fast like Uber - shows immediately)
        // Shows aggregators right away, updates distances when location is available
        function calculateAndDisplayAggregators() {
            const container = document.getElementById('aggregatorsContainer');
            if (!container) return;
            
            // Show aggregators immediately even without location (like Uber shows nearby drivers)
            if (aggregatorsData.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No subscribed aggregators available at this time. Please check back later.</div>';
                return;
            }
            
            container.innerHTML = '';
            
            // Separate aggregators with and without GPS coordinates
            const aggregatorsWithGPS = [];
            const aggregatorsWithoutGPS = [];
            
            aggregatorsData.forEach(agg => {
                // Only calculate distance if both collector and aggregator have GPS coordinates
                if (agg.latitude && agg.longitude && collectorLat && collectorLng) {
                    const distance = calculateDistance(collectorLat, collectorLng, agg.latitude, agg.longitude);
                    const times = calculateTravelTimes(distance);
                    const badge = getDistanceBadge(distance);
                    aggregatorsWithGPS.push({ ...agg, distance, times, badge });
                } else {
                    // Aggregator without GPS coordinates or collector location not captured yet
                    aggregatorsWithoutGPS.push({ ...agg, distance: null, times: null, badge: null });
                }
            });
            
            // Sort those with GPS by distance (closest first)
            aggregatorsWithGPS.sort((a, b) => a.distance - b.distance);
            
            // Combine: GPS-enabled first (sorted by distance), then those without GPS
            const allAggregators = [...aggregatorsWithGPS, ...aggregatorsWithoutGPS];
            
            allAggregators.forEach((agg, index) => {
                const distanceKm = agg.distance.toFixed(2);
                const distanceMeters = Math.round(agg.distance * 1000);
                const isClosest = index === 0;
                
                const card = document.createElement('div');
                card.style.cssText = 'padding: 1.5rem; border: 2px solid var(--light-gray); border-radius: 0.75rem; transition: all 0.3s; cursor: pointer; position: relative;';
                card.onmouseover = function() {
                    this.style.borderColor = 'var(--primary-green)';
                    this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                };
                card.onmouseout = function() {
                    this.style.borderColor = 'var(--light-gray)';
                    this.style.boxShadow = 'none';
                };
                card.onclick = function() {
                    selectAggregator(agg.userID);
                };
                
                const hasDistance = agg.distance !== null && agg.times !== null && agg.badge !== null;
                const distanceKm = hasDistance ? agg.distance.toFixed(2) : null;
                const distanceMeters = hasDistance && agg.distance < 1 ? Math.round(agg.distance * 1000) : null;
                const isClosest = index === 0 && hasDistance;
                
                card.innerHTML = `
                    ${isClosest ? '<div style="position: absolute; top: -10px; left: 20px; background: linear-gradient(135deg, #4caf50, var(--primary-green)); color: white; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2); z-index: 10;">🏆 CLOSEST</div>' : ''}
                    <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1rem;">
                        <div style="flex: 1; min-width: 250px;">
                            <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-green); font-size: 1.2rem;">
                                📦 ${agg.businessName}
                            </h4>
                            <p style="margin: 0.25rem 0; color: var(--gray);">
                                <strong>📍 Address:</strong> ${agg.address || 'Not provided'}
                            </p>
                            <p style="margin: 0.25rem 0; color: var(--gray);">
                                <strong>📞 Contact:</strong> ${agg.userContact || agg.userName}
                            </p>
                            ${agg.rating > 0 ? `<p style="margin: 0.25rem 0; color: var(--orange);">⭐ ${agg.rating.toFixed(1)}/5.0 (${agg.totalRatings} reviews)</p>` : ''}
                            ${!hasDistance && (!agg.address || agg.address.trim() === '' || agg.address === 'Address not set') ? '<p style="margin: 0.5rem 0; padding: 0.5rem; background: #fff3cd; border-radius: 0.25rem; color: #856404; font-size: 0.9rem;">⚠️ GPS coordinates not set - distance unavailable</p>' : ''}
                        </div>
                        <div style="text-align: right; min-width: 200px;">
                            ${hasDistance ? `
                                <div style="background: linear-gradient(135deg, ${agg.badge.color}, var(--dark-green)); color: white; padding: 1rem 1.5rem; border-radius: 0.75rem; font-weight: bold; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                    <div style="font-size: 1.8rem; margin-bottom: 0.25rem;">
                                        ${distanceKm} km
                                        ${agg.distance < 1 ? `<span style="font-size: 1rem; opacity: 0.9;">(${distanceMeters} m)</span>` : ''}
                                    </div>
                                    <div style="font-size: 0.85rem; opacity: 0.95;">${agg.badge.text}</div>
                                </div>
                                <div style="background: var(--very-light-green); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; text-align: left;">
                                    <div style="font-size: 0.85rem; color: var(--gray); margin-bottom: 0.5rem; font-weight: 600;">⏱️ Estimated Travel Time:</div>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; font-size: 0.9rem;">
                                        <div style="text-align: center; padding: 0.5rem; background: white; border-radius: 0.25rem;">
                                            <div style="font-size: 1.2rem;">🚶</div>
                                            <div style="font-weight: bold; color: var(--dark);">${agg.times.walking} min</div>
                                            <div style="font-size: 0.75rem; color: var(--gray);">Walking</div>
                                        </div>
                                        <div style="text-align: center; padding: 0.5rem; background: white; border-radius: 0.25rem;">
                                            <div style="font-size: 1.2rem;">🚴</div>
                                            <div style="font-weight: bold; color: var(--dark);">${agg.times.cycling} min</div>
                                            <div style="font-size: 0.75rem; color: var(--gray);">Cycling</div>
                                        </div>
                                        <div style="text-align: center; padding: 0.5rem; background: white; border-radius: 0.25rem;">
                                            <div style="font-size: 1.2rem;">🚗</div>
                                            <div style="font-weight: bold; color: var(--dark);">${agg.times.driving} min</div>
                                            <div style="font-size: 0.75rem; color: var(--gray);">Driving</div>
                                        </div>
                                    </div>
                                </div>
                            ` : ''}
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <button class="btn btn-primary" onclick="event.stopPropagation(); selectAggregator(${agg.userID});" style="white-space: nowrap; padding: 0.75rem 1.5rem; font-weight: bold;">
                                    ✅ Select & Go
                                </button>
                                ${hasDistance && agg.latitude && agg.longitude ? `
                                    <a href="https://www.google.com/maps/dir/?api=1&destination=${agg.latitude},${agg.longitude}" 
                                       target="_blank" 
                                       class="btn btn-secondary" 
                                       style="white-space: nowrap; text-decoration: none; padding: 0.75rem 1.5rem;"
                                       onclick="event.stopPropagation();">
                                        🗺️ Get Directions
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
                
                container.appendChild(card);
            });
        }
        
        // Select aggregator function
        // Make selectAggregator globally available (if not already defined)
        if (typeof window.selectAggregator !== 'function') {
            // Use the globally defined function if available, otherwise define it
            if (typeof window.selectAggregator === 'undefined') {
                window.selectAggregator = function(aggregatorID) {
                    console.log('Selecting aggregator:', aggregatorID);
                    if (!aggregatorID) {
                        alert('Error: Invalid aggregator ID');
                        return;
                    }
                    window.location.href = '<?php echo VIEWS_URL; ?>/collector/submit_waste.php?aggregatorID=' + aggregatorID;
                };
            }
        }

        // Update plastic type description and price
        const plasticTypeSelect = document.getElementById('plasticType');
        if (plasticTypeSelect) {
            plasticTypeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const description = selectedOption.getAttribute('data-description');
                const price = parseFloat(selectedOption.getAttribute('data-price') || 0);
                const hasPricing = selectedOption.getAttribute('data-has-pricing') === '1';
                const helpText = document.getElementById('plasticTypeHelp');
                const priceDisplay = document.getElementById('priceDisplay');
                const pricePerKg = document.getElementById('pricePerKg');
                const totalPrice = document.getElementById('totalPrice');
                const weightInput = document.getElementById('weight');
                
                if (description) {
                    helpText.textContent = description;
                    helpText.style.display = 'block';
                } else {
                    helpText.style.display = 'none';
                }
                
                // Show price if available
                if (hasPricing && price > 0) {
                    pricePerKg.textContent = 'GH₵' + price.toFixed(2) + '/kg';
                    priceDisplay.style.display = 'block';
                    updateTotalPrice(price, parseFloat(weightInput.value) || 0);
                } else {
                    priceDisplay.style.display = 'none';
                }
            });
        }

        // Update total price when weight changes
        const weightInput = document.getElementById('weight');
        if (weightInput) {
            weightInput.addEventListener('input', function() {
                const plasticTypeSelect = document.getElementById('plasticType');
                if (plasticTypeSelect && plasticTypeSelect.value) {
                    const selectedOption = plasticTypeSelect.options[plasticTypeSelect.selectedIndex];
                    const price = parseFloat(selectedOption.getAttribute('data-price') || 0);
                    const weight = parseFloat(this.value) || 0;
                    updateTotalPrice(price, weight);
                }
            });
        }

        function updateTotalPrice(pricePerKg, weight) {
            const totalPrice = document.getElementById('totalPrice');
            if (totalPrice && pricePerKg > 0 && weight > 0) {
                const total = pricePerKg * weight;
                const platformFee = total * 0.01;
                const netAmount = total - platformFee;
                totalPrice.innerHTML = `
                    <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--light-gray);">
                        <div style="display: flex; justify-content: space-between; margin: 0.25rem 0;">
                            <span>Gross Amount:</span>
                            <strong>GH₵${total.toFixed(2)}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin: 0.25rem 0; color: var(--gray);">
                            <span>Platform Fee (1%):</span>
                            <span>-GH₵${platformFee.toFixed(2)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin: 0.5rem 0; padding-top: 0.5rem; border-top: 2px solid var(--primary-green); font-size: 1.1rem;">
                            <strong>You'll Receive:</strong>
                            <strong style="color: var(--primary-green);">GH₵${netAmount.toFixed(2)}</strong>
                        </div>
                    </div>
                `;
            } else if (totalPrice) {
                totalPrice.innerHTML = '';
            }
        }

        // Get current location using Geolocation API
        function getCurrentLocation() {
            const statusDiv = document.getElementById('locationStatus');
            const getLocationBtn = document.getElementById('getLocationBtn');
            const latitudeInput = document.getElementById('latitude');
            const longitudeInput = document.getElementById('longitude');
            const locationInput = document.getElementById('location');
            
            if (!navigator.geolocation) {
                statusDiv.innerHTML = '<span style="color: var(--error);">❌ Geolocation is not supported by your browser</span>';
                statusDiv.style.display = 'block';
                statusDiv.style.background = '#ffebee';
                return;
            }
            
            getLocationBtn.disabled = true;
            getLocationBtn.textContent = '⏳ Getting location...';
            statusDiv.innerHTML = '<span style="color: var(--primary-green);">📍 Requesting location permission...</span>';
            statusDiv.style.display = 'block';
            statusDiv.style.background = '#e8f5e9';
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    latitudeInput.value = lat;
                    longitudeInput.value = lng;
                    
                    // Reverse geocoding to get address (optional - using a simple approach)
                    // You can use a geocoding API here if needed
                    statusDiv.innerHTML = '<span style="color: var(--primary-green);">✅ Location captured! Latitude: ' + lat.toFixed(6) + ', Longitude: ' + lng.toFixed(6) + '</span>';
                    statusDiv.style.background = '#e8f5e9';
                    
                    // If location input is empty, suggest using coordinates
                    if (!locationInput.value) {
                        locationInput.placeholder = 'Location will be calculated from GPS coordinates';
                    }
                    
                    getLocationBtn.disabled = false;
                    getLocationBtn.textContent = '📍 Update Location';
                },
                function(error) {
                    let errorMsg = '❌ Error getting location: ';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg += 'Permission denied. Please allow location access.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg += 'Location information unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMsg += 'Location request timed out.';
                            break;
                        default:
                            errorMsg += 'Unknown error occurred.';
                            break;
                    }
                    statusDiv.innerHTML = '<span style="color: var(--error);">' + errorMsg + '</span>';
                    statusDiv.style.background = '#ffebee';
                    getLocationBtn.disabled = false;
                    getLocationBtn.textContent = '📍 Get My Location';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        // Photo preview
        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
            const previewImg = document.getElementById('previewImg');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

        // Form validation
        document.getElementById('wasteForm').addEventListener('submit', function(e) {
            const weight = parseFloat(document.getElementById('weight').value);
            const plasticType = document.getElementById('plasticType').value;
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            
            if (!plasticType) {
                e.preventDefault();
                alert('Please select a plastic type');
                return false;
            }
            
            if (!weight || weight <= 0) {
                e.preventDefault();
                alert('Please enter a valid weight greater than 0');
                return false;
            }
            
            // Require GPS location when at aggregator location
            if (!latitude || !longitude) {
                e.preventDefault();
                alert('⚠️ Please capture your GPS location to verify you are at the aggregator\'s location. Click "Capture Location" button.');
                return false;
            }
            
            // Require aggregator selection
            const aggregatorID = document.querySelector('input[name="aggregatorID"]');
            if (!aggregatorID || !aggregatorID.value) {
                e.preventDefault();
                alert('⚠️ Please select an aggregator first before submitting waste.');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Submitting...';
        });
    </script>
</body>
</html>


