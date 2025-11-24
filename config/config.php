<?php 
// WasteJustice Configuration
// Fair, Transparent Waste Management in Ghana

// Define base directory if not defined
if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(dirname(__FILE__)));
}

// Define paths
define('ROOT_PATH', BASE_DIR);
define('CONFIG_PATH', BASE_DIR . '/config');
define('VIEWS_PATH', BASE_DIR . '/views');
define('CONTROLLERS_PATH', BASE_DIR . '/controllers');
define('MODELS_PATH', BASE_DIR . '/classes');
define('ACTIONS_PATH', BASE_DIR . '/actions');
define('ASSETS_PATH', BASE_DIR . '/assets');
define('UPLOADS_PATH', BASE_DIR . '/uploads');
define('API_PATH', BASE_DIR . '/api');
define('DB_PATH', BASE_DIR . '/db');

// Define URL base
define('BASE_URL', '/WasteJustice');
define('ASSETS_URL', BASE_URL . '/assets');
define('VIEWS_URL', BASE_URL . '/views');
define('ACTIONS_URL', BASE_URL . '/actions');
define('API_URL', BASE_URL . '/api');

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection settings
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "wastejustice"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("WasteJustice Connection Failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// WasteJustice color scheme - Green environmental theme
define('PRIMARY_COLOR', '#10b981'); // Green
define('SECONDARY_COLOR', '#059669'); // Dark Green
define('ACCENT_COLOR', '#34d399'); // Light Green
define('WARNING_COLOR', '#f59e0b'); // Orange
define('DANGER_COLOR', '#ef4444'); // Red
define('TEXT_DARK', '#1f2937'); // Dark Gray
define('TEXT_LIGHT', '#6b7280'); // Light Gray
define('BACKGROUND', '#f0fdf4'); // Very Light Green

// Application settings
define('APP_NAME', 'WasteJustice');
define('APP_TAGLINE', 'Fair, Transparent Waste Management in Ghana');

// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
