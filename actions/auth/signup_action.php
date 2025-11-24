<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userName = $_POST['userName'];
    $userContact = $_POST['userContact'];
    $userEmail = $_POST['userEmail'];
    $userRole = $_POST['userRole'];
    $userPassword = $_POST['userPassword'];
    
    // Check if email already exists
    $check = $conn->prepare("SELECT userID FROM User WHERE userEmail = ?");
    $check->bind_param("s", $userEmail);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        header("Location: " . VIEWS_URL . "/auth/signup.php?error=exists");
        exit();
    }
    
    // Get location data if provided
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] ? floatval($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] ? floatval($_POST['longitude']) : null;
    $address = trim($_POST['address'] ?? '');
    
    // For aggregators, address is MANDATORY
    if ($userRole == 'Aggregator') {
        if (empty($address) || strlen($address) < 5) {
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=address_required&role=aggregator");
            exit();
        }
    }
    
    // For recycling companies, address is MANDATORY
    if ($userRole == 'Recycling Company') {
        if (empty($address) || strlen($address) < 5) {
            header("Location: " . VIEWS_URL . "/auth/signup.php?error=address_required&role=company");
            exit();
        }
    }
    
    // Insert new user (in production, hash the password)
    // Auto-approve users on signup (set status to 'active')
    // Handle NULL values for latitude/longitude properly
    if ($latitude === null || $longitude === null) {
        $stmt = $conn->prepare("INSERT INTO User (userName, userContact, userEmail, userPassword, userRole, latitude, longitude, address, status) VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, 'active')");
        $stmt->bind_param("ssssss", $userName, $userContact, $userEmail, $userPassword, $userRole, $address);
    } else {
        $stmt = $conn->prepare("INSERT INTO User (userName, userContact, userEmail, userPassword, userRole, latitude, longitude, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("sssssdds", $userName, $userContact, $userEmail, $userPassword, $userRole, $latitude, $longitude, $address);
    }
    
    if ($stmt->execute()) {
        $userID = $conn->insert_id;
        
        // If recycling company, add to CompanyRegistration
        if ($userRole == 'Recycling Company') {
            $companyName = trim($_POST['companyName'] ?? $userName);
            if (empty($companyName) || strlen($companyName) < 3) {
                header("Location: " . VIEWS_URL . "/auth/signup.php?error=company_name_required");
                exit();
            }
            
            // Ensure address is set (already validated above, but double-check)
            if (empty($address)) {
                header("Location: " . VIEWS_URL . "/auth/signup.php?error=address_required");
                exit();
            }
            
            $businessLicense = trim($_POST['businessLicense'] ?? '');
            
            $compStmt = $conn->prepare("INSERT INTO CompanyRegistration (userID, companyName, companyContact, companyEmail, businessLicense) VALUES (?, ?, ?, ?, ?)");
            $compStmt->bind_param("issss", $userID, $companyName, $userContact, $userEmail, $businessLicense);
            $compStmt->execute();
        }
        
        // If aggregator, add to AggregatorRegistration and create default pricing
        if ($userRole == 'Aggregator') {
            $businessName = trim($_POST['businessName'] ?? $userName);
            if (empty($businessName) || strlen($businessName) < 3) {
                header("Location: " . VIEWS_URL . "/auth/signup.php?error=business_name_required");
                exit();
            }
            
            $businessLicense = trim($_POST['businessLicense'] ?? '');
            $capacity = isset($_POST['capacity']) && $_POST['capacity'] ? floatval($_POST['capacity']) : 0;
            
            // Ensure address is set (already validated above, but double-check)
            if (empty($address)) {
                header("Location: " . VIEWS_URL . "/auth/signup.php?error=address_required");
                exit();
            }
            
            $aggStmt = $conn->prepare("INSERT INTO AggregatorRegistration (userID, businessName, businessLicense, capacity) VALUES (?, ?, ?, ?)");
            $aggStmt->bind_param("issd", $userID, $businessName, $businessLicense, $capacity);
            $aggStmt->execute();
            
            // Auto-generate randomized default prices for all plastic types
            // Each aggregator gets unique pricing with significant variation
            // Base prices (GH₵ per kg) - market average prices
            $basePrices = [
                1 => 5.00,  // HDPE
                2 => 4.50,  // PET
                3 => 4.00,  // PVC
                4 => 3.50,  // LDPE
                5 => 4.20   // PP
            ];
            
            // Generate unique randomized prices for this aggregator
            // Use userID + timestamp for more randomization, but keep it deterministic per aggregator
            $seed = ($userID * 137) + (int)(time() / 86400); // Changes daily but consistent per day
            mt_srand($seed);
            
            // Each plastic type gets independent randomization
            foreach ($basePrices as $plasticTypeID => $basePrice) {
                // Generate significant variation: -15% to +20% of base price
                // This ensures noticeable differences between aggregators
                $variationPercent = mt_rand(-15, 20); // -15% to +20%
                $variation = ($basePrice * $variationPercent) / 100;
                $pricePerKg = round($basePrice + $variation, 2);
                
                // Add additional small random factor for more uniqueness (0.00 to 0.50)
                $extraVariation = mt_rand(0, 50) / 100;
                $pricePerKg = round($pricePerKg + $extraVariation, 2);
                
                // Ensure price stays within realistic market bounds (2.50 to 7.50 GH₵)
                $pricePerKg = max(2.50, min(7.50, $pricePerKg));
                
                // Insert pricing record
                $priceStmt = $conn->prepare("INSERT INTO AggregatorPricing (aggregatorID, plasticTypeID, pricePerKg, isActive) VALUES (?, ?, ?, 1)");
                $priceStmt->bind_param("iid", $userID, $plasticTypeID, $pricePerKg);
                $priceStmt->execute();
            }
        }
        
        header("Location: " . VIEWS_URL . "/auth/login.php?registered=1");
        exit();
    } else {
        header("Location: " . VIEWS_URL . "/auth/signup.php?error=failed");
        exit();
    }
}
?>
