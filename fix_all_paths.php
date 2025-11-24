<?php
/**
 * Quick Fix Script for All Paths
 * This script helps identify all files that need path fixes
 */

$rootDir = __DIR__;

// Files that need checking
$filesToCheck = [
    // Dashboard files
    'views/collector/dashboard.php',
    'views/aggregator/dashboard.php',
    'views/recycling/dashboard.php',
    'views/admin/dashboard.php',
    'views/subscription.php',
    
    // Other view files
    'views/collector/view_aggregators.php',
    'views/collector/submit_waste.php',
    
    // Action files
    'actions/subscription_action.php',
    'actions/upload_waste_action.php',
    'actions/update_waste_action.php',
    'actions/remove_waste_action.php',
    'actions/assign_aggregator_action.php',
    'actions/accept_delivery_action.php',
    'actions/reject_delivery_action.php',
    'actions/create_batch_action.php',
    'actions/process_payment_action.php',
    'actions/aggregator_sale_action.php',
    'actions/verify_purchase_action.php',
    'actions/feedback_action.php',
    'actions/renew_subscription_action.php',
    'actions/cancel_subscription_action.php',
    
    // API files
    'api/get_aggregators.php',
    'api/get_companies_with_prices.php',
    'api/get_prices.php',
    'api/payment_status.php',
    'api/subscription_status.php',
    
    // Controller files
    'controllers/collector_controller.php',
    'controllers/aggregator_controller.php',
    'controllers/recycling_controller.php',
    'controllers/admin_controller.php',
    'controllers/subscription_controller.php',
    'controllers/role_controller.php',
    
    // Model files
    'classes/collector_class.php',
    'classes/aggregator_class.php',
    'classes/recycling_class.php',
    'classes/admin_class.php',
];

echo "Files that need path checking:\n";
foreach ($filesToCheck as $file) {
    if (file_exists($rootDir . '/' . $file)) {
        echo "✓ $file\n";
    } else {
        echo "✗ $file (NOT FOUND)\n";
    }
}

echo "\nCommon patterns to fix:\n";
echo "1. require_once 'config.php' → require_once dirname(...) . '/config/config.php'\n";
echo "2. href=\"styles.css\" → href=\"<?php echo ASSETS_URL; ?>/css/styles.css\"\n";
echo "3. src=\"js/...\" → src=\"js/...\" (JavaScript files are in root js/ folder)\n";
echo "4. Location: login.php → Location: " . VIEWS_URL . "/auth/login.php\n";
echo "5. href=\"collector_dashboard.php\" → href=\"<?php echo VIEWS_URL; ?>/collector/dashboard.php\"\n";

?>

