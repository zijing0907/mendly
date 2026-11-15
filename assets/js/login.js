const loginForm = document.getElementById("loginForm");

const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");

const emailError = document.getElementById("emailError");
const passwordError = document.getElementById("passwordError");

const successMessage = document.getElementById("successMessage");
const showPasswordButton = document.getElementById("showPassword");

showPasswordButton.addEventListener("click", function () {
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        showPasswordButton.textContent = "Hide";
    } else {
        passwordInput.type = "password";
        showPasswordButton.textContent = "Show";
    }
});

loginForm.addEventListener("submit", function (event) {
    event.preventDefault();

    emailError.textContent = "";
    passwordError.textContent = "";
    successMessage.textContent = "";

    const email = emailInput.value.trim();
    const password = passwordInput.value;

    let isValid = true;

    if (email === "") {
        emailError.textContent = "Email address is required.";
        isValid = false;
    } else if (!email.includes("@")) {
        emailError.textContent = "Please enter a valid email address.";
        isValid = false;
    }

    if (password === "") {
        passwordError.textContent = "Password is required.";
        isValid = false;
    } else if (password.length < 6) {
        passwordError.textContent =
            "Password must contain at least 6 characters.";
        isValid = false;
    }

    if (isValid) {
        // Client-side checks passed — hand off to the server
        // (process-login.php) to look up the account and verify the
        // password hash.
        loginForm.submit();
    }
});