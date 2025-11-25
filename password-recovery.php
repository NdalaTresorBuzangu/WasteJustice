<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Password Recovery</h1>
        <p>Please enter your email to proceed to the login page.</p>
        <form action="password-recovery-action.php" method="POST">
            <div class="input-space">
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn">Submit</button>
        </form>
    </div>
</body>
</html>

