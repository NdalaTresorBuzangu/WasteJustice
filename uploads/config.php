<?php

// Database connection settings
$servername = "localhost";
$username = "tresor.ndala"; 
$password = "Ndala1950@@"; 
$dbname = "webtech_fall2024_tresor_ndala";

// Create connection
$con = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

?>


