document.addEventListener("DOMContentLoaded", function () {
    const passwordRequirements = document.querySelector(".password-requirements");

    // Check if we are on the signup page and show/hide password requirements accordingly
    const isSignupPage = document.body.classList.contains("signup-page");

    // If it's the login page, hide password requirements
    if (!isSignupPage && passwordRequirements) {
        passwordRequirements.style.display = "none";  // Hide requirements for login
    }

    // Password Validation for SignUp page (if needed)
    if (isSignupPage) {
        // Example of password validation logic for showing the requirements dynamically
        const passwordInput = document.getElementById("password");
        passwordInput.addEventListener("input", function () {
            const password = passwordInput.value;
            const requirements = passwordRequirements.querySelectorAll(".requirement");

            // Check if each requirement is met
            const lengthReq = requirements[0];
            const upperReq = requirements[1];
            const lowerReq = requirements[2];
            const numberReq = requirements[3];
            const specialReq = requirements[4];

            lengthReq.classList.toggle("valid", password.length >= 8);
            upperReq.classList.toggle("valid", /[A-Z]/.test(password));
            lowerReq.classList.toggle("valid", /[a-z]/.test(password));
            numberReq.classList.toggle("valid", /\d/.test(password));
            specialReq.classList.toggle("valid", /[!@#$%^&*(),.?":{}|<>]/.test(password));
        });
    }
});
