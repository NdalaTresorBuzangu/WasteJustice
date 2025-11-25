<?php
// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define a function to check if the user is logged in
function isLogin() {
    // Check if the session has the required values
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        // Redirect to the login page if not logged in
        $current_page = $_SERVER['REQUEST_URI'];
        header('Location: login.php?redirect=' . urlencode($current_page));
        exit;
    }
}

// Optional: Check if the logged-in user is an admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin';
}



