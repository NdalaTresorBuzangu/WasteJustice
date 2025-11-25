<?php
// Include the database configuration file
include 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON response header
header('Content-Type: application/json');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $user_role = trim($_POST['userRole'] ?? '');

    // Validate required fields
    if (empty($full_name) || empty($email) || empty($contact) || empty($password) || empty($confirm_password) || empty($user_role)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    // Ensure password confirmation matches
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }

    // Check database connection
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
        exit;
    }

    // Check if email already exists
    $stmt = $conn->prepare('SELECT userEmail FROM User WHERE userEmail = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $results = $stmt->get_result();

    if ($results->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'User already registered with this email.']);
        exit;
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert the new user into the database
    $query = 'INSERT INTO User (userName, userEmail, userContact, userPassword, userRole) VALUES (?, ?, ?, ?, ?)';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssss', $full_name, $email, $contact, $hashed_password, $user_role);

    if ($stmt->execute()) {
        // Send success response and redirect to login page
        echo json_encode(['success' => true, 'message' => 'Signup successful!', 'redirect' => 'login.php']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
