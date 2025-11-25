<?php
// Start the password recovery action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the email from the form (optional, just to validate the input)
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    // Redirect to the login page regardless of the email
    header('Location: login.php');
    exit;
}
?>
