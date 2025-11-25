<?php
session_start();
include 'config.php'; // Include database connection

// Enable error reporting for debugging (optional for development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set JSON response header
header('Content-Type: application/json');

try {
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }

    // Sanitize and validate inputs
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    // Prepare database query
    $query = 'SELECT userID, userName, userpassword, userRole FROM User WHERE userEmail = ?';
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('s', $email); // Bind email parameter
        $stmt->execute(); // Execute the query
        $result = $stmt->get_result(); // Fetch result

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['userID'];
            $username = $user['userName'];
            $user_password = $user['userpassword'];
            $user_role = $user['userRole'];

            // Verify password
            if (password_verify($password, $user_password)) {
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_role'] = $user_role ?: 'Affected Student';
                $_SESSION['username'] = $username;

                 // Redirect based on role
                if ($user_role === 'Affected Student') {
                    $redirect_url = 'student_dashboard.php';
                } elseif ($user_role === 'School') {
                    $redirect_url = 'subscribe_school.php';
                } elseif ($user_role === 'Admin') {
                    $redirect_url = 'admin_landing.php';
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid user role.']);
                    exit;
                }

                echo json_encode(['success' => true, 'redirect' => $redirect_url]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No user found with this email.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database query error.']);
    }
} catch (Exception $e) {
    // Handle unexpected errors gracefully
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.', 'error' => $e->getMessage()]);
}

exit; // Terminate the script


