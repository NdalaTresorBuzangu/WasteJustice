<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Query database
    $stmt = $conn->prepare("SELECT * FROM User WHERE userEmail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Simple password check (in production, use password_verify with hashed passwords)
        if ($password === $user['userPassword']) {
            // Set session
            $_SESSION['userID'] = $user['userID'];
            $_SESSION['userName'] = $user['userName'];
            $_SESSION['userEmail'] = $user['userEmail'];
            $_SESSION['userRole'] = $user['userRole'];
            
            // Check subscription status and redirect accordingly
            require_once dirname(dirname(dirname(__FILE__))) . '/controllers/role_controller.php';
            $roleController = new RoleController($conn);
            $redirectURL = $roleController->getRedirectURL($user['userID'], $user['userRole']);
            
            // Set session with subscription info if applicable
            $accessCheck = $roleController->subscriptionController->validateAccess($user['userID'], $user['userRole']);
            if ($accessCheck['access'] && isset($accessCheck['subscription'])) {
                $_SESSION['subscription_status'] = $accessCheck['subscription']['paymentStatus'] == 'Success' ? 'active' : 'pending';
                $_SESSION['subscription_expires'] = $accessCheck['subscription']['subscriptionEnd'];
            }
            
            // getRedirectURL now returns full paths, so use directly
            header("Location: " . $redirectURL);
            exit();
        }
    }
    
    // Login failed
    header("Location: " . VIEWS_URL . "/auth/login.php?error=1");
    exit();
}
?>
