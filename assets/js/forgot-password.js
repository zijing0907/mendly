const forgotPasswordForm = document.getElementById("forgotPasswordForm");
const emailInput = document.getElementById("email");
const emailError = document.getElementById("emailError");

forgotPasswordForm.addEventListener("submit", function (event) {
    event.preventDefault();

    emailError.textContent = "";

    const email = emailInput.value.trim();
    let isValid = true;

    if (email === "") {
        emailError.textContent = "Email address is required.";
        isValid = false;
    } else if (!email.includes("@")) {
        emailError.textContent = "Please enter a valid email address.";
        isValid = false;
    }

    if (isValid) {
        forgotPasswordForm.submit();
    }
});
