<?php
/**
 * Setup Default Pricing for Aggregators
 * This script adds default pricing for aggregators who don't have pricing set up yet
 * Can be run manually or called from admin panel
 */

require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';

// Check if logged in as admin (optional - can be run from command line too)
if (php_sapi_name() !== 'cli') {
    if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Admin') {
        header("Location: " . VIEWS_URL . "/auth/login.php");
        exit();
    }
}

// Base prices (GH₵ per kg) - market average prices
$basePrices = [
    1 => 5.00,  // HDPE
    2 => 4.50,  // PET
    3 => 4.00,  // PVC
    4 => 3.50,  // LDPE
    5 => 4.20   // PP
];

// Get all aggregators
$aggregators = $conn->query("
    SELECT u.userID 
    FROM User u
    JOIN AggregatorRegistration ar ON u.userID = ar.userID
    WHERE u.userRole = 'Aggregator' AND u.status = 'active'
");

$processed = 0;
$skipped = 0;
$errors = [];

while ($agg = $aggregators->fetch_assoc()) {
    $userID = $agg['userID'];
    
    // Check if aggregator already has pricing
    $existingPrices = $conn->query("
        SELECT COUNT(*) as count 
        FROM AggregatorPricing 
        WHERE aggregatorID = $userID AND isActive = 1
    ");
    $count = $existingPrices->fetch_assoc()['count'];
    
    if ($count > 0) {
        $skipped++;
        continue; // Skip if already has pricing
    }
    
    // Generate unique randomized prices for this aggregator
    // Use userID + timestamp for more randomization, but keep it deterministic per aggregator
    $seed = ($userID * 137) + (int)(time() / 86400); // Changes daily but consistent per day
    mt_srand($seed);
    
    $success = true;
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
        
        // Insert pricing record (use INSERT IGNORE to avoid duplicates)
        $priceStmt = $conn->prepare("
            INSERT IGNORE INTO AggregatorPricing (aggregatorID, plasticTypeID, pricePerKg, isActive) 
            VALUES (?, ?, ?, 1)
        ");
        $priceStmt->bind_param("iid", $userID, $plasticTypeID, $pricePerKg);
        
        if (!$priceStmt->execute()) {
            $success = false;
            $errors[] = "Failed to add pricing for aggregator #$userID, plastic type #$plasticTypeID";
        }
    }
    
    if ($success) {
        $processed++;
    }
}

// Output results
if (php_sapi_name() === 'cli') {
    echo "Default Pricing Setup Complete!\n";
    echo "Processed: $processed aggregators\n";
    echo "Skipped: $skipped aggregators (already have pricing)\n";
    if (!empty($errors)) {
        echo "Errors: " . count($errors) . "\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
} else {
    // Web interface
    $message = "Default pricing setup complete! Processed: $processed, Skipped: $skipped";
    if (!empty($errors)) {
        $message .= ". Errors: " . count($errors);
    }
    header("Location: " . VIEWS_URL . "/admin/dashboard.php?success=" . urlencode($message));
    exit();
}
?>

