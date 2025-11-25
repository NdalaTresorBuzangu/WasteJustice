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
        
        // Verify password using password_verify (secure method for hashed passwords)
        // This handles both old plain text passwords (for migration) and new hashed passwords
        $passwordValid = false;
        
        // Check if password is hashed (starts with $2y$ for bcrypt)
        if (strpos($user['userPassword'], '$2y$') === 0 || strpos($user['userPassword'], '$2a$') === 0 || strpos($user['userPassword'], '$2b$') === 0) {
            // Password is hashed, use password_verify
            $passwordValid = password_verify($password, $user['userPassword']);
            
            // If verification fails but old password might be plain text, try that (for migration)
            if (!$passwordValid && strlen($user['userPassword']) < 60) {
                // Likely old plain text password, verify and upgrade
                if ($password === $user['userPassword']) {
                    $passwordValid = true;
                    // Upgrade to hashed password
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $conn->prepare("UPDATE User SET userPassword = ? WHERE userID = ?");
                    $updateStmt->bind_param("si", $newHash, $user['userID']);
                    $updateStmt->execute();
                }
            }
        } else {
            // Old plain text password (for backward compatibility during migration)
            if ($password === $user['userPassword']) {
                $passwordValid = true;
                // Upgrade to hashed password
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE User SET userPassword = ? WHERE userID = ?");
                $updateStmt->bind_param("si", $newHash, $user['userID']);
                $updateStmt->execute();
            }
        }
        
        if ($passwordValid) {
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
