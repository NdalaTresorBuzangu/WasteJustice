<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';

$error = '';
if (isset($_GET['error'])) {
    $error = 'Invalid email or password. Please try again.';
}
if (isset($_GET['registered'])) {
    $success = 'Registration successful! Please login.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h2>🌍 <?php echo APP_NAME; ?></h2>
                <p><?php echo APP_TAGLINE; ?></p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo ACTIONS_URL; ?>/auth/login_action.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        placeholder="your@email.com"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        placeholder="Enter your password"
                    >
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Login to WasteJustice
                </button>
            </form>

            <div class="text-center mt-3">
                <p style="color: var(--gray);">
                    Don't have an account? 
                    <a href="signup.php" style="color: var(--primary-green); font-weight: 600; text-decoration: none;">
                        Sign up here
                    </a>
                </p>
                <p style="margin-top: 1rem;">
                    <a href="<?php echo BASE_URL; ?>/index.php" style="color: var(--gray); text-decoration: none; margin-right: 1rem;">
                        ← Back to Home
                    </a>
                    <a href="<?php echo BASE_URL; ?>/about.php" style="color: var(--gray); text-decoration: none;">
                        About Us
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
