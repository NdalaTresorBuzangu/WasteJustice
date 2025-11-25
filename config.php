<?php 
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection settings
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "campus_maintenance"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection


// Do not close the connection here; let other files use it
// $conn->close(); // Remove this line
?>


