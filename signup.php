<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusFixIt - Sign Up</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        /* General Styles */
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: url('sign up login background.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        /* Navbar Styles */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(0, 0, 0, 0.8);
            width: 100%;
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .nav-logo a {
            color: white;
            font-size: 1.5rem;
            text-decoration: none;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 20px;
        }

        .nav-links li a {
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            text-transform: uppercase;
            transition: color 0.3s;
        }

        .nav-links li a:hover {
            color: #5c6bc0;
        }

        /* Container Styles */
        .container {
            width: 400px;
            background: rgba(0, 0, 0, 0.7);
            padding: 20px 30px;
            border-radius: 15px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            position: relative;
            margin-top: 100px; /* push down below navbar */
        }

        /* Header and Text */
        .container h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .container p {
            text-align: center;
            font-size: 14px;
            margin-bottom: 10px;
        }

        /* Input Space */
        .input-space {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
        }

        .input-space input,
        .input-space select {
            width: 100%;
            padding: 10px 35px 10px 15px;
            border: 1px solid white;
            border-radius: 20px;
            background: transparent;
            color: white;
            font-size: 14px;
        }

        /* Placeholder text */
        .input-space input::placeholder {
            color: rgba(255, 255, 255, 0.7);
            font-style: italic;
        }

        /* Fix for dropdown colors */
        .input-space select {
            background-color: #222;
            color: #fff;
        }

        .input-space select option {
            background-color: #333;
            color: #fff;
        }

        .input-space select option:checked,
        .input-space select option:hover {
            background-color: #5c6bc0;
            color: #fff;
        }

        /* Input Icons */
        .input-space i {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.8);
            font-size: 18px;
        }

        /* Button */
        .login-button {
            width: 100%;
            padding: 12px;
            background: #5c6bc0;
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .login-button:hover {
            background: #426558;
        }

        /* Register Section */
        .register {
            text-align: center;
            font-size: 14px;
            margin-top: 10px;
        }

        .register a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .register a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .container {
                width: 90%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="nav-logo">
            <a href="#">CampusFixIt</a>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About</a></li>
        </ul>
    </div>

    <!-- Sign Up Form -->
    <div class="container">
        <h1>Sign Up</h1>
        <form id="signupForm">
            <div class="input-space">
                <input type="text" placeholder="Full Name" name="full_name" required>
                <i class='bx bxs-user'></i>
            </div>
            <div class="input-space">
                <input type="text" placeholder="Contact" name="contact" required>
                <i class='bx bxs-phone'></i>
            </div>
            <div class="input-space">
                <input type="email" placeholder="Email" name="email" required>
                <i class='bx bxs-envelope'></i>
            </div>
            <div class="input-space">
                <input type="password" placeholder="Password" name="password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>
            <div class="input-space">
                <input type="password" placeholder="Confirm Password" name="confirm_password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>
            <div class="input-space">
                <select name="userRole" required>
                    <option value="" disabled selected>Select Role</option>
                    <option value="Affected Student">Affected Student</option>
                    <option value="School">School</option>
                    <!-- Admin is intentionally hidden from signup -->
                </select>
                <i class='bx bxs-user-check'></i>
            </div>
            <button type="submit" class="login-button">Sign Up</button>
        </form>
        <div class="register">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>

    <script>
        document.getElementById('signupForm').addEventListener('submit', async function (event) {
            event.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('signup_action.php', {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json();

                if (result.success) {
                    alert('Signup successful! Redirecting to login page...');
                    window.location.href = result.redirect;
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Signup error:', error);
                alert('An unexpected error occurred. Please try again later.');
            }
        });
    </script>
</body>
</html>
