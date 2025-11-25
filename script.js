// script.js

// Validate the form on submission
function validateForm() {
    // Get form input values
    const fullName = document.querySelector('input[name="full_name"]').value.trim();
    const email = document.querySelector('input[name="email"]').value.trim();
    const password = document.querySelector('input[name="password"]').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
    const errorMessage = document.getElementById("error_message");

    // Clear any previous error messages
    errorMessage.innerHTML = "";

    // Basic validation
    if (!fullName || !email || !password || !confirmPassword) {
        errorMessage.textContent = "All fields are required!";
        return false;
    }

    // Validate email format
    if (!validateEmail(email)) {
        errorMessage.textContent = "Please enter a valid email address.";
        return false;
    }

    // Validate password strength
    if (!validatePassword(password)) {
        errorMessage.textContent = "Password must be at least 8 characters, include an uppercase letter, a lowercase letter, a number, and a special character.";
        return false;
    }

    // Check if passwords match
    if (password !== confirmPassword) {
        errorMessage.textContent = "Passwords do not match.";
        return false;
    }

    // If all checks pass, submit the form
    return true;
}

// Email format validation
function validateEmail(email) {
    const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    return emailPattern.test(email);
}

// Password strength validation
function validatePassword(password) {
    // Minimum eight characters, at least one uppercase, one lowercase, one number, and one special character
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    return passwordPattern.test(password);
}

// Real-time feedback for password matching
document.querySelector('input[name="confirm_password"]').addEventListener("input", function() {
    const password = document.querySelector('input[name="password"]').value;
    const confirmPassword = this.value;
    const errorMessage = document.getElementById("error_message");

    if (password !== confirmPassword) {
        errorMessage.textContent = "Passwords do not match.";
    } else {
        errorMessage.textContent = "";
    }
});
