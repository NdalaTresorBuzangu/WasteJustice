<?php 
// WasteJustice Configuration

// Define base directory
if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(dirname(__FILE__)));
}

// Define URL base
define('BASE_URL', '/WasteJustice');
define('ASSETS_URL', BASE_URL . '/assets');
define('VIEWS_URL', BASE_URL . '/views');
define('ACTIONS_URL', BASE_URL . '/actions');
define('API_URL', BASE_URL . '/api');

// Application settings
define('APP_NAME', 'WasteJustice');
define('APP_TAGLINE', 'Fair, Transparent Waste Management in Ghana');

// Database connection
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "wastejustice"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
