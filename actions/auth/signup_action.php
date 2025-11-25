<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log

require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: " . VIEWS_URL . "/auth/signup.php");
    exit();
}

// Get and validate required fields
$userName = isset($_POST['userName']) ? trim($_POST['userName']) : '';
$userContact = isset($_POST['userContact']) ? trim($_POST['userContact']) : '';
$userEmail = isset($_POST['userEmail']) ? trim($_POST['userEmail']) : '';
$userRole = isset($_POST['userRole']) ? trim($_POST['userRole']) : '';
$userPassword = isset($_POST['userPassword']) ? $_POST['userPassword'] : '';

// Validate required fields
if (empty($userName) || empty($userContact) || empty($userEmail) || empty($userRole) || empty($userPassword)) {
    header("Location: " . VIEWS_URL . "/auth/signup.php?error=failed&message=" . urlencode('All fields are required'));
    exit();
}

// Validate password strength (minimum 6 characters)
if (strlen($userPassword) < 6) {
    header("Location: " . VIEWS_URL . "/auth/signup.php?error=failed&message=" . urlencode('Password must be at least 6 characters long'));
    exit();
}

// Hash the password using PHP's secure password_hash function
// PASSWORD_DEFAULT uses bcrypt algorithm and automatically handles salt
$hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

if ($hashedPassword === false) {
    header("Location: " . VIEWS_URL . "/auth/signup.php?error=failed&message=" . urlencode('Password hashing failed. Please try again.'));
    exit();
}

// Check if email already exists
$check = $conn->prepare("SELECT userID FROM User WHERE userEmail = ?");
$check->bind_param("s", $userEmail);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    header("Location: " . VIEWS_URL . "/auth/signup.php?error=exists");
    exit();
}

// Get location data if provided (for aggregators and recycling companies)
$latitude = null;
$longitude = null;
$address = '';

// Only get address/location for roles that need it
if ($userRole == 'Aggregator' || $userRole == 'Recycling Company') {
    // Get latitude/longitude - check both possible field names
    $latitude = null;
    $longitude = null;
    
    if (isset($_POST['latitude']) && !empty($_POST['latitude'])) {
        $latitude = floatval($_POST['latitude']);
    }
    if (isset($_POST['longitude']) && !empty($_POST['longitude'])) {
        $longitude = floatval($_POST['longitude']);
    }
    
    // Get address - use role-specific field names to avoid conflicts
    if ($userRole == 'Aggregator') {
        // Aggregator uses "aggregatorAddress" field name
        $address = isset($_POST['aggregatorAddress']) ? trim($_POST['aggregatorAddress']) : '';
        // Reduced minimum length to 3 characters for easier testing
        if (empty($address) || strlen($address) < 3) {
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=address_required&role=aggregator");
            exit();
        }
    } elseif ($userRole == 'Recycling Company') {
        // Recycling company uses "companyAddress" field name
        $address = isset($_POST['companyAddress']) ? trim($_POST['companyAddress']) : '';
        // Reduced minimum length to 3 characters for easier testing
        if (empty($address) || strlen($address) < 3) {
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=address_required&role=company");
            exit();
        }
    }
}

// Auto-approve users on signup (set status to 'active')
// Handle NULL values for latitude/longitude and address properly
// For Waste Collectors, address is optional; for Aggregators and Recycling Companies, it's required (already validated above)

// Normalize empty address to NULL for database
if (empty($address)) {
    $address = null;
}

// Build INSERT statement based on what data we have
// Note: $userPassword is now $hashedPassword (already hashed above)
if ($latitude === null || $longitude === null) {
    // No GPS coordinates - use NULL for lat/lng
    if ($address === null) {
        // No address either (Waste Collector case)
        $stmt = $conn->prepare("INSERT INTO User (userName, userContact, userEmail, userPassword, userRole, latitude, longitude, address, status) VALUES (?, ?, ?, ?, ?, NULL, NULL, NULL, 'active')");
        if (!$stmt) {
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=failed&message=" . urlencode('Database error: ' . $conn->error));
            exit();
        }
        $stmt->bind_param("sssss", $userName, $userContact, $userEmail, $hashedPassword, $userRole);
    } else {
        // Has address but no GPS (Aggregator/Company case)
        $stmt = $conn->prepare("INSERT INTO User (userName, userContact, userEmail, userPassword, userRole, latitude, longitude, address, status) VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, 'active')");
        if (!$stmt) {
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=failed&message=" . urlencode('Database error: ' . $conn->error));
            exit();
        }
        $stmt->bind_param("ssssss", $userName, $userContact, $userEmail, $hashedPassword, $userRole, $address);
    }
} else {
    // Has GPS coordinates
    if ($address === null) {
        // Has GPS but no address (shouldn't happen, but handle it)
        $stmt = $conn->prepare("INSERT INTO User (userName, userContact, userEmail, userPassword, userRole, latitude, longitude, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, 'active')");
        if (!$stmt) {
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=failed&message=" . urlencode('Database error: ' . $conn->error));
            exit();
        }
        $stmt->bind_param("sssssdd", $userName, $userContact, $userEmail, $hashedPassword, $userRole, $latitude, $longitude);
    } else {
        // Has both GPS and address
        $stmt = $conn->prepare("INSERT INTO User (userName, userContact, userEmail, userPassword, userRole, latitude, longitude, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        if (!$stmt) {
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=failed&message=" . urlencode('Database error: ' . $conn->error));
            exit();
        }
        $stmt->bind_param("sssssdds", $userName, $userContact, $userEmail, $hashedPassword, $userRole, $latitude, $longitude, $address);
    }
}

