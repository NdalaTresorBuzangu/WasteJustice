<?php
/**
 * Test Paths Script
 * Verifies that all paths are correctly configured
 */

echo "=== WasteJustice Path Verification ===\n\n";

// Test 1: Check if config file exists and loads
echo "1. Testing config/config.php...\n";
if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
    echo "   ✓ Config file exists and loads\n";
    echo "   ✓ BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "\n";
    echo "   ✓ ASSETS_URL: " . (defined('ASSETS_URL') ? ASSETS_URL : 'NOT DEFINED') . "\n";
    echo "   ✓ VIEWS_URL: " . (defined('VIEWS_URL') ? VIEWS_URL : 'NOT DEFINED') . "\n";
    echo "   ✓ Database connection: " . (isset($conn) && $conn ? "OK" : "FAILED") . "\n";
} else {
    echo "   ✗ Config file NOT FOUND\n";
    exit(1);
}

echo "\n2. Testing critical files...\n";

$criticalFiles = [
    'index.php' => 'Root index file',
    'views/auth/login.php' => 'Login page',
    'views/auth/signup.php' => 'Signup page',
    'views/subscription.php' => 'Subscription page',
    'views/collector/dashboard.php' => 'Collector dashboard',
    'views/aggregator/dashboard.php' => 'Aggregator dashboard',
    'views/recycling/dashboard.php' => 'Recycling dashboard',
    'views/admin/dashboard.php' => 'Admin dashboard',
    'actions/auth/login_action.php' => 'Login action',
    'actions/auth/signup_action.php' => 'Signup action',
    'actions/auth/logout.php' => 'Logout action',
    'assets/css/styles.css' => 'Stylesheet',
];

foreach ($criticalFiles as $file => $description) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "   ✓ $description: EXISTS\n";
    } else {
        echo "   ✗ $description: NOT FOUND ($file)\n";
    }
}

echo "\n3. Testing JavaScript files...\n";
$jsFiles = glob(__DIR__ . '/js/*.js');
echo "   ✓ JavaScript files found: " . count($jsFiles) . "\n";
foreach ($jsFiles as $file) {
    echo "     - " . basename($file) . "\n";
}

echo "\n4. Testing database files...\n";
$dbPath = __DIR__ . '/db';
if (is_dir($dbPath)) {
    $dbFiles = glob($dbPath . '/*.sql');
    echo "   ✓ Database folder exists\n";
    echo "   ✓ SQL files found: " . count($dbFiles) . "\n";
    foreach ($dbFiles as $file) {
        echo "     - " . basename($file) . "\n";
    }
} else {
    echo "   ✗ Database folder NOT FOUND\n";
}

echo "\n5. Testing view structure...\n";
$viewFolders = ['auth', 'collector', 'aggregator', 'recycling', 'admin'];
foreach ($viewFolders as $folder) {
    $path = __DIR__ . '/views/' . $folder;
    if (is_dir($path)) {
        $files = glob($path . '/*.php');
        echo "   ✓ views/$folder/: " . count($files) . " file(s)\n";
    } else {
        echo "   ✗ views/$folder/: NOT FOUND\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "\nIf all tests passed, the app should run correctly!\n";
echo "Access the app at: http://localhost/WasteJustice/index.php\n";

?>

