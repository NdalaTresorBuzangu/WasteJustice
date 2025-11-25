<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusFixIt - Login</title>
    <link rel="stylesheet" href="styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body class="login-page">

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-logo">
            <a href="index.php">Tshijuka RDP</a>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="progress.php">Track Progress</a></li>
        </ul>
    </nav>

    <!-- Login Container -->
    <div class="container">
        <h2>Login to Your Account</h2>

        <!-- Error Message Container -->
        <div class="error-message" id="error-message" style="display: none; color: red;"></div>

        <!-- Login Form -->
        <form id="loginForm" novalidate>
            <div class="input-space">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
                <i class='bx bxs-user'></i>
            </div>

            <div class="input-space">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>

            <div class="remember-forgot">
                <label for="remember-me">
                    <input type="checkbox" id="remember-me" name="remember"> Remember me
                </label>
                <a href="password-recovery.php">Forgot Password?</a>
            </div>

            <button type="submit" class="login-button">Login</button>
        </form>

        <div class="register">
            <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
        </div>
    </div>

    <!-- JavaScript for AJAX login -->
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function (event) {
            event.preventDefault(); // Prevent form from submitting normally

            const formData = new FormData(this); // Collect form data

            try {
                const response = await fetch('login_action.php', {
                    method: 'POST', // Ensure it's a POST request
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    // Redirect if login successful
                    window.location.href = result.redirect;
                } else {
                    // Display error message
                    const errorMessage = document.getElementById('error-message');
                    errorMessage.style.display = 'block';
                    errorMessage.innerText = result.message;
                }
            } catch (error) {
                console.error('Login error:', error);
                alert(`An unexpected error occurred: ${error.message}`);
            }
        });
    </script>

</body>
</html>