if ($stmt->execute()) {
    $userID = $conn->insert_id;
    
    // If recycling company, add to CompanyRegistration
    if ($userRole == 'Recycling Company') {
        $companyName = trim($_POST['companyName'] ?? $userName);
        // Reduced minimum length to 2 characters
        if (empty($companyName) || strlen($companyName) < 2) {
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=company_name_required");
            exit();
        }
        
        // Ensure address is set (already validated above, but double-check)
        if (empty($address) || strlen($address) < 3) {
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=address_required&role=company");
            exit();
        }
        
        $businessLicense = trim($_POST['businessLicense'] ?? '');
        
        $compStmt = $conn->prepare("INSERT INTO CompanyRegistration (userID, companyName, companyContact, companyEmail, businessLicense) VALUES (?, ?, ?, ?, ?)");
        $compStmt->bind_param("issss", $userID, $companyName, $userContact, $userEmail, $businessLicense);
        
        if (!$compStmt->execute()) {
            // If CompanyRegistration insert fails, rollback user creation
            $conn->query("DELETE FROM User WHERE userID = $userID");
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=failed&message=" . urlencode('Failed to register company: ' . $compStmt->error));
            exit();
        }
        
        // Auto-generate default pricing for recycling companies
        $basePrices = [
            1 => 7.00,  // HDPE
            2 => 6.50,  // PET
            3 => 6.00,  // PVC
            4 => 5.50,  // LDPE
            5 => 6.20   // PP
        ];
        
        foreach ($basePrices as $plasticTypeID => $pricePerKg) {
            $priceStmt = $conn->prepare("INSERT INTO CompanyPricing (companyID, plasticTypeID, pricePerKg, isActive) VALUES (?, ?, ?, 1)");
            $priceStmt->bind_param("iid", $userID, $plasticTypeID, $pricePerKg);
            $priceStmt->execute();
        }
    }
    
    // If aggregator, add to AggregatorRegistration and create default pricing
    if ($userRole == 'Aggregator') {
        $businessName = trim($_POST['businessName'] ?? $userName);
        // Reduced minimum length to 2 characters
        if (empty($businessName) || strlen($businessName) < 2) {
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=business_name_required");
            exit();
        }
        
        $businessLicense = trim($_POST['businessLicense'] ?? '');
        
        // Ensure address is set (already validated above, but double-check)
        if (empty($address) || strlen($address) < 3) {
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=address_required&role=aggregator");
            exit();
        }
        
        // Insert aggregator registration (capacity is optional, default to 0)
        $capacity = isset($_POST['capacity']) && $_POST['capacity'] ? floatval($_POST['capacity']) : 0;
        $aggStmt = $conn->prepare("INSERT INTO AggregatorRegistration (userID, businessName, businessLicense, capacity) VALUES (?, ?, ?, ?)");
        $aggStmt->bind_param("issd", $userID, $businessName, $businessLicense, $capacity);
        if (!$aggStmt->execute()) {
            // If AggregatorRegistration insert fails, rollback user creation
            $conn->query("DELETE FROM User WHERE userID = $userID");
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=failed&message=" . urlencode('Failed to register aggregator: ' . $aggStmt->error));
            exit();
        }
        
        // Auto-generate randomized default prices for all plastic types
        $basePrices = [
            1 => 5.00,  // HDPE
            2 => 4.50,  // PET
            3 => 4.00,  // PVC
            4 => 3.50,  // LDPE
            5 => 4.20   // PP
        ];
        
        $seed = ($userID * 137) + (int)(time() / 86400);
        mt_srand($seed);
        
        foreach ($basePrices as $plasticTypeID => $basePrice) {
            $variationPercent = mt_rand(-15, 20);
            $variation = ($basePrice * $variationPercent) / 100;
            $pricePerKg = round($basePrice + $variation, 2);
            
            $extraVariation = mt_rand(0, 50) / 100;
            $pricePerKg = round($pricePerKg + $extraVariation, 2);
            
            $pricePerKg = max(2.50, min(7.50, $pricePerKg));
            
            $priceStmt = $conn->prepare("INSERT INTO AggregatorPricing (aggregatorID, plasticTypeID, pricePerKg, isActive) VALUES (?, ?, ?, 1)");
            $priceStmt->bind_param("iid", $userID, $plasticTypeID, $pricePerKg);
            $priceStmt->execute();
        }
    }
    
    // Success - redirect to login
    header("Location: " . VIEWS_URL . "/auth/login.php?registered=1");
    exit();
} else {
    // User creation failed
    $errorMsg = $stmt->error ? $stmt->error : $conn->error;
    header("Location: " . VIEWS_URL . "/auth/signup.php?error=failed&message=" . urlencode($errorMsg));
    exit();
}
?>
